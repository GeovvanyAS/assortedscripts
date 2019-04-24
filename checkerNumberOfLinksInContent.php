<?php
    //provide URLs to check
    $urls = [];

    //Provide domains to check
    $url1 = "";
    $url2 = "";
    $url3 = "";

    foreach ($urls as $url) {
        $content = file_get_contents($url);
        preg_match('@<div class="entry-content">(?P<postContent>.+?)<div id=\'jp-relatedposts@msi', $content, $match);
        $postContent = $match['postContent'];
        if (empty($postContent)) {
            preg_match('@<div class="entry-content">(?P<postContent2>.+?)</div>@msi', $content, $match2);
            $postContent = $match2['postContent2'];
        }
        preg_match_all("@(?P<urls>{$url1})@msi", $postContent, $matches1);
        preg_match_all("@(?P<urls>{$url2})@msi", $postContent, $matches2);
        preg_match_all("@(?P<urls>{$url3})@msi", $postContent, $matches3);
        $total = count($matches1['urls']) + count($matches2['urls']) + count($matches3['urls']);
        print_r($url . "\n");
        print_r("{$url1}" . count($matches1['urls']) . "\n");
        print_r("{$url2}" . count($matches2['urls']) . "\n");
        print_r("{$url3}" . count($matches3['urls']) . "\n");
        print_r("Total: " . $total . "\n" . "\n");
        if (count($matches1['urls']) > 2 || count($matches2['urls']) > 2 || count($matches3['urls']) > 2) {
            file_put_contents("CheckPosts.txt", $url . "\n", FILE_APPEND);
        }
        if ($total < 1) {
            file_put_contents("nolinks.txt", $url . "\n", FILE_APPEND);
        }
        unset($matches1);
        unset($matches2);
        unset($matches3);
    }
