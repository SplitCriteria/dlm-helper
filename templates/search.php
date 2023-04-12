<?php
class _class_ {

	private $domain = '_website_';
	private $qurl = '_queryurl_';
	public $max_results = _maxresults_;
	public $verbose = false;

	public function prepare($curl, $query) {
		$url = $this->domain . $this->qurl . urlencode($query);
		curl_setopt($curl, CURLOPT_URL, $url);
	}

	/**
	 * Returns a size in bytes
	 * 
	 * @param size 		unmodified size (e.g. 1)
	 * @param modifier	modifier (e.g. 'KB', 'MB', 'GB', 'TB')
	 * @return bytesize	size in bytes (e.g. 1,048,576)
	 */
	private function sizeInBytes($size, $modifier) {
		switch (strtoupper($modifier)) {
		case 'KB':
			return $size * 1024;
		case 'MB':
			return $size * 1024 * 1024;
		case 'GB':
			return $size * 1024 * 1024 * 1024;
		case 'TB':
			return $size * 1024 * 1024 * 1024 * 1024;
		default:
			return $size;
		}
	}

	/**
	 * Called when INFO file has 'accountsupport' set to true
	 * 
	 * Verifies the user account and returns true if valid.
	 */
	public function VerifyAccount($username, $password) {
		// TODO: Verify users account in whatever method you need
		
		// $curl = curl_init();
		// curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		// curl_setopt($curl, CURLOPT_URL, _your_url_here_);
		// curl_close($curl);
		return true;
	}

	public function parse($plugin, $response) {

		<<RSS:return $plugin->addRSSResults($response);
			>>
		
		<<JSON:# add key below which identifies the list of results in the JSON message 
		$result_array_key = '';
		// TODO: Replace the values below with the field names in the JSON message
		$fieldmapping = array(
			"title" => "title",
			"download" => "download",
			"size" => "size",
			"date" => "date",
			"page" => "page",
			"hash" => "hash",
			"seeds" => "seeds",
			"leechs" => "leechs",
			"category" => "category");
		return $plugin->addJsonResults($response, $result_array_key, $fieldmapping);>>
		
		<<MANUAL:// TODO: Enter regular expression to parse the response
		$regx = "//";

		if (!($result_count = preg_match_all($regx, $response, $rows, PREG_SET_ORDER))) {
			if ($this->verbose) {
				echo "Parsing: no matches found using regx '$regx'\n";
			}
			return 0;
		} else {
			if ($this->verbose) {
				echo "Parsing: found $result_count matches.\n";
			}
		}

		/* Get all the row data -- up to max_results */
		$count = 0;
		foreach ($rows as $row) {

			$plugin->addResult(
				$row[#],  // title
				$row[#],  // torrent download url/magnet
				$row[#],  // size in bytes
				$row[#],  // date (e.g. "2017-05-03 12:05:02")
				$row[#],  // url to torrent page referring this torrent
				$row[#],  // hash
				$row[#],  // seeds
				$row[#],  // leechs
				$row[#]); // category
	
			$count++;
			if ($this->max_results > 0 && $count == $this->max_results) {
				break;
			}
		}
		return $count;>>

		<<EXTRA:// TODO: Delete, or get additional required data
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_URL, _your_url_here_);
		curl_close($curl);>>
	}
}
?>
