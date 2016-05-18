Restore caches
--------------

This bundle restores the caches.  
Save the caches temporarily before clearing, and restore them while re-creating.

This feature is especially effective when the config file is large size.  
Creating the cache is faster than usual with the help of skipping the parsing config file process.

##### Setup

First, enable the restoring setting on `app/config/config.yml`:

```yml
# app/config/config.yml
yahoo_japan_config_cache:
    cache_restore: true
```

Enable the `FileResource` setting on the Extension class.  
Set the fourth argument true:

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
            new FileResource(__DIR__.'/../Resources/config/sample.yml', null, 'sample', true),
        ));
        $cache->register();
    }
}
```

This is enabled only if you use the `FileResource` and specify an alias (the third argument).

Finally, create the cache and clear with the Symfony console:

```sh
# Symfony 2.x
$ app/console cache:warmup
$ app/console cache:clear --no-warmup

# Symfony 3.x
$ bin/console cache:warmup
$ bin/console cache:clear --no-warmup
```

After the second time, creating the cache is faster.

##### Clean-up the cache

If you would like to initialize the cache state by removing all the caches Symfony and this bundle create, run the following command:

```sh
# Symfony 2.x
$ app/console config-cache:cleanup

# Symfony 3.x
$ bin/console config-cache:cleanup
```
