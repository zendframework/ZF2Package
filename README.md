README
======

Building Pyrus, Composer & full/minimal packages
------------------------------------------------

Copy ``build/build-config.php.dist`` to ``build/build-config.php``. Edit
``build/build-config.php``, in particular to update the ``$release`` string at
the top of the build. (If you want to control what is actually built, you can
comment out various parts of this file.)

Now run:

    php build/build.php

(I typically pipe this to "tee build.log" so I can check for errors if needed.)

Check the artifacts to see that everything was built. In particular, verify that
you have 

- Pyrus packages for each component, as well as the ZF metapackage; 
- Composer packages for each component;
- API documentation packages;
- End-user documentation packages;
- and both full and minimal ZF packages.

At this point, you can now release the packages:

    php build/release.php

(I typically pipe this to "tee release.log" so I can check for errors if needed.)

Verify that:

- Pyrus packages and XML were properly created, including phar, tgz, and zip
  variants;
- Composer packages were pushed to the public tree, and the packages.json
  updated;
- ZF full and minimal builds, API docs, and end-user doc packages were copied to
  the public tree;
- The manual and API docs were properly unpacked in the public tree, and the
  "latest" link updated to the new release.

That's it. Then add the new files and the changed packages.json, commit, and
push to the github repository.
 
Updating the site
-----------------

Edit the ``public/index.html`` file, and ensure the version strings in it
reflect the new version. Commit and push the file.

Deploying
---------

Checkout from the server, where the actual channel is located.
