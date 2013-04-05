<?php
namespace phpdotnet\phd;
/* $Id: PIHandler.php 293260 2010-01-08 10:41:19Z rquadling $ */

abstract class PIHandler {
    protected $format;

    public function __construct($format) {
        $this->format = $format;
    }

    public abstract function parse($target, $data);

}

/*
* vim600: sw=4 ts=4 syntax=php et
* vim<600: sw=4 ts=4
*/

