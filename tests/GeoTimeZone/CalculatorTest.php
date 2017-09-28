<?php

namespace Tests\GeoTimeZone;

use GeoTimeZone\Calculator;
use PHPUnit\Runner\Exception;
use Tests\AbstractUnitTestCase;

class CalculatorTest extends AbstractUnitTestCase
{
    const DATA_DIRECTORY = "/../../data/";
    
    protected $calculator;
    
    protected function setUp()
    {
        $this->calculator = new Calculator(__DIR__ . self::DATA_DIRECTORY);
    }
    
    public function getDataLocalDate()
    {
        return array(
            'Testing Lisbon' => array(
                'latitude' => 41.142700,
                'longitude' => -8.612150,
                'timestamp' => 1458844434,
                'expectedTimeZone' => 'Europe/Lisbon',
                '$expectedOffset' => 0
            ),
            'Testing Madrid' => array(
                'latitude' => 39.452800,
                'longitude' => -0.347038,
                'timestamp' => 1469387760,
                'expectedTimeZone' => 'Europe/Madrid',
                '$expectedOffset' => 7200
            ),
            'Testing Caracas' => array(
                'latitude' => 7.811258,
                'longitude' => -72.199897,
                'timestamp' => 1412482901,
                'expectedTimeZone' => 'America/Caracas',
                '$expectedOffset' => -16200
            ),
            'Testing Praga' => array(
                'latitude' => 50.087257,
                'longitude' => 14.636790,
                'timestamp' => 1506408879,
                'expectedTimeZone' => 'Europe/Prague',
                '$expectedOffset' => 7200
            ),
            'Testing Berlin Limit' => array(
                'latitude' => 48.518129,
                'longitude' => 13.730860,
                'timestamp' => 1506408879,
                'expectedTimeZone' => 'Europe/Vienna',
                '$expectedOffset' => 7200
            ),
            'Testing Roma Limit' => array(
                'latitude' => 46.840306,
                'longitude' => 12.301866,
                'timestamp' => 1506408879,
                'expectedTimeZone' => 'Europe/Rome',
                '$expectedOffset' => 7200
            )
        );
    }
    
    public function getDataCorrectTimestamp()
    {
        return array(
            'Testing Lisbon' => array(
                'latitude' => 41.142700,
                'longitude' => -8.612150,
                'timestamp' => 1458844434,
                'expectedTimestamp' => 1458844434,
            ),
            'Testing Madrid' => array(
                'latitude' => 39.452800,
                'longitude' => -0.347038,
                'timestamp' => 1469387760,
                'expectedTimestamp' => 1469380560,
            )
        );
    }
    
    public function getDataTimeZoneName()
    {
        return array(
            'Testing Lisbon' => array(
                'latitude' => 41.142700,
                'longitude' => -8.612150,
                'expectedTimeZone' => 'Europe/Lisbon',
            ),
            'Testing Madrid' => array(
                'latitude' => 39.452800,
                'longitude' => -0.347038,
                'expectedTimeZone' => 'Europe/Madrid',
            )
        );
    }
    
    public function getNoTimezone()
    {
        return array(
            'Testing None Timezone' => array(
                'latitude' => -1,
                'longitude' => -1,
                'timestamp' => 0,
                'expectedException' => "ERROR: TimeZone not found",
            )
        );
    }
    
    public function getDataWrongLatitude()
    {
        return array(
            'Testing Wrong Latitude' => array(
                'latitude' => 10000000,
                'longitude' => -8.612150,
                'expectedException' => "Invalid latitude: 10000000",
            ),
            'Testing Null Latitude' => array(
                'latitude' => null,
                'longitude' => -8.612150,
                'expectedException' => "Invalid latitude: ",
            )
        );
    }
    
    public function getDataWrongLongitude()
    {
        return array(
            'Testing Wrong Longitude' => array(
                'latitude' => 41.142700,
                'longitude' => 10000000,
                'expectedException' => "Invalid longitude: 10000000",
            
            ),
            'Testing Null Longitude' => array(
                'latitude' => 41.142700,
                'longitude' => null,
                'expectedException' => "Invalid longitude: ",
            )
        );
    }
    
