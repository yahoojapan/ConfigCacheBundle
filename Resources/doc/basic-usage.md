基本的な使い方
--------------

AcmeDemoBundleに設定ファイルsample.ymlを配置するケースを例にして，キャッシュ生成までの手順を示します。

##### 設定ファイル

Resources/config配下に定義したい設定ファイルを配置します。

```yml
# src/Acme/DemoBundle/Resources/config/sample.yml
acme_demo:
   function1:
       key1: value1
   function2: value2
```

設定ファイルのルートキーはバンドル名に一致させる必要があります。  
例えばAcmeDemoBundleならルートキーは`acme_demo`になります。

##### Configurationクラス

DependencyInjection/Configuration.phpを以下のように記述します。

```php
<?php

// src/Acme/DemoBundle/DependencyInjection/Configuration.php
namespace Acme\DemoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('acme_demo');
        $rootNode
            ->children()
                ->arrayNode('function1')
                    ->children()
                        ->scalarNode('key1')->end()
                    ->end()
                ->end()
                ->scalarNode('function2')->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
```

`Configuration`クラスの作成は必須です。  
`Configuration`クラスの作成を省略したい場合は[自動生成ツール](generate-configuration.md)を用意していますのでそちらをお試しください。

##### Extensionクラス

DependencyInjection/AcmeDemoExtension.phpにサービス登録の記述を追加します。

```php
<?php

// src/Acme/DemoBundle/DependencyInjection/AcmeDemoExtension.php
namespace Acme\DemoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
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
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // add
        $cache = new Register($this, $config, $container, array(
            new FileResource(__DIR__.'/../Resources/config/sample.yml'),
        ));
        $cache->register();
    }
}
```

AcmeDemoBundleがapp/config/config.ymlやservices.ymlを使わないのであれば以下のように記述することもできます。

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
        $cache = new Register($this, array(), $container, array(
            new FileResource(__DIR__.'/../Resources/config/sample.yml'),
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
        $this->container->get('config.acme_demo')->create();
    }
}
```

ここで登場するサービスID`config.acme_demo`はバンドル名をもとに自動的に割り振られます。  
`AcmeDemoBundle`なら`config.acme_demo`となります。

##### キャッシュ生成

Symfonyのconsoleを使ってキャッシュ生成します。

```sh
$ app/console cache:warmup
```

ここまで完了すると設定ファイルキャッシュのオブジェクト`ConfigCache`がサービス化された状態になります。

```sh
$ app/console debug:container config.acme_demo
[container] Information for service config.acme_demo
Service Id       config.acme_demo
Class            YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache
Tags             -
Scope            container
Public           yes
Synthetic        no
Lazy             yes
Synchronized     no
Abstract         no
```

生成された設定ファイルキャッシュはSymfonyのキャッシュディレクトリ配下に配置されます。

```sh
$ cat app/cache/dev/acme_demo/ec/5b63616368655d5b315d.php
<?php return array (
  'lifetime' => 0,
  'data' =>
  array (
    'function1' =>
    array (
      'key1' => 'value1',
    ),
    'function2' => 'value2',
  ),
);
```

##### サービスを使う

コンテナから直接サービスを取り出すか，またはservices.ymlに記述することで`ConfigCache`サービスのインジェクトができるようになります。

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

        // = array('key1' => 'value1')
        $cache->find('function1');

        // = 'value1'
        $cache->find('function1.key1');

        // = array('function1' => array('key1' => 'value1'), 'function2' => 'value2')
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
            - @config.acme_demo
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
        $this->config->find('function1');

        // = 'value1'
        $this->config->find('function1.key1');

        // ...
    }
}
```
