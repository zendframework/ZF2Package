README
======

Building Pyrus & Composer packages
----------------------------------

* First, copy packager.ini.orig to packager.ini.
* Next, configure:
    * path to the ZF2 root directory
    * path to pyrus script
    * a release version name

For Pyrus:

* Run:

  `php scripts/make-all-packages.php`

For Composer:

* Run:

  `php scripts/make-all-composer-packages.php`

For External Packages:

* Run:

  `php scripts/make-all-external-composer-packages.php`

* Finally, run:

  `php scripts/make-all-external-packages.php`

Releasing:

* Finally, run:

  `php scripts/release-all-packages.php`

* Finally, run:

  `php scripts/release-all-composer-packages.php`


That's it. Then add the new files & the changed packages.json, commit, and push to the 
github repository.
 
Building full/minimal packages
------------------------------

Two steps:

* Run:

  `php scripts/make-zf-package.php`

* Run:

  `php scripts/make-zf-minimal-package.php`

Add and commit the new `public/releases/ZendFramework-<version>/`
subdirectory that was created.

Building the end-user manual
----------------------------

* Run:

  `php scripts/make-zf-manual.php`

Add and commit the new `public/releases/ZendFramework-<version>/*-manual*`
files that were created.

Building the API documenation
-----------------------------

* Run:

  `php scripts/make-zf-apidoc.php`

Add and commit the new `public/releases/ZendFramework-<version>/*-apidoc.*`
files that were created.

**Note:** this command takes some time to run!

Deploying
---------

Checkout from the server, where the actual channel is located.
