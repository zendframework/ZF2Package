#!/usr/local/bin/php
<?php
ini_set('memory_limit', -1);
date_default_timezone_set('UTC');
require_once '/var/www/website/vendor/autoload.php';
$git = '/usr/local/bin/git';

$worker = new GearmanWorker();
$worker->addServer();
$worker->addFunction('process_composer', function (GearmanJob $job) use ($git) {
    $logfile = tempnam('/tmp', 'composer-worker');
    $log     = fopen($logfile, 'a');
    fwrite($log, "Received job\n");

    $workload   = $job->workload();
    fwrite($log, "Received workload: $workload\n");

    $workload   = json_decode($workload);
    $ref        = $workload->ref;
    $sha        = $workload->sha;
    $repository = $workload->repository;

    fwrite($log, json_encode(array(
        'ref'        => $ref,
        'sha'        => $sha,
        'repository' => $repository,
    )) . "\n");

    // Make sure we recognize the repository
    if (!preg_match('#^https?://github.com/zendframework/(zf2|ZendSkeletonApplication)$#i', $repository, $matches)) {
        fwrite($log, "Unrecognized repository\n");
        fclose($log);
        $job->sendComplete('Unrecognized repository; finished');
        return;
    }
    switch (strtolower($matches[1])) {
        case 'zf2':
            $package = 'zendframework/zendframework';
            break;
        case 'zendskeletonapplication':
            $package = 'zendframework/skeleton-application';
            break;
    }

    // Make sure we recognize the branch
    $branch = false;
    if (preg_match('#/(master|develop)$#', $ref, $matches)) {
        $branch = $matches[1];
    }
    if (!$branch) {
        fwrite($log, "Unrecognized branch\n");
        fclose($log);
        $job->sendComplete('Unrecognized branch; finished');
        return;
    }

    // Update packages.json
    fwrite($log, "Preparing to update packages.json\n");
    $date       = new DateTime();
    $packages   = file_get_contents('/var/www/packages.zendframework.com/public/packages.json');
    $packages   = json_decode($packages, true);
    $branchName = 'dev-' . $branch;
    $packages['packages'][$package][$branchName]['source']['reference'] = $sha;
    $packages['packages'][$package][$branchName]['source']['time'] = $date->format('Y-m-d H:i:s');
    $packages   = Zend\Json\Json::encode($packages);
    $packages   = Zend\Json\Json::prettyPrint($packages, array('indent' => '    '));
    file_put_contents('/var/www/packages.zendframework.com/public/packages.json', $packages);

    // Commit packages.json
    fwrite($log, "Adding changes\n");
    $output = '';
    $return = null;
    chdir('/var/www/packages.zendframework.com');
    exec($git . ' add public/packages.json', $output, $return);
    if (0 !== $return) {
        fwrite($log, "Failed to add changes\n");
        fclose($log);
        $job->sendFail();
        return;
    }

    fwrite($log, "Committing changes\n");
    $output = '';
    $return = null;
    exec($git . ' commit -m "Updated packages.json to $ref at $sha"', $output, $return);
    if (0 !== $return) {
        fwrite($log, "Failed to commit changes\n");
        fclose($log);
        $job->sendFail();
        return;
    }

    fwrite($log, "Pushing changes\n");
    $output = '';
    $return = null;
    exec($git . ' push origin production:production', $output, $return);
    if (0 !== $return) {
        fwrite($log, "Failed to push changes\n");
        fclose($log);
        $job->sendFail();
        return;
    }

    fwrite($log, "Job complete\n");
    fclose($log);
    $job->sendComplete('Finished updating packages.json on production');
});

while ($worker->work()) {
    if (GEARMAN_SUCCESS != $worker->returnCode()) {
        $log->err("Worker failed: " . $worker->error());
        echo "Worker failed: " . $worker->error() . "\n";
    }
}
