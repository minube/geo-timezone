<?php

namespace GeoTimeZone\Quadrant;

use ErrorException;
use GeoTimeZone\Geometry\Utils;

class Tree extends Element
{
    const DATA_TREE_FILENAME = "index.json";
    const GEO_FEATURE_FILENAME = "geo.json";
    const NONE_TIMEZONE = "none";
    protected $dataTree = null;
    protected $dataDirectory;
    protected $utils;
    
    /**
     * Tree constructor.
     * @param $dataDirectory
     */
    public function __construct($dataDirectory=null)
    {
        if (isset($dataDirectory) && is_dir($dataDirectory)) {
            Element::__construct();
            $this->dataDirectory = $dataDirectory;
            $this->utils = new Utils();
        }else{
            new ErrorException('Invalid data directory: ' . $dataDirectory);
        }
    }
    
    /**
     * Data tree is loaded from json file
     */
    public function initializeDataTree()
    {
        $jsonData = file_get_contents($this->dataDirectory . self::DATA_TREE_FILENAME);
        $this->dataTree = json_decode($jsonData, true);
    }
    
    /**
     * Load json features data from a particular geo quadrant path
     * @param $quadrantPath
     * @return mixed
     */
    protected function loadFeatures($quadrantPath)
    {
        $filePath = $this->dataDirectory . implode('/', str_split($quadrantPath)) . DIRECTORY_SEPARATOR .
            self::GEO_FEATURE_FILENAME;
        $geoJson = json_decode(file_get_contents($filePath), true);
        return $geoJson;
    }
    
    /**
     * Check if a particular location (latitude, longitude)is IN a particular quadrant
     * @param $quadrantPath
     * @param $latitude
     * @param $longitude
     * @return string
     */
    protected function evaluateFeatures($quadrantPath, $latitude, $longitude)
    {
        $features = $this->loadFeatures($quadrantPath);
        $timeZone = $this->utils->isPointInQuadrantFeatures($features, $latitude, $longitude);
        return $timeZone;
    }
    
    /**
     * Get valid timezone
     * @param $zoneData
     * @param $quadrantPath
     * @param $latitude
     * @param $longitude
     * @return string
     * @throws ErrorException
     */
    protected function evaluateQuadrantData($zoneData, $quadrantPath, $latitude, $longitude)
    {
        $validTimezone = self::NONE_TIMEZONE;
        if (!isset($zoneData)) {
            throw new ErrorException('Unexpected data type');
        } elseif ($zoneData === "f") {
            $validTimezone = $this->evaluateFeatures($quadrantPath, $latitude, $longitude);
        } elseif (is_numeric($zoneData)) {
            $validTimezone = $this->dataTree['timezones'][$zoneData];
        }
        return $validTimezone;
    }
    
    /**
     * Check if timezone is valid
     * @param $timeZone
     * @return bool
     */
    protected function isValidTimeZone($timeZone)
    {
        return $timeZone != self::NONE_TIMEZONE;
    }
    
    /**
     * Main function for looking the timezone associated to a particular location (latitude, longitude)
     * @param $latitude
     * @param $longitude
     * @return string
     * @throws ErrorException
     */
    public function lookForTimeZone($latitude, $longitude)
    {
        $geoQuadrant = new Element();
        $timeZone = self::NONE_TIMEZONE;
        $quadrantPath = '';
        $quadrantTree = $this->dataTree['lookup'];
        
        while (!$this->isValidTimeZone($timeZone)) {
            $geoQuadrant->moveToNextQuadrant($latitude, $longitude);
            if (!isset($quadrantTree[$geoQuadrant->getLevel()])) {
                break;
            }
            $quadrantTree =  $quadrantTree[$geoQuadrant->getLevel()];
            $quadrantPath = $quadrantPath . $geoQuadrant->getLevel();
            $timeZone = $this->evaluateQuadrantData($quadrantTree, $quadrantPath, $latitude, $longitude);
            $geoQuadrant->updateMidCoordinates();
        }
        
        if ($timeZone == self::NONE_TIMEZONE || $timeZone == Utils::NOT_FOUND_IN_FEATURES) {
            throw new ErrorException("ERROR: TimeZone not found");
        }
        
        return $timeZone;
    }
}
