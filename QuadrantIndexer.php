<?php

include "QuadrantTree.php";
include_once('/media/ana/Datos/testing_repos/geoPHP/geoPHP.inc');


class QuadrantIndexer extends QuadrantTree
{
    const DEFAULT_DATA_SOURCE_PATH = "./data/downloads/timezones/dist/timezones.json";
    const TARGET_INDEX_PERCENT = 0.5;

    protected $dataSourcePath;
    protected $dataSource;
    protected $timezones = array();
    protected $lookup = array();
    protected $zoneLevels = array();

    protected function initZoneLevels()
    {
        $this->zoneLevels = array(
            array(
                "id" => QuadrantTree::LEVEL_A,
                "bounds" => array(0, 0, QuadrantTree::ABS_LONGITUDE_LIMIT, QuadrantTree::ABS_LATITUDE_LIMIT)
            ),
            array(
                "id" => QuadrantTree::LEVEL_B,
                "bounds" => array(-QuadrantTree::ABS_LONGITUDE_LIMIT, 0, 0, QuadrantTree::ABS_LATITUDE_LIMIT)
            ),
            array(
                "id" => QuadrantTree::LEVEL_C,
                "bounds" => array(-QuadrantTree::ABS_LONGITUDE_LIMIT, -QuadrantTree::ABS_LATITUDE_LIMIT, 0, 0)
            ),
            array(
                "id" => QuadrantTree::LEVEL_D,
                "bounds" => array(0, -QuadrantTree::ABS_LATITUDE_LIMIT, QuadrantTree::ABS_LONGITUDE_LIMIT, 0)
            )
        );
    }

    protected function readDataSource()
    {
        echo "Reading timezone description json file.\n";
        $this->dataSourcePath = is_null($this->dataSourcePath) ? self::DEFAULT_DATA_SOURCE_PATH : $this->dataSourcePath;
        $jsonData = file_get_contents($this->dataSourcePath);
        $this->dataSource = json_decode($jsonData, true);
    }

    protected function setTimezonesArray()
    {
        foreach ($this->dataSource['features'] as $feature) {
            $this->timezones[] = $feature['properties']['tzid'];
        }
    }

    protected function inspectZones($timezonesToInspect, $geoBoundsPolygon)
    {
        echo "Inspecting zones...\n";
        $intersectedZones = [];
        $foundExactMatch = false;
        for ($inspectIdx = count($timezonesToInspect) - 1; $inspectIdx >= 0; $inspectIdx--) {
            $zoneIdx = $timezonesToInspect[$inspectIdx];
            $zonePointsJson = $this->dataSource['features'][$zoneIdx]['geometry'];
            echo $inspectIdx . ": " . $this->dataSource['features'][$zoneIdx]['properties']['tzid'] . "\n";
            $zonePolygon = $this->createPolygonFromJson($zonePointsJson);
            if ($zonePolygon->intersects($geoBoundsPolygon)) {
                echo "-> Inside the zone! -> ";
                if ($geoBoundsPolygon->within($zonePolygon)) {
                    echo "Exact match!\n";
                    $intersectedZones = [$zoneIdx];
                    $foundExactMatch = true;
                    break;
                } else {
                    echo "Add zone\n";
                    $intersectedZones[] = $zoneIdx;
                }
            }
        }

        return array(
            'foundExactMatch' => $foundExactMatch,
            'intersectedZones' => $intersectedZones
        );
    }

    protected function intersection($geoFeaturesJsonA, $geoFeaturesJsonB)
    {
        $polygonA = $this->createPolygonFromJson($geoFeaturesJsonA);
        $polygonB = $this->createPolygonFromJson($geoFeaturesJsonB);
        $intersectionData = $polygonA->intersection($polygonB);
        return $intersectionData->out('json', true);
    }

