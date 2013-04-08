<?php
namespace phpdotnet\phd;

class Package_Generic_ZFOnlineChunkedXHTML extends Package_Generic_ZFPackageChunkedXHTML
{
    private $myelementmap = array();

    public function __construct() 
    {
        parent::__construct();
        $this->registerFormatName("ZF-Online-Chunked-XHTML");
        $this->myelementmap = array_merge(
            array(),                                         
            static::getDefaultElementMap()
        );
    }

    public function writeChunk($id, $fp)
    {
        $filename = $this->getOutputDir() . Format::getFilename($id) . '.phtml';

        rewind($fp);
        file_put_contents($filename, $this->header($id));
        file_put_contents($filename, $fp, FILE_APPEND);
        file_put_contents($filename, $this->footer($id), FILE_APPEND);
    }

    public function header($id) 
    {
        $navData = $this->getNavData($id);
        $title   = $this->getLongDescription($id);
        $title   = ($title != $navData['root']['ldesc']) ? $title . ' - ' : '';
        $lang    = Config::language();
        static $cssLinks = null;
        if ($cssLinks === null) {
            $cssLinks = $this->createCSSLinks();
        }
        $pageNav = $this->createPageNavigation($navData);

        return
'<?php $this->headTitle(\'' . str_replace("'", "\\'", $title) . '\' . (($this->version != \'current\') ? $this->version . \' - \' : \'\') . \'Zend Framework Manual\'); ?>
<?php $this->rightNav()->captureStart(\'append\', \'Navigation\') ?>
' . $this->createNavBar($id) . '
<?php $this->rightNav()->captureEnd() ?>
' . $pageNav . "<hr />\n";
    }

    public function footer($id) 
    {
        $navData = $this->getNavData($id);
        return "\n        <hr />\n" . $this->createPageNavigation($navData);
    }

    protected function createNavBar($id) 
    {
        $root = Format::getRootIndex();
        $navBar =  '<ul class="manual toc">
    <li class="header home"><a href="'.$root["filename"].".".$this->getExt().'">'.$root["ldesc"].'</a></li>
';
        // Fetch ancestors of the current node
        $ancestors = array();
        $currentId = $id;
        while (($currentId = $this->getParent($currentId)) && $currentId != "index") {
            $desc = "";
            $link = $this->createLink($currentId, $desc);
            $ancestors[] = array("desc" => $desc, "link" => $link);
        }

        // Show them from the root to the closest parent
        foreach (array_reverse($ancestors) as $ancestor) {
        	$navBar .= "    <li class=\"header up\"><a href=\"{$ancestor["link"]}\">{$ancestor["desc"]}</a></li>\n";
        }

        // Fetch siblings of the current node
        $parent = $this->getParent($id);
        foreach ($this->getChildren($parent) as $child) {
            $desc    = "";
            $link    = $this->createLink($child, $desc);
            $active  = ($id === $child);
            $navBar .= "    <li" .($active ? " class=\"active\"" : ""). "><a href=\"$link\">$desc</a></li>\n";
        }
        return $navBar . "</ul>";
    }
}
