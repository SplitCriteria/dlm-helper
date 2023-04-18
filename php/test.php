<?php
ini_set('display_errors', 1);
include_once('./cache.php');
include_once('./search.php');
$options = [
    "query" => [
        "domain" => "https://1337x.to",
        "queryPrefix" => "/search/",
        "querySuffix" => "/1/"
    ],
    "maxResults" => 0,
    "verbose" => true,
    "patterns" => [
        "body" => "/<tbody>(.*?)<\/tbody>/s",
        "item" => "/<tr>(.*?)<\/tr>/s",
        "title" => "/href=\"\/torrent\/[^>]*>([^<]+)/s",
        "page" => "/href=\"(\/torrent\/[^\"]*)/",
        "hash" => "/magnet:\?xt=urn:btih:([A-Z0-9]{40})/",
        "size" => "/size[^\"]*\">([^<]*)/",
        "leeches" => "/leeches\">(\d+)/",
        "seeds" => "/seeds\">(\d+)/",
        "date" => "/date\">([^<]*)/",
        "download" => "/magnet:[^\"]*/",
        "category" => "/Category.*<span>(\w+)/"
    ],
    "usePage" => [
        "hash" => true,
        "download" => true,
        "category" => true
    ],
    "cache" => [
        "enable" => true,
        "directory" => "../cache"
    ]
];
$dlm = new DLMClass($options);

class DSPlugin {

    public function addResult($title, $download, $size, $date, 
            $page, $hash, $seeds, $leeches, $category) {
        echo "Result added:\n\tTitle: $title\n\tDownload: $download\n\tSize: $size".
            "\n\tDate: $date\n\tPage: $page\n\tHash: $hash\n\tSeeds: $seeds".
            "\n\tLeeches: $leeches\n\tCategory: $category\n";
    }
}

header('Content-Type: text/plain');

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($curl, CURLOPT_VERBOSE, true);
$dlm->prepare($curl, "The Simpsons");

/* Get the full URL after the DLM module has prepared it */
$url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

/* Get the response from the URL source or the cache, if it exists */
$cache = new Cache('../cache');
if (!($result = $cache->get($url))) {
    /* Not cached -- get from the source */
    if ($result = curl_exec($curl)) {
        /* If there's a good result, cache it */
        $cache->put($url, $result);
    } else {
        echo "Unable to get response from $url\n";
    }
}

/* Close the curl object */
curl_close($curl);

/* If we got a good response, then parse the result */
if ($result) {
    $dlm->parse(new DSPlugin(), $result);
}

// echo json_encode($options, JSON_PRETTY_PRINT);

?>