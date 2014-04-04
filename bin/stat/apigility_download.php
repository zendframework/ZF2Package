<?php
/**
 * Get the Apigility download statistics
 * 
 * @author Enrico Zimuel (enrico@zend.com)
 */
require_once "vendor/autoload.php";

/**
 * Class for Github release download of Apigility
 */
Class Apigility_Release_Github
{
	CONST GITHUB_RELEASE = 'https://api.github.com/repos/zfcampus/zf-apigility-skeleton/releases';

	public function __construct()
	{
		// Set the User-Agent, required from github
		ini_set("user_agent", "zfampus");
	}
	
	public function getReleases()
	{
		$result = @file_get_contents(self::GITHUB_RELEASE);
		if (empty($result)) {
			return false;
		}
		$stat          = array();
		$stat['total'] = 0;
		$result        = json_decode($result, true);
		foreach ($result as $release) {
			$totAsset = 0;
			if (isset($release['assets'])) {
				foreach ($release['assets'] as $asset) {
					$totAsset += $asset['download_count'];
				}
			}
			$stat[$release['tag_name']] = $totAsset;
			$stat['total'] += $totAsset;
		}
		return $stat;
	}
}

/**
 * Class for Packagist download of Apigility
 */
Class Apigility_Download_Packagist 
{
    CONST APIGILITY_SKELETON_URL = 'https://packagist.org/packages/zfcampus/%s.json';
       
	public function getDownload($project)
    {
        $result = @file_get_contents(sprintf(self::APIGILITY_SKELETON_URL, $project));
        if (empty($result)) {
        	return false;
        }
        $result = json_decode($result, true);
        return array(
        	'total' => $result['package']['downloads']['total'],
            'month' => $result['package']['downloads']['monthly'],
            'day'   => $result['package']['downloads']['daily'] 
        );
    }
}

/**
 * Format a data for Excel
 * 
 * @param integer $year
 * @param integer $month
 * @param integer $day
 * @return string
 */
function excelDate($year, $month = null, $day = null)
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
 * Get column by value and row
 * 
 * @param PHPExcel $workbook
 * @param integer $row
 * @param string $value
 * @return integer|boolean
 */
function getColumnByValueAndRow($workbook, $row, $value)
{
	$lastColumn = $workbook->getActiveSheet()->getHighestColumn();
	$lastColumn = PHPExcel_Cell::columnIndexFromString($lastColumn);
	for ($column = 0; $column <= $lastColumn; $column++) {
		$cell = $workbook->getActiveSheet()->getCell(PHPExcel_Cell::stringFromColumnIndex($column).$row);
		if ($cell->getValue() === $value) {
			return $column;
		}
	}
	return false;
}


/**
 * MAIN PROGRAM
 */

$argv = $_SERVER['argv'];
array_shift($argv);

if (!isset($argv[0]) || !is_dir($argv[0])) {
    printf("ERROR: You missed the path to Apache log parameter\n");
    printf('Usage: php ' . basename(__FILE__) . " /path/to/apache/log\n");
    exit(1);  
}

// Configuration
$filestat      = __DIR__ . '/data/apigility.ser';
$excelFile     = __DIR__ . '/data/apigility_download.xlsx';
$apacheLogPath = $argv[0];

$projects = array(
		'zf-apigility-skeleton', 'zf-apigility', 'zf-apigility-admin', 'zf-apigility-documentation',
		'zf-content-validation', 'zf-content-negotiation', 'zf-oauth2', 'zf-api-problem', 'zf-apigility-documentation',
		'zf-rest', 'zf-rpc', 'zf-hal', 'zf-apigility-provider', 'zf-mvc-auth', 'zf-versioning', 'zf-configuration',
		'zf-development-mode', 'zf-apigility-documentation-swagger'
);

$data = array();

if (file_exists($filestat)) {
    $data = include $filestat;
}

// Update stat from Packagist
$packagist = new Apigility_Download_Packagist();
$year  = (string) date("Y");
$day   = (string) date("d");
$month = (string) date("m");

$tot = 0;
foreach ($projects as $prj) {
	$downloads = $packagist->getDownload($prj);
	$data['packagist'][$prj]['days'][$year][$month][$day] = $downloads['day'];
	$data['packagist'][$prj]['months'][$year][$month] = $downloads['month'];
	$data['packagist'][$prj]['total'] = $downloads['total'];
	$tot += $downloads['total'];
}

$data['packagist']['total'] = $tot;
$time = time();

// Update stat from Github
$github = new Apigility_Release_Github();
$data['github'][$time] = $github->getReleases();

