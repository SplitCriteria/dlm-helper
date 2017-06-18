<?php
namespace BoogsDLM;
class DLMPlugin {

	public $results = array();
	
	function addRSSResults($response) {
		echo "DLMPlugin->addRSSResults() called -- not implemented yet!\n";
		return 0;
	}

	function addJsonResults($response, $resultKey, $kvMap) {
		/* Decode the JSON data */
		$data = json_decode($response, true);
		if (!$data) {
			echo "Error decoding JSON (" . json_last_error_msg() . ")";
			return 0;
		}
		/* Make sure the result key exists */
		if (!array_key_exists($resultKey, $data)) {
			echo "Unable to find key '" . $resultKey . "' in JSON response.";
			return 0;
		}
		
		/* Cache the custom keys */
		$titleKey = $kvMap["title"];
		$downloadKey = $kvMap["download"];
		$sizeKey = $kvMap["size"];
		$dateKey = $kvMap["date"];
		$pageKey = $kvMap["page"];
		$hashKey = $kvMap["hash"];
		$seedsKey = $kvMap["seeds"];
		$leechsKey = $kvMap["leechs"];
		$categoryKey = $kvMap["category"];

		/* Add each result in the JSON array */
		$count = 0;
		foreach ($data[$resultKey] as $singleResult) {
			addResult(
				$singleResult[$titleKey],
				$singleResult[$downloadKey],
				$singleResult[$sizeKey],
				$singleResult[$dateKey],
				$singleResult[$pageKey],
				$singleResult[$hashKey],
				$singleResult[$seedsKey],
				$singleResult[$leechsKey],
				$singleResult[$categoryKey]);
			$count++;
		}
		return $count;
	}

	function addResult($title, $downloadURL, $size, $datetime, $pageURL, $hash, $seeds, $leechs, $category) {
		$this->results[] = array(
			"title" => $title,
			"download" => $downloadURL,
			"size" => $size,
			"date" => $datetime,
			"hash" => $hash,
			"seeds" => $seeds,
			"leechs" => $leechs,
			"page" => $pageURL,
			"category" => $category);
	}
}
?>
