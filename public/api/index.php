<?php

require __DIR__ . '/../../vendor/autoload.php';

use Simulacrum\Upload;

$directory = $_SERVER['PHP_AUTH_USER'] ?? '';
$key = trim($_SERVER['PHP_AUTH_PW'] ?? '');

$db = new \SQLite3('../../simulacrum.db', SQLITE3_OPEN_READWRITE);
$query = $db->prepare('SELECT * FROM directories WHERE directory = :directory');
$query->bindValue(':directory', $directory);
$res = $query->execute();
$row = $res->fetchArray();

if (!(password_verify($key, $row['api_key']))) {
  header('HTTP/1.1 401 Unauthorized');
  echo "Invalid or missing API key.\n";
  exit;
}

if (!is_writeable(IMAGES_ROOT) || !is_dir(IMAGES_ROOT)) {
  header('HTTP/1.1 500 Internal Server Error');
  echo sprintf("`%s` is not a writeable directory.\n", IMAGES_ROOT);
  exit;
}

$res = Upload\handle([
  'http_method' => $_SERVER['REQUEST_METHOD'],
  'path'        => $_SERVER['PATH_INFO'],
  'directory'   => $directory,
  'image_data'  => file_get_contents('php://input'),
]);

if ($res['status'] === 200) {
  header('Content-Type: application/json');
} else {
  header([
    400 => 'HTTP/1.1 400 Bad Request',
    404 => 'HTTP/1.1 404 Not Found',
    405 => 'HTTP/1.1 405 Method Not Allowed',
    500 => 'HTTP/1.1 500 Internal Server Error',
  ][$res['status'] ?? 500]);
}

echo json_encode($res['body']);