    public function getDataMaxLatitude()
    {
        return array(
            'Testing Positive Max Latitude' => array(
                'latitude' => 90.0,
                'longitude' => -8.612150,
                'adjustedLatitude' => 89.9999,
                'expectedException' => "ERROR: TimeZone not found",
            ),
            'Testing Negative Max Latitude' => array(
                'latitude' => -90.0,
                'longitude' => -8.612150,
                'adjustedLatitude' => -89.9999,
                'expectedException' => "ERROR: TimeZone not found",
            )
        );
    }
    
    public function getDataMaxLongitude()
    {
        return array(
            'Testing Positive Max Longitude' => array(
                'latitude' => -8.612150,
                'longitude' => 180.0,
                'adjustedLongitude' => 179.9999,
                'expectedException' => "ERROR: TimeZone not found",
            ),
            'Testing Negative Max Longitude' => array(
                'latitude' => -8.612150,
                'longitude' => -180.0,
                'adjustedLongitude' => -179.9999,
                'expectedException' => "ERROR: TimeZone not found",
            )
        );
    }
    
    /**
     * @dataProvider getDataWrongLatitude
     * @param $latitude
     * @param $longitude
     * @param $expectedException
     */
    public function testGetTimeZoneNameWithWrongLatitude($latitude, $longitude, $expectedException)
    {
        try {
            $this->expectException(\ErrorException::class);
            $this->expectExceptionMessage($expectedException);
            $timeZone = $this->calculator->getTimeZoneName($latitude, $longitude);
        } catch (Exception $error) {
            echo $error->getMessage();
        }
    }
    
    /**
     * @dataProvider getDataWrongLongitude
     * @param $latitude
     * @param $longitude
     * @param $expectedException
     */
    public function testGetTimeZoneNameWithWrongLongitude($latitude, $longitude, $expectedException)
    {
        try {
            $this->expectException(\ErrorException::class);
            $this->expectExceptionMessage($expectedException);
            $timeZone = $this->calculator->getTimeZoneName($latitude, $longitude);
        } catch (Exception $error) {
            echo $error->getMessage();
        }
    }
    
    /**
     * @dataProvider getDataMaxLatitude
     * @param $latitude
     * @param $longitude
     * @param $adjustedLatitude
     * @param $expectedException
     */
    public function testGetTimeZoneNameWithMaxLatitude($latitude, $longitude, $adjustedLatitude, $expectedException)
    {
        try {
            $this->expectException(\ErrorException::class);
            $this->expectExceptionMessage($expectedException);
            $timeZone = $this->calculator->getTimeZoneName($latitude, $longitude);
        } catch (Exception $error) {
            echo $error->getMessage();
        }
    }
    
    /**
     * @dataProvider getDataMaxLongitude
     * @param $latitude
     * @param $longitude
     * @param $adjustedLongitude
     * @param $expectedException
     */
    public function testGetTimeZoneNameWithMaxLongitude($latitude, $longitude, $adjustedLongitude, $expectedException)
    {
        try {
            $this->expectException(\ErrorException::class);
            $this->expectExceptionMessage($expectedException);
            $timeZone = $this->calculator->getTimeZoneName($latitude, $longitude);
        } catch (Exception $error) {
            echo $error->getMessage();
        }
    }
    
    /**
     * @dataProvider getDataMaxLatitude
     * @param $latitude
     * @param $longitude
     * @param $adjustedLatitude
     * @param $expectedException
     */
    public function testAdjustLatitudeWithMaxLatitude($latitude, $longitude, $adjustedLatitude, $expectedException)
    {
        $method = $this->getPrivateMethod(get_class($this->calculator), 'adjustLatitude');
        $latitudeToTest = $method->invokeArgs($this->calculator, array($latitude));
        $this->assertEquals($adjustedLatitude, $latitudeToTest);
    }
    
