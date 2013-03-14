<?php
/**
 * zend_download_report.php
 * Copyright 2006-2013 Zend Technologies.
 * 
 * Read Apache common log format.  Parse Zend Framework downloads, including
 * composer packages.
 *
 * Usage of this script:
 *
 * zend_download_report.php [ -v ] [ -f ] [ -s path ] directory ...
 *
 * -v enables verbose output.
 * -f forces logs to be re-parsed even if the aggregate data persisted file
 *    is present.  Also forces log for current date to be parsed.
 * -s specifies that a spreadsheet to be written to the given pathname.
 *    If the basename file is given as "DEFAULT", then the filename is
 *    "ZendFramework-DownloadReport-YYYYMMDD.xlsx", in the directory given in the path.
 *    E.g. "-s /tmp/DEFAULT" writes the spreadsheet at the path:
 *     "/tmp/ZendFramework-DownloadReport-YYYYMMDD.xlsx".
 *
 * The script searches the directories specified in the command-line
 * arguments for files called "access.log".  If such a file is found,
 * the script also looks for a file named "downloads.ser" in the same
 * subdirectory.
 *
 * If "downloads.ser" is present, the script reads that file and uses eval()
 * to un-persist, then merges the data into an array.
 *
 * If "downloads.ser" is not present, but "access.log" is, the script reads
 * the log file and counts Zend Framework downloads.  But the script does
 * not by default analyze a log file whose last modification date is the
 * same as the current date.
 *
 * The log file is also analyzed if the "-f" flag is given;
 * any "downloads.ser" file present in that subdirectory is overwritten.
 * This also causes the log file for the current day to be analyzed.
 *
 * Last update 2013-02-28 by Enrico Zimuel (enrico@zend.com)
 */
require_once "vendor/autoload.php";

$start = microtime(true);

class Zend_Apache_Log_DownloadCount
{

    /**
     * @var array
     */
    protected static $_monthNameToNumber = array(
        'Jan'       => 1,
        'January'   => 1,
        'Feb'       => 2,
        'February'  => 2,
        'Mar'       => 3,
        'March'     => 3,
        'Apr'       => 4,
        'April'     => 4,
        'May'       => 5,
        'Jun'       => 6,
        'June'      => 6,
        'Jul'       => 7,
        'July'      => 7,
        'Aug'       => 8,
        'August'    => 8,
        'Sep'       => 9,
        'September' => 9,
        'Oct'       => 10,
        'October'   => 10,
        'Nov'       => 11,
        'November'  => 11,
        'Dec'       => 12,
        'December'  => 12,
    );

    /**
     * @var array
     */
    protected static $_restrictedIPs = array(
        '10.220.223.46' => 'fw01',
        '10.220.223.47' => 'fw02',
        '127.0.0.1'     => 'localhost',
    );


    /**
     * @var int
     */
    protected $_urlNo = 1;

    /**
     * @var array
     */
    protected $_resources = array();

    /**
     * @var array
     */
    protected $_fullResourcesList = array();

    /**
     * @var array
     */
    protected $_logData = array();

    /**
     * @var array
     */
    protected $_format = array();

    /**
     * @var bool
     */
    protected $_fullData = true;

    /**
     * @var int
     */
    protected static $_bytesMin = 0;

    /**
     * @var array
     */
    protected static $_httpCodeOk = array(200, 206);

    /**
     * @param bool $fullData
     */
    public function __construct($fullData = true)
    {
        $this->_fullData = $fullData;
    }
    
    /**
     * @param Worksheet $worksheet
     * @return void
     */
    protected function writeSpreadsheetHeaders($worksheet, $sheet)
    {
        $worksheet->setActiveSheetIndex($sheet);
        $worksheet->getActiveSheet()
                  ->setCellValueByColumnAndRow(0, 1, 'Date');

        foreach ($this->_resources as $resource => $col) {
            $worksheet->getActiveSheet()
                  ->setCellValueByColumnAndRow($col, 1, $resource);

        }
    }

