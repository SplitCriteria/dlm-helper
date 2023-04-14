<?php

ini_set('display_errors', 1);

// Define the directory in which the temp dlm files are stored
$tmpDir = $_SERVER['DOCUMENT_ROOT'].'/tmp/';

// If that temp directory doesn't exist, create it
if (!file_exists($tmpDir)) {
    mkdir($tmpDir, 0700); // 0700: only root permissions
}

// Name the temp tar and compressed (dlm) files
$tarFile = $tmpDir.'temp.tar';
$dlmFile = $tarFile.'.gz';

// Create the metadata which becomes the INFO file
$info = [ ];
$info['name'] = 'testModule';
$info['displayname'] = 'displayname';
$info['description'] = 'description';
$info['version'] = '0.1';
$info['site'] = 'www.google.com';
$info['module'] = 'search.php';
$info['type'] = 'search';
$info['class'] = 'DLMClass';
$info['accountsupport'] = false;

// Create the archive
$archive = new PharData($tarFile);
// Add the INFO (metadata) file
$archive['INFO'] = json_encode($info);
// Add the search module
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

// Compress as a tar.gz
$archive->compress(Phar::GZ);

// Tell the browser we're serving a tar.gz file
header('Content-Type: application/x-gtar');
// Tell the browser there's an attachment and give the name
header('Content-Disposition: attachment; filename="test.dlm"');

// Dump the file
readfile($dlmFile);

// Delete the temp files
unlink($tarFile);
unlink($dlmFile);

?>