<?php

namespace Simulacrum\Api;

use Simulacrum\Upload;

define('ROUTES', [
  'PUT'              => [
    '/api/directory' => Upload\create_directory::class,
    '/api/file'      => Upload\upload_file::class,
  ],
  'DELETE'           => [
    '/api/directory' => Upload\delete_directory::class,
    '/api/file'      => Upload\delete_file::class,
  ],
]);

function error_body($message) {
  return json_encode(['success' => false, 'error' => $message]);
}

function expand_user(array $user) : array {
  $user['roles'] = explode(',', $user['roles']);
  return $user;
}

function handle(array $req) {
  $db = new \SQLite3('simulacrum.db', SQLITE3_OPEN_READWRITE);
  $query = $db->prepare('SELECT * FROM directories WHERE directory = :directory');
  $query->bindValue(':directory', $req['directory']);
  $result = $query->execute();
  $user = $result->fetchArray();

  if (!($user && password_verify($req['key'], $user['api_key']))) {
    return [
      'status' => 401,
      'body' => ['success' => false, 'error' => 'Invalid or missing API key.'],
    ];
  }

  $req['user'] = expand_user($user);
  $req['db']   = $db;

  if (!is_writeable(IMAGES_ROOT) || !is_dir(IMAGES_ROOT)) {
    return [
      'status' => 500,
      'body' => [
        'success' => false,
        'error' => sprintf('`%s` is not a writeable directory.', IMAGES_ROOT),
      ],
    ];
  }

  $handler = ROUTES[$_SERVER['REQUEST_METHOD']][$req['path']] ?? null;
  if (!$handler) {
    return [
      'status' => 405,
      'body'   => ['success' => false, 'error' => 'No such API route'],
    ];
  }

  return $handler($req);
}
