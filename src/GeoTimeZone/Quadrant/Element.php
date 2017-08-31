<?php

namespace GeoTimeZone\Quadrant;

class Element
{
    const MAX_ABS_LATITUDE = 90.0;
    const MAX_ABS_LONGITUDE = 180.0;
    const ABS_LATITUDE_LIMIT = 89.9999;
    const ABS_LONGITUDE_LIMIT = 179.9999;
    const LEVEL_A = 'a';
    const LEVEL_B = 'b';
    const LEVEL_C = 'c';
    const LEVEL_D = 'd';
    
    protected $top;
    protected $bottom;
    protected $left;
    protected $right;
    protected $midLat;
    protected $midLon;
    protected $level;
    
    /**
     * Element constructor.
     */
    public function __construct()
    {
        $this->top = self::MAX_ABS_LATITUDE;
        $this->bottom = (-1) * self::MAX_ABS_LATITUDE;
        $this->left = (-1) * self::MAX_ABS_LONGITUDE;
        $this->right = self::MAX_ABS_LONGITUDE;
        $this->midLat = 0.0;
        $this->midLon = 0.0;
        $this->level = self::LEVEL_A;
    }
    
    /**
     * Move the current quadrant to a particular location (latitude, longitude)
     * @param $latitude
     * @param $longitude
     */
    public function moveToNextQuadrant($latitude, $longitude)
    {
        if ($latitude >= $this->midLat) {
            $this->bottom = $this->midLat;
            if ($longitude >= $this->midLon) {
                $this->level = self::LEVEL_A;
                $this->left = $this->midLon;
            } else {
                $this->level = self::LEVEL_B;
                $this->right = $this->midLon;
            }
        } elseif ($longitude < $this->midLon) {
            $this->level = self::LEVEL_C;
            $this->top = $this->midLat;
            $this->right = $this->midLon;
        } else {
            $this->level = self::LEVEL_D;
            $this->top = $this->midLat;
            $this->left = $this->midLon;
        }
    }
    
    /**
     * Update the mid coordinates attributes of the quadrant
     */
    public function updateMidCoordinates()
    {
        $this->midLat = ($this->top + $this->bottom) / 2.0;
        $this->midLon = ($this->left + $this->right) / 2.0;
    }
    
    /**
     * Get the quadrant level (a, b, c or d)
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }
}
