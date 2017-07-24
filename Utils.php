<?php

include_once('./lib/geoPHP.inc');

class Utils
{
    protected function createPolygonJsonFromPoints($polygonPoints)
    {
        $polygonData = array(
            'type' => "Polygon",
            'coordinates' => $polygonPoints
        );

        return $polygonData;
    }

    protected function createPolygonFromPoints($polygonPoints)
    {
        $polygonData = $this->createPolygonJsonFromPoints($polygonPoints);
        return geoPHP::load(json_encode($polygonData), 'json');
    }

    protected function createPolygonFromJson($polygonJson)
    {
        return geoPHP::load(json_encode($polygonJson), 'json');
    }

    protected function adaptQuadrantBoundsToPolygon($curBounds)
    {
        return array(
            array(
                array($curBounds[0], $curBounds[1]),
                array($curBounds[0], $curBounds[3]),
                array($curBounds[2], $curBounds[3]),
                array($curBounds[2], $curBounds[1]),
                array($curBounds[0], $curBounds[1])
            )
        );
    }

    protected function getQuadrantBoundsPolygon($curBounds)
    {
        $polygonPoints = $this->adaptQuadrantBoundsToPolygon($curBounds);
        return $this->createPolygonFromPoints($polygonPoints);
    }

    protected function structureFeatures($features)
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

    protected function getFeatureCollection($features)
    {
        $featuresCollection = array(
            "type" => "FeatureCollection",
            "features" => array(
                $this->structureFeatures($features)
            )
        );
        return $featuresCollection;
    }

    protected function intersection($geoFeaturesJsonA, $geoFeaturesJsonB)
    {
        $polygonA = $this->createPolygonFromJson($geoFeaturesJsonA);
        $polygonB = $this->createPolygonFromJson($geoFeaturesJsonB);
        $intersectionData = $polygonA->intersection($polygonB);
        return $intersectionData->out('json', true);
    }
}