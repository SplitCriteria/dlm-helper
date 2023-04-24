<?php
include_once('./cache.php');
include_once('./parse_url.php');
include_once('./search.php');

header('Content-Type: text/html');

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
        "enable" => $_POST["cache"],
        "directory" => $_POST["cacheDir"]
    ]
];

$dlm = new DLMClass($options);

class DSPlugin {

    public function addResult($title, $download, $size, $date, 
            $page, $hash, $seeds, $leeches, $category) {
        echo '<div class="col-12">
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
$dlm->prepare($curl, $_POST['searchText']);

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
        echo "<p>Unable to get response from $url</p>";
    }
}

/* Close the curl object */
curl_close($curl);

/* If we got a good response, then parse the result 
   which will dump the result in HTML */
if ($result) {
    $dlm->parse(new DSPlugin(), $result);
}
?>