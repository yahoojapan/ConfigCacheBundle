ConfigCacheBundle
=================

ConfigCacheBundleは設定ファイルキャッシュを扱うバンドルです。

主な機能
--------

* [設定ファイル](about-config.md)をキャッシュとして生成  
Symfonyが生成するサービスコンテナなどのキャッシュではなく別途ファイルキャッシュを持ちます
* キャッシュオブジェクトをSymfonyのサービスコンテナに登録
* 複数ファイルのマージ、バリデーション
* ローダの拡張による事前処理
* 多言語対応

動作環境
--------

PHP 5.3.9以上  
またSymfonyコンポーネントおよびdoctrine/cacheが必要です。  
詳細は[composer.json](../../composer.json)を参照ください。

インストール
------------

##### バンドルの入手

```sh
$ composer require yahoojapan/config-cache-bundle
```

##### バンドルを有効にする

app/AppKernel.phpにConfigCacheBundleを追加してください。

```php
<?php

// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            //...
            new YahooJapan\ConfigCacheBundle\YahooJapanConfigCacheBundle(),
        );
    }
}
```

使い方
------

* [基本的な使い方](basic-usage.md)
* [複数ファイル指定](specify-files.md)
* [マージ](merge-files.md)
* [ディレクトリ指定](specify-directory.md)
* [バンドルをまたいで設定ファイルを集める](config-over-bundles.md)
* [ローダを拡張する](extends-loader.md)
* [多言語対応](multi-languages.md)
* [Configuration自動生成](generate-configuration.md)

補足
----

* [設定ファイルについて](about-config.md)
