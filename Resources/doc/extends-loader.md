ローダを拡張する
----------------

`ConfigCache`サービスは設定ファイルをロードする際にConfigCacheBundle付属の`YamlFileLoader`または`XmlFileLoader`を使います。  
これらのファイルローダの内部には`ArrayLoader`を持たせることもできます。  
`ArrayLoader`を実装して`YamlFileLoader`に追加することで設定ファイルのロード後に何か処理をさせることができます。

例えば以下のような`ArrayLoader`を実装したとします。

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
        foreach ($resource as $key => &$value) {
            if ($key === 'function1') {
                $value['key1'] = 'replaced';
            }
        }

        return $resource;
    }
}
```

これをAcmeDemoBundleでサービスにします。

```yml
# src/Acme/DemoBundle/Resources/config/services.yml
services:
    acme_demo.array_loader:
        class: Acme\DemoBundle\Loader\ArrayLoader

    acme_demo.yaml_file_loader:
        class: YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader
        calls:
            - [addLoader, [@acme_demo.array_loader]]
```

`Bundle`クラスでキャッシュ生成前に`YamlFileLoader`を差し替えます。

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
        $loader = $this->container->get('acme_demo.yaml_file_loader');
        $this->container->get('config.acme_demo')
            ->setLoader($loader)
            ->create()
            ;
    }
}
```

これで`config.acme_demo`は`ArrayLoader`による置き換え後の配列データを持った状態になります。

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
         *     'function1' => array('key1' => 'replaced'),
         *     'function2' => 'value2',
         * )
         */
        $cache->findAll();

        // ...
    }
}
```