    /**
     * @dataProvider getDataMaxLongitude
     * @param $latitude
     * @param $longitude
     * @param $adjustedLongitude
     * @param $expectedException
     */
    public function testAdjustMaxLongitudeWithMaxLongitude($latitude, $longitude, $adjustedLongitude,
                                                           $expectedException)
    {
        $method = $this->getPrivateMethod(get_class($this->calculator), 'adjustLongitude');
        $longitudeToTest = $method->invokeArgs($this->calculator, array($longitude));
        $this->assertEquals($adjustedLongitude, $longitudeToTest);
    }
    
    /**
     * @dataProvider getDataLocalDate
     * @param $latitude
     * @param $longitude
     * @param $timestamp
     * @param $expectedTimeZone
     * @param $expectedOffset
     */
    public function testGetLocalDate($latitude, $longitude, $timestamp, $expectedTimeZone, $expectedOffset)
    {
        $localDate = $this->calculator->getLocalDate($latitude, $longitude, $timestamp);
        $this->assertInstanceOf('DateTime', $localDate);
        echo $localDate->getTimezone()->getName() . "\n";
        $this->assertEquals(
            $localDate->getTimezone()->getName(),
            $expectedTimeZone
        );
        $this->assertEquals(
            $localDate->getOffset(),
            $expectedOffset
        );
    }
    
    /**
     * @dataProvider getNoTimezone
     * @param $latitude
     * @param $longitude
     * @param $timestamp
     * @param $expectedException
     */
    public function testGetLocalDateException($latitude, $longitude, $timestamp, $expectedException)
    {
        try {
            $this->expectException(\ErrorException::class);
            $this->expectExceptionMessage($expectedException);
            $localDate = $this->calculator->getLocalDate($latitude, $longitude, $timestamp);
        } catch (Exception $error) {
            echo $error->getMessage();
        }
    }
    
    /**
     * @dataProvider getDataCorrectTimestamp
     * @param $latitude
     * @param $longitude
     * @param $timestamp
     * @param $expectedTimestamp
     */
    public function testGetCorrectTimestamp($latitude, $longitude, $timestamp, $expectedTimestamp)
    {
        $correctTimestamp = $this->calculator->getCorrectTimestamp($latitude, $longitude, $timestamp);
        $this->assertEquals($correctTimestamp, $expectedTimestamp);
    }
    
    /**
     * @dataProvider getNoTimezone
     * @param $latitude
     * @param $longitude
     * @param $timestamp
     * @param $expectedException
     */
    public function testGetCorrectTimestampException($latitude, $longitude, $timestamp, $expectedException)
    {
        try {
            $this->expectException(\ErrorException::class);
            $this->expectExceptionMessage($expectedException);
            $correctTimestamp = $this->calculator->getCorrectTimestamp($latitude, $longitude, $timestamp);
        } catch (Exception $error) {
            echo $error->getMessage();
        }
    }
    
    /**
     * @dataProvider getDataTimeZoneName
     * @param $latitude
     * @param $longitude
     * @param $expectedTimeZone
     */
    public function testGetTimeZoneName($latitude, $longitude, $expectedTimeZone)
    {
        try {
            $timeZoneName = $this->calculator->getTimeZoneName($latitude, $longitude);
            $this->assertEquals($timeZoneName, $expectedTimeZone);
        } catch (Exception $error) {
            echo $error->getMessage();
        }
    }
    
    /**
     * @dataProvider getNoTimezone
     * @param $latitude
     * @param $longitude
     * @param $timestamp
     * @param $expectedException
     */
    public function testGetTimeZoneNameException($latitude, $longitude, $timestamp, $expectedException)
    {
        try {
            $this->expectException(\ErrorException::class);
            $this->expectExceptionMessage($expectedException);
            $timeZoneName = $this->calculator->getTimeZoneName($latitude, $longitude);
        } catch (Exception $error) {
            echo $error->getMessage();
        }
    }
}
