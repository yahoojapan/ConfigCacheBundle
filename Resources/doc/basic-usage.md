Basic usage
-----------

The following is a procedure to create a cache as an example of setting sample.yml in AcmeDemoBundle.

##### Setup Symfony

This bundle requires the Symfony framework.
See the Symfony [documentation](http://symfony.com/doc/current/book/installation.html) for setup Symfony.

##### Generate AcmeDemoBundle

Prepare a Symfony sample bundle beforehand:

```sh
# Symfony 2.x
$ app/console generate:bundle --namespace=Acme/DemoBundle --format=yml
# Symfony 3.x
$ bin/console generate:bundle --namespace=Acme/DemoBundle --format=yml
```

##### Configuration file

Set a configuration file on `Resources/config`:

```yml
# src/Acme/DemoBundle/Resources/config/sample.yml
invoice: 34843
date   : '2001-01-23'
bill-to:
    given  : Chris
    family : Dumars
```

##### Extension

Add a service definition in `DependencyInjection/AcmeDemoExtension.php`:

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

##### Bundle

Add a description to create a cache in `AcmeDemoBundle.php`:

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

This service ID `config.acme_demo.sample` is generated automatically based on an alias you specify in `FileResource` and the bundle name "AcmeDemoBundle".  
If a bundle name is "AcmeDemoBundle" and `FileResource` alias is "sample", the service name is `config.acme_demo.sample`.

##### Create a cache

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

##### Use the service

Getting the service container directly or defining in services.yml, you can use the `ConfigCache` service named `config.acme_demo.sample`:

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
