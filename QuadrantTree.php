<?php

include "Quadrant.php";

class QuadrantTree extends Quadrant
{
    const DATA_TREE_FILENAME = 'index.json';
    const DATA_DIRECTORY = './data/';
    const GEO_FEATURE_FILENAME = 'geobuf.json';
    protected $dataTree = null;

    public function __construct()
    {
        parent::__construct();
        $this->initializeDataTree();
    }

    protected function initializeDataTree()
    {
        $jsonData = file_get_contents(self::DATA_DIRECTORY . self::DATA_TREE_FILENAME);
        $this->dataTree = json_decode($jsonData, true);
    }

    protected function loadFeatures($quadrantPath)
    {
        $filePath = implode('/', str_split($quadrantPath));
        $filePath = self::DATA_DIRECTORY; //TODO REMOVE
        $geoJson = json_decode(file_get_contents($filePath . self::GEO_FEATURE_FILENAME), true);
        return $geoJson;
    }

    protected function evaluateFeatures($quadrantPath, $latitude, $longitude)
    {
        $features = $this->loadFeatures($quadrantPath);
        $timeZone = $this->isInQuadrantFeatures($features, $latitude, $longitude);
        return $timeZone;
    }

    protected function getPolygon($feature)
    {
        $feature = json_encode($feature);
        $coordinatesPolygon = explode('"geometry":', $feature)[1];
        $coordinatesPolygon = explode(',"properties"', $coordinatesPolygon)[0];
        $coordinatesPolygon = str_replace("MultiPolygon", "Polygon", $coordinatesPolygon);
        $coordinatesPolygon = str_replace("[[[", "[[", $coordinatesPolygon);
        $coordinatesPolygon = str_replace("]]]", "]]", $coordinatesPolygon);
        return geoPHP::load($coordinatesPolygon, 'json');
    }

    //TODO update when the .buf files can be read
    protected function isInQuadrantFeatures($features, $latitude, $longitude)
    {
        $timeZone = null;
        $point = geoPHP::load('POINT(' . $latitude . ' ' . $longitude . ')', 'wkt');
        foreach ($features['features'] as $feature) {
            $polygon = $this->getPolygon($feature);
            if ($polygon->pointInPolygon($point)) {
                $timeZone = $feature['properties']['tzid'];
                break;
            }
        }
        return $timeZone;
    }

    protected function evaluateQuadrantData($zoneData, $quadrantPath, $latitude, $longitude)
    {
        $validTimezone = 'none';
        if (!isset($zoneData)) {
            $validTimezone = null;
        } elseif ($zoneData === 'f') {
            $validTimezone = $this->evaluateFeatures($quadrantPath, $latitude, $longitude);
        } elseif (is_numeric($zoneData)) {
            $validTimezone = $this->dataTree['timezones'][$zoneData];
        } elseif (!isset($zoneData)) {
            throw new ErrorException('Unexpected data type');
        }
        return $validTimezone;
    }

    protected function isValidTimeZone($timeZone)
    {
        return $timeZone == null || $timeZone != "none";
    }

    public function lookForTimeZone($latitude, $longitude)
    {
        $geoQuadrant = new Quadrant();
        $timeZone = 'none';
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
