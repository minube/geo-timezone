<?php

namespace GeoTimeZone\Geometry;

use Exception;
use geoPHP;

class Utils
{
    const POLYGON_GEOJSON_NAME = "Polygon";
    const POINT_WKT_NAME = "POINT";
    const FEATURE_COLLECTION_GEOJSON_NAME = "FeatureCollection";
    const FEATURE_GEOJSON_NAME = "Feature";
    const WKT_EXTENSION = "wkt";
    const GEOJSON_EXTENSION = "json";
    const NOT_FOUND_IN_FEATURES = "notFoundInFeatures";
    
    /**
     * Convert array of coordinates to polygon structured json array
     * @param $polygonPoints
     * @return array
     */
    public function createPolygonJsonFromPoints($polygonPoints)
    {
        return array(
            'type' => self::POLYGON_GEOJSON_NAME,
            'coordinates' => $this->structurePolygonCoordinates($polygonPoints)
        );
    }
    
    /**
     * Structure polygon coordinates as geoPHP needs
     * @param $polygonPoints
     * @return array
     */
    protected function structurePolygonCoordinates($polygonPoints)
    {
        $structuredCoordinates = array();
        foreach ($polygonPoints as $points) {
            if (count($points) == 2) {
                $structuredCoordinates[] = $polygonPoints;
                break;
            }
            $structuredCoordinates[] = $points;
        }
        return $structuredCoordinates;
    }
    
    /**
     * Create polygon geometry object from polygon points array
     * @param $polygonPoints
     * @return bool|geoPHP::GeometryCollection|mixed
     */
    protected function createPolygonFromPoints($polygonPoints)
    {
        $polygonData = $this->createPolygonJsonFromPoints($polygonPoints);
        return $this->createPolygonFromJson(json_encode($polygonData));
    }
    
