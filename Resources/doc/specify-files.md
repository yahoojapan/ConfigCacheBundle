複数ファイル指定
----------------

複数のファイルをキャッシュの対象にすることができます。  
`Register`の第3引数でリスト指定します。

```yml
# src/Acme/DemoBundle/Resources/config/sample1.yml
all:
   function1:
       key1: value1
   function2: value2
```

```yml
# src/Acme/DemoBundle/Resources/config/sample2.yml
all:
   function3: value3
   function4: value4
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

ファイルごとにキャッシュおよびサービスが生成されます。

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
         *     'all' => array(
         *         'function1' => array('key1' => 'value1'),
         *         'function2' => 'value2',
         *     ),
         * )
         */
        $cache1->findAll();

        /**
         * array(
         *     'all' => array(
         *         'function3' => 'value3',
         *         'function4' => 'value4',
         *     ),
         * )
         */
        $cache2->findAll();

        // ...
    }
}
```
