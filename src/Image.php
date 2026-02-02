<?php

namespace Simulacrum\Image;

use Gumlet\ImageResizeException;

use Simulacrum;
use Simulacrum\ParseError;

define('MAX_AGE', intval(getenv('MAX_AGE') ?: 10 * 365 * 24 * 3600));

function handle(array $req) {
  try {
    $chain = Simulacrum\parse_uri($req['uri']);
  } catch (ParseError $e) {
    header('HTTP/1.1 400 Bad Request');
    header('X-Error: ' . $e->getMessage());
    exit;
  }

  $staleAt = $req['if-modified-since'] ? strtotime($req['if-modified-since']) : 0;
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

  header('X-Content-Type-Options: nosniff');

  return $image->output($chain['type']);
}