// Update data for packages.zendframework.com
$logFile = $apacheLogPath . "/$year/$month/$day/access.log";
if (file_exists($logFile)) {
	$count = exec("grep -c \"GET /composer/zfcampus-zf-apigility\" $logFile");
	if (!is_int($count)) {
		$count = 0;
	}
}
if (!isset($data['packages'])) {
	$data['packages'] = array();
	$data['packages']['total'] = 0;
}
if (isset($count) && $count > 0) {
	$data['packages']['total'] += $count;
}

// Update filestat
file_put_contents($filestat, '<?php return '. var_export($data, true) . ';');

if (!file_exists($excelFile)) {
	// Generate Excel file
	$workbook = new PHPExcel();
	$workbook->getProperties()->setCreator("Zend Technologies");
	$workbook->getProperties()->setLastModifiedBy("Zend Technologies");
	$workbook->getProperties()->setTitle("Apigility download statistics");
	$workbook->getProperties()->setSubject("Statistics data");
	$workbook->getProperties()->setDescription("Apigility download statistics");
	
	// Packagist
	$workbook->setActiveSheetIndex(0);
	$workbook->getActiveSheet()->setTitle('Packagist');
	
	$workbook->getActiveSheet()->setCellValueByColumnAndRow(1, 1, 'total');
	
	$column = 2;
	foreach ($projects as $prj) {
		$workbook->getActiveSheet()->setCellValueByColumnAndRow($column, 1, $prj);
		$column++;
	}
	
	// Github
	$wsGithub = new PHPExcel_Worksheet($workbook, 'Github');
	$workbook->addSheet($wsGithub, 1);
	$workbook->setActiveSheetIndex(1);
	
	$column = 1;
	$releases = array_keys($data['github'][$time]);
	foreach ($releases as $release) {
		$workbook->getActiveSheet()->setCellValueByColumnAndRow($column, 1, $release);
		$column++;
	}
	
	// packages.zendframework.com
	$wsPackages = new PHPExcel_Worksheet($workbook, 'packages.zendframework.com');
	$workbook->addSheet($wsPackages, 2);
	$workbook->setActiveSheetIndex(2);
	$workbook->getActiveSheet()->setCellValueByColumnAndRow(1, 1, 'zfcampus-zf-apigility*');
	
	
} else {
	$workbook = PHPExcel_IOFactory::load($excelFile);
}

// Packagist
$workbook->setActiveSheetIndexByName('Packagist');
$numRows = $workbook->getActiveSheet()->getHighestRow() + 1;
$workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $numRows, excelDate($year, $month, $day));

foreach ($data['packagist'] as $prj => $value) {
	$col = getColumnByValueAndRow($workbook, 1, $prj);
	if (false === $col) {
		$totColumn = $workbook->getActiveSheet()->getHighestColumn() + 1;
		$workbook->getActiveSheet()->setCellValueByColumnAndRow($totColumn, 1, $prj);
		$col = $totColumn;
	}
	if (strtolower($prj) === 'total') {
		$workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $numRows, $value);
	} else {
		$workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $numRows, $value['total']);
	}
}

// Github
$workbook->setActiveSheetIndexByName('Github');
$numRows = $workbook->getActiveSheet()->getHighestRow() + 1;
$workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $numRows, excelDate($year, $month, $day));

foreach ($data['github'][$time] as $release => $value) {
	$col = getColumnByValueAndRow($workbook, 1, $release);
	if (false === $col) {
		$totColumn = $workbook->getActiveSheet()->getHighestColumn() + 1;
		$workbook->getActiveSheet()->setCellValueByColumnAndRow($totColumn, 1, $release);
		$col = $totColumn;
	}
	$workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $numRows, $value);
}

// packages.zendframework.com
$workbook->setActiveSheetIndexByName('packages.zendframework.com');
$numRows = $workbook->getActiveSheet()->getHighestRow() + 1;
$workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $numRows, excelDate($year, $month, $day));
$workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $numRows, $data['packages']['total']);

$objWriter = new PHPExcel_Writer_Excel2007($workbook);
$objWriter->save($excelFile);

// estimate the total download for Packagist
$totPackagist = $data['packagist']['zf-apigility-skeleton']['total'] + 
				$data['packagist']['zf-apigility']['total'] +
				$data['packagist']['zf-apigility-admin']['total'];

printf("Apigility download statistics (total)\n\n");
printf("From github releases            : %d\n", $data['github'][$time]['total']);
printf("From packagist.org              : %d\n", $totPackagist);
printf("From packages.zendframework.com : %d\n", $data['packages']['total']);
printf("TOTAL                           : %d\n", $data['github'][$time]['total'] + $totPackagist + $data['packages']['total']);
