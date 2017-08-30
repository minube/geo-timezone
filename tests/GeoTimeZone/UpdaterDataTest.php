<?php

namespace Tests;

include __DIR__ . "/../../vendor/autoload.php";

use GeoTimeZone\UpdaterData;

class UpdaterDataTest
{
    public function main()
    {
        try{
            $updater = new UpdaterData("/media/ana/Datos/geo-timezone/data/");
            $updater->updateData();
        }catch (\ErrorException $error){
            echo $error->getMessage();
        }
        
    }
}
$test = new UpdaterDataTest();
$test->main();