    protected function getNextZones($zoneId, $intersectedZones, $curBounds)
    {
        $topRight = array(
            'id' => $zoneId . '.a',
            'timezones' => $intersectedZones,
            'bounds' => [
                ($curBounds[0] + $curBounds[2]) / 2,
                ($curBounds[1] + $curBounds[3]) / 2,
                $curBounds[2],
                $curBounds[3]
            ]
        );

        $topLeft = array(
            'id' => $zoneId . '.b',
            'timezones' => $intersectedZones,
            'bounds' => [
                $curBounds[0],
                ($curBounds[1] + $curBounds[3]) / 2,
                ($curBounds[0] + $curBounds[2]) / 2,
                $curBounds[3]
            ]
        );

        $bottomLeft = array(
            'id' => $zoneId . '.c',
            'timezones' => $intersectedZones,
            'bounds' => [
                $curBounds[0],
                $curBounds[1],
                ($curBounds[0] + $curBounds[2]) / 2,
                ($curBounds[1] + $curBounds[3]) / 2
            ]
        );

        $bottomRight = array(
            'id' => $zoneId . '.d',
            'timezones' => $intersectedZones,
            'bounds' => [
                ($curBounds[0] + $curBounds[2]) / 2,
                $curBounds[1],
                $curBounds[2],
                ($curBounds[1] + $curBounds[3]) / 2
            ]
        );

        return array($topRight, $topLeft, $bottomLeft, $bottomRight);
    }

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

    protected function detectTimeZonesToInspect($previousTimezone)
    {
        $timezonesToInspect = [];
        if (isset($previousTimezone['timezones'])) {
            $timezonesToInspect = $previousTimezone;
        } else {
            for ($zoneIdx = count($this->dataSource['features']) - 1; $zoneIdx >= 0; $zoneIdx--) {
                $timezonesToInspect[] = $zoneIdx;
            }
        }
        return $timezonesToInspect;
    }

    protected function adaptGeoBoundsToPolygon($curBounds)
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

    protected function getGeoBoundsPolygon($curBounds)
    {
        $polygonPoints = $this->adaptGeoBoundsToPolygon($curBounds);
        return $this->createPolygonFromPoints($polygonPoints);
    }

