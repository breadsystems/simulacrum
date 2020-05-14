<?php

namespace Simulacrum\Build;

use Gumlet\ImageResize;

use Simulacrum\ParseError;

function crop(array $tokens, string $opStr) : array {
  $w = array_shift($tokens);
  if (!is_numeric($w)) {
    throw new ParseError(sprintf('Expected int for width param, got "%s" in "%s"', $w, $opStr));
  }
  $h = array_shift($tokens);
  if (!is_numeric($h)) {
    throw new ParseError(sprintf('Expected int for height param, got "%s" in "%s"', $h, $opStr));
  }
  $params = [(int) $w, (int) $h];

  if ($tokens && in_array($tokens[0], ['center', 'top', 'bottom'])) {
    $gravity  = array_shift($tokens);
    $params[] = [
      'center' => ImageResize::CROPCENTER,
      'top'    => ImageResize::CROPTOP,
      'bottom' => ImageResize::CROPBOTTOM,
    ][$gravity];
  } elseif (count($tokens) > 1 && is_numeric($tokens[0])) {
    $params[] = (int) array_shift($tokens);

    $y = array_shift($tokens);
    if (!is_numeric($y)) {
      throw new ParseError(sprintf('Expected int for y param, got "%s" in "%s"', $y, $opStr));
    }
    $params[] = (int) $y;
  }

  $op = count($params) === 4 ? 'freecrop' : 'crop';

  return [
    [
      'op' => $op,
      'params' => $params,
    ],
    $tokens,
  ];
}

function scale(array $tokens, string $opStr) {
  $percentage = array_shift($tokens);
  if (!is_numeric($percentage)) {
    throw new ParseError(sprintf('Expected int for scale percentage, got "%s" in "%s"', $percentage, $opStr));
  }

  return [
    [
      'op'     => 'scale',
      'params' => [$percentage],
    ],
    $tokens,
  ];
}

function resizer(array $args, array $optionalArgs, string $op) : callable {
  return function(array $tokens, $opStr) use ($args) {
    // map over all required args to this op
    $params = array_map(function(array $arg) use ($tokens, $opStr) {
      $predicate = $arg['predicate'];
      $param     = array_shift($tokens);

      if (!$predicate($param)) {
        throw new ParseError('Bad!');
      }

      return $param;
    }, $args);
    
    return [
      [
        'op'     => $op,
        'params' => $params,
      ],
      $tokens,
    ];
  };
}

function resize_preserving_aspect_ratio(array $tokens, string $opStr) {
  $width = array_shift($tokens);
  if (!is_numeric($width)) {
    throw new ParseError(sprintf('Expected int for width, got "%s" in "%s"', $width, $opStr));
  }

  $height = array_shift($tokens);
  if (!is_numeric($height)) {
    throw new ParseError(sprintf('Expected int for height, got "%s" in "%s"', $height, $opStr));
  }

  $params = [$width, $height];

  if ($tokens && $tokens[0] === 'enlarge') {
    $params[] = true;
    array_shift($tokens);
  }

  return [
    [
      'op'     => 'resize',
      'params' => $params,
    ],
    $tokens,
  ];
}

function resize_to_width(array $tokens, string $opStr) {
  $width = array_shift($tokens);
  if (!is_numeric($width)) {
    throw new ParseError(sprintf('Expected int for width, got "%s" in "%s"', $width, $opStr));
  }

  $params = [$width];

  if ($tokens && $tokens[0] === 'enlarge') {
    array_shift($tokens);
    $params[] = true;
  }

  return [
    [
      'op'     => 'resizeToWidth',
      'params' => $params,
    ],
    $tokens,
  ];
}

function resize_to_height(array $tokens, string $opStr) {
  $height = array_shift($tokens);
  if (!is_numeric($height)) {
    throw new ParseError(sprintf('Expected int for height, got "%s" in "%s"', $height, $opStr));
  }

  $params = [$height];

  if ($tokens && $tokens[0] === 'enlarge') {
    array_shift($tokens);
    $params[] = true;
  }

  return [
    [
      'op'     => 'resizeToHeight',
      'params' => $params,
    ],
    $tokens,
  ];
}

function resize_to_long_side(array $tokens, string $opStr) {
  $length = array_shift($tokens);
  if (!is_numeric($length)) {
    throw new ParseError(sprintf('Expected int for length, got "%s" in "%s"', $length, $opStr));
  }

  return [
    [
      'op'     => 'resizeToLongSide',
      'params' => [$length],
    ],
    $tokens,
  ];
}

function resize_to_short_side(array $tokens, string $opStr) {
  $length = array_shift($tokens);
  if (!is_numeric($length)) {
    throw new ParseError(sprintf('Expected int for length, got "%s" in "%s"', $length, $opStr));
  }

  return [
    [
      'op'     => 'resizeToShortSide',
      'params' => [$length],
    ],
    $tokens,
  ];
}

function resize_to_best_fit(array $tokens, string $opStr) {
  $width = array_shift($tokens);
  if (!is_numeric($width)) {
    throw new ParseError(sprintf('Expected int for width, got "%s" in "%s"', $width, $opStr));
  }

  $height = array_shift($tokens);
  if (!is_numeric($height)) {
    throw new ParseError(sprintf('Expected int for height, got "%s" in "%s"', $height, $opStr));
  }

  $params = [$width, $height];

  if ($tokens && $tokens[0] === 'enlarge') {
    $params[] = true;
    array_shift($tokens);
  }

  return [
    [
      'op'     => 'resizeToBestFit',
      'params' => $params,
    ],
    $tokens,
  ];
}