Extend loaders
--------------

When the `ConfigCache` service loads configuration files, the service uses `YamlFileLoader` or `XmlFileLoader` this bundle includes.  
You can also give these file loaders `ArrayLoader`.  
Implementing `ArrayLoader` and adding it to `YamlFileLoader`, you can let the loader do something after loading configuration files.

We suppose `ArrayLoader` implementation below:

```php
<?php

// src/Acme/DemoBundle/Loader/ArrayLoader.php
namespace Acme\DemoBundle\Loader;

use YahooJapan\ConfigCacheBundle\ConfigCache\Loader\ArrayLoaderInterface;

class ArrayLoader implements ArrayLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $resource)
    {
        $resource['bill-to']['given']  = 'Taro';
        $resource['bill-to']['family'] = 'Yahoo';

        return $resource;
    }
}
```

Create `ArrayLoader` service in AcmeDemoBundle:

```yml
# src/Acme/DemoBundle/Resources/config/services.yml
services:
    acme_demo.array_loader:
        class: Acme\DemoBundle\Loader\ArrayLoader

    acme_demo.yaml_file_loader:
        class: YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader
        calls:
            - [addLoader, ['@acme_demo.array_loader']]
```

Replace `YamlFileLoader` before creating a cache in `AcmeDemoBundle`:

```php
<?php

// src/Acme/DemoBundle/AcmeDemoBundle.php
namespace Acme\DemoBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AcmeDemoBundle extends Bundle
{
    public function boot()
    {
        $loader = $this->container->get('acme_demo.yaml_file_loader');
        $this->container->get('config.acme_demo.sample')
            ->setLoader($loader)
            ->create()
            ;
    }
}
```

As a result, the `config.acme_demo.sample` service has a content that is replaced by `ArrayLoader`:

```php
<?php

// src/Acme/DemoBundle/Controller/WelcomeController.php
namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WelcomeController extends Controller
{
    public function indexAction()
    {
        $cache = $this->get('config.acme_demo.sample');

        /**
         * array(
         *     'invoice' => 34843,
         *     'date' => '2001-01-23',
         *     'bill-to' => array(
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
