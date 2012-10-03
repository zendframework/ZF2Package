#!/usr/bin/env php
<?php
ini_set('memory_limit', -1);
date_default_timezone_set('America/Los_Angeles');

$worker = new GearmanWorker();
$worker->addServer();
$worker->addFunction('process_composer', function (GearmanJob $job) {
    $workload   = $job->workload();
    $workload   = json_decode($workload);
    $ref        = $workload->ref;
    $sha        = $workload->sha;
    $repository = $workload->repository;

    // Make sure we recognize the repository
    if (!preg_match('#^https?://github.com/zendframework/zf2$#', $repository)) {
        $job->sendComplete('Unrecognized repository; finished');
        return;
    }

    // Make sure we recognize the branch
    $branch = false;
    if (preg_match('#/(master|develop)$#', $ref, $matches)) {
        $branch = $matches[1];
    }
    if (!$branch) {
        $job->sendComplete('Unrecognized branch; finished');
        return;
    }

    // Update packages.json
    $packages   = file_get_contents('/var/www/packages.zendframework.com/public/packages.json');
    $packages   = json_decode($packages);
    $branchName = 'dev-' . $branch;
    $packages->{'zendframework/zendframework'}->{$branchName}->source->reference = $sha;
    file_put_contents('/var/www/packages.zendframework.com/public/packages.json', json_encode($packages));

    // Commit packages.json
    $output = '';
    $return = null;
    chdir('/var/www/packages.zendframework.com');
    exec('git add public/packages.json', $output, $return);
    if (0 !== $return) {
        $job->sendFail();
        return;
    }

    $output = '';
    $return = null;
    exec('git commit -m "Updated packages.json to $ref at $sha"', $output, $return);
    if (0 !== $return) {
        $job->sendFail();
        return;
    }

    $output = '';
    $return = null;
    exec('git push origin production:production', $output, $return);
    if (0 !== $return) {
        $job->sendFail();
        return;
    }

    $job->sendComplete('Finished updating packages.json on production');
});

while ($worker->work()) {
    if (GEARMAN_SUCCESS != $worker->returnCode()) {
        $log->err("Worker failed: " . $worker->error());
        echo "Worker failed: " . $worker->error() . "\n";
    }
}
