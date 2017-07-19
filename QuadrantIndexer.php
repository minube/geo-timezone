<?php

include "QuadrantTree.php";

class QuadrantIndexer extends QuadrantTree
{
    const DEFAULT_DATA_SOURCE_PATH = "./data/downloads/timezones/dist/timezones.json";
    const TARGET_INDEX_PERCENT = 0.5;

    protected $dataSourcePath;
    protected $dataSource;
    protected $timezones = array();
    protected $lookup = array();
    protected $zoneLevels = array(
        array(
            "id" => QuadrantTree::LEVEL_A,
            "bounds" => array(0, 0, QuadrantTree::ABS_LONGITUDE_LIMIT, QuadrantTree::ABS_LATITUDE_LIMIT)
        ),
        array(
            "id" => QuadrantTree::LEVEL_B,
            "bounds" => array((-1) * QuadrantTree::ABS_LONGITUDE_LIMIT, 0, 0, QuadrantTree::ABS_LATITUDE_LIMIT)
        ),
        array(
            "id" => QuadrantTree::LEVEL_C,
            "bounds" => array((-1) * QuadrantTree::ABS_LONGITUDE_LIMIT, (-1) * QuadrantTree::ABS_LATITUDE_LIMIT, 0, 0)
        ),
        array(
            "id" => QuadrantTree::LEVEL_D,
            "bounds" => array(0, (-1) * QuadrantTree::ABS_LATITUDE_LIMIT, QuadrantTree::ABS_LONGITUDE_LIMIT, 0)
        )
    );

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

    protected function inspectZones($timezonesToInspect, $curBoundsGeoJson)
    {
        $intersectedZones = [];
        $foundExactMatch = false;
        $boundsGeoPolygon = geoPHP::load($curBoundsGeoJson, 'json');
        for ($j = count($timezonesToInspect) - 1; $j >= 0; $j--) {
            $curZoneIdx = $timezonesToInspect[$j];
            $curZoneGeoJson = $this->dataSource['features'][$curZoneIdx]['geometry'];
            $curZoneGeoPolygon = geoPHP::load($curZoneGeoJson, 'json');
            if (geoPhp::intersects($curZoneGeoPolygon, $boundsGeoPolygon)) {
                if (geoPhp::within($curZoneGeoPolygon, $boundsGeoPolygon)) {
                    $intersectedZones = [$curZoneIdx];
                    $foundExactMatch = true;
                    break;
                } else {
                    $intersectedZones[] = $curZoneIdx;
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
            'tzs' => $intersectedZones,
            'bounds' => [
                ($curBounds[0] + $curBounds[2]) / 2,
                ($curBounds[1] + $curBounds[3]) / 2,
                $curBounds[2],
                $curBounds[3]
            ]
        );

        $topLeft = array(
            'id' => $zoneId . '.b',
            'tzs' => $intersectedZones,
            'bounds' => [
                $curBounds[0],
                ($curBounds[1] + $curBounds[3]) / 2,
                ($curBounds[0] + $curBounds[2]) / 2,
                $curBounds[3]
            ]
        );

        $bottomLeft = array(
            'id' => $zoneId . '.c',
            'tzs' => $intersectedZones,
            'bounds' => [
                $curBounds[0],
                $curBounds[1],
                ($curBounds[0] + $curBounds[2]) / 2,
                ($curBounds[1] + $curBounds[3]) / 2
            ]
        );

        $bottomRight = array(
            'id' => $zoneId . '.d',
            'tzs' => $intersectedZones,
            'bounds' => [
                ($curBounds[0] + $curBounds[2]) / 2,
                $curBounds[1],
                $curBounds[2],
                ($curBounds[1] + $curBounds[3]) / 2
            ]
        );

        return array($topRight, $topLeft, $bottomLeft, $bottomRight);
    }

    protected function createPolygon($polygonPoints)
    {
        return geoPHP::load($polygonPoints, 'wkt');
    }

    protected function detectTimeZonesToInspect($previousTimezone)
    {
        $timezonesToInspect = [];
        if ($previousTimezone) {
            $timezonesToInspect = $previousTimezone;
        } else {
            for ($j = count($this->dataSource['features']) - 1; $j >= 0; $j--) {
                $timezonesToInspect[] = $j;
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
        return $this->createPolygon($polygonPoints);
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
            print_r($curZone);
            die;
            $curBounds = $curZone['bounds'];
            $curBoundsGeoJson = $this->getGeoBoundsPolygon($curBounds);
            $timezonesToInspect = $curZone['tzs'];
            if (!$lastLevel) {
                $timezonesToInspect = $this->detectTimeZonesToInspect($curZone['tzs']);
            }
            $intersectionResult = $this->inspectZones($timezonesToInspect, $curBoundsGeoJson);
            $analyzedResults = $this->analyzeIntersectedZones($intersectionResult, $curZone, $lastLevel);
            $nextZones[] = $analyzedResults['nextZones'];
            $this->updateLookup($analyzedResults['zoneResult'], $curZone['id']);
        }
        return $nextZones;
    }

    protected function generateIndexes()
    {
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
