<?php

$keyFile = __DIR__ . '/../../api.key';
$hash    = is_readable($keyFile) ? file_get_contents($keyFile) : '';
if (!$hash) {
  header('HTTP/1.1 500 Internal Server Error');
  echo "No API key configured. All API calls will fail.\n";
  exit;
}

$key = array_change_key_case(getallheaders())['x-simulacrum-key'] ?? '';
if (!($key && password_verify($key, $hash))) {
  header('HTTP/1.1 401 Unauthorized');
  echo "Invalid or missing API key.\n";
  exit;
}

define('IMAGES_ROOT', getenv('IMAGES_ROOT'));

if (!is_writeable(IMAGES_ROOT) || !is_dir(IMAGES_ROOT)) {
  header('HTTP/1.1 500 Internal Server Error');
  echo sprintf("`%s` is not a writeable directory.\n", IMAGES_ROOT);
  exit;
}

// TODO handle DELETE

$img = file_get_contents('php://input');
if (!$img) {
  header('HTTP/1.1 400 Bad Request');
  echo "No image uploaded.\n";
  exit;
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
  header('HTTP/1.1 400 Bad Request');
  echo "Invalid MIME type.\n";
  exit;
}

if (empty($_GET['file'])) {
  header('HTTP/1.1 400 Bad Request');
  echo "You must specify a file name to save to, i.e. ?file=...\n";
  exit;
}

// Filter out blank dirs as a result of duplicate or leading/trailing slashes.
$segments = array_filter(explode('/', $_GET['file']));

if (count($segments) !== 2) {
  header('HTTP/1.1 400 Bad Request');
  echo "file path must be exactly two levels deep (dir/file.ext).\n";
  exit;
}

[$dir, $file] = $segments;

if (strpos($dir, '.') !== false) {
  header('HTTP/1.1 400 Bad Request');
  echo "Directory name cannot contain dots (".").\n";
  exit;
}

$path = implode(DIRECTORY_SEPARATOR, [IMAGES_ROOT, $dir, $file]);

$newDir = false;
if (!is_dir(dirname($path))) {
  mkdir(dirname($path));
  $newDir = true;
}

file_put_contents($path, $img);

[$width, $height] = getimagesize($path);

header('Content-Type: application/json');
echo json_encode([
  'success'    => true,
  'path'       => implode(DIRECTORY_SEPARATOR, [$dir, $file]),
  'mime_type'  => $mime,
  'dimensions' => [$width, $height],
  'new_dir'    => $newDir,
]);
