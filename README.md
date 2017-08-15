# multitaxonomy-dbal-util-pagerfanta-twig-controller
Multitaxonomy for DBAL-Util persistence (controllers for  pagerfanta objects and twig templates)

```php
$routes->import(
    $this->getProjectDir().'/vendor/php-taxonomy/multitaxonomy-dbal-util-pagerfanta-twig-controller/default.yml',
    '/taxonomy',
    'yaml'
);

/*
$routes->import(
    $this->getProjectDir().'/vendor/php-taxonomy/multitaxonomy-dbal-util-pagerfanta-twig-controller/MultiTaxonomyController.php',
    '/taxonomy',
    'annotation'
); // does not work!
*/
```
Annotation may need the controllers to be inside of a directory named Controller, like in a Symfony bundle.

### TODO better model
* use an interface
* https://symfony.com/doc/master/service_container.html#the-autowire-option

### TODO Templating: remove ".html.twig" in templates filenames
* https://symfony.com/doc/current/components/templating.html
* http://symfony.com/doc/current/templating/formats.html

### TODO PSR-7 in a long time
* use PSR7 when ready in Symfony ie when it will have replaced or reorganized http-foundation and be supported, tested and documented in forms!
* http://symfony.com/blog/psr-7-support-in-symfony-is-here
* Symfony 3.3 PSR7 needs https://phppackages.org/p/sensio/framework-extra-bundle
* framework-extra-bundle requires symfony/framework-bundle which requires a lot of dependencies.
* also it is just a converter based on https://phppackages.org/p/zendframework/zend-diactoros
* internally http-fundation Request is still used
* This page may contain updates on evolution https://symfony.com/doc/master/request/psr7.html
* https://symfony.com/doc/current/controller/argument_value_resolver.html
* https://symfony.com/doc/master/service_container/alias_private.html#services-why-private
* https://dunglas.fr/2015/06/using-psr-7-in-symfony/
* https://wiki.php.net/rfc/immutability // PHP 7.2 ?
* https://github.com/php-fig/fig-standards/tree/master/proposed/http-factory
* https://github.com/php-fig/fig-standards/tree/master/proposed/http-middleware
* https://github.com/http-interop
* Be ready to redirect or forward responses

Icon: https://material.io/icons/#ic_label_outline
