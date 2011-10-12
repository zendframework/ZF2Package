README
======

Building packages
-----------------

It's just a couple of steps

* First, copy packager.ini.orig to packager.ini.
* Next, configure:
    * path to the ZF2 root directory
    * path to pyrus script
    * a release version name
* Then, run:

  `php scripts/make-all-packages.php`

* Finally, run:

  `php scripts/release-all-packages.php`

That's it. Then commit to the github repository, and checkout
from the server (where the actual channel is located)
 