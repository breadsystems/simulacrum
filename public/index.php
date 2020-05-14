<?php

require __DIR__ . '/../vendor/autoload.php';

use Gumlet\ImageResizeException;

use Simulacrum\ParseError;

// TODO API support:
// * POST /path/to/image.ext
// * DELETE /path/to/image.ext

// let the frontend handle favicons
if ($_SERVER['REQUEST_URI'] === '/favicon.ico') {
  header('HTTP/1.1 404 Not Found');
  exit;
}

try {
  $chain = Simulacrum\parse_uri($_SERVER['REQUEST_URI']);
} catch (ParseError $e) {
  header('HTTP/1.1 400 Bad Request');
  header('X-Error: ' . $e->getMessage());
  exit;
}

try {
  $image = Simulacrum\execute($chain);
} catch (ImageResizeException $e) {
  header('HTTP/1.1 404 Not Found');
  header('X-Error: ' . $e->getMessage());
  exit;
}

echo $image->output();