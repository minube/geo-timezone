<?php

const DOWNLOAD_DIR = "./data/downloads/";
const TIMEZONE_ZIP_NAME = "timezones.zip";
const TIMEZONE_JSON_NAME = "timezones.json";
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
        getZipResponse($geoJsonUrl, DOWNLOAD_DIR . TIMEZONE_ZIP_NAME);
    }
}

function deletePreviousData()
{
    //TODO REMOVE ALL FILES AND FOLDERS IN DATA FOLDER
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
    }
    return $controlFlag;
}

downloadLastVersion();
//deletePreviousData();
unzipData(DOWNLOAD_DIR . TIMEZONE_ZIP_NAME);
