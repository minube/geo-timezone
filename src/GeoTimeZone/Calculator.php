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
     *
     * @param $dataDirectory
     *
     * @throws ErrorException
     */
    public function __construct($dataDirectory = null)
    {
        if (isset($dataDirectory) && is_dir($dataDirectory)) {
            $this->quadrantTree = new Tree($dataDirectory);
            $this->quadrantTree->initializeDataTree();
        } else {
            throw new ErrorException('Invalid data tree directory: '.$dataDirectory);
        }
    }

    /**
     * Adjust the latitude value.
     *
     * @param $latitude
     *
     * @return float|int
     *
     * @throws ErrorException
     */
    protected function adjustLatitude($latitude)
    {
        $newLatitude = $latitude;
        if (null == $latitude || abs($latitude) > Tree::MAX_ABS_LATITUDE) {
            throw new ErrorException('Invalid latitude: '.$latitude);
        }
        if (Tree::MAX_ABS_LATITUDE == abs($latitude)) {
            $newLatitude = ($latitude <=> 0) * Tree::ABS_LATITUDE_LIMIT;
        }

        return $newLatitude;
    }

    /**
     * Adjust longitude value.
     *
     * @param $longitude
     *
     * @return float|int
     *
     * @throws ErrorException
     */
    protected function adjustLongitude($longitude)
    {
        $newLongitude = $longitude;
        if (null == $longitude || abs($longitude) > Tree::MAX_ABS_LONGITUDE) {
            throw new ErrorException('Invalid longitude: '.$longitude);
        }
        if (Tree::MAX_ABS_LONGITUDE == abs($longitude)) {
            $newLongitude = ($longitude <=> 0) * Tree::ABS_LONGITUDE_LIMIT;
        }

        return $newLongitude;
    }

    /**
     * Get timezone name from a particular location (latitude, longitude).
     *
     * @param $latitude
     * @param $longitude
     *
     * @return string
     *
     * @throws ErrorException
     */
    public function getTimeZoneName($latitude, $longitude)
    {
        $timeZone = Tree::NONE_TIMEZONE;
        try {
            $latitude = $this->adjustLatitude($latitude);
            $longitude = $this->adjustLongitude($longitude);
            $timeZone = $this->quadrantTree->lookForTimezone($latitude, $longitude);
        } catch (ErrorException $error) {
            throw $error;
        }

        return $timeZone;
    }

    /**
     * Get the local date belonging to a particular latitude, longitude and timestamp.
     *
     * @param $latitude
     * @param $longitude
     * @param $timestamp
     *
     * @return DateTime
     *
     * @throws ErrorException
     */
    public function getLocalDate($latitude, $longitude, $timestamp)
    {
        $date = new DateTime();
        try {
            $timeZone = $this->getTimeZoneName($latitude, $longitude);
            $date->setTimestamp($timestamp);
            if (Tree::NONE_TIMEZONE != $timeZone) {
                $date->setTimezone(new DateTimeZone($timeZone));
            }
        } catch (ErrorException $error) {
            throw $error;
        }

        return $date;
    }

    /**
     * Get timestamp from latitude, longitude and localTimestamp.
     *
     * @param $latitude
     * @param $longitude
     * @param $localTimestamp
     *
     * @return mixed
     *
     * @throws ErrorException
     */
    public function getCorrectTimestamp($latitude, $longitude, $localTimestamp)
    {
        $timestamp = $localTimestamp;
        try {
            $timeZoneName = $this->getTimeZoneName($latitude, $longitude);
            if (Tree::NONE_TIMEZONE != $timeZoneName) {
                $date = new DateTime();
                $date->setTimestamp($localTimestamp);
                if (null != $timeZoneName) {
                    $date->setTimezone(new DateTimeZone($timeZoneName));
                }
                $timestamp = false != $date->getOffset() ? $localTimestamp - $date->getOffset() : $localTimestamp;
            }
        } catch (ErrorException $error) {
            throw $error;
        }

        return $timestamp;
    }
}
