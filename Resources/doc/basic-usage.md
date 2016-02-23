基本的な使い方
--------------

AcmeDemoBundleに設定ファイルsample.ymlを配置するケースを例にして、キャッシュ生成までの手順を示します。

##### AcmeDemoBundle生成

Symfonyのバンドルを1つ作成しておきます。

```sh
# Symfony 2.x
$ app/console generate:bundle --namespace=Acme/DemoBundle --format=yml
# Symfony 3.x
$ bin/console generate:bundle --namespace=Acme/DemoBundle --format=yml
```

##### 設定ファイル

Resources/config配下に定義したい設定ファイルを配置します。

```yml
# src/Acme/DemoBundle/Resources/config/sample.yml
invoice: 34843
date   : '2001-01-23'
bill-to:
    given  : Chris
    family : Dumars
```

##### Extensionクラス

DependencyInjection/AcmeDemoExtension.phpにサービス登録の記述を追加します。

```php
<?php

// src/Acme/DemoBundle/DependencyInjection/AcmeDemoExtension.php
namespace Acme\DemoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register;
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
        $cache = new Register($this, $container, array(
            new FileResource(__DIR__.'/../Resources/config/sample.yml', null, 'sample'),
            // 以下でも同じ
            //FileResource::create(__DIR__.'/../Resources/config/sample.yml')->setAlias('sample'),
        ));
        $cache->register();
    }
}
```

##### Bundleクラス

AcmeDemoBundle.phpにキャッシュ生成の記述を追加します。

```php
<?php

// src/Acme/DemoBundle/AcmeDemoBundle.php
namespace Acme\DemoBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AcmeDemoBundle extends Bundle
{
    public function boot()
    {
        $this->container->get('config.acme_demo.sample')->create();
    }
}
```

ここで登場するサービスID`config.acme_demo.sample`はバンドル名と`FileResource`で指定したエイリアスをもとに自動的に割り振られます。  
バンドル名が`AcmeDemoBundle`でエイリアスがsampleなら`config.acme_demo.sample`となります。

##### キャッシュ生成

Symfonyのconsoleを使ってキャッシュ生成します。

```sh
# Symfony 2.x
$ app/console cache:warmup
# Symfony 3.x
$ bin/console cache:warmup
```

ここまで完了するとキャッシュオブジェクト`ConfigCache`がサービス化された状態になります。

```sh
# Symfony 2.x
$ app/console debug:container config.acme_demo.sample
# Symfony 3.x
$ bin/console debug:container config.acme_demo.sample
[container] Information for service config.acme_demo.sample
Service Id       config.acme_demo.sample
Class            YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache
Tags             -
Scope            container
Public           yes
Synthetic        no
Lazy             yes
Synchronized     no
Abstract         no
```

生成されたキャッシュはSymfonyのキャッシュディレクトリ配下に配置されます。

```sh
// Symfony 2.x : app/cache/dev/acme_demo/75/5b73616d706c655d5b315d.php
// Symfony 3.x : var/cache/dev/acme_demo/75/5b73616d706c655d5b315d.php
<?php return array (
  'lifetime' => 0,
  'data' =>
  array (
    'invoice' => 34843,
    'date' => '2001-01-23',
    'bill-to' =>
    array (
      'given' => 'Chris',
      'family' => 'Dumars',
    )
  )
);
```

##### サービスを使う

コンテナから直接サービスを取り出すか、またはservices.ymlに記述することで`ConfigCache`サービスを使えるようになります。

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
        $cache = $this->get('config.acme_demo.sample');

        // 34843
        $cache->find('invoice');

        // 'Chris'
        $cache->find('bill-to.given');

        // array('invoice' => 34843, 'date' => '2001-01-23', 'bill-to' => array('given' => 'Chris', 'family' => 'Dumars'))
        $cache->findAll();

        // ...
    }
}
```

```yml
# src/Acme/DemoBundle/Resources/config/services.yml
services:
    acme_demo.sample_model:
        class: Acme\DemoBundle\SampleModel
        arguments:
            - '@config.acme_demo.sample'
```

```php
<?php

// src/Acme/DemoBundle/SampleModel.php
namespace Acme\DemoBundle;

use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache;

class SampleModel
{
    protected $config;

    public function __construct(ConfigCache $config)
    {
        $this->config = $config;
    }

    public function sampleMethod()
    {
        // 34843
        $this->config->find('invoice');

        // 'Chris'
        $this->config->find('bill-to.given');

        // ...
    }
}
```
