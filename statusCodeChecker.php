<?php

//This script gets the status code of provided URLs.

$urls = [];

    foreach ($urls as $url) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_FOLLOWLOCATION  => true, ]);
        $response = curl_exec($handle);
        $finalUrl = curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($httpCode == 404) {
            file_put_contents("404.txt", $url . "\n", FILE_APPEND);
        }

        print_r($finalUrl . " " . $httpCode . "\n");

        curl_close($handle);
    }
