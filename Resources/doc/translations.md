Translations
------------

We consider the following cases:
* Translate with the Symfony [translator](http://symfony.com/doc/current/book/translation.html)
* Define message IDs in configuration files and use them in a Controller or a model class.

Normally, we need to translate each time with the [translator](http://symfony.com/doc/current/book/translation.html) after taking the contents from the [configuration files](#sample_config).

```php
<?php

// src/Acme/DemoBundle/Controller/WelcomeController.php
namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WelcomeController extends Controller
{
    public function indexAction()
    {
        $translator = $this->get('translator.default');
        $cache      = $this->get('config.acme_demo.sample');
        $config     = $cache->find('bill-to.given');

        foreach ($config as $key => &$value) {
            if ($translator->getCatalogue()->has($value)) {
                $value = $translator->trans($value);
            }
        }

        // ...
    }
}
```

By creating the translated caches beforehand without the need to translate each time, we can skip this translation process.

This bundle enables a service to create caches that have translated messages beforehand in configuration files.  
To do so, use the `RegisterLocale`.  
The `RegisterLocale` registers a loader service of translations even as `ConfigCache` services.  
The `ConfigCache` service translates them by using the Symfony [translator]((http://symfony.com/doc/current/book/translation.html)) and a translational loader and caches translated contents.  
This service creates the same number of caches as locales and gets a setting value automatically selected according to the current locale.

The following is a sample implementation for translations.

<a id="sample_config">
##### Configuration files

Define a configuration file in AcmeDemoBundle.  
The leaf nodes of the content are translated:

```yml
# src/Acme/DemoBundle/Resources/config/sample.yml
invoice: 34843
date   : '2001-01-23'
bill-to:
    # values will be translated
    given  : id.001
    family : id.002
```

Prepare catalogues for translations:

```yml
# app/Resources/translations/messages.en.yml
id.001: Chris
id.002: Dumars
```

```yml
# app/Resources/translations/messages.ja.yml
id.001: クリス
id.002: デュマース
```

##### app/config/config.yml

Add a description in `app/config/config.yml`:

```yml
# app/config/config.yml
framework:
    translator: { fallbacks: [ja] }

yahoo_japan_config_cache:
    locale:
        enabled: true
        locales: [ja, en]
```

Describe all locales as a "locales" key.

##### Extension

Create `RegisterLocale` in `AcmeDemoExtension`:

```php
<?php

// src/Acme/DemoBundle/DependencyInjection/AcmeDemoExtension.php
namespace Acme\DemoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\RegisterLocale;
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
        $cache = new RegisterLocale($this, $container, array(
            new FileResource(__DIR__.'/../Resources/config/sample.yml', null, 'sample'),
        ));
        $cache->register();
    }
}
```

##### Use the service

You can get translated contents if you set the current locale as described [below](#the-current-locale):

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

        // 'Chris' if _locale is en
        // 'クリス' if _locale is ja
        $cache->find('bill-to.given');

        // ...
    }
}
```

##### The current locale

When you use a `ConfigCache` service of translations, you need the current locale setting.  
The reason is to use the cache according to the locale any one of created caches.

For this setting, you need some processes:

* Set the current locale to the `ConfigCache` service
* Set the Symfony Request::locale the `LocaleListener` refers to

##### Set the current locale to the ConfigCache service

The `LocaleListener` this bundle provides sets the current locale to the `ConfigCache` service.  
If [app/config/config.yml](#appconfigconfigyml) is enabled, this listener is registered automatically.
This listener refers to the Symfony `Request::locale` and sets it.

##### Set the Symfony Request::locale the LocaleListener refers to

This bundle doesn't set the Symfony `Request::locale`, so you need to implement additionally.  
There are some solutions to implement:
* Include a parameter `_locale` in your request URL
* Implement an event listener

For details of these implementations, see the Symfony [documentation](http://symfony.com/doc/current/book/translation.html#handling-the-user-s-locale).
