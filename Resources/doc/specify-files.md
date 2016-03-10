Specify files
-------------

This bundle creates caches for each configuration file.  
Specify an array that consists of the third argument of `Register`:

```yml
# src/Acme/DemoBundle/Resources/config/sample1.yml
invoice: 34843
date   : '2001-01-23'
bill-to:
    given  : Chris
    family : Dumars
```

```yml
# src/Acme/DemoBundle/Resources/config/sample2.yml
ship-to:
    given  : Taro
    family : Yahoo
```

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
            new FileResource(__DIR__.'/../Resources/config/sample1.yml', null, 'sample1'),
            new FileResource(__DIR__.'/../Resources/config/sample2.yml', null, 'sample2'),
        ));
        $cache->register();
    }
}
```

Caches and services are created for each file:

```php
<?php

// src/Acme/DemoBundle/AcmeDemoBundle.php
namespace Acme\DemoBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AcmeDemoBundle extends Bundle
{
    public function boot()
    {
        $this->container->get('config.acme_demo.sample1')->create();
        $this->container->get('config.acme_demo.sample2')->create();
    }
}
```

```php
<?php

// src/Acme/DemoBundle/Controller/WelcomeController.php
namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WelcomeController extends Controller
{
    public function indexAction()
    {
        $cache1 = $this->get('config.acme_demo.sample1');
        $cache2 = $this->get('config.acme_demo.sample2');

        /**
         * array(
         *     'invoice' => 34843,
         *     'date' => '2001-01-23',
         *     'bill-to' => array(
         *         'given' => 'Chris',
         *         'family' => 'Dumars',
         *     ),
         * )
         */
        $cache1->findAll();

        /**
         * array(
         *     'ship-to' => array(
         *         'given' => 'Taro',
         *         'family' => 'Yahoo',
         *     ),
         * )
         */
        $cache2->findAll();

        // ...
    }
}
```
