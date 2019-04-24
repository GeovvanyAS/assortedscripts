<?php

  //This script QA meta titles and meta descriptions by comparing provided external and internal URLs
    $options = [
      'http' => [
      'follow_location' => true,
      'method' => 'GET',
      'header' => 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0' .
                  'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ]
      ];

    $urlsSource = [

    ];

     $urlsInternal = [

    ];

    function getMetas($url, $options)
    {
        $context = stream_context_create($options);
        $content = file_get_contents($url, false, $context);
        sleep(2);
        if (preg_match('@keyw@msi', $content, $matchKw)) {
            print_r("KEYWORD FOUND!!!!" . $url);
            die();
        }
        preg_match('@<meta name="description" content="(?P<metaDescription>.+?)"@msi', $content, $matchMeta);
        preg_match('@<title>(?P<title>.+?)</title>@msi', $content, $matchTitle);
        $title = $matchTitle['title'];
        $title = trim($title);
        $metaDescription = trim($metaDescription);
        $output = $title . "-" . $metaDescription;
        $output = str_replace("&#039;", "'", $output);
        $output = str_replace("&amp;", "&", $output);
        $output = trim($output);
        return $output;
    }

    function getMetasInternal($url, $options)
    {
        $context = stream_context_create($options);
        $content = file_get_contents($url, false, $context);
        sleep(2);
        preg_match('@<meta name="description" content="(?P<metaDescription>.+?)"@msi', $content, $matchMeta);
        preg_match('@<title>(?P<title>.+?)</title>@msi', $content, $matchTitle);
        $title = $matchTitle['title'];
        $output = $title . "-" . $metaDescription;
        $output = str_replace("&#039;", "'", $output);
        $output = str_replace("&amp;", "&", $output);
        $output = trim($output);
        return $output;
    }

    for ($i=0; $i<count($urlsSource); $i++) {
        $metasSource = getMetas($urlsSource[$i], $options);
        $metasInternal = getMetasInternal($urlsInternal[$i], $options);
        print_r("source: " . $metasSource . "\n");
        print_r("Internal: " . $metasInternal . "\n");
        if ($metasSource === $metasInternal) {
            print_r("OK" . "\n" . "\n");
        } else {
            print_r("ERROR!!!" . "------>" . " " . $urlsSource[$i] . " " . $urlsInternal[$i] . "\n" . "\n");
            file_put_contents("bad.txt", "ERROR!!!" . "------>" . " " . $urlsSource[$i] . " " . $urlsInternal[$i] . "\n", FILE_APPEND);
        }
    }
