<?php
namespace phpdotnet\phd;

class Package_Generic_ZFPackageChunkedXHTML extends Package_Generic_ChunkedXHTML 
{
    private $myelementmap = array(
        'bookinfo'              => array(
            /* DEFAULT */         'format_div',
            'note'              => 'span',
        ),
        'edition'           => 'h1',
        'exceptionname'     => array(
            /* DEFAULT */ 'span',
            'ooclass' =>  array(
                'classsynopsisinfo' => 'format_classsynopsisinfo_ooclass_classname',
            ),
        ),
        'firstterm'         => 'b',
        'funcparams'        => 'code_funcparams',
        'inlinegraphic'     => 'format_imagedata',
        'inlinemediaobject' => 'div',
        'interface'         => array(
            /* DEFAULT */ 'span',
            'ooclass'  => array(
                'classsynopsisinfo' => 'format_classsynopsisinfo_ooclass_classname',
            ),
        ),
        'subtitle'          => 'format_note_title',
        'term'              => 'dt',
        'trademark'         => 'span_trademark',
        'ulink'             => 'a',
    );

    public function __construct() 
    {
        parent::__construct();
        $this->registerFormatName("ZF-Package-Chunked-XHTML");
        $this->myelementmap = array_merge(
            parent::getDefaultElementMap(),                                         
            static::getDefaultElementMap()
        );
    }

    public function getDefaultElementMap() 
    {
        return $this->myelementmap;
    }

    public function code_funcparams($open, $name, $attrs, $props)
    {
        if ($open) {
            return '<code class="funcparams">';
        }
        return '</code>';
    }

    public function span_trademark($open, $name, $attrs, $props)
    {
        if ($open) {
            return '<span class="trademark">';
        }
        return '</span>';
    }

    public function format_imagedata($open, $name, $attrs)
    {
        $file    = $attrs[Reader::XMLNS_DOCBOOK]["fileref"];

        if ($newpath = $this->mediamanager->handleFile($file)) {
            $curfile = $this->mediamanager->findFile($file);
            $width   = isset($attrs[Reader::XMLNS_DOCBOOK]["width"]) ? $attrs[Reader::XMLNS_DOCBOOK]["width"] : '';
            $height  = isset($attrs[Reader::XMLNS_DOCBOOK]["depth"]) ? $attrs[Reader::XMLNS_DOCBOOK]["depth"] : '';
            $alt     = 'alt="' . ($this->cchunk["mediaobject"]["alt"] !== false ? $this->cchunk["mediaobject"]["alt"] : basename($file)) . '"';

            // Generate height and width when none are supplied.
            if ($curfile && empty($width) && empty($height)) {
                list($width, $height) = getimagesize($curfile);
            } elseif ($curfile && (empty($width) || empty($height))) {
                list($width, $height) = getimagesize($curfile);
            }

            // Generate warnings when only 1 dimension supplied or alt is not supplied.
            if (!$width xor !$height) {
                v('Missing %s attribute for %s', (!$width ? 'width' : 'height'), $file, VERBOSE_MISSING_ATTRIBUTES);
            }

            $downscaled = false;
            if ((int) $width > 450) {
                list($width, $height) = $this->_downscaleImage($width, $height);
                $downscaled = true;
            }

            $dimensions = sprintf('width="%s" height="%s"', $width, $height);

            if (false === $this->cchunk["mediaobject"]["alt"]) {
                v('Missing alt attribute for %s', $file, VERBOSE_MISSING_ATTRIBUTES);
            }

            $markup = sprintf('<img src="%s" %s %s />', $newpath, $alt, $dimensions);
            if ($downscaled) {
                $markup = sprintf('<a href="%s">%s</a>', $newpath, $markup);
            }
            return $markup;
        } else {
            return '';
        }
    }

    public function format_methodparam($open, $name, $attrs)
    {
        if ($open) {
            $content = '<span class="methodparam">( ';
            return $content;
        }
        return ' )</span>';
    }

    public function format_methodsynopsis($open, $name, $attrs) 
    {
        if ($open) {
            $this->params = array("count" => 0, "opt" => 0, "content" => "");
            return '<div class="'.$name.' dc-description">';
        }
        $content = "";
        if ($this->params["opt"]) {
            $content = str_repeat("]", $this->params["opt"]);
        }
        $content .= "</div>\n";

        return $content;
    }

