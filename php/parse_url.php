<?php
/**
 * Takes a http(s) URL and parses the domain and path
 * parts (a prefix and a suffix) surrounding an exclusion
 * text.
 * 
 * @param url       a http(s) URL
 * @param exclude   part of the URL to exclude which creates
 *                  a prefix and suffix around the exclusion
 * @return object with "domain", "prefix", and "suffix"
 */
function parseURL($url, $exclude) {
    /* Extract the domain and search path */
    /* Define a domain (group 1) /path (group 2) regex */
    $domainPathPattern = "/((?:https?:\/\/)?[a-zA-Z0-9-.]+)(\/.*)?/";
    /* Extract the domain and query path */
    preg_match($domainPathPattern, $url, $domainPath);
    $domain = $domainPath[1];
    $queryPath = $domainPath[2];
    /* It's assumed the queryPath contains the search text (urlencoded)
    and optional prefix and optional suffix. Find the search prefix
    and suffix. */
    /* Escape special characters in the search text used as a regex */
    $searchPathPattern = str_replace(
        [ '$',  '-',  '+',  '.',  '*',  '(',  ')'], 
        ['\$', '\-', '\+', '\.', '\*', '\(', '\)'], 
        $exclude);
    /* Capture the prefix and suffix groups */
    $searchPathPattern = "/(.*)" . $searchPathPattern . "(.*)/";
    preg_match($searchPathPattern, $queryPath, $matches);
    $queryPrefix = $matches[1];
    $querySuffix = $matches[2];
    /* If the query path doesn't exist, use the root as the query path */
    if (empty($queryPath)) {
        $queryPath = '/';
    }
    return [
        "domain" => $domain,
        "prefix" => $queryPrefix,
        "suffix" => $querySuffix
    ];
}
?>