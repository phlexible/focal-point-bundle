PhlexibleFocalPointBundle
=========================

The PhlexibleFocalPointBundle adds support for country-based content support in phlexible.

Installation
------------

Installation is a 3 step process:

1. Download PhlexibleFocalPointBundle using composer
2. Enable the Bundle
3. Clear the symfony cache

### Step 1: Download PhlexibleFocalPointBundle using composer

Add PhlexibleFocalPointBundle by running the command:

``` bash
$ php composer.phar require phlexible/focal-point-bundle "~1.0.0"
```

Composer will install the bundle to your project's `vendor/phlexible` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Phlexible\Bundle\FocalPointBundle\PhlexibleFocalPointBundle(),
    );
}
```

### Step 3: Clear the symfony cache

If you access your phlexible application with environment prod, clear the cache:

``` bash
$ php app/console cache:clear --env=prod
```
