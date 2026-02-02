<?php

namespace Simulacrum\Database;

function db($flags = SQLITE3_OPEN_READWRITE) {
  return new \SQLite3(STORAGE_ROOT . '/simulacrum.db', $flags);
}
