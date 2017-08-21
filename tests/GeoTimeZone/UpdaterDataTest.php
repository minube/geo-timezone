<?php
namespace Tests\GeoTimeZone;

use GeoTimeZone\UpdaterData;
use Tests\AbstractUnitTestCase;

class UpdaterDataTest extends AbstractUnitTestCase
{
    protected $updater;
    
    protected function setUp()
    {
        $this->updater = new UpdaterData();
    }
    
    public function testDownloadLastVersion()
    {
        $method = $this->getPrivateMethod(get_class($this->updater), 'downloadLastVersion');
        $method->invokeArgs($this->updater, array());
    }
}
