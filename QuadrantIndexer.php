<?php

include "QuadrantTree.php";


class QuadrantIndexer extends QuadrantTree
{
    const DEFAULT_DATA_SOURCE_PATH = "./data/downloads/timezones/dist/timezones.json";
    const TARGET_INDEX_PERCENT = 0.91;
    const DEFAULT_ZONE_RESULT = -1;
    const LEVEL_DELIMITER_SYMBOL = ".";
    const TOTAL_LEVELS = 4;

    protected $dataSourcePath;
    protected $dataSource;
    protected $timezones = array();
    protected $lookup = array();
    protected $currentQuadrants = array();


    /**
     * Initialize the current quadrants attribute for the first indexing iteration
     */
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

    /**
     * Read the new timezones json to be indexed
     */
    protected function readDataSource()
    {
        $this->dataSourcePath = is_null($this->dataSourcePath) ? self::DEFAULT_DATA_SOURCE_PATH : $this->dataSourcePath;
        $jsonData = file_get_contents($this->dataSourcePath);
        $this->dataSource = json_decode($jsonData, true);
    }

    /**
     * Save timezones values from the reference file (timezones json) to timezones array attribute
     */
    protected function setTimezonesArray()
    {
        foreach ($this->dataSource['features'] as $feature) {
            $this->timezones[] = $feature['properties']['tzid'];
        }
    }

