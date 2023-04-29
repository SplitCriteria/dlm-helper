<?php

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

require_once('../vendor/autoload.php');

include_once('./cache.php');

/**
 * Fetches a URL using a chrome webdriver
 * 
 * @param url   target to fetch
 * @return html of the webpage on success or false on failure
 */
function webDriverFetch($url, $host = 'http://localhost:4444/wd/hub') {
    try {
        $capabilities = DesiredCapabilities::chrome();
        $driver = RemoteWebDriver::create($host, $capabilities);
        $driver->get($url);
        $driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('html'))
        );
        $result = $driver->findElement(WebDriverBy::cssSelector('html'));
        /* ->getAttribute('innerHTML') does not work in PHP WebDriver 
           implementation; instead use ->getDOMProperty */
        $result = $result->getDOMProperty('innerHTML');
    } catch(Exception $e) {
        /* Something didn't work -- just return false */
        return false;
    } finally {
        /* Quit the session */
        if ($driver) {
            $driver->quit();
        }
    }
    return $result;
}

/**
 * Fetches a URL using curl
 * 
 * @param url   target to fetch
 * @return html of the webpage on success or false on failure
 */
function curlFetch($url) {
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
    /* Get the result then close the object */
    $result = curl_exec($curl);
    curl_close($curl);

    return $result;
}

/* Only continue if there's a URL to fetch */
if (empty($_POST['url'])) {
    die;
}

/* Create a cache object if requested */
if ($_POST['cache'] == "true") {
    $cacheDir = empty($_POST['cacheDir']) ? '../cache' : $_POST['cacheDir'];
    $cache = new Cache($cacheDir);
}

/* Get the effective URL to use as a key in the cache */
$url = $_POST['url'];

/* If there's no cache, or not in the cache, fetch the page from its source */
if (empty($cache) || empty($result['data'] = $cache->get($url))) {
    /* If desired by the user, try to get the data from a 
       webdriver resource first then through curl second */
    if (!empty($_POST["proxy"])) {
        $result['data'] = webDriverFetch($url);
    }
    if (empty($result)) {
        $result['data'] = curlFetch($url);
        if (!empty($result)) {
            $result['source'] = 'curl';
        }
    } else {
        $result['source'] = 'webdriver';
    }
    
    /* If one of the responses was not empty then cache the result */
    if (!empty($result) && !empty($cache)) {
        $cache->put($url, $result['data']);
    }
} else {
    $result['source'] = 'cache';
}

/* Dump result or an error */
header('Content-type: application/json');
echo json_encode($result);
?>