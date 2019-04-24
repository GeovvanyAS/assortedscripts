<?php

    //This parser is to migrate convert from a Drupal DB to WP XML export file
    include('Redirectioner.php');
    include('simple_html_dom.php');
    include('Utils.php');
    include('htmlpurifier-4.7.0/library/HTMLPurifier.auto.php');

    $count = 0;
    echo "Setting Memory\n";
    error_reporting(0);
    date_default_timezone_set('America/Chicago');
    ini_set('memory_limit','10240M');
    ini_set('pcre.backtrack_limit', '10240M');
    
    echo "Getting File Info\n";
    $file = file_get_contents('llrx_tables.xml');

    $file = html_entity_decode($file);
    $file = preg_replace("@</?div.*?>@msi", " ", $file);
    $file = preg_replace("@</?font[^>]*>@msi", " ", $file);
    $file = str_replace('<?php echo ($_SERVER[\'REQUEST_URI\']) ?>', 'http://www.llrx.com/', $file);
    $file = preg_replace('@\s+@msi', ' ', $file);

    function removing_accents($textSource) {
        $not_allowed= array ("á","é","í","ó","ú","Á","É","Í","Ó","Ú","ñ","À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬","Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ","ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã","Ã„","Ã‹");
        $text = str_replace($not_allowed, "" ,$textSource);
        return $text;
    }
    
    echo "Parsing LLRX DB File\n";
    echo "Getting all the guest authors and their settings\n";
    
    //Arrays for Authors and Topics
    preg_match('@<table_data name="term_node">(?P<term_node>.+?)</table_data>@msi', $file, $match);
    preg_match_all('@<row>(?P<termNode>.+?)</row>@msi', $match['term_node'], $matches);
    $termNode = $matches['termNode'];
    
    preg_match('@<table_data name="term_data">(?P<term_data>.+?)</table_data>@msi', $file, $match);
    preg_match_all('@<row>(?P<termData>.+?)</row>@msi', $match['term_data'], $matches);
    $termData = $matches['termData'];
    
    //Arrays for URL Alias
    preg_match('@<table_data name="url_alias">(?P<url_alias>.+?)</table_data>@msi', $file, $match);
    preg_match_all('@<row>(?P<urlAlias>.+?)</row>@msi', $match['url_alias'], $matches);
    $urlAlias = $matches['urlAlias'];
    
    //Arrays for Amazon Node and Amazon Item
    preg_match('@<table_data name="amazonnode">(?P<amazon_node>.+?)</table_data>@msi', $file, $match);
    preg_match_all('@<row>(?P<amazonNode>.+?)</row>@msi', $match['amazon_node'], $matches);
    $amazonNode = $matches['amazonNode'];
    preg_match('@<table_data name="amazonitem">(?P<amazon_item>.+?)</table_data>@msi', $file, $match);
    preg_match_all('@<row>(?P<amazonItem>.+?)</row>@msi', $match['amazon_item'], $matches);
    $amazonItem = $matches['amazonItem'];
    
    //Array for Metas
    preg_match('@<table_data name="nodewords">(?P<nodewords>.+?)</table_data>@msi', $file, $match);
    preg_match_all('@<row>(?P<nodew>.+?)</row>@msi', $match['nodewords'], $matches);
    $nodewords = $matches['nodew'];
    
    //Array for Post Nodes
    preg_match('@<table_data name="node">(?P<table_node>.+?)</table_data>@msi', $file, $match);
    preg_match_all('@<row>(?P<node>.+?)</row>@msi', $match['table_node'], $matches);
    $node = $matches['node'];
    
    $initExportFile = '<?xml version="1.0" encoding="UTF-8" ?>'."\n".
'<rss version="2.0"'."\n".
'xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"'."\n".
'xmlns:content="http://purl.org/rss/1.0/modules/content/"'."\n".
'xmlns:wfw="http://wellformedweb.org/CommentAPI/"'."\n".
'xmlns:dc="http://purl.org/dc/elements/1.1/"'."\n".
'xmlns:wp="http://wordpress.org/export/1.2/"'."\n".
'>'."\n".
 "\n".
