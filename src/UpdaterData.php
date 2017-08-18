<?php

namespace TimeZone;

use TimeZone\Quadrant\Indexer as Indexer;

class UpdaterData
{
    const MAIN_DIR = "/../../data";
    const DOWNLOAD_DIR = "/../../data/downloads/";
    const TIMEZONE_FILE_NAME = "timezones";
    const REPO_HOST = "https://api.github.com";
    const REPO_USER = "node-geo-tz";
    const REPO_PATH = "/repos/evansiroky/timezone-boundary-builder/releases/latest";
    const GEO_JSON_DEFAULT_URL = "none";
    const GEO_JSON_DEFAULT_NAME = "geojson";
    
    protected $mainDir;
    protected $downloadDir;
    
    /**
     * UpdaterData constructor.
     */
    public function __construct()
    {
        $this->mainDir = __DIR__ . self::MAIN_DIR;
        $this->downloadDir = __DIR__ . self::DOWNLOAD_DIR;
    }
    
    /**
     * Get complete json response from repo
     * @param $url
     * @return mixed
     */
    protected function getResponse($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => self::REPO_USER
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    
    /**
     * Download zip file
     * @param $url
     * @param string $destinationPath
     */
    protected function getZipResponse($url, $destinationPath="none")
    {
        exec("wget {$url} --output-document={$destinationPath}");
    }
    
    /**
     * Get timezones json url
     * @param $data
     * @return string
     */
    protected function getGeoJsonUrl($data)
    {
        $jsonResp = json_decode($data, true);
        $geoJsonUrl = self::GEO_JSON_DEFAULT_URL;
        foreach($jsonResp['assets'] as $asset) {
            if(strpos($asset['name'], self::GEO_JSON_DEFAULT_NAME)) {
                $geoJsonUrl = $asset['browser_download_url'];
                break;
            }
        }
        return $geoJsonUrl;
    }
    
    /**
     * Download last version reference repo
     */
    protected function downloadLastVersion()
    {
        $response = $this->getResponse(self::REPO_HOST . self::REPO_PATH);
        $geoJsonUrl = $this->getGeoJsonUrl($response);
        if($geoJsonUrl != self::GEO_JSON_DEFAULT_URL)
        {
            if(!is_dir($this->downloadDir)) {
                mkdir($this->downloadDir);
            }
            $this->getZipResponse($geoJsonUrl, $this->downloadDir . self::TIMEZONE_FILE_NAME . ".zip");
        }
    }
    
    /**
     * Unzip data
     * @param $filePath
     * @return bool
     */
    protected function unzipData($filePath)
    {
        $zip = new \ZipArchive;
        $controlFlag = false;
        if ($zip->open($filePath) === TRUE) {
            $zipName = basename($filePath, ".zip");
            if(!is_dir($this->downloadDir . $zipName)) {
                mkdir($this->downloadDir . $zipName);
            }
            $zip->extractTo($this->downloadDir. $zipName);
            $zip->close();
            $controlFlag = true;
            unlink($filePath);
        }
        return $controlFlag;
    }
    
    /**
     * Rename downloaded timezones json file
     * @return bool
     */
    protected function renameTimezoneJson()
    {
        $path = realpath($this->downloadDir. self::TIMEZONE_FILE_NAME . "/");
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $jsonPath = "";
        foreach($files as $pathFile => $file){
            if(strpos($pathFile, ".json")) {
                $jsonPath = $pathFile;
                break;
            }
        }
        return rename($jsonPath, dirname($jsonPath) . "/" . self::TIMEZONE_FILE_NAME . ".json");
    }
    
    /**
     * Remove all directories tree in main data folder
     * @param $path
     */
    protected function removePreviousData($path)
    {
        $validDir = array(
            Indexer::LEVEL_A,
            Indexer::LEVEL_B,
            Indexer::LEVEL_C,
            Indexer::LEVEL_D
        );
        if (is_dir($path)) {
            $objects = scandir($path);
            foreach ($objects as $object) {
                $objectPath = $path . "/" . $object;
                if ($object != "." && $object != "..") {
                    if (is_dir($objectPath)) {
                        if(in_array(basename($object), $validDir)) {
                            $this->removePreviousData($objectPath);
                        }
                    } else {
                        unlink($objectPath);
                    }
                }
            }
            if (in_array(basename($path), $validDir)) {
                rmdir($path);
            }
        }
        return;
    }
    
    /**
     * Add folder to zip file
     * @param $mainDir
     * @param $zip
     * @param $exclusiveLength
     */
    protected function folderToZip($mainDir, &$zip, $exclusiveLength)
    {
        $handle = opendir($mainDir);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$mainDir/$f";
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zip->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    $zip->addEmptyDir($localPath);
                    $this->folderToZip($filePath, $zip, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }
    
    /**
     * Compress directory
     * @param $sourcePath
     * @param $outZipPath
     */
    protected function zipDir($sourcePath, $outZipPath)
    {
        $pathInfo = pathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];
        
        $z = new \ZipArchive();
        $z->open($outZipPath, ZIPARCHIVE::CREATE);
        $z->addEmptyDir($dirName);
        $this->folderToZip($sourcePath, $z, strlen("$parentPath/"));
        $z->close();
    }
    
    /**
     * Main function that runs all updating process
     */
    public function updateData()
    {
        echo "Downloading data...\n";
        $this->downloadLastVersion();
        /*echo "Unzip data...\n";
        $this->unzipData($this->downloadDir . self::TIMEZONE_FILE_NAME . ".zip");
        echo "Rename timezones json...\n";
        $this->renameTimezoneJson();
        echo "Remove previous data...\n";
        $this->removePreviousData($this->mainDir . "/");
        echo "Creating quadrant tree data...\n";
        $geoIndexer = new Indexer();
        $geoIndexer->createQuadrantTreeData();
        echo "Remove downloaded data...\n";
        $this->removePreviousData($this->downloadDir);
        echo "Zipping quadrant tree data...";
        $this->zipDir($this->mainDir, $this->mainDir . ".zip");*/
    }
}

