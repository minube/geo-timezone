<?php

include_once('./vendor/phayes/geophp/geoPHP.inc');

/**
 * Convert array of coordinates to polygon structured json array
 * @param $polygonPoints
 * @return array
 */
function createPolygonJsonFromPoints($polygonPoints)
{
    return array(
        'type' => "Polygon",
        'coordinates' => $polygonPoints
    );
}

/**
 * Create polygon geometry object from polygon points array
 * @param $polygonPoints
 * @return bool|GeometryCollection|mixed
 */
function createPolygonFromPoints($polygonPoints)
{
    $polygonData = createPolygonJsonFromPoints($polygonPoints);
    return geoPHP::load(json_encode($polygonData), 'json');
}

/**
 * Create polygon geometry object from structured polygon data (as json)
 * @param $polygonJson
 * @return bool|GeometryCollection|mixed
 */
function createPolygonFromJson($polygonJson)
{
    return geoPHP::load(json_encode($polygonJson), 'json');
}

/**
 * Adapt quadrant bounds to polygon array format
 * @param $quadrantBounds
 * @return array
 */
function adaptQuadrantBoundsToPolygon($quadrantBounds)
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
function getQuadrantPolygon($quadrantBounds)
{
    $polygonPoints = adaptQuadrantBoundsToPolygon($quadrantBounds);
    return createPolygonFromPoints($polygonPoints);
}

/**
 * Structure features data
 * @param $features
 * @return array
 */
function structureFeatures($features)
{
    $structuredFeatures = array();
    foreach ($features as $feature) {
        $structuredFeatures[] = structureOneFeature($feature);
    }
    return $structuredFeatures;
}

/**
 * Structure an isolated feature
 * @param $feature
 * @return array
 */
function structureOneFeature($feature)
{
    $structuredFeature = array(
        "type" => "Feature",
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
function getFeatureCollection($features)
{
    $featuresCollection = array(
        "type" => "FeatureCollection",
        "features" => array(
            structureFeatures($features)
        )
    );
    return $featuresCollection;
}

/**
 * Get intersection data json from two different geometry features
 * @param $geoFeaturesJsonA
 * @param $geoFeaturesJsonB
 * @return mixed
 */
function intersection($geoFeaturesJsonA, $geoFeaturesJsonB)
{
    $polygonA = createPolygonFromJson($geoFeaturesJsonA);
    $polygonB = createPolygonFromJson($geoFeaturesJsonB);
    $intersectionData = $polygonA->intersection($polygonB);
    return $intersectionData->out('json', true);
}

/**
 * Check if a particular object point is IN the indicated polygon
 * @param $point
 * @param $polygon
 * @return mixed
 */
function isInPolygon($point, $polygon)
{
    return $polygon->pointInPolygon($point);
}

/**
 * Create a point geometry object from coordinates (latitude, longitude)
 * @param $latitude
 * @param $longitude
 * @return bool|GeometryCollection|mixed
 */
function createPoint($latitude, $longitude)
{
    try {
        $point = geoPHP::load('POINT(' . $latitude . ' ' . $longitude . ')', 'wkt');
    } catch (Exception $exception) {
        echo $exception->getMessage();
        return null;
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
function isPointInQuadrantFeatures($features, $latitude, $longitude)
{
    $timeZone = null;
    $point = createPoint($latitude, $longitude);
    foreach ($features['features'] as $feature) {
        $polygon = createPolygonFromJson(createPolygonJsonFromPoints($feature['coordinates']));
        if (isInPolygon($point, $polygon)) {
            $timeZone = $feature['properties']['tzid'];
            break;
        }
    }
    return $timeZone;
}