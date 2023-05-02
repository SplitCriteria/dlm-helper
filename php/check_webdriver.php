<?php

if (empty($_POST['webdriver'])) {
    die('No webdriver given.');
}

/* Initialize a cURL object */
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($curl, CURLOPT_VERBOSE, true);
/* Set the proxy URL */
curl_setopt($curl, CURLOPT_URL, $_POST['webdriver'].'/status/');
/* Get the result then close the object */
$result = curl_exec($curl);
curl_close($curl);

header('Content-type: application/json');
echo $result;
?>