Generate configurations
-----------------------

This bundle provides a command that generates a `Configuration` skeleton.  
Execute a console command with the Symfony cache created after "[Enable the bundle](index.md#enable-the-bundle)":

```sh
# Symfony 2.x
$ app/console generate:configuration -b AcmeDemoBundle -f sample.yml
# Symfony 3.x
$ bin/console generate:configuration -b AcmeDemoBundle -f sample.yml
Generated file $ROOT_DIR/src/Acme/DemoBundle/DependencyInjection/Configuration.php
```

The console command generates `Configuration.php` as described [above](merge-files.md#implement-a-configuration-class).

### Generated Configuration

Generated `Configuration` is a skeleton, so the validation of configuration files is not strict.  
For more details of the validation, see the Symfony [documentation](http://symfony.com/doc/current/components/config/definition.html).
