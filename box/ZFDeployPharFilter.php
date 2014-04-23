<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

/**
 * FilterIterator implementation for zfdeploy.phar creation
 */
class ZFDeployPharFilter extends FilterIterator
{
    protected $basePath;

    public function __construct($iterator, $basePath)
    {
        parent::__construct($iterator);
        $this->basePath  = $basePath;

        $this->pathsIncludeRegex = array(
            '#^' . $basePath . '/bin#',
            '#^' . $basePath . '/config#',
            '#^' . $basePath . '/src#',
            '#^' . $basePath . '/vendor#',
        );

        $this->pathsExcludeRegex = array(
            '#^' . $basePath . '/vendor/.*?/tests#',
        );

        $this->filesIncludeRegex = array(
            '#\.(json|php|xml|xsd|png)$#',
        );
    }

    public function accept()
    {
        $file = $this->getInnerIterator()->current();

        if (! $file instanceof SplFileInfo) {
            return false;
        }

        $path = $file->getRealPath();

        $inInclude = false;
        foreach ($this->pathsIncludeRegex as $regex) {
            if (preg_match($regex, $path)) {
                $inInclude = true;
                break;
            }
        }
        if (! $inInclude) {
            return false;
        }

        foreach ($this->pathsExcludeRegex as $regex) {
            if (preg_match($regex, $path)) {
                return false;
            }
        }

        if (! $file->isFile()) {
            return true;
        }

        foreach ($this->filesIncludeRegex as $regex) {
            if (preg_match($regex, $path)) {
                return true;
            }
        }

        return false;
    }
}
