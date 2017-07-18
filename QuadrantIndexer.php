<?php

include "Quadrant.php";

class QuadrantIndexer extends Quadrant
{
    const DEFAULT_DATA_SOURCE_PATH = "./data/downloads/timezones/dist/timezones.json";
    const TARGET_INDEX_PERCENT = 0.5;

    protected $dataSourcePath;
    protected $dataSource;
    protected $timezones = array();
    protected $zoneLevels = array(
        array(
            "id" => Quadrant::LEVEL_A,
            "bounds" => array(0, 0, Quadrant::ABS_LONGITUDE_LIMIT, Quadrant::ABS_LATITUDE_LIMIT)),
        array(
            "id" => Quadrant::LEVEL_B,
            "bounds" => array((-1) * Quadrant::ABS_LONGITUDE_LIMIT, 0, 0, Quadrant::ABS_LATITUDE_LIMIT)),
        array(
            "id" => Quadrant::LEVEL_C,
            "bounds" => array((-1) * Quadrant::ABS_LONGITUDE_LIMIT, (-1) * Quadrant::ABS_LATITUDE_LIMIT, 0, 0)),
        array(
            "id" => Quadrant::LEVEL_D,
            "bounds" => array(0, (-1) * Quadrant::ABS_LATITUDE_LIMIT, Quadrant::ABS_LONGITUDE_LIMIT, 0))
    );

    protected function readDataSource()
    {
        $this->dataSourcePath = is_null($this->dataSourcePath) ? self::DEFAULT_DATA_SOURCE_PATH : $this->dataSourcePath;
        $jsonData = file_get_contents($this->dataSourcePath);
        $this->dataSource = json_decode($jsonData, true);
    }

    protected function setTimezonesArray()
    {
        foreach($this->dataSource['features'] as $feature)
        {
            $this->timezones[] = $feature['properties']['tzid'];
        }
    }

    protected function generateIndexes()
    {

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
