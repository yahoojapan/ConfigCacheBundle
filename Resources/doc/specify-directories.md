ディレクトリ指定
----------------

指定したディレクトリ配下すべてのファイルをキャッシュの対象にすることができます。  
`DirectoryResource`にディレクトリ名を指定します。

```php
<?php

// src/Acme/DemoBundle/DependencyInjection/AcmeDemoExtension.php
namespace Acme\DemoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource;

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
            new DirectoryResource(__DIR__.'/../Resources/config/any_directory'),
        ));
        $cache->register();
    }
}
```
