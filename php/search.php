<?php
class DLMClass {

	// <<< start >>>
	/* The above comment is used when parsing this file for 
	   packaging into a DLM. The global options variable and
	   the constructor are overwritten to hard-code the options */

	private $options;

	/**
	 * Constructs a DLM search class with required options
	 * 
	 * @param options	an object containing:
	 * 
	 * 						query (string)
	 * 							domain, queryPrefix, querySuffix
	 * 						maxResults (integer, 0 if no max)
	 * 						verbose (true/false)
	 * 						patterns (strings)
	 * 							body, item, title, page, hash,
	 * 							size, leeches, seeds, date, 
	 * 							download, category
	 * 						usePage (boolean)
	 * 							title, hash, size, leeches, 
	 * 							seeds, date, download, category
	 * 						useCache
	 * 							enable (boolean)
	 * 							directory (string)
	 * 	@return
	 */
	function __construct(&$options) {
		$this->options = &$options;
		/* If caching is enabled, then create a cache object */
		if ($options["cache"]["enable"]) {
			include_once('./cache.php');
			$this->options["cache"]["object"] = 
				new Cache($options["cache"]["directory"]);
		}
	}

	/* The comment below is the end token for parsing/packaging
	   into a DLM */
	// <<< end >>>

	/**
	 * Download station calls this function to set the appropriate URL 
	 * in the cURL object
	 * 
	 * @param curl	the cURL object
	 * @param query	the search string
	 * @return 		N/A
	 */
	public function prepare($curl, $query) {
		$q = $this->options["query"];
		/* Create the query URL and add it to the cURL object */
		$url = $q["domain"].$q["queryPrefix"].urlencode($query).$q["querySuffix"];
		curl_setopt($curl, CURLOPT_URL, $url);
	}

	/**
	 * Returns a size in bytes from a string and optional 
	 * modifier
	 * 
	 * @param size 	size as a string (e.g. "625.35 MB")
	 * @return size in bytes (e.g. 1048576) or -1 on error
	 */
	private function sizeInBytes($size) {
		/* Match a number with a single decimal point and 
		   optional size modifier. Remove any commas first. */
		$size = str_replace(',', '', $size);
		if (preg_match("/(\d+(?:\.[\d]+)?)\s*(KB|MB|GB|TB)?/i", $size, $matches)) {
			/* If a size modifier was found, then save it */
			$modifier = count($matches) > 2 ? 
				$modifier = $matches[2] : '';
		} else {
			/* No match? Return -1 as an error */
			return -1;
		}
		/* Get the number */
		$size = $matches[1];
		/* Convert to bytes using the modifier -- no modifier
		   assumes bytes */
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
	 * Called when INFO file has 'accountsupport' set to true and
	 * the user has clicked on
	 * Download Station > Settings > BT Search > [this DLM] > Edit
	 * 
	 * This function should employ its own method to validate 
	 * the username and password (e.g. construct a cURL object, 
	 * contact the login authority).
	 * 
	 * The response is shown to the user as a success/failure message
	 * 
	 * @param username	the user-entered username
	 * @param password	the user-entered password
	 * @return			true, if the account was validated.
	 */
	public function VerifyAccount($username, $password) {
		// $curl = curl_init();
		// curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		// curl_setopt($curl, CURLOPT_URL, _your_url_here_);
		// curl_close($curl);
		return true;
	}

	/**
	 * Uses cURL to fetch additional data at the given path. 
	 * The domain used is the one provided on object instantiation.
	 * 
	 * @param path	a path to the page details resource
	 * @param cache	a Cache object, or null to disable caching
	 */
	private function getPageDetails($path, $cache) {
		/* Make sure there's a path provided */
		if (empty($path)) {
			return false;
		}
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, true);
		/* Add the path to the domain */
		curl_setopt($curl, CURLOPT_URL, $this->options["query"]["domain"] . $path);
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
		return $result;
	}

