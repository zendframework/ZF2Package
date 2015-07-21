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

if (! isset($_GET['secret'])
    || $_GET['secret'] !== get_cfg_var('zfdeploy.secret')
) {
    header('HTTP/1.1 400 Bad Request');
    exit(0);
}

// Verify request method
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    exit(0);
}

// Check for event
if (! isset($_SERVER['HTTP_X_GITHUB_EVENT'])) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    printf('%s', json_encode(array('error' => 'missing event')));
    exit(0);
}
$event = $_SERVER['HTTP_X_GITHUB_EVENT'];
if (! in_array($event, array('create', 'ping', 'push', 'release'))) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    printf('%s', json_encode(array('error' => 'invalid event')));
    exit(0);
}

// Check for a payload
$json = file_get_contents('php://input');
if (empty($json)) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    printf('%s', json_encode(array('error' => 'missing payload')));
    exit(0);
}

// Check if we can deserialize the payload
$data = json_decode($json);
if (json_last_error() !== JSON_ERROR_NONE) {
    header('HTTP/1.1 415 Unsupported Media Type');
    header('Content-Type: application/json');
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
switch ($event) {
    // Ping
    case 'ping':
        echo json_encode(array('ack' => $data->zen));
        break;

    // Push
    case 'push':
        if ($data->ref !== 'refs/heads/master') {
            // Not against master; nothing to do
            header('HTTP/1.1 204 No Content');
            exit(0);
        }

        $version = substr($data->after, 0, 8);
        break;

    // Create (tag)
    case 'create':
    case 'tag':
        if ($data->ref_type !== 'tag') {
            // Not a tag; nothing to do
            header('HTTP/1.1 204 No Content');
            exit(0);
        }
        $version = $data->ref;
        break;

    // Release
    case 'release':
        if (! isset($data->release->tag_name)) {
            header('HTTP/1.1 422 Unprocessable Entity');
            echo json_encode(array('error' => 'missing tag name in release object'));
            exit(0);
        }

        $version = $data->release->tag_name;
        break;

    default:
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'unexpected event'));
        exit(0);
}

$queue = new ZendJobQueue();
$id    = $queue->createHttpJob(
    'http://jobs.zendframework.com/zf-deploy/job.php',
    array('version' => $version),
    array(
        'name'           => 'Rebuild zfdeploy.phar',
        'scheduled_time' => date('Y-m-d H:i:s', time() + 10),
    )
);
if (! $id) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'Failed to queue job'));
    exit(0);
}

header('HTTP/1.1 204 No Content');
