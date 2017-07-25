<?php

include_once('./lib/geoPHP.inc');

class Utils
{
    public static function createPolygonJsonFromPoints($polygonPoints)
    {
        $polygonData = array(
            'type' => "Polygon",
            'coordinates' => $polygonPoints
        );

        return $polygonData;
    }

    public static function createPolygonFromPoints($polygonPoints)
    {
        $polygonData = self::createPolygonJsonFromPoints($polygonPoints);
        return geoPHP::load(json_encode($polygonData), 'json');
    }

    public static function createPolygonFromJson($polygonJson)
    {
        return geoPHP::load(json_encode($polygonJson), 'json');
    }

    public static function adaptQuadrantBoundsToPolygon($quadrantBounds)
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

    public static function getQuadrantPolygon($quadrantBounds)
    {
        $polygonPoints = self::adaptQuadrantBoundsToPolygon($quadrantBounds);
        return self::createPolygonFromPoints($polygonPoints);
    }

    public static function structureFeatures($features)
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

    public static function getFeatureCollection($features)
    {
        $featuresCollection = array(
            "type" => "FeatureCollection",
            "features" => array(
                self::structureFeatures($features)
            )
        );
        return $featuresCollection;
    }

    public static function intersection($geoFeaturesJsonA, $geoFeaturesJsonB)
    {
        $polygonA = self::createPolygonFromJson($geoFeaturesJsonA);
        $polygonB = self::createPolygonFromJson($geoFeaturesJsonB);
        $intersectionData = $polygonA->intersection($polygonB);
        return $intersectionData->out('json', true);
    }
}