    /**
     * @param Worksheet $worksheet
     * @param int $row
     * @param boolean $writeRightTotal
     * @return void
     */
    protected function writeSpreadsheetTotals($worksheet, $sheet, $row, $writeRightTotal = true)
    {
        // Write lower left TOTAL label
        $worksheet->setActiveSheetIndex($sheet);
        $worksheet->getActiveSheet()
                  ->setCellValueByColumnAndRow(0, $row, 'TOTAL');

        if ($writeRightTotal) {
            // Write upper right TOTAL header label
            $col = count($this->_resources)+1;
            $worksheet->getActiveSheet()
                  ->setCellValueByColumnAndRow($col, 0, 'TOTAL');

            // Write end-of-row totals
            $col = count($this->_resources)+1;
            for ($i = 1; $i < $row; ++$i) {
                $cell1 = PHPExcel_Cell::stringFromColumnIndex(1) . $i;
                $cell2 = PHPExcel_Cell::stringFromColumnIndex($col - 1) . $i;
                $worksheet->getActiveSheet()
                          ->setCellValueByColumnAndRow($col, $i, "=SUM($cell1:$cell2)");
            }
        }

        // Write bottom-of-column totals
        foreach ($this->_resources as $resource => $col) {
            $cell1 = PHPExcel_Cell::stringFromColumnIndex($col) . '1';
            $cell2 = PHPExcel_Cell::stringFromColumnIndex($col) . (integer) ($row - 1);
            $worksheet->getActiveSheet()
                      ->setCellValueByColumnAndRow($col, $row, "=SUM($cell1:$cell2)");
        }

        if ($writeRightTotal) {
            // Calculate grand total
            $cell1 = PHPExcel_Cell::stringFromColumnIndex(1) . $row;
            $cell2 = PHPExcel_Cell::stringFromColumnIndex(count($this->_resources)) . $row;
            $col = count($this->_resources)+1;
            $worksheet->getActiveSheet()
                      ->setCellValueByColumnAndRow($col, $row, "=SUM($cell1:$cell2)");
        }
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $day
     * @return int
     */
    protected function excelDate($year, $month = null, $day = null)
    {
        if (($day !== null) && ($month !== null)) {
            return sprintf("%04d-%02d-%02d", $year, $month, $day);
        } elseif (($day === null) && ($month !== null)) {
            return sprintf("%04d-%02d", $year, $month);
        } else {
            return sprintf("%04d", $year);
        }
    }

    /**
     * @param string $workbookFilename
     * @param boolean $writeRightTotal
     * @return void
     */
    public function saveSpreadsheet($workbookFilename = 'downloadReport.xlsx', $writeRightTotal = true)
    {
        
        $workbook = new PHPExcel();
        $workbook->getProperties()->setCreator("Zend Technologies");
        $workbook->getProperties()->setLastModifiedBy("Zend Technologies");
        $workbook->getProperties()->setTitle("ZF download statistics");
        $workbook->getProperties()->setSubject("Statistics data");
        $workbook->getProperties()->setDescription("Zend Framework download statistics");
               
        // Downloads per Year
        $workbook->setActiveSheetIndex(0);
        $workbook->getActiveSheet()->setTitle('Downloads per Year');
        
        $wsMonth = new PHPExcel_Worksheet($workbook, 'Downloads per Month');
        $workbook->addSheet($wsMonth, 1);
        $wsDay = new PHPExcel_Worksheet($workbook, 'Downloads per Day');
        $workbook->addSheet($wsDay, 2);
        
        if ($this->_fullData) {
            $wsFullData = new PHPExcel_Worksheet($workbook, 'Full Data');
            $workbook->addSheet($wsFullData, 3);
        } 

        $dayRow = 1;
        $monthRow = 1;
        $totalRow = 1;
        $r = 1;

        // ksort($this->_resources);
        $resNames    = array();
        $resVersions = array();
        foreach ($this->_resources as $resKey => $value) {
            $patternIsFound = false;
            foreach ($this->_rexpr as $resName => $pattern) {
                if (($offset = strpos($resKey, $resName)) !== false) {
                    $resNames[]    = $resName;
                    $resVersions[] = substr($resKey, $offset + strlen($resName));
                    $patternIsFound = true;
                    break;
                }
            }
            if (!$patternIsFound) {
                $resNames[]    = $resKey;
                $resVersions[] = '0';
            }
        }
        uasort($resVersions, 'version_compare');               // Sort by version with storing original index in keys
        $resVersionsIDs = array_keys($resVersions);            // Get original IDs, sorted by version
        $resVersionSortIndexes = array_flip($resVersionsIDs);  // Store original IDs as keys and sorting
                                                               // index (0, 1, 2, ...) as values
        ksort($resVersionSortIndexes);                         // Restore original order (corresponding to $this->_resources
                                                               // and $resNames arrays) with calculated sorting index
        array_multisort($resNames, SORT_STRING, $resVersionSortIndexes, SORT_DESC, $this->_resources);

        foreach (array_keys($this->_resources) as $resource) {
            $this->_resources[$resource] = $r++;
        }

        $this->writeSpreadsheetHeaders($workbook, 0);
        $this->writeSpreadsheetHeaders($workbook, 1);
        $this->writeSpreadsheetHeaders($workbook, 2);
        if ($this->_fullData) {
            $this->writeSpreadsheetHeaders($workbook, 3);
        }

        ++$totalRow;
        ++$monthRow;
        ++$dayRow;

        $ZF2tot = 0;
        $ZF2month = array();
        
        ksort($this->_logData);
        foreach ($this->_logData as $year => $monthArray) {
            $yearTotal = array();

            ksort($monthArray);
            foreach ($monthArray as $month => $dayArray) {
                $monthTotal = array();

                ksort($dayArray);
                foreach ($dayArray as $day => $resourceArray) {
                    /**
                     * Write date in day and fulldata worksheets
                     */
                    $workbook->setActiveSheetIndex(2);
                    $workbook->getActiveSheet()
                             ->setCellValueByColumnAndRow(0, $dayRow, $this->excelDate($year, $month, $day));

                    if ($this->_fullData) {
                        $workbook->setActiveSheetIndex(3);
                        $workbook->getActiveSheet()
                             ->setCellValueByColumnAndRow(0, $dayRow, $this->excelDate($year, $month, $day));
                    }

                    /**
                     * Output totals per resource per day
                     */
                    foreach ($resourceArray as $resource => $ipArray) {
                        if (!isset($this->_resources[$resource])) {
                            continue;
                        }

                        $col = $this->_resources[$resource];

                        if (!is_array($ipArray)) {
                            $count = $ipArray;
                        } else {
                            $count = count($ipArray);
                        }
                        
                        $workbook->setActiveSheetIndex(2);
                        $workbook->getActiveSheet()
                                 ->setCellValueByColumnAndRow($col, $dayRow, $count);

                        if ($this->_fullData) {
                            if (!is_array($ipArray)) {
                                $sum = $ipArray;
                            } else {
                                $sum = array_sum($ipArray);
                            }
                            $workbook->setActiveSheetIndex(3);
                            $workbook->getActiveSheet()
                                     ->setCellValueByColumnAndRow($col, $dayRow, $sum);
                        }

                        if (!isset($monthTotal[$resource])) {
                            $monthTotal[$resource] = $count;
                        } else {
                            $monthTotal[$resource] += $count;
                        }
                        if (!isset($yearTotal[$resource])) {
                            $yearTotal[$resource] = $count;
                        } else {
                            $yearTotal[$resource] += $count;
                        }
                    }

                    ++$dayRow;
                }
                
                // Get the summary information for ZF2 of the last year and the last 2 months
                if ($year === (integer) date('Y')) {
                    
                   $tot = 0;
                   foreach ($monthTotal as $zf => $value) {
                        if (preg_match('/^ZF 2./', $zf) || ($zf === 'ZF2 from packagist.org')) {
                            $tot += $value;
                        }
                   }
                   
                   if (($month === (integer) date('m') ||
                        $month === (integer) date('m', strtotime('last month')))) {
                       $ZF2month[$month] = $tot;
                   }
                   $ZF2tot += $tot;
                }

                /**
                 * Write date in month worksheet
                 */
                $workbook->setActiveSheetIndex(1);
                $workbook->getActiveSheet()
                         ->setCellValueByColumnAndRow(0, $monthRow, $this->excelDate($year, $month));

                /**
                 * Write totals per resource per month
                 */
                foreach ($this->_resources as $resource => $col)
                {
                    if (!isset($monthTotal[$resource])) {
                        $monthTotal[$resource] = null;
                    }

                    $workbook->getActiveSheet()
                             ->setCellValueByColumnAndRow($col, $monthRow, $monthTotal[$resource]);
                }

                ++$monthRow;
            }

            /**
             * Write date in year worksheet
             */
            $workbook->setActiveSheetIndex(0);
            $workbook->getActiveSheet()
                     ->setCellValueByColumnAndRow(0, $totalRow, $this->excelDate($year));

            /**
             * Write totals per resource per year
             */
            foreach ($this->_resources as $resource => $col) {
                if (!isset($yearTotal[$resource])) {
                    $yearTotal[$resource] = null;
                }
                $workbook->getActiveSheet()
                     ->setCellValueByColumnAndRow($col, $totalRow, $yearTotal[$resource]);
            }

            ++$totalRow;
        }

        $this->writeSpreadsheetTotals($workbook, 0, $totalRow, $writeRightTotal);
        $this->writeSpreadsheetTotals($workbook, 1, $monthRow, $writeRightTotal);
        $this->writeSpreadsheetTotals($workbook, 2 , $dayRow, $writeRightTotal);
        if ($this->_fullData) {
            $this->writeSpreadsheetTotals($workbook, 3, $dayRow, $writeRightTotal);
        }

        $objWriter = new PHPExcel_Writer_Excel2007($workbook);
        $objWriter->save($workbookFilename);
        
        $month = (integer) date('m');
        $lastMonth = $month - 1;
	$year = date('Y');
        
        $output = sprintf("ZF 2.x downloads in %s %s (to date): %s\n", date('M'), $year, number_format($ZF2month[$month]));
        if ($month !== 1) {
            $output .= sprintf("ZF 2.x downloads in %s %s: %s\n", date('M', strtotime('last month')), $year, number_format($ZF2month[$lastMonth]));
        }
        $output .= sprintf("ZF 2.x downloads in %s (to date): %s\n", $year, number_format($ZF2tot));
        return $output;
    }

    /**
     * @var array
     */
    protected $_rexpr = array(
        'ZF'       => '^/releases/ZendFramework-(\d+.\d+)',
        //'Gdata'    => '^/releases/ZendG[Dd]ata-(\d+.\d+)',
        //'Infocard' => '^/releases/ZendInfo[Cc]ard-(\d+.\d+)',
        //'AMF'      => '^/releases/Zend[Aa][Mm][Ff]-(\d+.\d+)',
        'ZF2 components' => '^/composer/Zend_'
    );

    /**
     * @param array $log
     * @return string
     */
    private function getResource($log)
    {
        foreach ($this->_rexpr as $label => $pattern) {
            if (is_array($pattern)) {
                $method = $pattern[1];
                if ($log['method'] != $method) {
                    continue;
                }
                $pattern = $pattern[0];
            }
            if (preg_match("|$pattern|", $log['url'], $matches)) {
                array_shift($matches);
                foreach ($matches as $arg) {
                    if ($arg[0]!=='_') {
                        $label .= ' ';
                    }
                    $label .= $arg;
                }
                return $label;
            }
        }
        return null;
    }

    /**
     * @param array $log
     * @return void
     */
    public function processLogEntry($log)
    {
        if (is_numeric($log['bytes'])  &&  $log['bytes'] <= self::$_bytesMin) {
            return;
        }
        if (!in_array($log['code'], self::$_httpCodeOk)) {
            return;
        }
        $resource = $this->getResource($log);
        if ($resource) {
            $this->_resources[$resource] = true;
            if (!isset($this->_logData[ $log['year'] ][ $log['month'] ][ $log['day'] ][ $resource ][ $log['ip'] ])) {
                $this->_logData[ $log['year'] ][ $log['month'] ][ $log['day'] ][ $resource ][ $log['ip'] ] = 1;
            } else {
                $this->_logData[ $log['year'] ][ $log['month'] ][ $log['day'] ][ $resource ][ $log['ip'] ]++;
            }
        }
    }

    /**
     * @param resource $resource
     * @return void
     */
    public function processLogStream($resource)
    {
        while (!feof($resource)) {
            $buffer = fgets($resource);
            if ($buffer === false) {
                continue;
            }
            $buffer = substr($buffer, 0, -1); // remove \n
            /**
             * The following assumes Apache common log format
             * If you change to combined or other log format,
             * this code needs to be adapted.
             */
            $logData = explode(' ', $buffer, 10);
            if (count($logData) < 10) {
                continue;
            }

            list($log['ip'],
                $log['user'],
                $log['vhost'],
                $log['datetime'],
                $log['tz'],
                $log['method'],
                $log['url'],
                $log['http'],
                $log['code'],
                $log['bytes']) = $logData;
            $log['datetime'] = substr($log['datetime'], 1); // remove [
            $log['tz']       = substr($log['tz'], 0, -1);   // remove ]
            $log['method']   = substr($log['method'], 1);   // remove "
            $log['http']     = substr($log['http'], 0, -1); // remove "
            list($date,
                $log['hour'],
                $log['minute'],
                $log['second']) = explode(':', $log['datetime']);
            list($log['day'],
                $log['month'],
                $log['year']) = explode('/', $date);
            $log['month'] = self::$_monthNameToNumber[$log['month']];
            $log['day'] = (integer) $log['day'];
            $this->processLogEntry($log);
        }
    }

    /**
     * @param string $filename
     * @return void
     */
    public function processLogFile($filename)
    {
        $logFile = fopen($filename, 'r');
        if (!$logFile) {
            die($filename);
        }

        $this->processLogStream($logFile);

        fclose($logFile);
    }

    /**
     * @param string $filename
     * @return void
     */
    public function processLogArchive($filename)
    {
        $logFile = popen('bunzip2 -cdkq ' . escapeshellarg($filename), 'r');
        if (!$logFile) {
            die($filename);
        }

        $this->processLogStream($logFile);

        pclose($logFile);
    }


    /**
     * @param string $filename
     * @return void
     */
    public function saveData($filename)
    {
        $freeze = var_export($this->_logData, true);
        if (!($fp = fopen($filename, 'w'))) {
            die("cannot open $filename");
        }
        if (fwrite($fp, $freeze) == false) {
            die("cannot write to $filename");
        }
        fclose($fp);
    }

    /**
     * @param string $filename
     * @return void
     */
    public function loadData($filename)
    {
        if (!($fp = fopen($filename, 'r'))) {
            die("cannot open $filename");
        }
        if (($thaw = fread($fp, filesize($filename))) == false) {
            die("cannot read $filename");
        }
        fclose($fp);
        eval('$data = ' . $thaw . ';');
        foreach ($data as $year => $monthArray) {
            foreach ($monthArray as $month => $dayArray) {
                foreach ($dayArray as $day => $resourceArray) {
                    foreach ($resourceArray as $resource => $ipArray) {
                        $this->_resources[$resource] = 1;
                        foreach ($ipArray as $ip => $count) {
                            if (isset(self::$_restrictedIPs[$ip])) {
                                continue;
                            }

                            if (!isset($this->_logData[ $year ][ $month ][ $day ][ $resource ][ $ip ])) {
                                $this->_logData[ $year ][ $month ][ $day ][ $resource ][ $ip ] = $count;
                            } else {
                                $this->_logData[ $year ][ $month ][ $day ][ $resource ][ $ip ] += $count;
                            }
                        }
                    }
                }
            }
        }

        $this->_fullResourcesList = $this->_resources;
    }

    /**
     * Load the download data of packagist
     * 
     * @param  string $filename 
     * @return boolean
     */
    public function loadPackagist($filename) {
        if (file_exists($filename)) {
            $data = include $filename; 
        } else {
            return false;
        }
        $resource = 'ZF2 from packagist.org';
        $this->_resources[$resource] = 1;
        foreach ($data['days'] as $year => $monthArray) {
            foreach ($monthArray as $month => $dayArray) {
                foreach ($dayArray as $day => $downloads) {
                    $this->_logData[(integer) $year][(integer) $month][(integer) $day][$resource] = (integer) $downloads;
                }
            }
        }
        $this->_fullResourcesList = $this->_resources;
        return true;
    }
    
    /*
     * @param array|null $includeFilter   If parameter is set it contains resources list allowed to be included into report
     * @param array|null $excludeFilter   If $includeFilter is null and this parameter is not null it contains
     *                                    respurces list to exclude from the report
     * @return void
     */
    public function setResourcesFilter($includeFilter = null, $excludeFilter = null)
    {
        if ($includeFilter !== null) {
            $this->_resources = array();

            foreach ($this->_fullResourcesList as $resKey => $resValue) {
                if (in_array($resKey, $includeFilter)) {
                    $this->_resources[$resKey] = $resValue;
                }
            }
        } else if ($excludeFilter !== null) {
            $this->_resources = $this->_fullResourcesList;

            foreach ($excludeFilter as $resource) {
                unset($this->_resources[$resource]);
            }
        } else {
            $this->clearResourcesFilter();
        }
    }

    /*
     * @return void
     */
    public function clearResourcesFilter($includeFilter = null, $excludeFilter = null)
    {
        $this->_resources = $this->_fullResourcesList;
    }

    /**
     * @return void
     */
    public function clearData()
    {
        $this->_logData = array();
    }

}

/**
 * @param string $directory
 * @param bool $recursive
 * @return array
 */
function directoryToArray($directory, $recursive)
{
    $array_items = array();
    if ($handle = opendir($directory)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if (is_dir($directory. "/" . $file)) {
                    if($recursive) {
                        $array_items = array_merge($array_items, directoryToArray($directory. "/" . $file, $recursive));
                    }
                    $file = $directory . "/" . $file;
                    $array_items[] = preg_replace("/\/\//si", "/", $file);
                } else {
                    $file = $directory . "/" . $file;
                    $array_items[] = preg_replace("/\/\//si", "/", $file);
                }
            }
        }
        closedir($handle);
    }
    return $array_items;
}

