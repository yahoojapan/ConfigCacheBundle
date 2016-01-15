バンドルをまたいで設定ファイルを集める
--------------------------------------

AppKernel.phpに登録されているすべてのバンドルを対象として設定ファイルを集めることができます。  
`registerAll()`を使います。

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
            new FileResource('/Resources/config/sample.yml'),
        ));
        $cache->registerAll();
    }
}
```

AcmeDemoBundleに限らずすべてのバンドルの/Resources/config/sample.ymlをマージして1個のキャッシュを生成します。
