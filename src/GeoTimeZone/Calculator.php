<?php

namespace GeoTimeZone;

use DateTime;
use DateTimeZone;
use ErrorException;
use GeoTimeZone\Quadrant\Tree;

class Calculator
{
    protected $quadrantTree;
    
    /**
     * TimeZone constructor.
     */
    public function __construct()
    {
        $this->quadrantTree = new Tree();
        $this->quadrantTree->initializeDataTree();
    }
    
    /**
     * Adjust the latitude value
     * @param $latitude
     * @return float|int
     * @throws \ErrorException
     */
    protected function adjustLatitude($latitude)
    {
        $newLatitude = $latitude;
        if (null == $latitude || abs($latitude) > Tree::MAX_ABS_LATITUDE) {
            throw new ErrorException('Invalid latitude: ' . $latitude);
        }
        if (abs($latitude) == Tree::MAX_ABS_LATITUDE) {
            $newLatitude = ($latitude / Tree::MAX_ABS_LATITUDE) * Tree::ABS_LATITUDE_LIMIT;
        }
        return $newLatitude;
    }
    
    /**
     * Adjust longitude value
     * @param $longitude
     * @return float|int
     * @throws \ErrorException
     */
    protected function adjustLongitude($longitude)
    {
        $newLongitude = $longitude;
        if (null == $longitude || abs($longitude) > Tree::MAX_ABS_LONGITUDE) {
            throw new ErrorException('Invalid latitude: ' . $longitude);
        }
        if (abs($longitude) == Tree::MAX_ABS_LONGITUDE) {
            $newLongitude = ($longitude / Tree::MAX_ABS_LONGITUDE) * Tree::ABS_LONGITUDE_LIMIT;
        }
        return $newLongitude;
    }
    
    /**
     * Get timezone name from a particular location (latitude, longitude)
     * @param $latitude
     * @param $longitude
     * @return null|string
     */
    public function getTimeZoneName($latitude, $longitude)
    {
        $latitude = $this->adjustLatitude($latitude);
        $longitude = $this->adjustLongitude($longitude);
        $timeZone = $this->quadrantTree->lookForTimezone($latitude, $longitude);
        return $timeZone;
    }
    
    /**
     * Get the local date belonging to a particular latitude, longitude and timestamp
     * @param $latitude
     * @param $longitude
     * @param $timestamp
     * @return DateTime
     */
    public function getLocalDate($latitude, $longitude, $timestamp)
    {
        $timeZone = $this->getTimeZoneName($latitude, $longitude);
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        if ($timeZone != null) {
            $date->setTimezone(new DateTimeZone($timeZone));
        }
        return $date;
    }
    
    /**
     * Get timestamp from latitude, longitude and localTimestamp
     * @param $latitude
     * @param $longitude
     * @param $localTimestamp
     * @return mixed
     */
    public function getCorrectTimestamp($latitude, $longitude, $localTimestamp)
    {
        $timestamp = $localTimestamp;
        $timeZoneName = $this->getTimeZoneName($latitude, $longitude);
        if ($timeZoneName != "none") {
            $date = new DateTime();
            $date->setTimestamp($localTimestamp);
            if ($timeZoneName != null) {
                $date->setTimezone(new DateTimeZone($timeZoneName));
            }
            $timestamp = $date->getOffset() != false ? $localTimestamp - $date->getOffset() : $localTimestamp;
        }
        return $timestamp;
    }
}

