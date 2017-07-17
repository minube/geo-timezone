<?php
/**
 * User: abmo
 */

const DOWNLOAD_DIR = "./data/downloads/";
const TIMEZONE_ZIP_NAME = "timezones.zip";
const TIMEZONE_JSON_NAME = "timezones.json";
const REPO_HOST = "https://api.github.com";
const REPO_USER = "node-geo-tz";
const REPO_PATH = "/repos/evansiroky/timezone-boundary-builder/releases/latest";
const GEO_JSON_DEFAULT_URL = "none";
const GEO_JSON_DEFAULT_NAME = "geojson";

function getResponse($url, $saveData="none")
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => REPO_USER,
        CURLOPT_RETURNTRANSFER => true
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function saveData($data, $destinationPath)
{
//    $file = fopen($destinationPath, "w+");
//    fputs($file, $data);
//    fclose($file);

    file_put_contents($destinationPath, $data);

//    $zip = new ZipArchive();
//    if ($zip->open($destinationPath, ZipArchive::CREATE)!==TRUE) {
//        exit("cannot open <$destinationPath>\n");
//    }
//    $zip->addFile($data);
//    $zip->close();
}

function getGeoJsonUrl($data)
{
    $jsonResp =json_decode($data, true);
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
        $destination = DOWNLOAD_DIR . TIMEZONE_ZIP_NAME;
        $response = getResponse($geoJsonUrl, $destination);
        saveData($response, $destination);
    }
}

function deletePreviousData()
{
    //TODO REMOVE ALL FILES AND FOLDERS IN DATA FOLDER
}

function unzipData($filePath)
{
    $zip = zip_open($filePath);
    print_r(zip_read($zip));
    zip_close($zip);
}

downloadLastVersion();
//deletePreviousData();
unzipData(DOWNLOAD_DIR . "timezones.geojson.zip");
