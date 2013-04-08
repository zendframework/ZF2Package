Zend Framework 1 Build System
=============================

This directory provides build tools for Zend Framework 1. It uses `make`; you
will have to have `make` installed on your system, as well as PHP,
phpDocumentor, and `xsltproc`.

The following build targets are of interest:

- `make all` - creates and releases all product distribution files
- `make dist` - creates the full and minimal distribution files for Zend
  Framework 1.x.
- `make amf` - creates the `Zend_AMF` distribution files
- `make gdata` - creates the `Zend_Gdata` distribution files
- `make infocard` - creates the `Zend_InfoCard` distribution files
- `make release-all` - copies all product distribution files to the release directory
- `make release` - copies the ZF 1.X distribution files to the release directory
- `make release-amf` - copies the `Zend_AMF` distribution files to the release directory
- `make release-gdata` - copies the `Zend_Gdata` distribution files to the release directory
- `make release-infocard` - copies the `Zend_InfoCard` distribution files to the release directory
- `make clean` - removes all staging and distribution files
- `make clean-export` - removes all export files (raw downloads prior to processing)
- `make clean-all` - removes all staging, distribution, and export files

All `make` targets require that you pass the `ZF_VERSION` variable; you can do
this on the commandline as follows:

```sh
make all ZF_VERSION=1.12.3
```

Configuration files
-------------------

The `config` directory has a variety of `rsync` "include-file" and
"exclude-from" files for use with the various distributions.

Documentation utilities
-----------------------

The `doc-utils` directory contains:

- DocBook 4 to DocBook 5 conversion tools
- A script for merging the ZF1 extras documentation
- [PhD](http://doc.php.net/phd/), and custom templates/stylesheets
- Truncated `manual.xml` files for the AMF, GData, and InfoCard distributions

Scripts
-------

The `scripts` directory contains a number of scripts consumed by the `Makefile`.