    public function format_programlisting($open, $name, $attrs) 
    {
        if ($open) {
            $tag = '<div class="programlisting';
            if (isset($attrs[Reader::XMLNS_DOCBOOK]["language"])) {
                $this->role = $attrs[Reader::XMLNS_DOCBOOK]["language"];
                $tag .= ' ' . $this->role;
            } else {
                $this->role = false;
            }

            $tag .= '">';
            return $tag;
        }
        $this->role = false;
        return "</div>\n";
    }

    public function header($id) 
    {
        $title   = $this->getLongDescription($id);
        $lang    = Config::language();
        $navData = $this->getNavData($id);
        static $cssLinks = null;
        if ($cssLinks === null) {
            $cssLinks = $this->createCSSLinks();
        }
        $pageNav = $this->createPageNavigation($navData);

        return
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' .$lang. '" lang="' .$lang. '">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title>'.(($title != $navData['root']["ldesc"]) ? $title .= ' - Zend Framework Manual' : "Zend Framework Manual") . '</title>
'.$cssLinks.'
</head>
<body>
<table width="100%">
    <tr valign="top">
        <td width="85%">' . $pageNav . "<hr />\n";
    }

    public function footer($id) 
    {
        $navBar  = $this->createNavBar($id);
        $navData = $this->getNavData($id);
        return "\n        <hr />\n" . $this->createPageNavigation($navData) . '</td>
        <td style="font-size: smaller;" width="15%"> ' . $navBar . ' </td>
    </tr>
</table>
</body>
</html>';
    }

    public function getNavData($id)
    {
        $root = Format::getRootIndex();
        $prev = $next = $parent = array("href" => null, "desc" => null);

        if ($parentId = $this->getParent($id)) {
            $parent = array("href" => $this->getFilename($parentId) . '.' .$this->getExt(),
                "desc" => $this->getShortDescription($parentId));
        }
        if ($prevId = Format::getPrevious($id)) {
            $prev = array("href" => Format::getFilename($prevId) . '.' .$this->getExt(),
                "desc" => $this->getShortDescription($prevId));
        }
        if ($nextId = Format::getNext($id)) {
            $next = array("href" => Format::getFilename($nextId) . '.' .$this->getExt(),
                "desc" => $this->getShortDescription($nextId));
        }
        return array(
            'prevId'   => $prevId,
            'prev'     => $prev,
            'nextId'   => $nextId,
            'next'     => $next,
            'parentId' => $parentId,
            'parent'   => $parent,
            'root'     => $root,
        );
    }

    public function createPageNavigation(array $data)
    {
        return '
            <table width="100%">
                <tr>
                    <td width="25%" style="text-align: left;">
                    '.($data['prevId'] ? '<a href="' .$data['prev']["href"]. '">' .$data['prev']["desc"]. '</a>' : '') .'
                    </td>

                    <td width="50%" style="text-align: center;">
                        <div class="up">'.($data['parentId'] ? '<span class="up"><a href="' .$data['parent']["href"]. '">' .$data['parent']["desc"]. '</a></span><br />' : '') .'
                        <span class="home"><a href="'.$data['root']["filename"].".".$this->getExt().'">'.$data['root']["ldesc"].'</a></span></div>
                    </td>

                    <td width="25%" style="text-align: right;">
                        '.($data['nextId'] ? '<div class="next" style="text-align: right; float: right;"><a href="' .$data['next']["href"]. '">' .$data['next']["desc"].'</a></div>' : '') .'
                    </td>
                </tr>
            </table>
';
            /*
            <div style="text-align: center;">
                '.($data['prevId'] ? '<div class="prev" style="text-align: left; float: left;"><a href="' .$data['prev']["href"]. '">' .$data['prev']["desc"]. '</a></div>' : '') .'
                '.($data['nextId'] ? '<div class="next" style="text-align: right; float: right;"><a href="' .$data['next']["href"]. '">' .$data['next']["desc"].'</a></div>' : '') .'
                <div class="up">'.($data['parentId'] ? '<span class="up"><a href="' .$data['parent']["href"]. '">' .$data['parent']["desc"]. '</a></span><br />' : '') .'
                <span class="home"><a href="'.$data['root']["filename"].".".$this->getExt().'">'.$data['root']["ldesc"].'</a></span></div>
            </div>
';
            */

    }

    protected function _downscaleImage($width, $height)
    {
        $x_ratio   = 450 / $width;
        $tn_width  = 450;
        $tn_height = ceil($x_ratio * $height);
        return array($tn_width, $tn_height);
    }
}
