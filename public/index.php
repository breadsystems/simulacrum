<?php

require __DIR__ . '/../vendor/autoload.php';

use Gumlet\ImageResizeException;

use Simulacrum\Api;
use Simulacrum\ParseError;

$path = $_SERVER['PATH_INFO'] ?? '';
if (substr($path, 0, 4) === '/api') {
  $res = Api\handle([
    'http_method' => $_SERVER['REQUEST_METHOD'],
    'path'        => $_SERVER['PATH_INFO'],
    'directory'   => $_SERVER['PHP_AUTH_USER'] ?? '',
    'key'         => trim($_SERVER['PHP_AUTH_PW'] ?? ''),
    'image_data'  => file_get_contents('php://input'),
  ]);

  if ($res['status'] === 200) {
    header('Content-Type: application/json');
  } else {
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

// let the frontend handle favicons
if ($_SERVER['REQUEST_URI'] === '/favicon.ico') {
  header('HTTP/1.1 404 Not Found');
  exit;
}

define('MAX_AGE', intval(getenv('MAX_AGE') ?: 10 * 365 * 24 * 3600));

try {
  $chain = Simulacrum\parse_uri($_SERVER['REQUEST_URI']);
} catch (ParseError $e) {
  header('HTTP/1.1 400 Bad Request');
  header('X-Error: ' . $e->getMessage());
  exit;
}

$since = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? 0;
$staleAt = $since ? strtotime($since) : 0;
$lastModified = $chain['stat']['mtime'] ?? PHP_INT_MAX;
if ($staleAt > $lastModified) {
  header('HTTP/1.1 304 Not Modified');
  exit;
}

try {
  $image = Simulacrum\execute($chain);
  header('Last-Modified: ' . date('D, d M Y h:m:s \G\M\T', $lastModified));
  header('Cache-Control: public, max-age=' . MAX_AGE . ', immutable');
} catch (ImageResizeException $e) {
  header('HTTP/1.1 404 Not Found');
  header('X-Error: ' . $e->getMessage());
  exit;
}

echo $image->output($chain['type']);
