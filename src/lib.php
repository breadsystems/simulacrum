<?php

namespace Simulacrum;

use Gumlet\ImageResize;

use Simulacrum\Ops;

define('TOKEN_MAP', [
  'c'                    => Ops\crop::class,
  'crop'                 => Ops\crop::class,
  's'                    => Ops\scale::class,
  'scale'                => Ops\scale::class,
  'w'                    => Ops\resize_to_width::class,
  'resize_to_width'      => Ops\resize_to_width::class,
  'h'                    => Ops\resize_to_height::class,
  'resize_to_height'     => Ops\resize_to_height::class,
  'r'                    => Ops\resize::class,
  'long'                 => Ops\resize_to_long_side::class,
  'resize_to_long_side'  => Ops\resize_to_long_side::class,
  'short'                => Ops\resize_to_short_side::class,
  'resize_to_short_side' => Ops\resize_to_short_side::class,
  'fit'                  => Ops\resize_to_best_fit::class,
  'resize_to_best_fit'   => Ops\resize_to_best_fit::class,
]);

define('PHP_IMAGETYPE_MAP', [
  'jpg'  => IMAGETYPE_JPEG,
  'jpeg' => IMAGETYPE_JPEG,
  'png'  => IMAGETYPE_PNG,
  'gif'  => IMAGETYPE_GIF,
]);

/**
 * Takes a URI returns an array representing an abstract chain of operations.
 * @example
 * ```php
 * Simulacrum\parse_uri("/img/s,50,c,100,100/cat.jpg");
 * // -> [
 * //   'directory' => 'img',
 * //   'ops'       => [[
 * //     'op'      => 'scale',
 * //     'params'  => [50],
 * //   ], [
 * /      'op'      => 'crop',
 * //     'params'  => [100, 100],
 * //   ]],
 * //   'filename'  => 'cat.jpg'
 * // ]
 * ```
 * @param string $uri any valid URI string
 * @param array $opts (option) array of options. No options currently supported;
 * to be expanded later to support custom operations.
 * @return array
 */
function parse_uri(string $uri, array $opts = []) : array {
  if (empty($uri)) return [];

  $segments = array_values(array_filter(explode('/', $uri)));

  // we need at least a directory and a filename
  if (count($segments) < 2) return [];

  // TODO proper API routing

  $ops = array_reduce(array_slice($segments, 1, -1), parse_ops::class, []);

  $dir = array_reduce(array_filter($ops, 'is_string'), function($path, $subdir) {
    return "$path/$subdir";
  }, $segments[0]);

  $ops = array_filter($ops, 'is_array');

  $filename = basename($uri);
  $dotPos   = strrpos($filename, '.');
  $stub     = substr($filename, 0, $dotPos);
  $ext      = substr($filename, $dotPos + 1);

  return [
    'directory' => $dir,
    'ops'       => $ops,
    'filename'  => $filename,
    'stub'      => $stub,
    'extension' => $ext,
    'type'      => PHP_IMAGETYPE_MAP[$ext] ?? IMAGETYPE_JPEG,
  ];
}


/**
 * @internal
 */
function parse_ops(array $ops, string $opStr) {
  // tokenize ops
  $tokens = preg_split('~[:,]~', $opStr);

  // walk tokens
  while ($token = array_shift($tokens)) {
    $func = TOKEN_MAP[$token] ?? null;
    if ($func) {
      // we get a tuple of the current op and the remaining tokens
      [$op, $tokens] = $func($tokens, $opStr);
    } else {
      throw new ParseError('Invalid operation: ' . $token);
    }

    $ops[] = $op;
  }

  return $ops;
}


/**
 * Execute the image manipulation operations described by $chain using the
 * Gumlet\ImageResize lib
 */
function execute(array $chain) {
  $path = sprintf(
    '%s/%s/%s',
    getenv('IMAGES_ROOT'),
    $chain['directory'] ?? '',
    $chain['filename'] ?? ''
  );

  if (!file_exists($path)) {
    $alternates = ['jpg', 'jpeg', 'png', 'gif'];
    do {
      $ext = array_shift($alternates);

      // we already know at least one extension doesn't work...
      if ($ext === $chain['extension']) continue;

      $path = sprintf(
        '%s/%s/%s.%s',
        getenv('IMAGES_ROOT'),
        $chain['directory'] ?? '',
        $chain['stub'] ?? '',
        $ext
      );
    } while (!file_exists($path) && $alternates);
  }

  $image = new ImageResize($path);

  return array_reduce($chain['ops'], function($image, $op) {
    $method = [$image, $op['op']];
    return ImageResize::createFromString((string) $method(...$op['params']));
  }, $image);
}