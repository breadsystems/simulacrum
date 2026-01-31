#!/usr/bin/env php
<?php

namespace Simulacrum;

$dir = getenv('SIMULACRUM_DEFAULT_DIR') ?: 'simulacrum';
$password = getenv('SIMULACRUM_DEFAULT_API_KEY');

if (!$password) {
  echo "SIMULACRUM_DEFAULT_API_KEY is required to be set!\n";
  exit(1);
}

$db = new \SQLite3('simulacrum.db', SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE);

/*
 * NOTE: ONE USER <=> ONE DIRECTORY
 *
 * If you need more than one directory for some reason, create a new user and
 * store the key you get back.
 *
 * directory
 * - directory:text
 * - api_key:text
 * - roles:text
 */

// A user can have many directories
$db->exec('CREATE TABLE IF NOT EXISTS directories (directory TEXT, api_key TEXT, roles TEXT)');

$insert = $db->prepare('INSERT INTO directories (directory, api_key, roles) VALUES (:directory, :api_key, :roles)');
$insert->bindValue(':directory', $dir, SQLITE3_TEXT);
$insert->bindValue(':api_key', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
$insert->bindValue(':roles', 'create_directory');
$insert->execute();
