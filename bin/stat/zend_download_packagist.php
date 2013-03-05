#!/usr/local/bin/php
<?php
/**
 * Get the ZF download statistics from packagist.org
 * 
 * @author Enrico Zimuel (enrico@zend.com)
 */

// set the same timezone of packagist.org
date_default_timezone_set('UTC');

Class Zend_Download_Packagist {
    CONST ZF2_URL = 'https://packagist.org/packages/zendframework/zendframework.json';
    
    /**
     * Get the zendframework downloads from packagist.org
     * 
     * @return array|boolean 
     */
    public function getZfDownloads()
    {
        $result = @file_get_contents(self::ZF2_URL);
        if (!empty($result)) {
            $result = json_decode($result, true);
            return array(
                'total' => $result['package']['downloads']['total'],
                'month' => $result['package']['downloads']['monthly'],
                'day'   => $result['package']['downloads']['daily'] 
            );
        }
        return false;
    }
}

$filestat = __DIR__ . '/data/packagist.ser';

$data = array();

if (file_exists($filestat)) {
    $data = include $filestat;
}

$packagist = new Zend_Download_Packagist();
$downloads = $packagist->getZfDownloads();

if ($downloads === false) {
    die();
}

$year  = (string) date("Y");
$day   = (string) date("d");
$month = (string) date("m");

$data['days'][$year][$month][$day] = $downloads['day'];
$data['months'][$year][$month] = $downloads['month'];
$data['total'] = $downloads['total'];

$update = date("Y-m-d H:i:s");
file_put_contents($filestat, "<?php // Last update $update\nreturn ". var_export($data, true) . ';');
