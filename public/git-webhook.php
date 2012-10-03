<?php
$logfile = tempnam('/tmp', 'composer');
if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
    header('HTTP/1.0 405 Method Not Allowed');
    header('Status: 405 Method Not Allowed');
    file_put_contents($logfile, "Invalid request method\n");
    exit();
}

if (!isset($_POST['payload'])) {
    header('HTTP/1.0 424 Failed Dependency');
    header('Status: 424 Failed Dependency');
    file_put_contents($logfile, "Missing payload\n");
    exit();
}

$payload = $_POST['payload'];
$payload = json_decode($payload);
if (!is_object($payload)) {
    header('HTTP/1.0 422 Unprocessable Entity');
    header('Status: 422 Unprocessable Entity');
    file_put_contents($logfile, "Invalid payload\n");
    exit();
}

if (!isset($payload->ref) 
    || !isset($payload->after)
    || !isset($payload->repository)
    || !isset($payload->repository->url)
) {
    header('HTTP/1.0 422 Unprocessable Entity');
    header('Status: 422 Unprocessable Entity');
    file_put_contents($logfile, "Payload missing artifacts\n");
    exit();
}

file_put_contents($logfile, json_encode(array(
    'ref'        => $payload->ref,
    'sha'        => $payload->after,
    'repository' => $payload->repository->url
));
$gearman = new GearmanClient();
$gearman->addServer();
$job = $gearman->doBackground('process_composer', json_encode(array(
    'ref'        => $payload->ref,
    'sha'        => $payload->after,
    'repository' => $payload->repository->url
)));

if ($gearman->returnCode() != GEARMAN_SUCCESS) {
    header('HTTP/1.0 500 Internal Server Error');
    header('Status: 500 Internal Server Error');
    exit();
}

header('HTTP/1.0 201 Created');
header('Status: 201 Created');
