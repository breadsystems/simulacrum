<?php

namespace Simulacrum\Upload;

use finfo;

function user_can(string $role, array $user) : bool {
  return in_array($role, $user['roles'], true);
}

function gen_key(int $len = 64) : string {
  $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  $key = '';
  $i = 0;
  while ($i < $len) {
    $key .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    $i++;
  }
  return $key;
}

function create_directory(array $req) : array {
  if (!user_can('create_directory', $req['user'])) {
    return [
      'status'    => 401,
      'body'      => [
        'success' => false,
        'error'   => 'Not allowed: missing create_directory role',
      ],
    ];
  }

  $dir = trim($req['query_params']['directory'] ?? '');
  if (!$dir) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'Invalid or missing directory param',
      ],
    ];
  }

  if (strpos($dir, '.') !== false) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'Directory name cannot contain dots (".")',
      ],
    ];
  }

  $path = implode(DIRECTORY_SEPARATOR, [IMAGES_ROOT, $dir]);

  if (is_dir($path)) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'Directory already exists',
      ],
    ];
  }

  $insert = $req['db']->prepare(
    'INSERT INTO directories (directory, api_key, roles) VALUES (:directory, :api_key, :roles)'
  );
  $insert->bindValue(':directory', $dir);

  $key = gen_key();
  $insert->bindValue(':api_key', password_hash($key, PASSWORD_DEFAULT));

  // User cannot grant roles they don't have themself.
  $roles = array_filter(
    array_map('trim', explode(',', $req['query_params']['roles'] ?? '')),
    fn($role) => user_can($role, $req['user']),
  );
  if ($roles) {
    $insert->bindValue(':roles', implode(',', $roles));
  }

  // DO THE THING.
  $result = $insert->execute();
  if (!$result) {
    return [
      'status'    => 500,
      'body'      => [
        'success' => false,
        'error'   => 'Unexpected error',
      ],
    ];
  }
  mkdir($path, 0755);

  return [
    'status'      => 200,
    'body'        => [
      'success'   => true,
      'directory' => $dir,
      'api_key'   => $key,
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

  if (empty($req['query_params']['file'])) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'You must specify a filename, i.e. ?file=file.ext',
      ],
    ];
  }

  $dir  = $req['directory'];
  $file = basename($req['query_params']['file']);
  $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));

  if (strpos($dir, '.') !== false) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'Directory name cannot contain dots (".")',
      ],
    ];
  }

  $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff'];
  if (!in_array($ext, $allowedExtensions, true)) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => sprintf(
          'Invalid file extension. Must be one of: ',
          implode(', ', $allowedExtensions),
        ),
      ],
    ];
  }

  $path = implode(DIRECTORY_SEPARATOR, [IMAGES_ROOT, $dir, $file]);

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

function delete_directory(array $req) : array {
  if (!user_can('create_directory', $req['user'])) {
    return [
      'status'    => 401,
      'body'      => [
        'success' => false,
        'error'   => 'Not allowed: missing create_directory role',
      ],
    ];
  }

  $dir = $req['user']['directory'];

  $delete = $req['db']->prepare('DELETE FROM directories WHERE directory = :directory');
  $delete->bindValue(':directory', $dir);

  $result = $delete->execute();
  if (!$result) {
    return [
      'status'    => 500,
      'body'      => [
        'success' => false,
        'error'   => 'Unexpected error. Try again?',
      ],
    ];
  }

  $path = implode(DIRECTORY_SEPARATOR, [IMAGES_ROOT, $dir]);
  try {
    exec(sprintf("rm -rf %s", escapeshellarg($path)));
  } catch (Exception $e) {
    return [
      'status'    => 500,
      'body'      => [
        'success' => false,
        'error'   => 'Unexpected error. Try again?',
      ],
    ];
  }

  return [
    'status'      => 200,
    'body'        => [
      'success'   => true,
      'directory' => $dir,
    ],
  ];
}

function delete_file(array $req) : array {
  if (empty($req['query_params']['file'])) {
    return [
      'status'    => 400,
      'body'      => [
        'success' => false,
        'error'   => 'You must specify a filename, i.e. ?file=dir/file.ext',
      ],
    ];
  }

  $dir  = $req['directory'];
  $file = basename($req['query_params']['file']);
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
        'error'   => 'Unexpected error.',
      ],
    ];
  }

  return [
    'status'    => 200,
    'body'      => [
      'success' => true,
      'path'    => implode(DIRECTORY_SEPARATOR, [$dir, $file]),
    ],
  ];
}
