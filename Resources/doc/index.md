ConfigCacheBundle
=================

ConfigCacheBundleは設定ファイルキャッシュを扱うバンドルです。  
バンドルごとに定義された設定ファイルをキャッシュします。  
また設定ファイルキャッシュをオブジェクトとして持ちSymfony上でサービス化することで扱いやすい形にします。

主な機能
--------

* [バンドルごとに定義された設定ファイル](#バンドルごとに定義された設定ファイル)をキャッシュとして生成  
Symfonyが生成するキャッシュ(サービスコンテナなど)ではなく別途ファイルキャッシュを持ちます
* 生成されたキャッシュを持つオブジェクトをSymfonyのサービスコンテナに登録  
サービス登録のための記述は最小限で済むため簡単に設定できます
* キャッシュ対象ファイルの指定方法  
複数ファイル指定，ディレクトリ指定，バンドルをまたいだファイル指定ができます
* ローダの拡張  
設定ファイルを読み込むローダを差し替えることでキャッシュ生成前の配列データに対して事前処理ができます
* 多言語対応

##### バンドルごとに定義された設定ファイル

バンドルごとに定義された設定ファイルとは，アプリケーション設定に依らないバンドル固有の設定ファイルのことを指します。  
アプリケーション設定はapp配下のapp/config/config.ymlなどで設定します。  
アプリケーション設定に依らないバンドル固有の設定はapp配下ではなくバンドル配下の/Resources/config以下に配置します。  
例えばAcmeDemoBundleならsrc/Acme/DemoBundle/Resources/config/sample.ymlなどです。  
ConfigCacheBundleではこのような設定ファイルをキャッシュすることを目的としています。

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
* [ディレクトリ指定](specify-directory.md)
* [バンドルをまたいで設定ファイルを集める](config-over-bundles.md)
* [ローダを拡張する](extends-loader.md)
* [多言語対応](multi-languages.md)
* [Configuration自動生成](generate-configuration.md)
