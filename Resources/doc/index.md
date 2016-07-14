ConfigCacheBundle
=================

The ConfigCacheBundle is a bundle that handles configuration file caches.

Features
--------

* Create a cache from configuration files  
A file cache is created separately from caches the Symfony creates as a service container.
* Register cache objects to the Symfony service container
* Merge and validate configuration files
* Preprocess by extending loaders
* Translations

Requirements
------------

PHP >= 5.3.9  
Symfony >= 2.7  
doctrine/cache >= 1.3

See the [composer.json](../../composer.json) for details.

Installation
------------

### Get the bundle

```sh
$ composer require yahoojapan/config-cache-bundle
```

### Enable the bundle

Add the ConfigCacheBundle in `app/AppKernel.php`:

```php
<?php

// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            //...
            new YahooJapan\ConfigCacheBundle\YahooJapanConfigCacheBundle(),
        );
    }
}
```

Usage
-----

* [Basic usage](basic-usage.md)
* [Merge files](merge-files.md)
* [Specify directories](specify-directories.md)
* [Merge files of all bundles](merge-files-of-all-bundles.md)
* [Extend loaders](extend-loaders.md)
* [Translations](translations.md)
* [Restore caches](restore-caches.md)
* [Generate configurations](generate-configurations.md)
