<?php

//Download images from provided URLS
$urls = [];
    $count = 0;
    foreach ($urls as $url) {
        $content = file_get_contents($url);
        $fileName = explode("/", $url);
        $fileName = $fileName[count($fileName)-1];
        $name = $fileName;
        $name = preg_replace("@\?.+@msi", "", $name);
        $path = __DIR__ . "/images/";
        $fp = fopen($path . $name, "w");
        fwrite($fp, $content);
        fclose($fp);
        print_r("downloaded: " . $name . "\n");
        $count++;
    }
    print_r("Total of downloaded images:" . " " . $count . "\n");
