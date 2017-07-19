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
        echo "Inside inspectZones()\n";
        $intersectedZones = [];
        $foundExactMatch = false;
        for ($inspectIdx = count($timezonesToInspect) - 1; $inspectIdx >= 0; $inspectIdx--) {
            echo $inspectIdx . "\n";
            $zoneIdx = $timezonesToInspect[$inspectIdx];
            $zonePointsJson = $this->dataSource['features'][$zoneIdx]['geometry'];
            $zonePolygon = $this->createPolygonFromJson($zonePointsJson);
            var_dump($geoBoundsPolygon->intersects($zonePolygon));
            if ($geoBoundsPolygon->intersects($zonePolygon)) { //$geoBoundsPolygon->asText())) {
                echo "Inside the zone!";
                if ($geoBoundsPolygon->within($zonePolygon->asText())) {
                    echo "Exact match!";
                    $intersectedZones = [$zoneIdx];
                    $foundExactMatch = true;
                    break;
                } else {
                    echo "add zone";
                    $intersectedZones[] = $zoneIdx;
                }
            }
        }

        return array(
            'foundExactMatch' => $foundExactMatch,
            'intersectedZones' => $intersectedZones
        );
    }

    protected function intersection($geoFeaturesA, $geoFeaturesB)
    {
        //TODO COMPLETE!
        $intersection = array();
        return $intersection;
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

    protected function createPolygonFromPoints($polygonPoints)
    {
        $polygonData = array(
            'type' => "Polygon",
            'coordinates' => $polygonPoints
        );
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

    protected function getGeoBoundsPolygon($curBounds)
    {
        $polygonPoints = array(
            array($curBounds[0], $curBounds[1]),
            array($curBounds[0], $curBounds[3]),
            array($curBounds[2], $curBounds[3]),
            array($curBounds[2], $curBounds[1]),
            array($curBounds[0], $curBounds[1])
        );
        return $this->createPolygonFromPoints($polygonPoints);
    }

    protected function updateLookup($zoneResult, $curZoneId)
    {
        // TODO complete
        if ($zoneResult !== -1) {
            $this->lookup[] = $curZoneId;
            $this->lookup[] = $zoneResult;
            //_ . set(data . lookup, $curZone . id, $zoneResult)
        } else {
            //_ .unset(data . lookup, $curZone . id)
        }
    }

    protected function getFeatureCollection($features)
    {
        // TODO COMPLETE
        return $features;
    }

    protected function getFeatures($intersectionResult, $curZone)
    {
        $features = [];
        for ($zoneIdx = count($intersectionResult['intersectedZones']) - 1; $zoneIdx >= 0; $zoneIdx--) {
            $tzIdx = $intersectionResult['intersectedZones'][$zoneIdx];
            $curBoundsGeoJson = $this->getGeoBoundsPolygon($curZone['bounds']);
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

    protected function analyzeIntersectedZones($intersectionResult, $curZone, $lastLevel)
    {
        $zoneResult = -1;
        $nextZones = [];
        if (count($intersectionResult['intersectedZones']) === 1 && $intersectionResult['foundExactMatch']) {
            $zoneResult = $intersectionResult['intersectedZones'][0];
        } elseif (count($intersectionResult['intersectedZones']) > 0) {
            if ($lastLevel) {
                $features = $this->getFeatures($intersectionResult, $curZone);
                $areaGeoJson = $this->getFeatureCollection($features);
                $path = QuadrantTree::DATA_DIRECTORY . '/' . str_replace('/\./g', '/', $curZone['id']);
                $this->writeGeoFeaturesJson($areaGeoJson, $path);
                $zoneResult = 'f';
            } else {
                $nextZones = $this->getNextZones(
                    $curZone['id'],
                    $intersectionResult['intersectedZones'],
                    $curZone['bounds']
                );
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
        return $curPctIndexed < self::TARGET_INDEX_PERCENT;
    }

    protected function indexNextZones($lastLevel)
    {
        $nextZones = array();
        for ($levelIdx = count($this->zoneLevels) - 1; $levelIdx >= 0; $levelIdx--) {
            $curZone = $this->zoneLevels[$levelIdx];
            $curBounds = $curZone['bounds'];
            $geoBoundsPolygon = $this->getGeoBoundsPolygon($curBounds);
            $timezonesToInspect = $this->detectTimeZonesToInspect($curZone);
            $intersectionResult = $this->inspectZones($timezonesToInspect, $geoBoundsPolygon);
            print_r($intersectionResult);
            $analyzedResults = $this->analyzeIntersectedZones($intersectionResult, $curZone, $lastLevel);
            $nextZones[] = $analyzedResults['nextZones'];
            $this->updateLookup($analyzedResults['zoneResult'], $curZone['id']);
        }
        return $nextZones;
    }

    protected function generateIndexes()
    {
        $this->initZoneLevels();
        $curLevel = 1;
        $numZones = 0;
        $lastLevel = false;
        while ($this->validIndexingPercentage($curLevel, $numZones)) {
            $curLevel++;
            $this->zoneLevels = $this->indexNextZones($lastLevel);
            $numZones = count($this->zoneLevels);
        }

        $this->zoneLevels = $this->indexNextZones(true);
        $this->writeQuadrantTreeJson();
    }

    protected function writeGeoFeaturesJson($jsonData, $path)
    {
        $writtenBytes = false;
        $path = realpath($path);
        if ($path && is_writable($path)) {
            $full = $path . DIRECTORY_SEPARATOR . QuadrantTree::GEO_FEATURE_FILENAME;
            $writtenBytes = file_put_contents($full, json_encode($jsonData));
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
