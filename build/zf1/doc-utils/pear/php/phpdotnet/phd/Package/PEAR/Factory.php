<?php
namespace phpdotnet\phd;
/* $Id: Factory.php 289584 2009-10-12 18:14:55Z cweiske $ */

class Package_PEAR_Factory extends Format_Factory {
    private $formats = array(
        'xhtml'         => 'Package_PEAR_ChunkedXHTML',
        'bigxhtml'      => 'Package_PEAR_BigXHTML',
        'php'           => 'Package_PEAR_Web',
        'chm'           => 'Package_PEAR_CHM',
        'tocfeed'       => 'Package_PEAR_TocFeed',
    );
    
    public function __construct() {
        parent::setPackageName("PEAR");
        parent::registerOutputFormats($this->formats);
    }
}

/*
* vim600: sw=4 ts=4 syntax=php et
* vim<600: sw=4 ts=4
*/

