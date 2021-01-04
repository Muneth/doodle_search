<?php

// Database connect file
include("config.php");
// Include the classes file
include("classes/DomDocumentParser.php");

$alreadyCrawled = array();
$crawling = array();
$alreadyFoundImages = array();

// DATABASE INSERT FUNCTION.

// Getting rid of Duplicates.

function linkExists($url) {
    global $con;

    $query = $con->prepare("SELECT * FROM sites WHERE url = :url");

    $query->bindParam(":url", $url);
    $query->execute();

    return $query->rowCount() != 0;
}

// Insert Sites in the Database

function insertLink($url, $title, $description, $keywords) {
    global $con;

    $query = $con->prepare("INSERT INTO sites(url, title, description, keywords)
                            VALUES(:url, :title, :description, :keywords)");

    $query->bindParam(":url", $url);
    $query->bindParam(":title", $title);
    $query->bindParam(":description", $description);
    $query->bindParam(":keywords", $keywords);

    return $query->execute();
}

// Insert IMAGES to the Database

function insertImage($url, $src, $alt, $title) {
    global $con;

    $query = $con->prepare("INSERT INTO images(sitesUrl, imageUrl, alt, title)
                            VALUES(:sitesUrl, :imageUrl, :alt, :title)");

    $query->bindParam(":sitesUrl", $url);
    $query->bindParam(":imageUrl", $src);
    $query->bindParam(":alt", $alt);
    $query->bindParam(":title", $title);

    return $query->execute();
}


function createLink($src, $url) {

    // http 
    $scheme = parse_url($url)["scheme"]; 
    // host website
    $host = parse_url($url)["host"];

    if(substr($src, 0, 2) == "//") {
        $src =  $scheme . ":" . $src;
    }
    else if(substr($src, 0, 1) == "/") {
        $src =  $scheme . "://" .$host . $src;
    }
    else if(substr($src, 0, 2) == "./") {
        $src = $scheme . "://" . $host . dirname(parse_url($url)["path"]) . substr($src, 1);
    }
    else if(substr($src, 0, 3) == "../") {
        $src = $scheme . "://" . $host . "/" . $src;
    }
    else if(substr($src, 0, 5) != "https" && substr($src, 0, 4) != "http") {
        $src = $scheme . "://" . $host . "/" . $src;
    }
    
    return $src;
}

function getDetails($url) {

    global $alreadyFoundImages;

    $parser = new DomDocumentParser($url);

    $titleArray = $parser->getTitletags();

    if(sizeof($titleArray) == 0 || $titleArray->item(0) == NULL) {
        return;
    }

    $title = $titleArray->item(0)->nodeValue;
    $title = str_replace("\n", "", $title);

    if($title == "") {
        return;
    }

    $description = "";
    $keywords = "";

    $metaArray = $parser->getMetaTags();

    foreach($metaArray as $meta) {

        if($meta->getAttribute("name") == "description") {
            $description = $meta->getAttribute("content");
        }
        if($meta->getAttribute("name") == "keywords") {
            $keywords = $meta->getAttribute("content");
        }
    }

    $description = str_replace("\n", "", $description);
    $keywords = str_replace("\n", "", $keywords);

    if(linkExists($url)) {
        echo "$url already exists<br>";
    }
    else if ( insertLink($url, $title, $description, $keywords)) {
        echo "SUCCESS: $url<br>";
    }
    else {
        echo "ERROE: Failed to insert $url<br>";
    }

    $imageArray = $parser->getImages();
    foreach($imageArray as $image) {
        $src = $image->getAttribute("src");
        $alt = $image->getAttribute("alt");
        $title = $image->getAttribute("title");

        if(!$title && !$alt) {
            continue;
        }

        $src = createLink($src, $url);

        if(!in_array($src, $alreadyFoundImages)) {
            $alreadyFoundImages[] = $src;

            echo "INSERT: " . insertImage($url, $src, $alt, $title);
        }
    }

}

function followLinks($url) {

    global $alreadyCrawled;
    global $crawling;

    $parser = new DomDocumentParser($url);

    $linkList = $parser->getLinks();

    foreach($linkList as $link) {
        $href = $link->getAttribute("href");

        // ignore Empty Anchor links

        if(strpos($href, "#") !== false) {
            continue;
        }

        // ignore javaScript as well

        else if(substr($href, 0, 11) == "javascript:") {
            continue;
        }

        $href = createLink($href, $url);

        if(!in_array($href, $alreadyCrawled)) {
            $alreadyCrawled[] = $href;
            $crawling[] = $href;

            getDetails($href);
        }

    }

    array_shift($crawling);

    foreach($crawling as $site) {
        followLinks($site);
    }

}

$startUrl = "https://www.facebook.com/";
followLinks($startUrl);
?>