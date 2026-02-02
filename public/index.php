<?php

require __DIR__ . '/../vendor/autoload.php';

use Simulacrum\Api;
use Simulacrum\Image;

$path = $_SERVER['PATH_INFO'] ?? '';
if (substr($path, 0, 4) === '/api') {
  $res = Api\handle([
    'http_method' => $_SERVER['REQUEST_METHOD'],
    'path'        => $_SERVER['PATH_INFO'],
    'directory'   => $_SERVER['PHP_AUTH_USER'] ?? '',
    'key'         => trim($_SERVER['PHP_AUTH_PW'] ?? ''),
    'image_data'  => file_get_contents('php://input'),
  ]);

  header('Content-Type: application/json');
  if ($res['status'] !== 200) {
    header([
      400 => 'HTTP/1.1 400 Bad Request',
      401 => 'HTTP/1.1 401 Unauthorized',
      404 => 'HTTP/1.1 404 Not Found',
      405 => 'HTTP/1.1 405 Method Not Allowed',
      500 => 'HTTP/1.1 500 Internal Server Error',
    ][$res['status'] ?? 500]);
  }

  echo json_encode($res['body']);

  exit;
}

echo Image\handle([
  'uri'               => $_SERVER['REQUEST_URI'],
  'if-modified-since' => $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? 0,
]);
