<?php

require __DIR__ . '/../../vendor/autoload.php';

use Simulacrum\Upload;

$keyFile = __DIR__ . '/../../api.key';
$hash    = is_readable($keyFile) ? file_get_contents($keyFile) : '';
if (!$hash) {
  header('HTTP/1.1 500 Internal Server Error');
  echo "No API key configured. All API calls will fail.\n";
  exit;
}

$key = array_change_key_case(getallheaders())['x-simulacrum-key'] ?? '';
if (!($key && password_verify($key, $hash))) {
  header('HTTP/1.1 401 Unauthorized');
  echo "Invalid or missing API key.\n";
  exit;
}

define('IMAGES_ROOT', getenv('IMAGES_ROOT'));

if (!is_writeable(IMAGES_ROOT) || !is_dir(IMAGES_ROOT)) {
  header('HTTP/1.1 500 Internal Server Error');
  echo sprintf("`%s` is not a writeable directory.\n", IMAGES_ROOT);
  exit;
}

$res = Upload\handle([
  'http_method' => $_SERVER['REQUEST_METHOD'],
  'image_data'  => file_get_contents('php://input'),
]);

if ($res['status'] === 200) {
  header('Content-Type: application/json');
} else {
  header([
    400 => 'HTTP/1.1 400 Bad Request',
    404 => 'HTTP/1.1 404 Not Found',
    500 => 'HTTP/1.1 500 Internal Server Error',
  ][$res['status'] ?? 500]);
}

echo json_encode($res['body']);
