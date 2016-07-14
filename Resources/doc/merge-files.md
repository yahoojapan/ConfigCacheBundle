Merge files
-----------

This bundle creates a cache of the content into which this bundle merges configuration files.  
However, some conditions below are required to merge files:

* Fix a top-level key of configuration files
* Implement a `Configuration` class
* Don't specify `FileResource` aliases

### Fix a top-level key of configuration files

You need to match a top-level key of configuration files and a bundle name.  
For example, if the bundle name is "AcmeDemoBundle", the top-level key is `acme_demo`:

```yml
# src/Acme/DemoBundle/Resources/config/sample1.yml
acme_demo:
    invoice: 34843
    date   : '2001-01-23'
    bill_to:
        given  : Chris
        family : Dumars
```

```yml
# src/Acme/DemoBundle/Resources/config/sample2.yml
acme_demo:
    ship_to:
        given  : Taro
        family : Yahoo
```

### Implement a Configuration class

Implement the `DependencyInjection/Configuration.php`:

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
                ->integerNode('invoice')->end()
                ->scalarNode('date')->end()
                ->arrayNode('bill_to')
                    ->children()
                        ->scalarNode('given')->end()
                        ->scalarNode('family')->end()
                    ->end()
                ->end()
                ->arrayNode('ship_to')
                    ->children()
                        ->scalarNode('given')->end()
                        ->scalarNode('family')->end()
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
```

If you would like to generate this `Configuration` automatically, you can use [Configuration generator](generate-configurations.md).

### Don't specify FileResource aliases

When you define `Register` arguments, you don't have to specify the third argument aliases:

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
        // Don't specify the third argument when merging
        $cache = new Register($this, $container, array(
            new FileResource(__DIR__.'/../Resources/config/sample1.yml'),
            new FileResource(__DIR__.'/../Resources/config/sample2.yml'),
        ));
        $cache->register();
    }
}
```

All files are merged, and a cache is created.  
The service ID is `config.acme_demo` without an alias:

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
         *     'invoice' => 34843,
         *     'date' => '2001-01-23',
         *     'bill_to' => array(
         *         'given' => 'Chris',
         *         'family' => 'Dumars',
         *     ),
         *     'ship_to' => array(
         *         'given' => 'Taro',
         *         'family' => 'Yahoo',
         *     ),
         * )
         */
        $cache->findAll();

        // ...
    }
}
```