'<channel>'."\n".
'<wp:wxr_version>1.2</wp:wxr_version>'."\n"."\n";
    
    file_put_contents("exportFile.xml", $initExportFile, FILE_APPEND);

    $x = 501;
    $i = 0;
    
    $guest_authors = array();
    
    foreach($termData as $value){
        preg_match('@<field name="description">(?P<d>.*?)</field>@msi',$value,$dValue);
        preg_match('@<field name="tid">(?P<t>.*?)</field>@msi',$value,$tValue);
        $desc = strip_tags($dValue['d']);
        
        $desc = preg_replace('@\n@msi', ' ', $desc);
        $desc = preg_replace('@\s+@msi', ' ', $desc);
        
        preg_match('@<field name="name">(?P<n>.*?)</field>@msi',$value,$nValue);
        if(preg_match('@\,@', $nValue['n'])){
            $name = preg_split('@,@', $nValue['n']);
            $nicename = strtolower(preg_replace('@\W@msi','-',$nValue['n']));
            $nicename = preg_replace('@\?@msi','',$nicename);
            $nicename = preg_replace('@\¿@msi','',$nicename);
            $nicename = preg_replace('@\„@msi','',$nicename);
            $nicename = removing_accents($nicename);
            $nicename = trim(preg_replace('@-+@', '-', $nicename),'-');
            $guest_authors[$i]['node_id'] = $tValue['t'];
            $guest_authors[$i]['term_id'] = $x;
            $guest_authors[$i]['full_name'] = trim($name[1]).' '.trim($name[0]);
            $guest_authors[$i]['first_name'] = $name[1];
            $guest_authors[$i]['last_name'] = $name[0];
            $guest_authors[$i]['nicename'] = $nicename;
            if (preg_match('~mailto:(?P<m>.+?@[\w\.]+)~msi',$value,$mValue))
                $guest_authors[$i]['mail'] = $mValue['m'];
            $guest_authors[$i]['description'] = html_entity_decode($desc);
            $x += 1;
            $i += 1;
        }
        else{
            $name = $nValue['n'];
            $nicename = strtolower(preg_replace('@\W@msi','-',$nValue['n']));
            $nicename = preg_replace('@\?@msi','',$nicename);
            $nicename = trim(preg_replace('@-+@', '-', $nicename),'-');
            $topicRedirect = '    RedirectPermanent /category/'.$tValue['t'].' http://www.llrx.com/category/'.$nicename;
            file_put_contents("topics_redirects.txt", $topicRedirect . PHP_EOL, FILE_APPEND);
            file_put_contents("qa-topics.html", '<a href="http://www.llrx.com/category/'.$tValue['t'].'">'.$name.'</a><br>'.PHP_EOL, FILE_APPEND);
        }
    }
    

    foreach($guest_authors as $ga){
        $capWP =
'   <wp:term><wp:term_id><![CDATA['.$ga['term_id'].']]></wp:term_id><wp:term_taxonomy><![CDATA[author]]></wp:term_taxonomy><wp:term_slug><![CDATA[cap-'.$ga['nicename'].']]></wp:term_slug><wp:term_parent><![CDATA[]]></wp:term_parent><wp:term_name><![CDATA['.$ga['nicename'].']]></wp:term_name><wp:term_description><![CDATA['.$ga['full_name'].'   '.$ga['nicename'].' '.$ga['term_id'].' '.$ga['mail'].']]></wp:term_description></wp:term>'."\n";
        file_put_contents("exportFile.xml", $capWP, FILE_APPEND);
        $capNode = '    RedirectPermanent /authors/'.$ga['node_id'].' http://www.llrx.com/author/'.$ga['nicename'];
        file_put_contents("authors_redirects.txt", $capNode . PHP_EOL, FILE_APPEND);
        file_put_contents("qa-authors.html", '<a href="http://www.llrx.com/authors/'.$ga['node_id'].'">'.$ga['full_name'].'</a><br>'.PHP_EOL, FILE_APPEND);
    }
    
    foreach($guest_authors as $ga){
        $capWPFull =
'   <item>'."\n".
'       <title>'.$ga['full_name'].'</title>'."\n".
'       <link>http://JustiaDomain.com?post_type=guest-author&#038;p='.$ga['term_id'].'</link>'."\n".
'       <pubDate>Tue, 22 Sep 2015 15:51:29 +0000</pubDate>'."\n".
'       <dc:creator><![CDATA[sabrina1]]></dc:creator>'."\n".
'       <guid isPermaLink="false">http://JustiaDomain.com?post_type=guest-author&#038;p='.$ga['term_id'].'</guid>'."\n".
'       <description></description>'."\n".
'       <content:encoded><![CDATA[]]></content:encoded>'."\n".
'       <excerpt:encoded><![CDATA[]]></excerpt:encoded>'."\n".
'       <wp:post_id>'.$ga['term_id'].'</wp:post_id>'."\n".
'       <wp:post_date><![CDATA[2015-09-22 11:51:29]]></wp:post_date>'."\n".
'       <wp:post_date_gmt><![CDATA[2015-09-22 15:51:29]]></wp:post_date_gmt>'."\n".
'       <wp:comment_status><![CDATA[closed]]></wp:comment_status>'."\n".
'       <wp:ping_status><![CDATA[closed]]></wp:ping_status>'."\n".
'       <wp:post_name><![CDATA[cap-'.$ga['nicename'].']]></wp:post_name>'."\n".
'       <wp:status><![CDATA[publish]]></wp:status>'."\n".
'       <wp:post_parent>0</wp:post_parent>'."\n".
'       <wp:menu_order>0</wp:menu_order>'."\n".
'       <wp:post_type><![CDATA[guest-author]]></wp:post_type>'."\n".
'       <wp:post_password><![CDATA[]]></wp:post_password>'."\n".
'       <wp:is_sticky>0</wp:is_sticky>'."\n".
'       <wp:postmeta>'."\n".
'           <wp:meta_key><![CDATA[_edit_last]]></wp:meta_key>'."\n".
'           <wp:meta_value><![CDATA[2]]></wp:meta_value>'."\n".
'       </wp:postmeta>'."\n".
'       <wp:postmeta>'."\n".
'           <wp:meta_key><![CDATA[cap-display_name]]></wp:meta_key>'."\n".
'           <wp:meta_value><![CDATA['.$ga['full_name'].']]></wp:meta_value>'."\n".
'       </wp:postmeta>'."\n".
'       <wp:postmeta>'."\n".
'           <wp:meta_key><![CDATA[cap-first_name]]></wp:meta_key>'."\n".
'           <wp:meta_value><![CDATA['.$ga['first_name'].']]></wp:meta_value>'."\n".
'       </wp:postmeta>'."\n".
'       <wp:postmeta>'."\n".
'           <wp:meta_key><![CDATA[cap-last_name]]></wp:meta_key>'."\n".
'           <wp:meta_value><![CDATA['.$ga['last_name'].']]></wp:meta_value>'."\n".
'       </wp:postmeta>'."\n".
'       <wp:postmeta>'."\n".
'           <wp:meta_key><![CDATA[cap-user_login]]></wp:meta_key>'."\n".
'           <wp:meta_value><![CDATA['.$ga['nicename'].']]></wp:meta_value>'."\n".
'       </wp:postmeta>'."\n".
'       <wp:postmeta>'."\n".
'           <wp:meta_key><![CDATA[cap-user_email]]></wp:meta_key>'."\n".
'           <wp:meta_value><![CDATA['.$ga['mail'].']]></wp:meta_value>'."\n".
'       </wp:postmeta>'."\n".
'       <wp:postmeta>'."\n".
'           <wp:meta_key><![CDATA[cap-description]]></wp:meta_key>'."\n".
'           <wp:meta_value><![CDATA['.$ga['description'].']]></wp:meta_value>'."\n".
'       </wp:postmeta>'."\n".
'   </item>'."\n";
            file_put_contents("exportFile.xml", $capWPFull, FILE_APPEND);
    }
    
    file_put_contents("exportFile.xml", "\n\n" , FILE_APPEND);
    
    echo "Almost done\n";
    echo "Parsing all the posts and pages\n";
    
    foreach ($node as $match) {
        $title = get_title($match);
        $date = get_date($match);
        $terms = get_terms($match,$termNode,$termData);
        $status = get_status($match);
        $type = get_type($match);
        $contentEncoded = get_contentEncoded($match,$amazonNode,$amazonItem);
        $excerptEncoded = get_excerptEncoded($match);
        $description = "";
        $description = get_postMetaDescription($match);
        if($description == "")
            file_put_contents("missing-meta-description.txt", $title. "\n" , FILE_APPEND);
        $postName = get_postName($match, $urlAlias);
        $array['postName'][] = $postName;
        $name = add_numberToDuplicated($array['postName'],$postName, $count);
        $keywords = get_focusKW($match,$nodewords);
        $source = get_source($match, $urlAlias);

        $item = '   <item>'."\n".
                '       <title>'.$title.'</title>'."\n".
                '       <dc:creator><![CDATA[sabrina1]]></dc:creator>'."\n".
                '       <wp:post_date>'.$date.'</wp:post_date>'."\n".
                '       <wp:post_name>'.$name.'</wp:post_name>'."\n".
                '       <wp:status>'.$status.'</wp:status>'."\n".
                '       <wp:post_type>'.$type.'</wp:post_type>'."\n".
                '       <content:encoded><![CDATA['."\n".$contentEncoded."\n".']]></content:encoded>'."\n".
                '       <excerpt:encoded><![CDATA['."\n".$excerptEncoded.']]></excerpt:encoded>'."\n".
                '       <wp:comment_status>closed</wp:comment_status>'."\n".
                '       <wp:ping_status>closed</wp:ping_status>'.
                $terms."\n".
                '       <wp:postmeta>'."\n".'           <wp:meta_key><![CDATA[_yoast_wpseo_metadesc]]></wp:meta_key>'."\n".'            <wp:meta_value><![CDATA['.$description.']]></wp:meta_value>'."\n".'     </wp:postmeta>'."\n".
                '       <wp:postmeta>'."\n".'           <wp:meta_key><![CDATA[_yoast_wpseo_focuskw]]></wp:meta_key>'."\n".'         <wp:meta_value><![CDATA['.$keywords.']]></wp:meta_value>'."\n".'        </wp:postmeta>'."\n".
                '       <wp:postmeta>'."\n".'           <wp:meta_key><![CDATA[llrx_source_url]]></wp:meta_key>'."\n".'          <wp:meta_value><![CDATA['.$source.']]></wp:meta_value>'."\n".'      </wp:postmeta>'."\n".
                '       <wp:postmeta>'."\n".'           <wp:meta_key><![CDATA[_llrx_source_url]]></wp:meta_key>'."\n".'          <wp:meta_value><![CDATA[field_574d64e4d2826]]></wp:meta_value></wp:postmeta>'."\n".
                '   </item>'."\n\n";
        file_put_contents("exportFile.xml", $item, FILE_APPEND);
        $firstAuthor = "";
        $n ++;
        echo $n.". Post Added\n";
    }
    
    file_put_contents("exportFile.xml", "\n"."\n"."\t".'</channel>'."\n".'</rss>',FILE_APPEND);

    function get_type($match){
        if(preg_match('@.*?<field name="type">page</field>@msi',$match))
            return "page";
        else
            return "post";
    }
    
    function get_status($match){
        if(preg_match('@.*?<field name="status">1</field>@msi',$match))
            return "publish";
        else
            return "draft";
    }
    
    function add_numberToDuplicated($array,$postName,$count){
        $exist = false;
        foreach ($array as $a) {
            if ($a === $postName) {
                $exist = true;
                $count++;
            }
        }
        
        if (false){
            return $postName;
        }
        if($count==1){
            return $postName;
        }

        if (true and $count > 1) {
            $count--;
            $postName = $postName.'-'.$count;
            return $postName;
            add_number($array,$postName,$count);
        }

    }
    
    function get_title($match){
        preg_match('@<field name="title">(?P<title>.+?)</field>@msi',$match , $titleMatch);
        $title = $titleMatch['title'];
        $title = preg_replace('@([^ -~])@msi','',$title);
        
        return $title;
    }

    function get_terms($match, $termNode, $termData){
        $idTerms = array();
        $authorsName = array();
        $topicsName = array();
        $authors = "";
        $topics = "";
        preg_match('@<field name="nid">(?P<nid>[^<]+)@msi', $match, $idMatch);
        $id = $idMatch['nid'];
        foreach ($termNode as $tN){
            if(preg_match('@<field name="nid">'.$id.'</field>.*?<field name="tid">(?P<tid>[^<]+)@msi', $tN, $idMatch)){
                $tid = $idMatch['tid'];
                if(!in_array($tid, array_column($idTerms, 'tid'))){
                    $idTerms[]['tid'] = $tid;
                    foreach ($termData as $tD)
                        if(preg_match('@<field name="tid">'.$tid.'</field>.*?<field name="name">(?P<name>[^<]+)@msi', $tD, $termName)){
                            if(preg_match('@\,@', $termName['name'])){
                                $nicename = strtolower(preg_replace('@\W@msi','-',$termName['name']));
                                $nicename = preg_replace('@\?@msi','',$nicename);
                                $nicename = preg_replace('@\¿@msi','',$nicename);
                                $nicename = preg_replace('@\„@msi','',$nicename);
                                $nicename = removing_accents($nicename);
                                $nicename = trim(preg_replace('@-+@', '-', $nicename),'-');
                                $authorsName[$tid]['nicename'] = $nicename;
                                $authorsName[$tid]['name'] = $termName['name'];
                            }
                            else{
                                $nicename = strtolower(preg_replace('@\W@msi','-',$termName['name']));
                                $nicename = trim(preg_replace('@-+@', '-', $nicename),'-');
                                $topicsName[$tid]['nicename'] = $nicename;
                                $topicsName[$tid]['name'] = $termName['name'];
                            }
                        }
                }
            }
        }
        
        foreach ($authorsName as $name){
            $authors = $authors."\n".'      <category domain="author" nicename="cap-'.$name['nicename'].'"><![CDATA['.$name['name'].']]></category>';
        }
        
        foreach ($topicsName as $name){
            $topics = $topics."\n".'        <category domain="category" nicename="'.$name['nicename'].'"><![CDATA['.$name['name'].']]></category>';
        }
        
        if($topics == "")
            $topics = "\n".'        <category domain="category" nicename="uncategorized"><![CDATA[Uncategorized]]></category>';
        
        return $authors . $topics;
    }
    
    function get_date($match){
        preg_match('@<field name="created">(?P<date>.+?)</field>@msi',$match , $dateMatch);
        $date = $dateMatch['date'];
        $date = date("Y-m-d", $date);
        return $date;
    }
    
    function get_postName($match, $urlAlias){
        preg_match('@<field name="title">(?P<title>.+?)</field>@msi',$match , $titleMatch);
        $title = $titleMatch['title'];
        $postName = Utils::sanitize_title_with_dashes($title);
        $postName = preg_replace('@(%.+?\w)@msi',"-", $postName);
        $postName = preg_replace('@([-]{2,100})@msi', "-", $postName);
        
        //Start Posts Redirects
        
        $type = get_type($match);
        
        $date = get_date($match);        
        $date = substr($date, 0, 7);
        $date = "/".preg_replace("@-@","/",$date)."/";
        
        preg_match('@<field name="nid">(?P<nid>[^<]+)@msi', $match, $idMatch);
        $id = $idMatch['nid'];
        if($type == "page"){
            foreach ($urlAlias as $uA){
                if(preg_match('@<field name="src">node/'.$id.'</field>.*?<field name="dst">(?P<dst>[^<]+)@msi', $uA, $newURL)){
                    $stringR = "RedirectPermanent /node/".$id." http://JustiaDomain.com".$postName."\n";
                    file_put_contents('posts-redirections.txt', $stringR, FILE_APPEND | LOCK_EX);
                    $stringR = "RedirectPermanent /".$newURL["dst"]." http://JustiaDomain.com".$postName."\n";
                    file_put_contents('posts-redirections.txt', $stringR, FILE_APPEND | LOCK_EX);
                }
            }
        }
        else if($type == "post"){
            foreach ($urlAlias as $uA){
                if(preg_match('@<field name="src">node/'.$id.'</field>.*?<field name="dst">(?P<dst>[^<]+)@msi', $uA, $newURL)){
                    $stringR = "RedirectPermanent /node/".$id." http:/"JustiaDomain.com.$date.$postName.".html"."\n";
                    file_put_contents('posts-redirections.txt', $stringR, FILE_APPEND | LOCK_EX);
                    $stringR = "RedirectPermanent /".$newURL["dst"]." http:/"JustiaDomain.com.$date.$postName.".html"."\n";
                    file_put_contents('posts-redirections.txt', $stringR, FILE_APPEND | LOCK_EX);
                }
            }
        }
        return $postName;
    }

    function get_source($match, $urlAlias){
        preg_match('@<field name="nid">(?P<nid>[^<]+)@msi', $match, $idMatch);
        $id = $idMatch['nid'];
        foreach ($urlAlias as $uA){
            if(preg_match('@<field name="src">(?P<node>node/'.$id.')</field>.*?<field name="dst">(?P<dst>[^<]+)@msi', $uA, $url)){
                return "http://www.llrx.com/".$url['node'];
            }
        }
    }
   
    function get_excerptEncoded($match){
        if(preg_match('@<field name="teaser">(?P<excerptEncoded>.+?)</field>@msi', $match, $excerptMatch)){
            $excerpt = $excerptMatch['excerptEncoded'];
            $excerpt = str_replace('<?php echo ($_SERVER[\'REQUEST_URI\']) ?>', 'http://www.llrx.com/', $excerpt);
            $excerpt = preg_replace('@<script.*?>[^<]+</script>@msi', ' ', $excerpt);
            $excerpt = preg_replace('@\n@msi', ' ', $excerpt);
            $excerpt = str_replace('&nbsp;',' ',$excerpt);
            $excerpt = strip_tags($excerpt,'<a><strong><p><em><i><b><u><img><iframe><object><ul><li><table><tr><td><ol>');
            $excerpt = preg_replace('@\s+@msi', ' ', $excerpt);
            $excerpt = trim($excerpt);
            $excerpt = str_replace(array("\n\r", "\n", "\r"), "", $excerpt);
            
            return $excerpt;
        }
    }
    
    
    function get_postMetaDescription($match){
        preg_match('@<field name="teaser">(?P<excerptEncoded>.+?)</field>@msi',$match , $excerptMatch);
        $description = $excerptMatch['excerptEncoded'];
        $description = strip_tags($description);
        $description = trim($description);
        if (strlen($description)>150) {
            $description = substr($description, 0, 150);
            $description = $description."{[-";
            $description = preg_replace("@([^\s]+{\[-)@msi", "", $description);
            $description = str_replace("{[-"," ", $description);
            $description = str_replace('&nbsp;',' ',$description);
            $description = trim($description);
            $description = preg_replace('@<script.*?>[^<]+</script>@msi', ' ', $description);
            $description = str_replace('<?php echo ($_SERVER[\'REQUEST_URI\']) ?>', 'http://www.llrx.com/', $description);
            $description = preg_replace("@\n@msi", ' ', $description);
            $description = preg_replace("@\s+@msi", ' ', $description);
            $description = str_replace(array("\n\r", "\n", "\r"), "", $description);
        }    
        return $description;
    }
    
    function get_focusKW($match,$nodewords){
        preg_match('@<field name="nid">(?P<nid>.+?)</field>@msi', $match, $KWMatch);
        $id = $KWMatch['nid'];
        foreach ($nodewords as $n) {
            if(preg_match('@<field name="id">'.$id.'</field>.+?<field name="content">(?P<keyword>.+?)</field>@msi', $n, $match)){
                return $match['keyword'];
            }
        }
    }

    function get_contentEncoded($match, $amazonNode, $amazonItem){
        preg_match('@<field name="body">(?P<contentEncoded>.+?)</field>@msi',$match , $contentMatch);
        $content = $contentMatch['contentEncoded'];
        //$content = html_entity_decode($content);
        $content = preg_replace('@<script.*?>[^<]+</script>@msi', ' ', $content);
        $content = str_replace('<?php echo ($_SERVER[\'REQUEST_URI\']) ?>', 'http://www.llrx.com/', $content);
        $content = preg_replace('@\n@msi', ' ', $content);
        $content = str_replace('&nbsp;',' ',$content);
        $content = preg_replace('@\s+@msi', ' ', $content);
        $content = preg_replace('@href="([^http://].*?\.htm\#.*?\d{1,3})"@msi', 'href="http://JustiaDomain.com\1"', $content);
        
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        $content = $purifier->purify($content);

        if(preg_match('@<field name="type">amazon-node</field>@msi',$match)){
            $asinNumbers = array();
            $amazonURLsArray = array();
            $amazonURLs = null;
            preg_match('@<field name="nid">(?P<nid>[^<]+)</field>.*?<field name="type">amazon-node</field>@msi', $match, $idMatch);
            $id = $idMatch['nid'];
            foreach ($amazonNode as $aN){
                if(preg_match('@<field name="nid">'.$id.'</field>.*?<field name="ASIN">(?P<asin>[^<]+)</field>@msi', $aN, $idMatch)){
                    $asin = $idMatch['asin'];
                        foreach ($amazonItem as $aI){
                            if(preg_match('@<field name="ASIN">'.$asin.'</field>.+?<field name="DetailPageURL">(?P<DetailPageURL>[^<]+)</field>.*?<field name="LargeImageURL">(?P<LargeImageURL>[^<]+)</field>.*?<field name="Binding">(?P<Binding>[^<]+)</field>.*?<field name="listFormattedPrice">(?P<listFormattedPrice>[^<]+)</field>.*?<field name="FormattedPrice">(?P<FormattedPrice>[^<]*)</field>@msi', $aI, $urlMatch)){
                                $amazonURLsArray[$asin]['ASIN'] = $asin;
                                $amazonURLsArray[$asin]['DetailPageURL'] = $urlMatch['DetailPageURL'];
                                $amazonURLsArray[$asin]['LargeImageURL'] = $urlMatch['LargeImageURL'];
                                $amazonURLsArray[$asin]['Binding'] = $urlMatch['Binding'];
                                $amazonURLsArray[$asin]['listFormattedPrice'] = $urlMatch['listFormattedPrice'];
                                $amazonURLsArray[$asin]['FormattedPrice'] = $urlMatch['FormattedPrice'];
                            }
                        }
                }
            }
            
            foreach($amazonURLsArray as $au){
                $amazonURLs = 
'<p><a href="'.$au['DetailPageURL'].'"><img src="'.$au['LargeImageURL'].'" style="float:left; padding:0 20px 20px 0;"></a></p>'.
'<p>ASIN: '.$au['ASIN'].'</p>'."\n".
'<p>Binding: '.$au['Binding'].'</p>'."\n".
'<p>List price: '.$au['listFormattedPrice'].'</p>'."\n". 
'<p>Amazon price: '.$au['FormattedPrice'].'</p>'."\n"."\n"
;
            }
            return html_entity_decode($amazonURLs);
        }
        else{
            $content = html_entity_decode($content);
            $content = preg_replace("@(<o:p>\s+</o:p>)|(<span style='mso-spacerun:yes'>\s+</span>)@msi", "\s",$content);
            $content = preg_replace('@(<o:p></o:p>)|(<span style="mso-spacerun: yes"></span>)@msi', "",$content); 
            $content = preg_replace("@(<span style='mso-spacerun:yes'></span>)@msi", "",$content);
            $content = removeNewLine00($content);
            $content = removeNewLine01($content);
            $content = removeNewLine02($content);
            $content = removeNewLine03($content);
            $content = str_replace(PHP_EOL, '', $content);
            $content = preg_replace("/[\n\r]/","",$content);
            $content = preg_replace("/[\r\n]/","",$content);
            $content = preg_replace("@\n+|\r+@","",$content);
            $content = str_replace(PHP_EOL, '', $content);
            $content = preg_replace("@".PHP_EOL."@i", '', $content);
            $content = preg_replace('@[^ -~]+@msi', '', $content);
            $content = preg_replace("@</p>@msi", "</p>\r",$content);
            return $content;
        }
    }

    function removeNewLine00 ($text){
        $text = preg_replace('@[^ -~]+@msi', '', $text);
        return $text;
    }
    
    function removeNewLine01 ($text){
        $text = str_replace("\n\r", "", $text);
        return $text;
    }

    function removeNewLine02 ($text){
        $text = str_replace("\n", "", $text);
        return $text;
    }
    
    function removeNewLine03 ($text){
        $text = str_replace("\r", "", $text);
        return $text;
    }
    echo "Done!!!!\n";
?>