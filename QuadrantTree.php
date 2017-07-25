<?php

include "Quadrant.php";

class QuadrantTree extends Quadrant
{
    const DATA_TREE_FILENAME = "index.json";
    const DATA_DIRECTORY = "./data/";
    const GEO_FEATURE_FILENAME = "geo.json";
    protected $dataTree = null;

    /**
     * Data tree is loaded from json file
     */
    public function initializeDataTree()
    {
        $jsonData = file_get_contents(self::DATA_DIRECTORY . self::DATA_TREE_FILENAME);
        $this->dataTree = json_decode($jsonData, true);
    }

    /**
     * Load json features data from a particular geo quadrant path
     * @param $quadrantPath
     * @return mixed
     */
    protected function loadFeatures($quadrantPath)
    {
        $filePath = implode('/', str_split($quadrantPath));
        $filePath = self::DATA_DIRECTORY; //TODO REMOVE
        $geoJson = json_decode(file_get_contents($filePath . self::GEO_FEATURE_FILENAME), true);
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
        $timeZone = $this->isInQuadrantFeatures($features, $latitude, $longitude);
        return $timeZone;
    }

    /**
     * Create geoPHP Polygon from feature data json
     * @param $feature
     * @return mixed
     */
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

    /**
     * Check if point (latitude, longitude) is IN a particular features polygon
     * @param $features
     * @param $latitude
     * @param $longitude
     * @return null
     */
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
