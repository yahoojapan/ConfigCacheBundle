多言語対応
----------

以下のようなケースを考えます。
* Symfonyの[translator](http://symfony.com/doc/current/book/translation.html)を使って多言語対応をする
* 設定ファイルに多言語の文言を定義してmodelなどで使う

通常は設定ファイルのデータを取り出した後にtranslatorを使って都度翻訳する必要があります。

```php
<?php

// src/Acme/DemoBundle/Controller/WelcomeController.php
namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WelcomeController extends Controller
{
    public function indexAction()
    {
        $translator = $this->get('translator.default');
        $cache      = $this->get('config.acme_demo');
        $config     = $cache->find('function1');

        foreach ($config as $key => &$value) {
            if ($translator->getCatalogue()->has($value)) {
                $value = $translator->trans($value);
            }
        }

        // ...
    }
}
```

ここで都度翻訳するのではなく事前に翻訳済みのキャッシュを用意しておけば，使うときはそれを取り出すだけなので翻訳の余計な処理を省くことができます。

ConfigCacheBundleでは設定ファイルに記述した文言を事前に翻訳した状態でキャッシュさせることができます。  
そのためには多言語対応のサービス登録クラス`RegisterLocale`を使います。  
`RegisterLocale`を使うと翻訳用のローダをサービス化します。  
キャッシュ生成前に翻訳用のローダとSymfonyのtranslatorを使って翻訳し，翻訳された状態の配列データをキャッシュします。  
キャッシュは言語の数だけ生成され，キャッシュ取得時は現在の言語設定に応じたキャッシュを自動的に選択して設定値を取得します。

以下多言語対応のための実装例を示します。

##### 設定ファイル

AcmeDemoBundleに配置する設定ファイルを以下のように定義します。  
設定ファイルの値(葉要素)が翻訳の対象になります。

```yml
# src/Acme/DemoBundle/Resources/config/sample.yml
acme_demo:
    function1:
        # will be translated
        key1: sample_label_japan
    function2: value2
```

翻訳のためのcatalogueを用意します。

```yml
# app/Resources/translations/messages.en.yml
sample_label_japan: Japan
```

```yml
# app/Resources/translations/messages.ja.yml
sample_label_japan: 日本
```

##### app/config/config.yml

app/config/config.ymlに以下の記述を追加します。

```yml
# app/config/config.yml
framework:
    translator: { fallbacks: [ja] }

yahoo_japan_config_cache:
    locale:
        enabled: true
        locales: [ja, en]
```

`locales`には取りうる言語をすべて記述してください。

##### Extensionクラス

`RegisterLocale`を使って`Extension`に記述を追加します。

```php
<?php

// src/Acme/DemoBundle/DependencyInjection/AcmeDemoExtension.php
namespace Acme\DemoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\RegisterLocale;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AcmeDemoExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // add
        $cache = new RegisterLocale($this, $config, $container, array(
            new FileResource(__DIR__.'/../Resources/config/sample.yml'),
        ));
        $cache->register();
    }
}
```

##### サービスを使う

後述の[現在の言語設定](#現在の言語設定)がされていれば，以下のように翻訳済みの設定値を取得できます。

```php
<?php

// src/Acme/DemoBundle/Controller/WelcomeController.php
namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WelcomeController extends Controller
{
    public function indexAction()
    {
        // ConfigCache
        $cache = $this->get('config.acme_demo');

        // = 'Japan' if _locale is en
        // = '日本'  if _locale is ja
        $cache->find('function1.key1');

        // ...
    }
}
```

##### 現在の言語設定

多言語対応した`ConfigCache`サービスを使う際には現在の言語設定が必要になります。  
生成されたキャッシュのうち言語に対応したものを使うためです。

現在の言語設定をするためには以下が必要になります。

* [`ConfigCache`サービスに現在の言語設定を反映する](#configcache%E3%82%B5%E3%83%BC%E3%83%93%E3%82%B9%E3%81%AB%E7%8F%BE%E5%9C%A8%E3%81%AE%E8%A8%80%E8%AA%9E%E8%A8%AD%E5%AE%9A%E3%82%92%E5%8F%8D%E6%98%A0%E3%81%99%E3%82%8B)
* [`ConfigCacheListener`が参照するSymfonyの`Request::locale`の設定をする](#configcachelistener%E3%81%8C%E5%8F%82%E7%85%A7%E3%81%99%E3%82%8Bsymfony%E3%81%AErequestlocale%E3%81%AE%E8%A8%AD%E5%AE%9A%E3%82%92%E3%81%99%E3%82%8B)

##### ConfigCacheサービスに現在の言語設定を反映する

`ConfigCache`サービスへの現在の言語設定の反映はConfigCacheBundle付属の`ConfigCacheListener`が行います。  
`ConfigCacheListener`は自動的に登録されます。([app/config/config.yml](#appconfigconfigyml)の設定が有効のとき)

`ConfigCacheListener`ではSymfonyの`Request::locale`を参照して現在の言語設定を`ConfigCache`サービスに反映します。  
`ConfigCache`サービスへの反映は`RegisterLocale`を使ってサービス登録されていれば自動的に行われます。

##### ConfigCacheListenerが参照するSymfonyのRequest::localeの設定をする

ConfigCacheBundleではSymfonyの`Request::locale`の設定はされません。別途実装が必要になります。

`Request::locale`を適切に設定するためには以下のような方法があります。
* URLに`_locale`パラメータを含める
* リスナーを実装する

これらの実装方法の詳細については[公式ドキュメント](http://symfony.com/doc/current/book/translation.html#handling-the-user-s-locale)を参照ください。