    /**
     * Find the timezones that intersect with or are within the quadrant polygon
     * @param $timezonesToInspect
     * @param $quadrantPolygon
     * @return array
     */
    protected function whichTimeZonesIntersect($timezonesToInspect, $quadrantPolygon)
    {
        $intersectedZones = [];
        $foundExactMatch = false;
        for ($inspectIdx = count($timezonesToInspect) - 1; $inspectIdx >= 0; $inspectIdx--) {
            $zoneIdx = $timezonesToInspect[$inspectIdx];
            $zonePolygon = createPolygonFromJson($this->dataSource['features'][$zoneIdx]['geometry']);
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

    /**
     * Create new level of quadrants from the previous bounds, the intersected found timezones and the previous zone ID
     * @param $zoneId
     * @param $intersectedZones
     * @param $quadrantBounds
     * @return array
     */
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

    /**
     * Select timezones to find the intersections
     * @param $previousTimezone
     * @return array
     */
    protected function selectTimeZonesToInspect($previousTimezone)
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

    /**
     * Update the lookup table
     * @param $zoneResult
     * @param $curQuadrantId
     */
    protected function updateLookup($zoneResult, $curQuadrantId)
    {
        $levelPath = explode(self::LEVEL_DELIMITER_SYMBOL, $curQuadrantId);

        if ($zoneResult !== self::DEFAULT_ZONE_RESULT) {
            $this->addLevelToLookup($zoneResult, $levelPath);
        } else {
            $this->removeLevelFromLookup($levelPath);
        }
    }

    /**
     * Get intersection features from current quadrant and each intersected timezone areas
     * @param $intersectionResult
     * @param $curQuadrant
     * @return array
     */
    protected function getIntersectionFeatures($intersectionResult, $curQuadrant)
    {
        $features = [];
        for ($zoneIdx = count($intersectionResult['intersectedZones']) - 1; $zoneIdx >= 0; $zoneIdx--) {
            $tzIdx = $intersectionResult['intersectedZones'][$zoneIdx];
            $quadrantBoundsGeoJson = createPolygonJsonFromPoints(
                adaptQuadrantBoundsToPolygon($curQuadrant['bounds'])
            );
            $intersectedArea = intersection(
                $this->dataSource['features'][$tzIdx]['geometry'],
                $quadrantBoundsGeoJson);
            if ($intersectedArea) {
                $intersectedArea['properties']['tzid'] = $this->timezones[$tzIdx];
                $features[] = $intersectedArea;
            }
        }
        return $features;
    }

    /**
     * Find the associated zones to the current quadrants and the next quadrants to be evaluated
     * @param $intersectionResult
     * @param $curQuadrant
     * @param $lastLevelFlag
     * @return array
     */
    protected function getAssociatedZonesAndNextQuadrants($intersectionResult, $curQuadrant, $lastLevelFlag)
    {
        $zoneResult = self::DEFAULT_ZONE_RESULT;
        $nextQuadrants = [];
        if (count($intersectionResult['intersectedZones']) === 1 && $intersectionResult['foundExactMatch']) {
            $zoneResult = $intersectionResult['intersectedZones'];
        } elseif (count($intersectionResult['intersectedZones']) > 0) {
            if ($lastLevelFlag) {
                $features = $this->getIntersectionFeatures($intersectionResult, $curQuadrant);
                $featuresCollection = getFeatureCollection($features);
                $featuresPath = QuadrantTree::DATA_DIRECTORY . str_replace('.', "/", $curQuadrant['id']) . "/";
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

    /**
     * Check if the current indexing iteration should be carry on or not
     * @param $curLevel
     * @param $numQuadrants
     * @return bool
     */
    protected function validIndexingPercentage($curLevel, $numQuadrants)
    {
        $expectedAtLevel = pow(self::TOTAL_LEVELS, $curLevel + 1);
        $curPctIndexed = ($expectedAtLevel - $numQuadrants) / $expectedAtLevel;
        echo "Iteration " . $curLevel . "\n Num quadrants: " . $numQuadrants . "\n";
        echo " Indexing percentage: " . $curPctIndexed . "\n";
        return $curPctIndexed < self::TARGET_INDEX_PERCENT;
    }

    /**
     * Index current quadrants and get the new ones
     * @param $lastLevelFlag
     * @return array
     */
    protected function indexQuadrants($lastLevelFlag)
    {
        $nextQuadrants = array();
        for ($levelIdx = count($this->currentQuadrants) - 1; $levelIdx >= 0; $levelIdx--) {
            $curQuadrant = $this->currentQuadrants[$levelIdx];
            $nextStep = $this->findTimezonesAndNextQuadrants($lastLevelFlag, $curQuadrant);
            $this->updateLookup($nextStep['zoneResult'], $curQuadrant['id']);
            $nextQuadrants = array_merge($nextQuadrants, $nextStep['nextQuadrants']);
        }
        return $nextQuadrants;
    }

    /**
     * Main function that run all index processing
     */
    protected function generateIndexes()
    {
        $this->initCurrentQuadrants();
        $curLevel = 1;
        $numQuadrants = 16;
        $lastLevel = 0;

        while ($this->validIndexingPercentage($curLevel, $numQuadrants)) {
            $curLevel += 1;
            $this->currentQuadrants = $this->indexQuadrants($lastLevel);
            $numQuadrants = count($this->currentQuadrants);
        }
        echo "Last iteration... \n";
        $lastLevel = 1;
        $this->currentQuadrants = $this->indexQuadrants($lastLevel);
        echo "Writing quadrant tree json...\n";
        $this->writeQuadrantTreeJson();
    }

    /**
     * Create the directory tree
     * @param $path
     */
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

    /**
     * Create json file from timezone features
     * @param $features
     * @param $path
     * @return bool|int
     */
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

    /**
     * Build tree array to be save in a json file later
     * @return array
     */
    protected function buildTree()
    {
        $tree = array(
            'timezones' => $this->timezones,
            'lookup' => $this->lookup
        );
        return $tree;
    }

    /**
     * Write the quadrant tree in a json file
     * @return bool|int
     */
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

    /**
     * Set the input data source
     * @param $path
     */
    public function setGeoDataSource($path)
    {
        $this->dataSourcePath = $path;
    }

    /**
     * Main public function that starts all processing
     */
    public function createQuadrantTreeData()
    {
        echo "Reading data source...\n";
        $this->readDataSource();
        echo "Saving timezones array...\n";
        $this->setTimezonesArray();
        echo "Generating indexes...\n";
        $this->generateIndexes();
    }

    /**
     * Find the intersected timezones and the next quadrant to be evaluated
     * @param $lastLevelFlag
     * @param $curQuadrant
     * @return array
     */
    protected function findTimezonesAndNextQuadrants($lastLevelFlag, $curQuadrant)
    {
        $quadrantBounds = $curQuadrant['bounds'];
        $quadrantPolygon = getQuadrantPolygon($quadrantBounds);
        $timezonesToInspect = $this->selectTimeZonesToInspect($curQuadrant);
        $intersectionResult = $this->whichTimeZonesIntersect($timezonesToInspect, $quadrantPolygon);
        $zonesAndNextQuadrants = $this->getAssociatedZonesAndNextQuadrants(
            $intersectionResult,
            $curQuadrant,
            $lastLevelFlag);
        return $zonesAndNextQuadrants;
    }

    /**
     * Add level to the lookup table where the quadrant tree is being defined
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
     * Remove level from the lookup table where the quadrant tree is being defined
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
