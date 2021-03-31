<?php

function debug($x) {
  error_log(var_export($x, 1));
}
