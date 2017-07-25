<?php

include_once('./lib/geoPHP.inc');

function createPolygonJsonFromPoints($polygonPoints)
{
    $polygonData = array(
        'type' => "Polygon",
        'coordinates' => $polygonPoints
    );

    return $polygonData;
}

function createPolygonFromPoints($polygonPoints)
{
    $polygonData = self::createPolygonJsonFromPoints($polygonPoints);
    return geoPHP::load(json_encode($polygonData), 'json');
}

function createPolygonFromJson($polygonJson)
{
    return geoPHP::load(json_encode($polygonJson), 'json');
}

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

function getQuadrantPolygon($quadrantBounds)
{
    $polygonPoints = self::adaptQuadrantBoundsToPolygon($quadrantBounds);
    return self::createPolygonFromPoints($polygonPoints);
}

function structureFeatures($features)
{
    $structuredFeatures = array();
    foreach ($features as $feature) {
        $structuredFeatures[] = array(
            "type" => "Feature",
            "geometry" => array(
                "type" => $feature['type'],
                "coordinates" => $feature['coordinates']
            ),
            "properties" => $feature['properties']
        );
    }
    return $structuredFeatures;
}

function getFeatureCollection($features)
{
    $featuresCollection = array(
        "type" => "FeatureCollection",
        "features" => array(
            self::structureFeatures($features)
        )
    );
    return $featuresCollection;
}

function intersection($geoFeaturesJsonA, $geoFeaturesJsonB)
{
    $polygonA = self::createPolygonFromJson($geoFeaturesJsonA);
    $polygonB = self::createPolygonFromJson($geoFeaturesJsonB);
    $intersectionData = $polygonA->intersection($polygonB);
    return $intersectionData->out('json', true);
}