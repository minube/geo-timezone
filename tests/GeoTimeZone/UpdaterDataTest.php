<?php

namespace Tests;

include __DIR__ . "/../../vendor/autoload.php";

use GeoTimeZone\UpdaterData;

class UpdaterDataTest
{
    public function main()
    {
        $updater = new UpdaterData();
        $updater->updateData();
    }
}
$test = new UpdaterDataTest();
$test->main();