    protected function updateLookup($zoneResult, $curZoneId)
    {
        // TODO check it
        if ($zoneResult !== -1) {
            $this->lookup[] = array($curZoneId => $zoneResult);
            //_ . set(data . lookup, $curZone . id, $zoneResult)
        } else {
            unset($this->lookup[$curZoneId]);
            //_ .unset(data . lookup, $curZone . id)
        }
        print_r($this->lookup);
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

    protected function structureFeatures($features)
    {
        $structuredFeatures = array();
        foreach($features as $feature) {
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

    protected function getFeatures($intersectionResult, $curZone)
    {
        $features = [];
        for ($zoneIdx = count($intersectionResult['intersectedZones']) - 1; $zoneIdx >= 0; $zoneIdx--) {
            $tzIdx = $intersectionResult['intersectedZones'][$zoneIdx];
            $curBoundsGeoJson = $this->createPolygonJsonFromPoints($this->adaptGeoBoundsToPolygon($curZone['bounds']));
            $intersectedArea = $this->intersection(
                $this->dataSource['features'][$tzIdx]['geometry'],
                $curBoundsGeoJson);
            if ($intersectedArea) {
                $intersectedArea['properties']['tzid'] = $this->timezones[$tzIdx];
                $features[] = $intersectedArea;
            }
        }
        return $features;
    }

    protected function analyzeIntersectedZones($intersectionResult, $curZone, $lastLevelFlag)
    {
        var_dump($lastLevelFlag);
        $zoneResult = -1;
        $nextZones = [];
        if (count($intersectionResult['intersectedZones']) === 1 && $intersectionResult['foundExactMatch']) {
            $zoneResult = $intersectionResult['intersectedZones'][0];
        } elseif (count($intersectionResult['intersectedZones']) > 0) {
            if ($lastLevelFlag === 1) {
                echo "Last level!\n";
                $features = $this->getFeatures($intersectionResult, $curZone);
                $featuresCollection = $this->getFeatureCollection($features);
                $featuresPath = QuadrantTree::DATA_DIRECTORY . str_replace('/\./g', "/", $curZone['id']);
                $this->writeGeoFeaturesToJson($featuresCollection, $featuresPath);
                $zoneResult = 'f';
            } else {
                echo "continue to the next level!\n";
                $nextZones = $this->getNextZones(
                    $curZone['id'],
                    $intersectionResult['intersectedZones'],
                    $curZone['bounds']
                );
                echo "updating nextZones\n";
                print_r($nextZones);
                $zoneResult = array(
                    'a' => $intersectionResult['intersectedZones'],
                    'b' => $intersectionResult['intersectedZones'],
                    'c' => $intersectionResult['intersectedZones'],
                    'd' => $intersectionResult['intersectedZones']
                );
            }
        }
        return array(
            'zoneResult' => $zoneResult,
            'nextZones' => $nextZones
        );
    }

    protected function validIndexingPercentage($curLevel, $numZones)
    {
        $expectedAtLevel = pow(4, $curLevel + 1);
        $curPctIndexed = ($expectedAtLevel - count($numZones)) / $expectedAtLevel;
        echo "checking validIndexingPercentage()...  " . $curPctIndexed . " --> ";
        var_dump($curPctIndexed > self::TARGET_INDEX_PERCENT);
        return $curPctIndexed > self::TARGET_INDEX_PERCENT;
    }

    protected function indexNextZones($lastLevelFlag)
    {
        echo "Indexing next quadrant...\n";
        $nextZones = array();
        for ($levelIdx = count($this->zoneLevels) - 1; $levelIdx >= 0; $levelIdx--) {
            $curZone = $this->zoneLevels[$levelIdx];
            echo "Cuadrant to be analyzed: \n";
            print_r($curZone);
            $curBounds = $curZone['bounds'];
            $geoBoundsPolygon = $this->getGeoBoundsPolygon($curBounds);
            $timezonesToInspect = $this->detectTimeZonesToInspect($curZone);
            $intersectionResult = $this->inspectZones($timezonesToInspect, $geoBoundsPolygon);
            $analyzedResults = $this->analyzeIntersectedZones($intersectionResult, $curZone, $lastLevelFlag);
            $nextZones[] = $analyzedResults['nextZones'];
            $this->updateLookup($analyzedResults['zoneResult'], $curZone['id']);
        }
        return $nextZones;
    }

    protected function generateIndexes()
    {
        echo "Indexing timezones...\n";
        $this->initZoneLevels();
        $curLevel = 1;
        $numZones = 0;
        $lastLevel = 0;

        while ($this->validIndexingPercentage($curLevel, $numZones)) {
            $curLevel += 1;
            $this->zoneLevels = $this->indexNextZones($lastLevel);
            $numZones = count($this->zoneLevels);
        }

        $this->zoneLevels = $this->indexNextZones(true);
        $this->writeQuadrantTreeJson();
    }

    protected function writeGeoFeaturesToJson($features, $path)
    {
        $writtenBytes = false;
        if(!is_dir($path)) {
            mkdir($path);
        }
        if ($path && is_writable($path)) {
            $full = $path . DIRECTORY_SEPARATOR . QuadrantTree::GEO_FEATURE_FILENAME;
            $writtenBytes = file_put_contents($full, json_encode($features));
        }
        return $writtenBytes;
    }

    protected function buildTree()
    {
        $tree = array(
            'timezones' => $this->timezones,
            'lookup' => $this->lookup
        );
        return $tree;
    }

    protected function writeQuadrantTreeJson()
    {
        $writtenBytes = false;
        $tree = $this->buildTree();
        $path = realpath(QuadrantTree::DATA_DIRECTORY);
        if ($path && is_writable($path)) {
            $full = $path . DIRECTORY_SEPARATOR . QuadrantTree::DATA_TREE_FILENAME;
            $writtenBytes = file_put_contents($full, json_encode($tree));
        }
        return $writtenBytes;
    }

    public function setGeoDataSource($path)
    {
        $this->dataSourcePath = $path;
    }

    public function createQuadrantTreeData()
    {
        $this->readDataSource();
        $this->setTimezonesArray();
        $this->generateIndexes();
    }
}
