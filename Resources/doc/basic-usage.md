Basic usage
-----------

The following is a procedure to create a cache as an example of setting sample.yml in AcmeDemoBundle.

### Setup Symfony

This bundle requires the Symfony framework.
See the Symfony [documentation](http://symfony.com/doc/current/book/installation.html) for setup Symfony.

### Generate AcmeDemoBundle

Prepare a Symfony sample bundle beforehand:

```sh
# Symfony 2.x
$ app/console generate:bundle --namespace=Acme/DemoBundle --format=yml
# Symfony 3.x
$ bin/console generate:bundle --namespace=Acme/DemoBundle --format=yml
```

### Configuration file

Set a configuration file on `Resources/config`:

```yml
# src/Acme/DemoBundle/Resources/config/sample.yml
invoice: 34843
date   : '2001-01-23'
bill-to:
    given  : Chris
    family : Dumars
```

### services.yml

Add a service definition with tags in services.yml:

```yml
# src/Acme/DemoBundle/Resources/config/services.yml
services:
    acme_demo.config:
        class: YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache
        tags:
            - { name: config_cache.register, resource: sample.yml }
```

If you name the service ID without dependence on the bundle name, set the bundle attribute of the tag:

```yml
# src/Acme/DemoBundle/Resources/config/services.yml
services:
    any_service_id:
        class: YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache
        tags:
            - { name: config_cache.register, resource: sample.yml, bundle: acme_demo }
```

### Create a cache

Create a cache with the Symfony console:

```sh
# Symfony 2.x
$ app/console cache:warmup
# Symfony 3.x
$ bin/console cache:warmup
```

In this way, a cache object `ConfigCache` is registered as a service:

```sh
# Symfony 2.x
$ app/console debug:container acme_demo.config
# Symfony 3.x
$ bin/console debug:container acme_demo.config
[container] Information for service acme_demo.config
Service Id       acme_demo.config
Class            YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache
Tags             -
Scope            container
Public           yes
Synthetic        no
Lazy             yes
Synchronized     no
Abstract         no
```

A cache file is created and set under the Symfony cache directory:

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

### Use the service

Getting the service container directly or defining in services.yml, you can use the `ConfigCache` service named `acme_demo.config`:

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
        $cache = $this->get('acme_demo.config');

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
            - '@acme_demo.config'
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

### Register services with Extension

Instead of services.yml, you can also register services with `Register` in `AcmeDemoExtension` class:

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
            // the following is the same
            //FileResource::create(__DIR__.'/../Resources/config/sample.yml')->setAlias('sample'),
        ));
        $cache->register();
    }
}
```

In this case, the service ID `config.acme_demo.sample` is generated automatically.  
If a bundle name is "AcmeDemoBundle" and `FileResource` alias is "sample", the service name is `config.acme_demo.sample`.

Some features that are described below like merging files, specifying directory, and so on are available only by using `Register`.
