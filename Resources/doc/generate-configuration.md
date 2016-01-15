Configuration自動生成
---------------------

設定ファイルを指定して`Configuration`クラスのひな形を生成するコマンドを用意しました。  
[バンドルを有効にする](index.md#バンドルを有効にする)を実施した後にSymfonyのキャッシュを生成した状態で以下コマンドを実行してください。

```sh
$ app/console generate:configuration -b AcmeDemoBundle -f sample.yml
Generated file $ROOT_DIR/src/Acme/DemoBundle/DependencyInjection/Configuration.php
```

コマンドを実行すると[Configurationクラス](basic-usage.md#configuration%E3%82%AF%E3%83%A9%E3%82%B9)で示したようなConfiguration.phpファイルが自動生成されます。

##### 生成されるファイルについて

このコマンドで生成されるのはあくまでひな形のため，`Configuration`による厳密なバリデーションはされません。  
設定ファイルの細かいバリデーションを実施したい場合は[公式ドキュメント](http://symfony.com/doc/current/components/config/definition.html)を参考に自動生成されたConfiguration.phpを修正してください。

また自動生成されたConfiguration.phpは冗長な記述になることがあります。  
`prototype()`，`useAttributeAsKey()`などを使うことで`Configuration`の記述を簡潔にできる場合があります。
