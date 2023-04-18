<?php

ini_set('display_errors', 1);

/* Define the directory in which the temp dlm files are stored */
$tmpDir = $_SERVER['DOCUMENT_ROOT'].'/tmp/';

/* If that temp directory doesn't exist, create it */
if (!file_exists($tmpDir)) {
    mkdir($tmpDir, 0700); // 0700: only root permissions
}

/* Name the temp tar and compressed (dlm) files */
$tarFile = $tmpDir.'temp.tar';
$dlmFile = $tarFile.'.gz';

/* Extract the domain and search path */
/* Define a domain (group 1) /path (group 2) regex */
$domainPathPattern = "/((?:https?:\/\/)?[a-zA-Z0-9-.]+)(\/.*)?/";
/* Extract the domain and query path */
preg_match($domainPathPattern, $_POST['searchURL'], $domainPath);
$domain = $domainPath[1];
$queryPath = $domainPath[2];
/* It's assumed the queryPath contains the search text (urlencoded)
   and optional prefix and optional suffix. Find the search prefix
   and suffix. */
/* Escape special characters in the search text used as a regex */
$searchPathPattern = str_replace(
    [ '$',  '-',  '+',  '.',  '*',  '(',  ')'], 
    ['\$', '\-', '\+', '\.', '\*', '\(', '\)'], 
    $_POST['searchText']);
/* Capture the prefix and suffix groups */
$searchPathPattern = "/(.*)" . $searchPathPattern . "(.*)/";
preg_match($searchPathPattern, $queryPath, $matches);
$queryPrefix = $matches[1];
$querySuffix = $matches[2];
/* If the query path doesn't exist, use the root as the query path */
if (empty($queryPath)) {
    $queryPath = '/';
}

/* Create the metadata which becomes the INFO file */
$info = [ ];
$info['name'] = $_POST['moduleName'];
$info['displayname'] = $_POST['moduleDisplayName'];
$info['description'] = $_POST['moduleDescription'];
$info['version'] = $_POST['moduleVersion'];
$info['site'] = $domain;
$info['module'] = 'search.php';
$info['type'] = 'search';
$info['class'] = 'DLMClass';
$info['accountsupport'] = isset($_POST['moduleAccountSupport']) ? true : false;

/* Create the archive */
$archive = new PharData($tarFile);
/* Add the INFO (metadata) file */
$archive['INFO'] = json_encode($info);
/* Add the search module */
$archive['search.php'] = '
<?php class DLMClass {

    public function prepare($curl, $query) {
        curl_setopt($curl, CURLOPT_URL, "https://www.google.com");
    }

    public function parse($plugin, $response) {

        $plugin->addResult(
            "Title 1",  // title
            "magnet://something.er.other/",  // torrent download url/magnet
            100000000,  // size in bytes
            "2017-05-03 12:05:02",  // date (e.g. "2017-05-03 12:05:02")
            "www.google.com/1/",  // url to torrent page referring this torrent
            "",  // hash
            20,  // seeds
            2,  // leechs
            "test"); // category

        $plugin->addResult(
            "Title 2",  // title
            "magnet://something.er.other/",  // torrent download url/magnet
            200000000,  // size in bytes
            "2017-07-03 12:05:02",  // date (e.g. "2017-05-03 12:05:02")
            "www.google.com/1/",  // url to torrent page referring this torrent
            "",  // hash
            30,  // seeds
            3,  // leechs
            "test"); // category

        return 2;
    }
}
?>';

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

/* Dump the file */
readfile($dlmFile);

/* Delete the temp files */
unlink($tarFile);
unlink($dlmFile);

?>