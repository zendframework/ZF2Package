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
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    exit(0);
}

// Check for a payload
$json = file_get_contents('php://input');
if (empty($json)) {
    header('HTTP/1.1 400 Bad Request');
    exit(0);
}

// Check if we can deserialize the payload
$data = json_decode($json);
if (json_last_error() !== JSON_ERROR_NONE) {
    header('HTTP/1.1 415 Unsupported Media Type');
    $message = '';
    switch (json_last_error()) {
        case JSON_ERROR_DEPTH:
            $message = 'Maximum depth has been exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $message = 'Invalid or malformed JSON';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $message = 'Control character error, possibly incorrectly encoded';
            break;
        case JSON_ERROR_SYNTAX:
            $message = 'Syntax error';
            break;
        case JSON_ERROR_UTF8:
            $message = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        default:
            $message = 'Unknown error decoding JSON';
            break;
    }

    echo json_encode(array('error' => $message));
    exit(0);
}

// Check if we have a recognized payload type
if (! isset($data->after)
    && ! isset($data->release)
    && ! isset($data->zen)
) {
    header('HTTP/1.1 422 Unprocessable Entity');
    echo json_encode(array('error' => 'unexpected event'));
    exit(0);
}

switch (true) {
    // Ping
    case (isset($data->zen)):
        echo json_encode(array('ack' => $data->zen));
        break;

    // Push
    case (isset($data->after)):
        $version = $data->after;
        break;

    // Release
    case isset($data->release):
        if (! isset($data->release->tag_name)) {
            header('HTTP/1.1 422 Unprocessable Entity');
            echo json_encode(array('error' => 'missing tag name in release object'));
            exit(0);
        }

        $version = $data->release->tag_name;
        break;

    default:
        echo json_encode(array('error' => 'unexpected event'));
        header('HTTP/1.1 422 Unprocessable Entity');
        exit(0);
}

$gearman = new GearmanClient();
$gearman->addServer();
$gearman->doBackground('zfdeploy', $version);
if ($gearman->returnCode() !== GEARMAN_SUCCESS) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('error' => $gearman->error()));
    exit(0);
}

header('HTTP/1.1 204 No Content');
