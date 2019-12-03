#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

use GeoTimeZone\UpdaterData;

$updater = new UpdaterData(__DIR__.'/../data/geo.data');
$updater->updateData();
