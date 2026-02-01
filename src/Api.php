<?php

namespace Simulacrum\Api;

use Simulacrum\Upload;

define('ROUTES', [
  'PUT'     => [
    '/api/file' => Upload\upload_file::class,
  ],
  'DELETE'  => [
    '/api/file' => Upload\delete_file::class,
  ],
]);

function error_body($message) {
  return json_encode(['success' => false, 'error' => $message]);
}

function handle(array $req) {
  $db = new \SQLite3('simulacrum.db', SQLITE3_OPEN_READWRITE);
  $query = $db->prepare('SELECT * FROM directories WHERE directory = :directory');
  $query->bindValue(':directory', $req['directory']);
  $result = $query->execute();
  $row = $result->fetchArray();

  if (!($row && password_verify($req['key'], $row['api_key']))) {
    header('HTTP/1.1 401 Unauthorized');
    echo error_body('Invalid or missing API key.');
    exit;
  }

  if (!is_writeable(IMAGES_ROOT) || !is_dir(IMAGES_ROOT)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo error_body(sprintf('`%s` is not a writeable directory.', IMAGES_ROOT));
    exit;
  }

  $handler = ROUTES[$_SERVER['REQUEST_METHOD']][$req['path']] ?? null;
  if (!$handler) {
    return [
      'status' => 405,
      'body'   => 'Invalid',
    ];
  }

  return $handler($req);
}
