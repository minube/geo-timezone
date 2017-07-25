<?php

include "QuadrantTree.php";
include "Utils.php";


class QuadrantIndexer extends QuadrantTree
{
    const DEFAULT_DATA_SOURCE_PATH = "./data/downloads/timezones/dist/timezones.json";
    const TARGET_INDEX_PERCENT = 0.5; //0.98;
    const DEFAULT_ZONE_RESULT = -1;
    const LEVEL_DELIMITER_SYMBOL = ".";
    const TOTAL_LEVELS = 4;

    protected $dataSourcePath;
    protected $dataSource;
    protected $timezones = array();
    protected $lookup = array();
    protected $currentQuadrants = array();


    protected function initCurrentQuadrants()
    {
        $this->currentQuadrants = array(
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

    protected function inspectTimeZones($timezonesToInspect, $quadrantPolygon)
    {
        $intersectedZones = [];
        $foundExactMatch = false;
        for ($inspectIdx = count($timezonesToInspect) - 1; $inspectIdx >= 0; $inspectIdx--) {
            $zoneIdx = $timezonesToInspect[$inspectIdx];
            $zonePointsJson = $this->dataSource['features'][$zoneIdx]['geometry'];
            $zonePolygon = Utils::createPolygonFromJson($zonePointsJson);
            if ($zonePolygon->intersects($quadrantPolygon)) {
                if ($quadrantPolygon->within($zonePolygon)) {
                    $intersectedZones = $zoneIdx;
                    $foundExactMatch = true;
                    break;
                } else {
                    $intersectedZones[] = $zoneIdx;
                }
            }
        }

        return array(
            'foundExactMatch' => $foundExactMatch,
            'intersectedZones' => $intersectedZones
        );
    }

    protected function getNextQuadrants($zoneId, $intersectedZones, $quadrantBounds)
    {
        $topRight = array(
            'id' => $zoneId . '.a',
            'timezones' => $intersectedZones,
            'bounds' => [
                (float)($quadrantBounds[0] + $quadrantBounds[2]) / 2,
                (float)($quadrantBounds[1] + $quadrantBounds[3]) / 2,
                $quadrantBounds[2],
                $quadrantBounds[3]
            ]
        );

        $topLeft = array(
            'id' => $zoneId . '.b',
            'timezones' => $intersectedZones,
            'bounds' => [
                $quadrantBounds[0],
                (float)($quadrantBounds[1] + $quadrantBounds[3]) / 2.0,
                (float)($quadrantBounds[0] + $quadrantBounds[2]) / 2.0,
                $quadrantBounds[3]
            ]
        );

        $bottomLeft = array(
            'id' => $zoneId . '.c',
            'timezones' => $intersectedZones,
            'bounds' => [
                $quadrantBounds[0],
                $quadrantBounds[1],
                (float)($quadrantBounds[0] + $quadrantBounds[2]) / 2.0,
                (float)($quadrantBounds[1] + $quadrantBounds[3]) / 2.0
            ]
        );

        $bottomRight = array(
            'id' => $zoneId . '.d',
            'timezones' => $intersectedZones,
            'bounds' => [
                (float)($quadrantBounds[0] + $quadrantBounds[2]) / 2.0,
                $quadrantBounds[1],
                $quadrantBounds[2],
                (float)($quadrantBounds[1] + $quadrantBounds[3]) / 2.0
            ]
        );

        return array($topRight, $topLeft, $bottomLeft, $bottomRight);
    }

    protected function detectTimeZonesToInspect($previousTimezone)
    {
        $timezonesToInspect = [];
        if (isset($previousTimezone['timezones'])) {
            $timezonesToInspect = $previousTimezone['timezones'];
        } else {
            for ($zoneIdx = count($this->dataSource['features']) - 1; $zoneIdx >= 0; $zoneIdx--) {
                $timezonesToInspect[] = $zoneIdx;
            }
        }
        return $timezonesToInspect;
    }

    protected function updateLookup($zoneResult, $curQuadrantId)
    {
        $levelPath = explode(self::LEVEL_DELIMITER_SYMBOL, $curQuadrantId);

        if ($zoneResult !== self::DEFAULT_ZONE_RESULT) {
            $this->addLevelToLookup($zoneResult, $levelPath);
        } else {
            $this->removeLevelFromLookup($levelPath);
        }
    }

    protected function getFeatures($intersectionResult, $curQuadrant)
    {
        $features = [];
        for ($zoneIdx = count($intersectionResult['intersectedZones']) - 1; $zoneIdx >= 0; $zoneIdx--) {
            $tzIdx = $intersectionResult['intersectedZones'][$zoneIdx];
            $quadrantBoundsGeoJson = Utils::createPolygonJsonFromPoints(
                Utils::adaptQuadrantBoundsToPolygon($curQuadrant['bounds'])
            );
            $intersectedArea = Utils::intersection(
                $this->dataSource['features'][$tzIdx]['geometry'],
                $quadrantBoundsGeoJson);
            if ($intersectedArea) {
                $intersectedArea['properties']['tzid'] = $this->timezones[$tzIdx];
                $features[] = $intersectedArea;
            }
        }
        return $features;
    }

    protected function analyzeIntersectedZones($intersectionResult, $curQuadrant, $lastLevelFlag)
    {
        $zoneResult = self::DEFAULT_ZONE_RESULT;
        $nextQuadrants = [];
        if (count($intersectionResult['intersectedZones']) === 1 && $intersectionResult['foundExactMatch']) {
            $zoneResult = $intersectionResult['intersectedZones'];
        } elseif (count($intersectionResult['intersectedZones']) > 0) {
            if ($lastLevelFlag) {
                $features = $this->getFeatures($intersectionResult, $curQuadrant);
                $featuresCollection = Utils::getFeatureCollection($features);
                $featuresPath = QuadrantTree::DATA_DIRECTORY .
                    str_replace('.', "/", $curQuadrant['id']) . "/";
                $this->writeGeoFeaturesToJson($featuresCollection, $featuresPath);
                $zoneResult = 'f';
            } else {
                $nextQuadrants = $this->getNextQuadrants(
                    $curQuadrant['id'],
                    $intersectionResult['intersectedZones'],
                    $curQuadrant['bounds']
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
            'nextQuadrants' => $nextQuadrants
        );
    }

    protected function validIndexingPercentage($curLevel, $numZones)
    {
        $expectedAtLevel = pow(self::TOTAL_LEVELS, $curLevel + 1);
        $curPctIndexed = ($expectedAtLevel - $numZones) / $expectedAtLevel;
        return $curPctIndexed < self::TARGET_INDEX_PERCENT;
    }

    protected function indexNextQuadrants($lastLevelFlag)
    {
        $nextQuadrants = array();
        for ($levelIdx = count($this->currentQuadrants) - 1; $levelIdx >= 0; $levelIdx--) {
            $curQuadrant = $this->currentQuadrants[$levelIdx];
            $analyzedResults = $this->analyzeCurrentQuadrant($lastLevelFlag, $curQuadrant);
            $this->updateLookup($analyzedResults['zoneResult'], $curQuadrant['id']);
            $nextQuadrants = array_merge($nextQuadrants, $analyzedResults['nextQuadrants']);
        }
        return $nextQuadrants;
    }

    protected function generateIndexes()
    {
        $this->initCurrentQuadrants();
        $curLevel = 1;
        $numZones = 16;
        $lastLevel = 0;

        while ($this->validIndexingPercentage($curLevel, $numZones)) {
            $curLevel += 1;
            $this->currentQuadrants = $this->indexNextQuadrants($lastLevel);
            $numZones = count($this->currentQuadrants);
        }
        $this->currentQuadrants = $this->indexNextQuadrants(1);
        $this->writeQuadrantTreeJson();
    }

    protected function createDirectoryTree($path)
    {
        $directories = explode(QuadrantTree::DATA_DIRECTORY, $path)[1];
        $directories = explode("/", $directories);
        $currentDir = QuadrantTree::DATA_DIRECTORY;
        foreach ($directories as $dir) {
            $currentDir = $currentDir . "/" . $dir;
            if (!is_dir($currentDir)) {
                mkdir($currentDir);
            }
        }
    }

    protected function writeGeoFeaturesToJson($features, $path)
    {
        $writtenBytes = false;
        $this->createDirectoryTree($path);
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

    /**
     * @param $lastLevelFlag
     * @param $curQuadrant
     * @return array
     */
    protected function analyzeCurrentQuadrant($lastLevelFlag, $curQuadrant)
    {
        $quadrantBounds = $curQuadrant['bounds'];
        $quadrantPolygon = Utils::getQuadrantPolygon($quadrantBounds);
        $timezonesToInspect = $this->detectTimeZonesToInspect($curQuadrant);
        $intersectionResult = $this->inspectTimeZones($timezonesToInspect, $quadrantPolygon);
        $analyzedResults = $this->analyzeIntersectedZones($intersectionResult, $curQuadrant, $lastLevelFlag);
        return $analyzedResults;
    }

    /**
     * @param $zoneResult
     * @param $levelPath
     * @return mixed
     */
    protected function addLevelToLookup($zoneResult, $levelPath)
    {
        $level = &$this->lookup;
        foreach ($levelPath as $levelId) {
            $level = &$level[$levelId];
        }
        $level = $zoneResult;
    }

    /**
     * @param $levelPath
     */
    protected function removeLevelFromLookup($levelPath)
    {
        $level = &$this->lookup;
        $levelId = "a";
        foreach ($levelPath as $idx => $levelId) {
            if (isset($level[$levelId])) {
                if ($idx < count($levelPath) - 1) {
                    $level = &$level[$levelId];
                }
            }
        }
        unset($level[$levelId]);
    }
}
