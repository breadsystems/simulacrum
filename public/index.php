<?php

require __DIR__ . '/../vendor/autoload.php';

use Gumlet\ImageResizeException;

use Simulacrum\ParseError;

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
