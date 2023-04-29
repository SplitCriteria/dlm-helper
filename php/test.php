<?php
include_once('./cache.php');
include_once('./parse_url.php');
include_once('./search.php');

/* Parse the url */
$query = parseURL($_POST['searchURL'], $_POST['searchText']);

/* Create the search.php DLM class options */
$options = [
    "query" => [
        "domain" => $query["domain"],
        "queryPrefix" => $query["prefix"],
        "querySuffix" => $query["suffix"]
    ],
    "maxResults" => $_POST["maxResults"],
    "verbose" => true,
    "patterns" => [
        "body" => $_POST["patternBody"],
        "item" => $_POST["patternItem"],
        "title" => $_POST["patternTitle"],
        "page" => $_POST["patternPage"],
        "hash" => $_POST["patternHash"],
        "size" => $_POST["patternSize"],
        "leeches" => $_POST["patternLeeches"],
        "seeds" => $_POST["patternSeeds"],
        "date" => $_POST["patternDate"],
        "download" => $_POST["patternDownload"],
        "category" => $_POST["patternCategory"]
    ],
    "usePage" => [
        "title" => $_POST["patternTitleUsePage"] == "true",
        "hash" => $_POST["patternHashUsePage"] == "true",
        "size" => $_POST["patternSizeUsePage"] == "true",
        "leeches" => $_POST["patternLeechesUsePage"] == "true",
        "seeds" => $_POST["patternSeedsUsePage"] == "true",
        "date" => $_POST["patternDateUsePage"] == "true",
        "download" => $_POST["patternDownloadUsePage"] == "true",
        "category" => $_POST["patternCategoryUsePage"] == "true"
    ],
    "useCache" => [
        "enable" => $_POST["cache"] == "true",
        "directory" => $_POST["cacheDir"]
    ],
    "proxy" => [
        "enable" => $_POST["proxyEnable"] == "true",
        "url" => $_POST["proxyURL"]
    ]
];

$dlm = new DLMClass($options);

$results = [
    "info" => [],
    "data" => []
];

class DSPlugin {

    public function addResult($title, $download, $size, $date, 
            $page, $hash, $seeds, $leeches, $category) {
        global $results;
        $results['data'][] = 
            '<div class="col-12">
                <div class="card">
                    <div class="card-header">'.$title.'</div>
                    <div class="card-body">
                        <div class="card-text">Download: '.$download.'</div>
                        <div class="card-text">Size: '.$size.'</div>
                        <div class="card-text">Date: '.$date.'</div>
                        <div class="card-text">Page: '.$page.'</div>
                        <div class="card-text">Hash: '.$hash.'</div>
                        <div class="card-text">Seeds: '.$seeds.'</div>
                        <div class="card-text">Leeches: '.$leeches.'</div>
                        <div class="card-text">Category: '.$category.'</div>
                    </div>
                </div>
            </div>';
    }
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($curl, CURLOPT_VERBOSE, true);
/* Custom implementation of prepare returns the URL (a return 
   value is not specified in the Synology specification). The
   search text should be URL encoded, so decode it before 
   sending to the search class.  */
$url = $dlm->prepare($curl, urldecode($_POST['searchText']));

$results['info'][] = "URL to get: $url";

/* Create a cache object if desired */
if ($_POST['cache'] == "true") {
    $cacheDir = isset($_POST['cacheDir']) ? $_POST['cacheDir'] : '../cache';
    $cache = new Cache($cacheDir);
    $results['info'][] = "Using cache in directory '$cacheDir'";
}
/* Get the response from the URL source or the cache, if it exists */
if (empty($cache) || !($result = $cache->get($url))) {
    /* Not cached -- get from the source */
    if ($result = curl_exec($curl)) {
        /* If there's a good result, and there's a cache, store it */
        if (!empty($cache)) {
            $cache->put($url, $result);
        }
        $results['info'][] = "Data received from cURL";
    } else {
        $results['info'][] = "No response from '$url'";
    }
} else {
    $results['info'][] = "Data is from cache";
}

/* Close the curl object */
curl_close($curl);

/* If we got a good response, then parse the result 
   which will dump the result in HTML */
if ($result) {
    $results['source'] = $result;
    $dlm->parse(new DSPlugin(), $result);
}

/* Catch the DLM options and results (internals of the parse) */
$results['search.php options'] = $options;
$results['search.php parse'] = $dlm->getResults();

/* Output the data in JSON format */
header('Content-Type: application/json');
echo json_encode($results);

?>