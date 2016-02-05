基本的な使い方
--------------

AcmeDemoBundleに設定ファイルsample.ymlを配置するケースを例にして、キャッシュ生成までの手順を示します。

##### 設定ファイル

Resources/config配下に定義したい設定ファイルを配置します。

```yml
# src/Acme/DemoBundle/Resources/config/sample.yml
all:
   function1:
       key1: value1
   function2: value2
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

use Symfony\Component\DependencyInjection\ContainerBuilder;
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
$ app/console cache:warmup
```

ここまで完了するとキャッシュオブジェクト`ConfigCache`がサービス化された状態になります。

```sh
$ app/console debug:container config.acme_demo.sample
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
$ cat app/cache/dev/acme_demo/ec/5b63616368655d5b315d.php
<?php return array (
  'lifetime' => 0,
  'data' =>
  array (
    'all' =>
    array (
      'function1' =>
      array (
        'key1' => 'value1',
      ),
      'function2' => 'value2',
    ),
  ),
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

        // = array('function1' => array('key1' => 'value1'), 'function2' => 'value2')
        $cache->find('all');

        // = 'value1'
        $cache->find('all.function1.key1');

        // = array('all' => array('function1' => array('key1' => 'value1'), 'function2' => 'value2'))
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
            - @config.acme_demo.sample
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
        // = array('key1' => 'value1')
        $this->config->find('all.function1');

        // = 'value1'
        $this->config->find('all.function1.key1');

        // ...
    }
}
```