define('APPLICATION_PATH', dirname(dirname(__FILE__)));
ini_set('display_errors', false);
$argv = $_SERVER['argv'];
array_shift($argv);

$force = false;
$verbose = false;
$spreadsheet = false;
while (!empty($argv)) {
    switch ($argv[0]) {
    case '-f':
    case '--force':
        $force = true;
        array_shift($argv);
        break;
    case '-s':
    case '--spreadsheet':
        $spreadsheet    = $argv[1];
        array_shift($argv);
        array_shift($argv);
        break;
    case '-v':
    case '--verbose':
        $verbose = true;
        array_shift($argv);
        break;
    default:
        break 2;
    }
}

if (empty($argv)) {
    array_push($argv, '.');
}

$files = array();
foreach ($argv as $pathname) {
    if (is_dir($pathname)) {
        $files = array_merge($files, directoryToArray($pathname, true));
    } else if (file_exists($pathname)) {
        $files[] = $pathname;
    } else {
        die("Invalid file '$pathname' given");
    }
}

sort($files);

# Pass 1: parse log files and save serialized data
$parser = new Zend_Apache_Log_DownloadCount();
foreach ($files as $pathname) {
    if (basename($pathname) == 'access.log' ||
        basename($pathname) == 'access.log.bz2') {
        $statfile = dirname($pathname) . '/' . 'downloads.ser';
        $logStat = stat($pathname);
        $lastMod = date('Ymd', $logStat[9]);
        $today = date('Ymd');

        if ($force || (!is_readable($statfile) && $lastMod < $today)) {
            if ($verbose) {
                echo "Reading $pathname... ";
            }
            $parser->clearData();
            switch (basename($pathname)) {
                case 'access.log':
                    $parser->processLogFile($pathname);
                    break;

                case 'access.log.bz2':
                    $parser->processLogArchive($pathname);
                    break;
            }

            if ($verbose) {
                echo "done.\n";
                echo "Saving  $statfile... ";
            }
            $parser->saveData($statfile);
            if ($verbose) {
                echo "done.\n";
            }
        }

        if (basename($pathname) == 'access.log' && $lastMod < $today) {
            // Archive 'access.log' file if it wasn't done at previous step
            exec('bzip2 ' . escapeshellarg($pathname));
        }
    } else if (basename($pathname) == 'error.log') {
        $logStat = stat($pathname);
        $lastMod = date('Ymd', $logStat[9]);
        $today = date('Ymd');

        if ($lastMod < $today) {
            exec('bzip2 ' . escapeshellarg($pathname));
        }
    }
}

