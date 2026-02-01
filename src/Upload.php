<?php

namespace Simulacrum\Upload;

use finfo;

define('ROUTES', [
  'PUT'     => [
    '/file' => upload_file::class,
  ],
  'DELETE'  => [
    '/file' => delete_file::class,
  ],
]);

function handle(array $req) : array {
  $handler = ROUTES[$_SERVER['REQUEST_METHOD']][$req['path']] ?? null;
  if (!$handler) {
    return [
      'status' => 405,
      'body'   => 'Invalid',
    ];
  }

  return $handler($req);
}

function delete_file(array $req) : array {
  if (empty($_GET['file'])) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'You must specify a filename, i.e. ?file=dir/file.ext',
      ],
    ];
  }

  // Filter out blank dirs as a result of duplicate or leading/trailing slashes.
  $segments = array_filter(explode('/', $_GET['file']));

  if (count($segments) !== 2) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'file path must be exactly two levels deep (dir/file.ext)',
      ],
    ];
  }

  [$dir, $file] = $segments;

  if (strpos($dir, '.') !== false) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'Directory name cannot contain dots (".")',
      ],
    ];
  }

  $path = implode(DIRECTORY_SEPARATOR, [IMAGES_ROOT, $dir, $file]);

  if (!file_exists($path) || !is_writeable($path)) {
    return [
      'status'    => 404,
      'body'      => [
        'success' => false,
        'error'   => 'File does not exist or is not writeable.',
      ],
    ];
  }

  $deleted = unlink($path);

  if (!$deleted) {
    return [
      'status'    => 500,
      'body'      => [
        'success' => false,
        'error'   => 'Directory name cannot contain dots (".")',
      ],
    ];
  }

  return [
    'status' => 200,
    'body'   => [
      'success' => true,
      'path'    => implode(DIRECTORY_SEPARATOR, [$dir, $file]),
    ],
  ];
}

function upload_file(array $req) : array {
  $img = file_get_contents('php://input');
  if (!$img) {
    return [
      'status' => 400,
      'body'   => [
        'success' => false,
        'error'   => 'No file uploaded',
      ],
    ];
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->buffer($img);
  if (!in_array($mime, [
    'image/png',
    'image/jpeg',
    'image/gif',
    'image/webp',
    'image/tiff',
  ])) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'Invalid MIME type.',
      ],
    ];
  }

  if (empty($_GET['file'])) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'You must specify a filename, i.e. ?file=file.ext',
      ],
    ];
  }

  $dir  = $req['directory'];
  $file = basename($_GET['file']);

  if (strpos($dir, '.') !== false) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'Directory name cannot contain dots (".")',
      ],
    ];
  }

  $path = implode(DIRECTORY_SEPARATOR, [IMAGES_ROOT, $dir, $file]);

  // TODO mkdir in /dir endpoint
  if (!is_dir(dirname($path))) {
    mkdir(dirname($path));
  }

  $bytes = file_put_contents($path, $img);

  if (!$bytes) {
    return [
      'status'    => 500,
      'body'      => [
        'success' => false,
        'error'   => 'No bytes written! Try again?',
      ],
    ];
  }

  [$width, $height] = getimagesize($path);

  return [
    'status'      => 200,
    'body'        => [
      'success'   => true,
      'path'      => implode(DIRECTORY_SEPARATOR, [$dir, $file]),
      'mime_type' => $mime,
      'width'     => $width,
      'height'    => $height,
      'bytes'     => $bytes,
    ],
  ];
}
