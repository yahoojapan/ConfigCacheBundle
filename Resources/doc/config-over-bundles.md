バンドルをまたいで設定ファイルを集める
--------------------------------------

AppKernel.phpに登録されているすべてのバンドルを対象として設定ファイルを集めることができます。  
`registerAll()`を使います。

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
            new FileResource('/Resources/config/sample.yml'),
        ));
        $cache->registerAll();
    }
}
```

`FileResource`のエイリアス(第3引数)指定はしないようにします。
エイリアス指定をしたときは`register()`と同様の効果になります。

このサンプルではすべてのバンドルの/Resources/config/sample.ymlをマージして1個のキャッシュを生成します。
