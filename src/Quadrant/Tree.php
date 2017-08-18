<?php

namespace TimeZone\Quadrant;

use TimeZone\Geometry\Utils;


class Tree extends Element
{
    const DATA_TREE_FILENAME = "index.json";
    const DATA_DIRECTORY = "/../../data/";
    const GEO_FEATURE_FILENAME = "geo.json";
    protected $dataTree = null;
    protected $dataDirectory;
    
    /**
     * Tree constructor.
     */
    public function __construct()
    {
        $this->dataDirectory = __DIR__ . self::DATA_DIRECTORY;
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
        $timeZone = Utils::isPointInQuadrantFeatures($features, $latitude, $longitude);
        return $timeZone;
    }

    /**
     * Get valid timezone
     * @param $zoneData
     * @param $quadrantPath
     * @param $latitude
     * @param $longitude
     * @return null|string
     * @throws ErrorException
     */
    protected function evaluateQuadrantData($zoneData, $quadrantPath, $latitude, $longitude)
    {
        $validTimezone = 'none';
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
        return $timeZone == null || $timeZone != "none";
    }

    /**
     * Main function for looking the timezone associated to a particular location (latitude, longitude)
     * @param $latitude
     * @param $longitude
     * @return null|string
     */
    public function lookForTimeZone($latitude, $longitude)
    {
        $geoQuadrant = new Element();
        $timeZone = "none";
        $quadrantPath = '';
        $quadrantTree = $this->dataTree['lookup'];

        while (!$this->isValidTimeZone($timeZone)) {
            $geoQuadrant->moveToNextQuadrant($latitude, $longitude);
            $quadrantTree = $quadrantTree[$geoQuadrant->getLevel()];
            $quadrantPath = $quadrantPath . $geoQuadrant->getLevel();
            $timeZone = $this->evaluateQuadrantData($quadrantTree, $quadrantPath, $latitude, $longitude);
            $geoQuadrant->updateMidCoordinates();
        }
        return $timeZone;
    }
}
