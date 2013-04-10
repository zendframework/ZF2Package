<?php
function apply_replacements($source, $replacements) 
{
    foreach ($replacements as $var => $value) {
        $source = str_replace($var, $value, $source);
    }
    return $source;
}

function composer_name_to_package_name($composer_name) 
{
    $composer_name = ucwords(str_replace('-', ' ', $composer_name));
    return str_replace(
        array(' ', 'manager', 'Zendservice', 'Docbook', 'Inputfilter', 'Progressbar', 'Agilezen', 'Gogrid', 'Livedocx', 'Strikeiron', 'Windowsazure', 'Timesync', 'Xmlrpc'),
        array('_', 'Manager', 'ZendService', 'DocBook', 'InputFilter', 'ProgressBar', 'AgileZen', 'GoGrid', 'LiveDocx', 'StrikeIron', 'WindowsAzure', 'TimeSync', 'XmlRpc'),
        $composer_name
    );
}

function get_composer($source)
{
    $found = shell_exec(sprintf("find %s -name 'composer.json'", $source));
    $files = explode("\n", $found);
    $file  = array_shift($files);
    $file  = trim($file);
    return $file;
}

