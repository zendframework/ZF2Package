<?php
if ($argc < 2) {
    echo "No languages provided\n";
    exit(42);
}

if (isset($_ENV['PWD'])) {
    $pwd = $_ENV['PWD'];
} else {
    $pwd = trim(shell_exec('pwd'));
}
if (empty($pwd)) {
    $pwd = '.';
}
echo "PWD: '$pwd'\n";

$cmd  = "grep 'const VERSION' $pwd/library/Zend/Version.php";
$test = shell_exec($cmd);
if (!preg_match("#const VERSION = '(\d+\.\d+\.\d+)#", $test, $matches)) {
    echo "Failed to locate ZF version\n";
    exit(42);
}
$version = $matches[1];

$langs = $argv[1];
$langs = explode(' ', $langs);
if (empty($langs)) {
    echo "No languages provided\n";
    exit(42);
}

echo "COPYING extras manual chapters to manual source for\n";
foreach ($langs as $lang) {
    if (!preg_match('/^[a-z]{2}([_-][a-z]{2})?$/i', $lang)) {
        echo "    FAILED for $lang\n";
        continue;
    }
    echo "    $lang\n";
    system("cp $pwd/extras/documentation/manual/en/module_specs/*.xml $pwd/documentation/manual/$lang/module_specs/");
}

$extras = new DOMDocument();
$extras->load("$pwd/extras/documentation/manual/en/manual.xml.in");
$nodeList       = $extras->getElementsByTagName('chapter');
$extrasChapters = array();
for ($i = 0; $i < $nodeList->length; $i++) {
    $node = $nodeList->item($i);
    $extrasChapters[] = $node->cloneNode(true);
}


// Add Extras chapters into source
echo "ADDING extras manual chapters to manual source\n";
$manual = new DOMDocument();
$manual->load("$pwd/documentation/manual/en/manual.xml.in");
$xpath = new DOMXPath($manual);
if (version_compare($version, '1.9.7', '<=')) {
    $parts = $xpath->query("//chapter");
    $lastChapter   = $parts->item($parts->length - 1);
    $chapterParent = $lastChapter->parentNode;
    $appendixNode  = $lastChapter->nextSibling;
    if (0 < $parts->length) {
        foreach ($extrasChapters as $chapter) {
            $new = $manual->importNode($chapter, true);
            $chapterParent->insertBefore($new, $appendixNode);
        }
    }  
} else {
    $parts = $xpath->query("//part[@id='reference']");
    for ($i = 0; $i < $parts->length; $i++) { 
        $node = $parts->item($i);
        foreach ($extrasChapters as $chapter) {
            $new = $manual->importNode($chapter, true);
            $node->appendChild($new);
        }
    }
}
$manual->save("$pwd/documentation/manual/en/manual.xml.in");
