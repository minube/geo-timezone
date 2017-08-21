<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

set_include_path(
    __DIR__ . PATH_SEPARATOR . get_include_path()
);

include __DIR__ . "/../vendor/autoload.php";