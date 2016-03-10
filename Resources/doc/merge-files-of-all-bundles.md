Merge files of all bundles
--------------------------

This bundle creates a cache of the content into which this bundle merges configuration files of all bundles in `AppKernel.php`.  
Use `registerAll()`:

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
            new FileResource('/Resources/config/sample.yml'),
        ));
        $cache->registerAll();
    }
}
```

You don't have to specify the third argument aliases.  
If specifying, a cache has the general effects of the `register()`.  
In this sample code, `/Resources/config/sample.yml` of all bundles are merged.
