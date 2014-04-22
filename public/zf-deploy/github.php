<?php
/**
 * GitHub webhook for accepting push/release/ping events
 *
 * Queues events to Gearman's "zfdeploy" queue, using the sha1 or tag name as 
 * the version.
 *
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

// Verify request method
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    header('405 Method Not Allowed');
    header('Allow: POST');
    exit(0);
}

// Check for a payload
$json = file_get_contents('php://input');
if (empty($data)) {
    header('400 Bad Request');
    exit(0);
}

// Check if we can deserialize the payload
$data = json_decode($json);
if (json_last_error() !== JSON_ERROR_NONE) {
    header('415 Unsupported Media Type');
    exit(0);
}

// Check if we have an event, and if it's of the correct type
if (! isset($data->type)
    || ! in_array($data->type, array('ping', 'push', 'release'))
) {
    header('422 Unprocessable Entity');
    exit(0);
}

// Check if we have a payload
if (! isset($data->payload)) {
    header('422 Unprocessable Entity');
    exit(0);
}

$payload = $data->payload;

switch ($data->type) {
    case 'ping':
        if (! isset($payload->zen)) {
            header('422 Unprocessable Entity');
            exit(0);
        }
        echo json_encode(array('ack' => $payload->zen));
        break;

    case 'push':
        if (! isset($payload->head)) {
            header('422 Unprocessable Entity');
            exit(0);
        }

        $version = $payload->head;
        break;

    case 'release':
        if (! isset($payload->release) || ! isset($payload->release->tag_name)) {
            header('422 Unprocessable Entity');
            exit(0);
        }

        $version = $payload->release->tag_name;
        break;

    default:
        header('422 Unprocessable Entity');
        exit(0);
}

$gearman = new GearmanClient();
$gearman->addServer();
$gearman->doBackground('zfdeploy', $version);
if ($gearman->returnCode() !== GEARMAN_SUCCESS) {
    header('500 Internal Server Error');
    echo json_encode(array('error' => $gearman->error()));
    exit(0);
}

header('204 No Content');