$output = '';

# Pass 2: load serialized data and make report
$report = new Zend_Apache_Log_DownloadCount();
if ($spreadsheet) {
    foreach ($files as $pathname) {
        if (basename($pathname) == 'downloads.ser') {
            if (is_readable($pathname)) {
                if ($verbose) {
                    echo "Loading $pathname... ";
                }
                $report->loadData($pathname);
                if ($verbose) {
                    echo "done.\n";
                }
            }
        }
    }
    if (basename($spreadsheet) == 'DEFAULT') {
        $spreadsheet    = dirname($spreadsheet) . '/ZendFramework-DownloadReport-' . date('Ymd') . '.xlsx';
    }

    // Packagist data
    $packagist_file = 'data/packagist.ser';
    if ($verbose) {
       echo "Loading Packagist $packagist_file...";
    }
    $report->loadPackagist($packagist_file);
    if ($verbose) {
        echo "done.\n";
    }
    
    if ($verbose) {
        echo "Saving spreadsheet $spreadsheet... ";
    }
    $report->setResourcesFilter(null);
    $output = $report->saveSpreadsheet($spreadsheet);

    if ($verbose) {
        echo "done.\n";
    }
}

if ($verbose) {
    printf("Execution time: %.2f\n", microtime(true) - $start);
    printf("Memory usage: %d\n", memory_get_usage(true));
}

echo $output;
