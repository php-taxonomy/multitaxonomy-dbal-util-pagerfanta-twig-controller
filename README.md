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
