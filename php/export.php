<?php

include_once('./parse_url.php');

/* Define the directory in which the temp dlm files are stored */
$tmpDir = $_SERVER['DOCUMENT_ROOT'].'/tmp/';

/* If that temp directory doesn't exist, create it */
if (!file_exists($tmpDir)) {
    mkdir($tmpDir, 0700); // 0700: only root permissions
}

/* Name the temp tar and compressed (dlm) files */
$tarFile = $tmpDir.'temp.tar';
$dlmFile = $tarFile.'.gz';

/* Parse the url */
$query = parseURL($_POST['searchURL'], $_POST['searchText']);

/* Create the metadata which becomes the INFO file */
$info = [ ];
$info['name'] = $_POST['moduleName'];
$info['displayname'] = $_POST['moduleDisplayName'];
$info['description'] = $_POST['moduleDescription'];
$info['version'] = $_POST['moduleVersion'];
$info['site'] = $query["domain"];
$info['module'] = 'search.php';
$info['type'] = 'search';
$info['class'] = $_POST['moduleName'];
$info['accountsupport'] = isset($_POST['moduleAccountSupport']) ? true : false;

/* Hard code the options and blank constructor */
/* Use str_replace to escape any " marks in the patterns */
$newConstructor = '
class '.$_POST['moduleName']. ' {

    private $options = [
        "query" => [
            "domain" => "'.$query["domain"].'",
            "queryPrefix" => "'.$query["prefix"].'",
            "querySuffix" => "'.$query["suffix"].'",
        ],
        "maxResults" => '.intval($_POST["moduleMaxResults"]).',
        "verbose" => false,
        "patterns" => [
            "body" => "'.str_replace('"', '\"', $_POST["patternBody"]).'",
            "item" => "'.str_replace('"', '\"', $_POST["patternItem"]).'",
            "title" => "'.str_replace('"', '\"', $_POST["patternTitle"]).'",
            "page" => "'.str_replace('"', '\"', $_POST["patternPage"]).'",
            "hash" => "'.str_replace('"', '\"', $_POST["patternHash"]).'",
            "size" => "'.str_replace('"', '\"', $_POST["patternSize"]).'",
            "leeches" => "'.str_replace('"', '\"', $_POST["patternLeeches"]).'",
            "seeds" => "'.str_replace('"', '\"', $_POST["patternSeeds"]).'",
            "date" => "'.str_replace('"', '\"', $_POST["patternDate"]).'",
            "download" => "'.str_replace('"', '\"', $_POST["patternDownload"]).'",
            "category" => "'.str_replace('"', '\"', $_POST["patternCategory"]).'"
        ],
        "usePage" => [
            "title" => '.($_POST["patternTitleUsePage"] == "on" ? 'true' : 'false').',
            "hash" => '.($_POST["patternHashUsePage"] == "on" ? 'true' : 'false').',
            "size" => '.($_POST["patternSizeUsePage"] == "on" ? 'true' : 'false').',
            "leeches" => '.($_POST["patternLeechesUsePage"] == "on" ? 'true' : 'false').',
            "seeds" => '.($_POST["patternSeedsUsePage"] == "on" ? 'true' : 'false').',
            "date" => '.($_POST["patternDateUsePage"] == "on" ? 'true' : 'false').',
            "download" => '.($_POST["patternDownloadUsePage"] == "on" ? 'true' : 'false').',
            "category" => '.($_POST["patternCategoryUsePage"] == "on" ? 'true' : 'false').'
        ],
        "useCache" => [
            "enable" => false
        ],
        "proxy" => [
            "enable" => '.($_POST["moduleUseProxy"] == "on" ? 'true' : 'false').',
            "url" => "http://localhost:4445",
        ]
    ];

    function __construct() { }';

/* Create the archive */
$archive = new PharData($tarFile);
/* Add the INFO (metadata) file */
$archive['INFO'] = json_encode($info);
/* Add the search module which is the actual search.php used in 
   testing with the options hard-coded */
$searchFileParsingPattern = "/(.+)\/\/ <<< start >>>.+<<< end >>>(.+)/s";
/* Match the sections before and after the <<< start/end >>> cut points */
preg_match($searchFileParsingPattern, 
    file_get_contents('./search.php'), $matches);

/* Insert the hard-coded options and new constructor */
$archive['search.php'] = $matches[1].$newConstructor.$matches[2];

/* Compress as a tar.gz */
$archive->compress(Phar::GZ);

/* Create a space-less file name made from the module name 
   and the version */
$dlmUniqueName = 
    str_replace([' ','.'], '_', $_POST['moduleName'].'_v_'.$_POST['moduleVersion']);


/* Tell the browser we're serving a tar.gz file */
header('Content-Type: application/x-gtar');
/* Tell the browser there's an attachment and give the name */
header('Content-Disposition: attachment; filename="'.$dlmUniqueName.'.dlm"');
/* Tell the browser this DLM has already expired */
header('Expires: 0');

/* Dump the file */
readfile($dlmFile);

/* Delete the temp files */
unlink($tarFile);
unlink($dlmFile);

?>