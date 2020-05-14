<?php

namespace Simulacrum;

use Gumlet\ImageResize;

use Simulacrum\Build;

define('TOKEN_MAP', [
  'c'                    => Build\crop::class,
  'crop'                 => Build\crop::class,
  's'                    => Build\scale::class,
  'scale'                => Build\scale::class,
  'w'                    => Build\resize_to_width::class,
  'resize_to_width'      => Build\resize_to_width::class,
  'h'                    => Build\resize_to_height::class,
  'resize_to_height'     => Build\resize_to_height::class,
  'r'                    => Build\resize::class,
  'long'                 => Build\resize_to_long_side::class,
  'resize_to_long_side'  => Build\resize_to_long_side::class,
  'short'                => Build\resize_to_short_side::class,
  'resize_to_short_side' => Build\resize_to_short_side::class,
  'fit'                  => Build\resize_to_best_fit::class,
  'resize_to_best_fit'   => Build\resize_to_best_fit::class,
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

  // TODO extension detection
  // TODO proper API routing

  $ops = array_reduce(array_slice($segments, 1, -1), parse_ops::class, []);

  $dir = array_reduce(array_filter($ops, 'is_string'), function($path, $subdir) {
    return "$path/$subdir";
  }, $segments[0]);

  $ops = array_filter($ops, 'is_array');

  return [
    'directory' => $dir,
    'ops'       => $ops,
    'filename'  => $segments[count($segments)-1],
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
  error_log(var_export($chain,true));
  $path = sprintf(
    '%s/%s/%s',
    getenv('IMAGES_ROOT'),
    $chain['directory'] ?? '',
    $chain['filename'] ?? ''
  );

  $image = new ImageResize($path);

  return array_reduce($chain['ops'], function($image, $op) {
    $method = [$image, $op['op']];
    return ImageResize::createFromString((string) $method(...$op['params']));
  }, $image);
}