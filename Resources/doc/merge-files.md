マージ
------

複数のファイルをマージして1つのキャッシュを生成することができます。  
ただしマージをする際は以下の条件を満たす必要があります。

* 設定ファイルのトップレベルキーを固定
* `Configuration`クラスの実装
* `FileResource`のエイリアスを指定しない

##### 設定ファイルのトップレベルキーを固定

設定ファイルのトップレベルキーはバンドル名に一致させる必要があります。  
例えばAcmeDemoBundleならトップレベルキーは`acme_demo`になります。

```yml
# src/Acme/DemoBundle/Resources/config/sample1.yml
acme_demo:
   function1:
       key1: value1
   function2: value2
```

```yml
# src/Acme/DemoBundle/Resources/config/sample2.yml
acme_demo:
   function3: value3
   function4: value4
```

##### Configurationクラスの実装

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
                ->scalarNode('function3')->end()
                ->scalarNode('function4')->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
```

`Configuration`クラスを自動生成したい場合は[自動生成ツール](generate-configuration.md)を用意していますのでそちらをお試しください。

##### FileResourceのエイリアスを指定しない

以下のように`Register`生成時の`FileResource`を記述するときに第3引数のエイリアスを指定しないようにします。

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
        // マージする際はFileResourceのalias(第3引数)を指定しない
        $cache = new Register($this, $container, array(
            new FileResource(__DIR__.'/../Resources/config/sample1.yml'),
            new FileResource(__DIR__.'/../Resources/config/sample2.yml'),
        ));
        $cache->register();
    }
}
```

あとは同様に`Bundle`でキャッシュを生成します。

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

すべてのファイルの内容をマージして1個のキャッシュが生成されます。  
サービスIDは末尾にエイリアスがつかない`config.acme_demo`になります。

```php
<?php

// src/Acme/DemoBundle/Controller/WelcomeController.php
namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WelcomeController extends Controller
{
    public function indexAction()
    {
        $cache = $this->get('config.acme_demo');

        /**
         * array(
         *     'function1' => array('key1' => 'value1'),
         *     'function2' => 'value2',
         *     'function3' => 'value3',
         *     'function4' => 'value4',
         * )
         */
        $cache->findAll();

        // ...
    }
}
```
