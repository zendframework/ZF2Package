Build System for Zend Framework 2
=================================

This is the build system for Zend Framework 2. It utilizes the following
technologies/projects:

- [Satis](https://github.com/composer/satis): handles creation of the
  [Composer](http://getcomposer.org/) repository, including retrieving zipball
  packages from GitHub.
- [Pyrus](http://pear2.php.net/): handles creation of our Pyrus packages and
  channel.
- [phpDocumentor 2](http://phpdoc.org/): generation of API documentation.

Updating the Composer repository
--------------------------------

Updating the Composer repository is the simplest task, and the one task required
to release any new version. The same command will update any packages that have
new tags, and will also ensure that packages for existing branches are
up-to-date.

*Usage:*

```sh
make composer
```

The `all` target is a synonym for `composer`:

```sh
make all
```

Creating and releasing individual service Pyrus packages
--------------------------------------------------------

Service components are released on a separate schedule from the framework
itself. As such, you will typically only need to update the Composer repository,
and then release the specific package version for Pyrus.

The `VERSION` variable *must* be provided on the command line or via environment
variable for this target.

*Usage:*

```sh
make ZendService_AgileZen VERSION=2.0.1
```

Once created, release any Pyrus packages as follows:

```sh
make pyrus-release
```

*Available services:*

- `ZendCloud`
- `ZendGData`
- `ZendOAuth`
- `ZendOpenId`
- `ZendPdf`
- `ZendQueue`
- `ZendRest`
- `ZendService_AgileZen`
- `ZendService_Akismet`
- `ZendService_Amazon`
- `ZendService_Apple_Apns`
- `ZendService_Audioscrobbler`
- `ZendService_Delicious`
- `ZendService_DeveloperGarden`
- `ZendService_Flickr`
- `ZendService_GoGrid`
- `ZendService_Google_Gcm`
- `ZendService_LiveDocx`
- `ZendService_Nirvanix`
- `ZendService_Rackspace`
- `ZendService_ReCaptcha`
- `ZendService_SlideShare`
- `ZendService_StrikeIron`
- `ZendService_Technorati`
- `ZendService_Twitter`
- `ZendService_WindowsAzure`

Releasing a new ZF2 version
---------------------------

When a new Zend Framework 2 release is ready, besides updating Composer (per the
above section) the following tasks must also be done:

- Creation and release of the standalone archives
- Creation and release of the end-user documentation archives
- Creation and release of the API documentation archives
- Creation and release of the `ZendFramework` Pyrus package, as well as all
  individual component Pyrus packages

These above tasks may be done with the `zf2` and `zf2-release` targets.

*Usage:*

```sh
make zf2 VERSION=2.1.4
make zf2-release VERSION=2.1.4
```

The `VERSION` variable *must* be provided on the command line or via environment
variable for these targets.

General release target
----------------------

Occasionally, you may want to build and stage many packages, and then release
once. The following release targets exist:

- `zf2-release` will release archive and documentation packages for a specific
  ZF2 version.
- `pyrus-release` will release any pending Pyrus packages.
- `release` is a meta-target that combines the above.

The `zf2-release`, and by extension, `release`, targets require that the
`VERSION` variable be passed on the command line or via environment variable.

*Usage:*

```sh
make zf2-release VERSION=2.1.4
make pyrus-release
make release VERSION=2.1.4
```

ZFTool
------

You can rebuild and release `zftool.phar` using the targets `zftool.phar` and
`zftool.phar-release`.

*Usage:*

```sh
make zftool.phar
make zftool.phar-release
```

Since `zftool.phar-release` depends on `zftool.phar`, you can shorten this to:

```sh
make zftool.phar-release
```

Cleaning up
-----------

After packaging and releasing, clean up after yourself; use the `clean` target
to do this.

*Usage:*

```sh
make clean
```