    /**
     * Create polygon geometry object from structured polygon data (as json)
     * @param $polygonJson
     * @return bool|geoPHP::GeometryCollection|mixed
     */
    public function createPolygonFromJson($polygonJson)
    {
        $polygon = null;
        try {
            $polygon = geoPHP::load($polygonJson, self::GEOJSON_EXTENSION);
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
        return $polygon;
    }
    
    /**
     * Adapt quadrant bounds to polygon array format
     * @param $quadrantBounds
     * @return array
     */
    public function adaptQuadrantBoundsToPolygon($quadrantBounds)
    {
        return array(
            array(
                array($quadrantBounds[0], $quadrantBounds[1]),
                array($quadrantBounds[0], $quadrantBounds[3]),
                array($quadrantBounds[2], $quadrantBounds[3]),
                array($quadrantBounds[2], $quadrantBounds[1]),
                array($quadrantBounds[0], $quadrantBounds[1])
            )
        );
    }
    
    /**
     * Create polygon object from quadrant bounds
     * @param $quadrantBounds
     * @return mixed
     */
    public function getQuadrantPolygon($quadrantBounds)
    {
        $polygonPoints = $this->adaptQuadrantBoundsToPolygon($quadrantBounds);
        return $this->createPolygonFromPoints($polygonPoints);
    }
    
    /**
     * Structure features data
     * @param $features
     * @return array
     */
    protected function structureFeatures($features)
    {
        $structuredFeatures = array();
        foreach ($features as $feature) {
            $structuredFeatures[] = $this->structureOneFeature($feature);
        }
        return $structuredFeatures;
    }
    
    /**
     * Structure an isolated feature
     * @param $feature
     * @return array
     */
    protected function structureOneFeature($feature)
    {
        $structuredFeature = array(
            "type" => self::FEATURE_GEOJSON_NAME,
            "geometry" => array(
                "type" => $feature['type'],
                "coordinates" => $feature['coordinates']
            ),
            "properties" => $feature['properties']
        );
        return $structuredFeature;
    }
    
    /**
     * Create feature collection array from features list
     * @param $features
     * @return array
     */
    public function getFeatureCollection($features)
    {
        $featuresCollection = array(
            "type" => self::FEATURE_COLLECTION_GEOJSON_NAME,
            "features" => $this->structureFeatures($features)
        );
        return $featuresCollection;
    }
    
    /**
     * Get intersection data json from two different geometry features
     * @param $geoFeaturesJsonA
     * @param $geoFeaturesJsonB
     * @return mixed
     */
    public function intersection($geoFeaturesJsonA, $geoFeaturesJsonB)
    {
        $polygonA = $this->createPolygonFromJson($geoFeaturesJsonA);
        $polygonB = $this->createPolygonFromJson($geoFeaturesJsonB);
        $intersectionData = $polygonA->intersection($polygonB);
        return $intersectionData->out(self::GEOJSON_EXTENSION, true);
    }
    
    /**
     * Check if a particular object point is IN the indicated polygon (source: https://github.com/sookoll/geoPHP.git)
     * and if it is not contained inside, it checks the boundaries
     * @param $point
     * @param $polygon
     * @return mixed
     */
    protected function isInPolygon($point, $polygon)
    {
        $isInside = false;
        foreach ($polygon->components as $component) {
            $polygonPoints = $component->getComponents();
            $numPoints = count($polygonPoints);
            $pointIdxBack = $numPoints - 1;
            for ($pointIdx = 0; $pointIdx < $numPoints; $pointIdx++) {
                if ($this->isInside($point, $polygonPoints[$pointIdx], $polygonPoints[$pointIdxBack])) {
                    $isInside = true;
                    break;
                }
                $pointIdxBack = $pointIdx;
            }
        }
        return $isInside;
    }
    
    /**
     * Check if point is ON the boundaries of the polygon
     * @param $point
     * @param $polygon
     * @return mixed
     */
    protected function isOnPolygonBoundaries($point, $polygon)
    {
        return $polygon->pointOnVertex($point);
    }
    
    /**
     * Check if the polygonA intersects with polygonB
     * @param $polygonJsonA
     * @param $polygonBoundsB
     * @return mixed
     * @internal param $polygonA
     * @internal param $polygonB
     */
    public function intersectsPolygons($polygonJsonA, $polygonBoundsB)
    {
        $polygonA = $this->createPolygonFromJson(json_encode($polygonJsonA));
        $polygonB = $this->getQuadrantPolygon($polygonBoundsB);
        return $polygonA->intersects($polygonB);
    }
    
    /**
     * Check if the polygonA is within polygonB
     * @param $polygonBoundsOrigin
     * @param $polygonJsonDest
     * @return mixed
     * @internal param $polygonA
     * @internal param $polygonB
     */
    public function withinPolygon($polygonBoundsOrigin, $polygonJsonDest)
    {
        $polygonDest = $this->createPolygonFromJson(json_encode($polygonJsonDest));
        $polygonOrig = $this->getQuadrantPolygon($polygonBoundsOrigin);
        return $polygonOrig->within($polygonDest);
    }
    
    /**
     * Create a point geometry object from coordinates (latitude, longitude)
     * @param $latitude
     * @param $longitude
     * @return bool|geoPHP::GeometryCollection|mixed
     */
    protected function createPoint($latitude, $longitude)
    {
        $point = null;
        try {
            $formattedPoint = self::POINT_WKT_NAME . "({$longitude} {$latitude})";
            $point = geoPHP::load($formattedPoint, self::WKT_EXTENSION);
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
        return $point;
    }
    
    /**
     * Check if point (latitude, longitude) is IN a particular features polygon
     * @param $features
     * @param $latitude
     * @param $longitude
     * @return null|string
     */
    public function isPointInQuadrantFeatures($features, $latitude, $longitude)
    {
        $timeZone = self::NOT_FOUND_IN_FEATURES;
        $point = $this->createPoint($latitude, $longitude);
        if ($point != null) {
            foreach ($features['features'] as $feature) {
                foreach ($feature['geometry']['coordinates'] as $polygonFeatures) {
                    $polygon = $this->createPolygonFromJson(
                        json_encode($this->createPolygonJsonFromPoints(
                            $polygonFeatures
                        ))
                    );
                    if ($this->isInPolygon($point, $polygon) ||
                        $this->isOnPolygonBoundaries($point, $polygon)) {
                        $timeZone = $feature['properties']['tzid'];
                        break;
                    }
                }
            }
        }
        return $timeZone;
    }
    
    /**
     * Check if the point is between two points from the polygon
     * @param $point
     * @param $currentPolygonPoint
     * @param $backPolygonPoint
     * @return bool
     */
    protected function isInside($point, $currentPolygonPoint, $backPolygonPoint)
    {
        return ($currentPolygonPoint->y() > $point->y()) != ($backPolygonPoint->y() > $point->y()) &&
            (
                $point->x() < ($backPolygonPoint->x() - $currentPolygonPoint->x()) *
                ($point->y() - $currentPolygonPoint->y()) / ($backPolygonPoint->y() - $currentPolygonPoint->y()) +
                $currentPolygonPoint->x()
            );
    }
}
