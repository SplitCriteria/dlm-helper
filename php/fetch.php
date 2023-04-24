<?php

include_once('./cache.php');

/* Only continue if there's a URL to fetch */
if (empty($_POST['url'])) {
    die;
}

/* Create a cache object if requested */
if (!empty($_POST['cache'])) {
    $cache = new Cache('../cache');
}

/* Initialize a cURL object */
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($curl, CURLOPT_VERBOSE, true);

/* Set a user agent to mimic a real browser */
curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36");

/* Set the URL */
curl_setopt($curl, CURLOPT_URL, $_POST['url']);

/* Get the effective URL to use as a key in the cache */
$url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

/* If there's no cache, or it's not stored, then use cURL */
if (empty($cache) || !($result = $cache->get($url))) {
    $result = curl_exec($curl);
    /* If the cache object exists, then cache the result */
    if (!empty($cache)) {
        $cache->put($url, $result);
    }
}
curl_close($curl);

/* Dump the result */
echo $result;
?>