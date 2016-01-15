複数ファイル指定
----------------

複数のファイルを設定ファイルキャッシュの対象にすることができます。  
`Register`の第4引数でリスト指定します。

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

```php
<?php

// src/Acme/DemoBundle/DependencyInjection/AcmeDemoExtension.php
namespace Acme\DemoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
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
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // add
        $cache = new Register($this, $config, $container, array(
            new FileResource(__DIR__.'/../Resources/config/sample1.yml'),
            new FileResource(__DIR__.'/../Resources/config/sample2.yml'),
        ));
        $cache->register();
    }
}
```

すべてのファイルの内容をマージして1個のキャッシュを生成します。

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