	/**
	 * This function is called after Download Station has searched
	 * and received a response from the query URL. The response is
	 * passed to this function to parse the results and enter them
	 * into the plugin.
	 * 
	 * @param plugin	an object which accepts parsed results and
	 * 					will be shown to the user
	 * @param response	the raw response from the query URL
	 * @return 			# of results found (integer)
	 */
	public function parse($plugin, $response) {

		/* Shorten global variable names for ease of use */
		$patterns = $this->options["patterns"];
		$usePage = $this->options["usePage"];

		/* Create a parse results object which holds information
		   on the results of the parsing */
		$this->options["result"] = [
			"count" => 0,
			"info" => [],
			"errors" => [],
			"items" => [],
			"parsed" => []
		];

		$result = &$this->options["result"];
		$count = &$result["count"];
		$info = &$result["info"];
		$errors = &$result["errors"];
		$parsed = &$result["parsed"];
		$maxResults = $this->options["maxResults"];

		/* If there is a body pattern, then match the pattern and
		   provide the results for the item patterns */
		if (!empty($patterns["body"])) {
			if (preg_match($patterns["body"], $response, $matches)) {
				/* If there was a match then take either the first 
				   grouping in the match or, if no groupings, then
				   the whole match */
				if (count($matches) > 1) {
					$body = $matches[1];
					$info[] = "Body pattern with group matched";
				} else {
					$body = $matches[0];
					$info[] = "Body pattern (whole) matched";
				}
			} else {
				$info[] = "Body pattern did not match";
			}
		} else {
			$info[] = "No body pattern present";
		}

		/* There should be an item pattern which will match all the
		   individual results. The source, or subject, of the match
		   is either the whole response or, if a body pattern was
		   provided, then the body which was matched from the response.
		   Match all the items, returning the results in the order 
		   they were found and records the count. */
		if ($count = preg_match_all($patterns["item"], 
				empty($body) ? $response : $body, $result["items"], PREG_SET_ORDER)) {

			/* Go through all the matches (one per item) and extract 
			   the additional information (e.g. title, size, date) */
			$resultNum = 0;
			foreach ($result["items"] as $match) {
				/* The item is either the whole match or the 1st 
				   matching group */
				$item = count($match) > 1 ? $match[1] : $match[0];;
				
				/* Match the page which may contain follow-on details */
				if (!empty($patterns["page"])) {
					preg_match($patterns["page"], $item, $page);
					/* If there was a grouping matched, use that */
					$page = count($page) > 1 ? $page[1] : $page[0];
				}

				/* Fetch the details page which could be used below */
				$details = $this->getPageDetails($page, 
					$this->options["cache"]["object"]);
				
				/* Match each item */
				if (!empty($patterns["title"])) {
					/* If the usePage flag is set to true, then match
					   against the page details, otherwise use the 
					   item match */
					preg_match($patterns["title"], 
						$usePage["title"] ? $details : $item, $title);
					/* If there was a grouping matches, use that (1st match)
					   otherwise use the whole match (0th match) */;
					$title = count($title) > 1 ? $title[1] : $title[0];
				}

				if (!empty($patterns["download"])) {
					preg_match($patterns["download"], 
						$usePage["download"] ? $details : $item, $download);
					$download = count($download) > 1 ? $download[1] : $download[0];
				}
				if (!empty($patterns["size"])) {
					preg_match($patterns["size"], 
						$usePage["size"] ? $details : $item, $size);
					$size = count($size) > 1 ? $size[1] : $size[0];
					/* Convert to bytes */
					$size = $this->sizeInBytes($size);
				}
				if (!empty($patterns["date"])) {
					preg_match($patterns["date"], 
						$usePage["date"] ? $details : $item, $date);
					$date = count($date) > 1 ? $date[1] : $date[0];
				}
				if (!empty($patterns["hash"])) {
					preg_match($patterns["hash"], 
						$usePage["hash"] ? $details : $item, $hash);
					$hash = count($hash) > 1 ? $hash[1] : $hash[0];
				}
				if (!empty($patterns["seeds"])) {
					preg_match($patterns["seeds"], 
						$usePage["seeds"] ? $details : $item, $seeds);
					$seeds = intval(count($seeds) > 1 ? $seeds[1] : $seeds[0]);
				}
				if (!empty($patterns["leeches"])) {
					preg_match($patterns["leeches"], 
						$usePage["leeches"] ? $details : $item, $leeches);
					$leeches = intval(count($leeches) > 1 ? $leeches[1] : $leeches[0]);
				}
				if (!empty($patterns["category"])) {
					preg_match($patterns["category"], 
						$usePage["category"] ? $details : $item, $category);
					$category = count($category) > 1 ? $category[1] : $category[0];
				}

				/* TODO: Check the results */

				/* TODO: Convert the date to the expected format (e.g. "2017-05-03 12:05:02")

				/* Add the results to the plugin */
				$plugin->addResult($title, $download, $size, $date, 
					$page, $hash, $seeds, $leeches, $category);

				/* Add the results to the info object if in verbose mode */
				if ($this->options["verbose"]) {
					$parsed[] = [
						"title" => $title, "download" => $download,	"size" => $size, "date" => $date, 
						"page" => $page, "hash" => $hash, "seeds" => $seeds, "leeches" => $leeches,
						"category" => $category
					];
				}

				/* Artificially limit the number of results collected */
				$resultNum++;
				if ($maxResults > 0 && $resultNum == $maxResults) {
					$info[] = "Limited results to ".$resultNum." from ".count($result["items"]);
					/* Set the count to the maximum number, where we stopped */
					$count = $resultNum;
					break;
				}
			}
		} else {
			$errors[] = "No items matches found.";
		}

		/* Return the number of results found */
		return $count;
	}
}
?>