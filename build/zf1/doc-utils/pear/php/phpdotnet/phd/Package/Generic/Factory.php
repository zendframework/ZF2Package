<?php
namespace phpdotnet\phd;
/* $Id: Factory.php 290730 2009-11-14 01:21:08Z moacir $ */

class Package_Generic_Factory extends Format_Factory
{
    /**
     * List of cli format names and their corresponding class.
     *
     * @var array
     */
    private $formats = array(
        'xhtml'         => 'Package_Generic_ChunkedXHTML',
        'bigxhtml'      => 'Package_Generic_BigXHTML',
        'zfpackage'     => 'Package_Generic_ZFPackageChunkedXHTML',
        'zfonline'      => 'Package_Generic_ZFOnlineChunkedXHTML',
    );

    public function __construct()
    {
        parent::setPackageName('Generic');
        parent::registerOutputFormats($this->formats);
    }
}

/*
* vim600: sw=4 ts=4 syntax=php et
* vim<600: sw=4 ts=4
*/

