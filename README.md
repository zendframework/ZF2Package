README
======

Building Zend Framework 2 packages
----------------------------------

Descend into `build/zf2/` and execute:

- Updating the Composer channel:

  ```sh
  make composer
  ```

- Creating ZF2 packages:

  ```sh
  make composer
  make zf2 VERSION=2.1.4
  make release
  ```

- Releasing a service component:

  ```sh
  make composer
  make ZendService_AgileZen VERSION=2.0.1
  make pyrus-release
  ```

Once done, add the packages to the repository, commit, and push; don't forget to
also add `public/packages.json` and `public/index.html`.

When done, run:

```sh
make clean
```

For more information, read the [ZF2 README file](build/zf2/README.md).

Building Zend Framework 1 packages
----------------------------------

Descend into `build/zf1/` and execute:

```sh
make all ZF_VERSION=1.<minor>.<maintenance>
```

Once done, add the packages to the repository, commit, and push.

When done, run:

```sh
make clean
```

For more information, read the [ZF1 README file](build/zf1/README.md).

Building ZFTool
---------------

Descend into `build/zf2/` and execute:

```sh
make zftool.phar-release
```

Once done, add `public/zftool.phar` to the repository, commit, and push.

When done, run:

```sh
make clean
```

For more information, read the [ZF2 README file](build/zf2/README.md).
 
Deploying
---------

Checkout from the server, where the actual channel is located.

```sh
git fetch origin
git rebase origin/production
```
