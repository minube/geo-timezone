<?php

include "QuadrantIndexer.php";

const MAIN_DIR = "./data/";
const DOWNLOAD_DIR = "./data/downloads/";
const TIMEZONE_FILE_NAME = "timezones";
const REPO_HOST = "https://api.github.com";
const REPO_USER = "node-geo-tz";
const REPO_PATH = "/repos/evansiroky/timezone-boundary-builder/releases/latest";
const GEO_JSON_DEFAULT_URL = "none";
const GEO_JSON_DEFAULT_NAME = "geojson";


function getResponse($url)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => REPO_USER
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function getZipResponse($url, $destinationPath="none")
{
    exec("wget {$url} --output-document={$destinationPath}");
}

function getGeoJsonUrl($data)
{
    $jsonResp = json_decode($data, true);
    $geoJsonUrl = GEO_JSON_DEFAULT_URL;
    foreach($jsonResp['assets'] as $asset) {
        if(strpos($asset['name'], GEO_JSON_DEFAULT_NAME)) {
            $geoJsonUrl = $asset['browser_download_url'];
            break;
        }
    }
    return $geoJsonUrl;
}

function downloadLastVersion()
{
    $response = getResponse(REPO_HOST . REPO_PATH);
    $geoJsonUrl = getGeoJsonUrl($response);
    if($geoJsonUrl != GEO_JSON_DEFAULT_URL)
    {
        if(!is_dir(DOWNLOAD_DIR)) {
            mkdir(DOWNLOAD_DIR);
        }
        getZipResponse($geoJsonUrl, DOWNLOAD_DIR . TIMEZONE_FILE_NAME . ".zip");
    }
}

function unzipData($filePath)
{
    $zip = new ZipArchive;
    $controlFlag = false;
    if ($zip->open($filePath) === TRUE) {
        $zipName = basename($filePath, ".zip");
        if(!is_dir(DOWNLOAD_DIR . $zipName)) {
            mkdir(DOWNLOAD_DIR . $zipName);
        }
        $zip->extractTo(DOWNLOAD_DIR. $zipName);
        $zip->close();
        $controlFlag = true;
        unlink($filePath);
    }
    return $controlFlag;
}

function renameTimezoneJsonAndGetPath()
{
    $path = realpath(DOWNLOAD_DIR. TIMEZONE_FILE_NAME . "/");
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    $jsonPath = "";
    foreach($files as $pathFile => $file){
        if(strpos($pathFile, ".json")) {
            $jsonPath = $pathFile;
            break;
        }
    }
    return rename($jsonPath, dirname($jsonPath) . "/" . TIMEZONE_FILE_NAME . ".json");
}

function removePreviousData($path)
{
    $validDir = array(
        QuadrantIndexer::LEVEL_A,
        QuadrantIndexer::LEVEL_B,
        QuadrantIndexer::LEVEL_C,
        QuadrantIndexer::LEVEL_D
    );
    if (is_dir($path)) {
        $objects = scandir($path);
        foreach ($objects as $object) {
            $objectPath = $path . "/" . $object;
            if ($object != "." && $object != "..") {
                if (is_dir($objectPath)) {
                    if(in_array(basename($object), $validDir)) {
                        removePreviousData($objectPath);
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

function updateData()
{
    downloadLastVersion();
    unzipData(DOWNLOAD_DIR . TIMEZONE_FILE_NAME . ".zip");
    $timezoneJsonPath = renameTimezoneJsonAndGetPath();
    removePreviousData(MAIN_DIR);
    $geoIndexer = new QuadrantIndexer();
    $geoIndexer->createQuadrantTreeData();
}

updateData();

