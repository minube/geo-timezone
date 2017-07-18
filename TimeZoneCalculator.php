<?php

include "QuadrantTree.php";

class TimeZoneCalculator
{
    protected $quadrantTree;

    public function __construct()
    {
        $this->quadrantTree = new QuadrantTree();
    }

    protected function adjustLatitude($latitude)
    {
        if (null == $latitude || abs($latitude) > QuadrantTree::MAX_ABS_LATITUDE) {
            throw new ErrorException('Invalid latitude: ' . $latitude);
        }
        $newLatitude = $latitude;
        if (abs($latitude) == QuadrantTree::MAX_ABS_LATITUDE) {
            $newLatitude = ($latitude / QuadrantTree::MAX_ABS_LATITUDE) * QuadrantTree::ABS_LATITUDE_LIMIT;
        }
        return $newLatitude;
    }

    protected function adjustLongitude($longitude)
    {
        $newLongitude = $longitude;
        if (null == $longitude || abs($longitude) > QuadrantTree::MAX_ABS_LONGITUDE) {
            throw new ErrorException('Invalid latitude: ' . $longitude);
        }
        if (abs($longitude) == QuadrantTree::MAX_ABS_LONGITUDE) {
            $newLongitude = ($longitude / QuadrantTree::MAX_ABS_LONGITUDE) * QuadrantTree::ABS_LONGITUDE_LIMIT;
        }
        return $newLongitude;
    }

    public function getTimeZoneName($latitude, $longitude)
    {
        $latitude = $this->adjustLatitude($latitude);
        $longitude = $this->adjustLongitude($longitude);
        $timeZone = $this->quadrantTree->lookForTimezone($latitude, $longitude);
        return $timeZone;
    }

    public function getLocalDate($latitude, $longitude, $timestamp)
    {
        $timeZone = $this->getTimeZoneName($latitude, $longitude);
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $date->setTimezone(new DateTimeZone($timeZone));
        return $date;
    }

    public function getCorrectTimestamp($latitude, $longitude, $localTimestamp)
    {
        $timeZoneName = $this->getTimeZoneName($latitude, $longitude);
        $date = new DateTime();
        $date->setTimestamp($localTimestamp);
        $date->setTimezone(new DateTimeZone($timeZoneName));
        $timestamp = $localTimestamp - $date->getOffset();
        return $timestamp;
    }
}

