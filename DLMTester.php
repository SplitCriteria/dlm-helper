<?php
namespace SplitCriteria\DLMHelper;
use \Exception;

include_once('common.php');
include_once('DLMInfo.php');
include_once('DLMPlugin.php');

class DLMTester {

	private $isError;
	private $errorMsg;
	public $results;
	public $verbose = false;
	public $cache;

	private function createCountArray() {
		return array(
			"title" => 0,
			"download" => 0,
			"size" => 0, 
			"date" => 0,
			"page" => 0,
			"hash" => 0,
			"seeds" => 0,
			"leechs" => 0,
			"category" => 0);
	}

	private function sumCountArray($ca) {
		return $ca['title'] + $ca['download'] + $ca['size'] +
			$ca['date'] + $ca['page'] + $ca['hash'] +
			$ca['seeds'] + $ca['leechs'] + $ca['category'];
	}

	private function printCountArray($ca) {
		echo $ca['title'] > 0 ? ($ca['title'] . "x Title ") : "", 
			$ca['download'] > 0 ? ($ca['download'] . "x Download ") : "",
			$ca['size'] > 0 ? ($ca['size'] . "x Size ") : "",
			$ca['date'] > 0 ? ($ca['date'] . "x Date ") : "",
			$ca['page'] > 0 ? ($ca['page'] . "x Page ") : "",
			$ca['hash'] > 0 ? ($ca['hash'] . "x Hash ") : "",
			$ca['seeds'] > 0 ? ($ca['seeds'] . "x Seeds ") : "",
			$ca['leechs'] > 0 ? ($ca['leechs'] . "x Leechs ") : "",
			$ca['category'] > 0 ? ($ca['category'] . "x Category ") : "";
	} 

	public function btSearch(DLMInfo $info, $query, $maxresults = 0) {
		/* Set up the local variables */
		$error = &$this->isError;
		$msg = &$this->errorMsg;
		$error = false;
		$msg = '';
		/* Check the parameter */
		if (is_null($info)) {
			$error = true;
			appendOnNewLine($msg, "No DLMInfo object provided.");
			return;
		} else if (!$info->isWellFormed) {
			$error = true;
			appendOnNewLine($msg, "DLM info file is not well formed.");
			return;
		}
		
		/* Include the module file and instantiate the class */
		$module_file = $info->workingDir . DIRECTORY_SEPARATOR . $info->module;
		include_once($module_file);
		$searchClass = new $info->class;
		if ($this->verbose) {
			$searchClass->verbose = true;
		}
		$searchClass->max_results = $maxresults;
		if ($this->verbose) {
			echo "Results are " . ($maxresults > 0 ? "limited to $maxresults." : "NOT limited.") . "\n";
		}

		/* Test the curl prepare function */
		$curl = curl_init();
		$prepareFunc = "prepare";
		$searchClass->$prepareFunc($curl, $query);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		
		$isResultFromCache = false;
		if (isset($this->cache)) {
			/* Use the cache instead of executing a curl request */
			if (file_exists($this->cache)) {
				$result = file_get_contents($this->cache);
				if (!$result) {
					echo "Unable to read cache file (", $this->cache, ")\n";
					exit(1);
				}
				$isResultFromCache = true;
			} else {
				/* Make the curl request and cache it */ 		
				$result = curl_exec($curl);
				$fp = fopen($this->cache, 'wb');
				if (!$fp) {
					echo "Unable to open cache file (", $this->cache, ")!\n";
					var_dump(error_get_last());
				} else {
					if (!fwrite($fp, $result)) {
						echo "Unable to write ", strlen($result), 
							" bytes to cache file (", $this->cache, ")!\n";
					} else if ($this->verbose) {
						echo "Cache: ", strlen($result), " bytes written to ",
							$this->cache, "\n";
					}
					fclose($fp);
				}
			}
		} else {
			/* If no cache, then make the cur request */
			$result = curl_exec($curl);
		}

		if (!$result) {
			echo "Curl error: ", curl_error($curl), "\n";
		}
		if ($this->verbose) {
			echo "Query URL: ", curl_getinfo($curl, CURLINFO_EFFECTIVE_URL),
				($isResultFromCache ? " (not called -- cache used)" : ""), "\n";
			if (!$isResultFromCache) {
				echo "Website response code: ", 
					curl_getinfo($curl, CURLINFO_RESPONSE_CODE), "\n";
			}
		}
		curl_close($curl);
		
		/* Test the parse function */
		$parseFunc = "parse";
		$plugin = new DLMPlugin();
		$searchClass->$parseFunc($plugin, $result);
		$this->results = $plugin->results;

		/* Check the results */
		date_default_timezone_set("UTC"); /* Needed to use strtotime() without a warning */
		$validCount = 0;
		$regxURL = "/^(https?:\/\/)?([\w\.\-\?\[\]\$\(\)\*\+\/#@!&',:;~=_%]+)+$/";
		$regxMagnet = "/^magnet:\?xt=urn:(\w+):([a-zA-Z0-9]{40})&dn=([\w\.\-\?\[\]\$\(\)\*\+\/#@!&',:;~=_%]+)$";
		$regxHash = "/^[a-zA-Z0-9]{40}$/";
		$regxInt = "/^[0-9]+$/";
		$emptyFields = $this->createCountArray();
		$invalidFields = $this->createCountArray();
		/* Check for properly formatted fields */
		$count = 0;
		foreach ($this->results as $result) {
			if ($this->verbose) {
				echo "Result #$count:\n";
			}
			
			/* Assume the result is valid */
			$invalid = false;
			/* Check the title field -- basically could be anything */
			if (empty($result['title'])) {
				$emptyFields['title']++;
				$invalidFields['title']++;
				$invalid = true;
				echo ConsoleText::RED_BOLD;
			}
			if ($this->verbose) {
				echo "\tTitle: ", $result['title'], ConsoleText::NORMAL, "\n";
			}

			/* Check the download URL */
			if (empty($result['download'])) {
				$emptyFields['download']++;
				/* Empty also means invalid, but if the URL is empty, it will 
				   also fail the preg_match below which flags as invalid.  */
			}
			if (!(preg_match($regxURL, $result['download']) || preg_match($regxMagnet, $result['download']))) {	
				$invalidFields['download']++;
				$invalid = true;
				echo ConsoleText::RED_BOLD;
			}
			if ($this->verbose) {
				echo "\tTorrent URL: ", $result['download'], ConsoleText::NORMAL, "\n";
			}
		
			/* Check the size field (integer or float are valid) */
			if (empty($result['size'])) {
				$emptyFields['size']++;
			} else if (!(is_int($result['size']) || is_float($result['size']))) {
				$invalidFields['size']++;
				$invalid = true;
				echo ConsoleText::RED_BOLD;
			}
			if ($this->verbose) {
				echo "\tSize: ", $result['size'], ConsoleText::NORMAL, "\n";
			}

			/* Check the date/time field -- check with php strtotime function */
			if (empty($result['date'])) {
				$emptyFields['date']++;
			/* Call to common.php isDateValid(String) */
			} else if (!isDateValid($result['date'])) {
				$invalidFields['date']++;
				$invalid = true;
				echo ConsoleText::RED_BOLD;
			}
			if ($this->verbose) {
				echo "\tDate: ", $result['date'], ConsoleText::NORMAL, "\n";
			}

			/* Check the page URL */
			if (empty($result['page'])) {
				$emptyFields['page']++;
			} else if (!preg_match($regxURL, $result['page'])) {
				$invalidFields['page']++;
				$invalid = true;
				echo ConsoleText::RED_BOLD;
			}
			if ($this->verbose) {
				echo "\tDetails URL: ", $result['page'], ConsoleText::NORMAL, "\n";
			}

			/* Check the hash variable */
			if (empty($result['hash'])) {
				$emptyFields['hash']++;
			} else if (!preg_match($regxHash, $result['hash'])) {
				$invalidFields['hash']++;
				$invalid = true;
				echo ConsoleText::RED_BOLD;
			}
			if ($this->verbose) {
				echo "\tHash: ", $result['hash'], ConsoleText::NORMAL, "\n";
			}

			/* Check the seeds and leechs fields */
			if (empty($result['seeds'])) {
				$emptyFields['seeds']++;
			} else if (!(is_int($result['seeds']) || preg_match($regxInt, $result['seeds']))) {
				$invalidFields['seeds']++;
				$invalid = true;
				echo ConsoleText::RED_BOLD;
			}
			if ($this->verbose) { 
				echo "\tSeeds: ", $result['seeds'], ConsoleText::NORMAL, "\n";
			}

			if (empty($result['leechs'])) {
				$emptyFields['leechs']++;
			} else if (!(is_int($result['leechs']) || preg_match($regxInt, $result['leechs']))) {
				$invalidFields['leechs']++;
				$invalid = true;
				echo ConsoleText::RED_BOLD;
			}
			if ($this->verbose) {
				echo "\tLeechs: ", $result['leechs'], ConsoleText::NORMAL, "\n";
			}

			/* The category field really doesn't have a restriction */
			if (empty($result['category'])) {
				$emptyFields['category']++;
			}
			if ($this->verbose) { 
				echo "\tCategory: ", $result['category'], ConsoleText::NORMAL, "\n";
			}

			/* Total results */
			if (!$invalid) {
				$validCount++;
			}
			$count++;
		}
		/* Reset the console text color */
		echo ConsoleText::NORMAL;
		/* Print the results to the user */
		echo "Search module returned $count results ($validCount of $count appear to be valid).\n";
		if ($validCount < $count) {
			echo "Invalid Fields (", $this->sumCountArray($invalidFields), " found): ", $this->printCountArray($invalidFields), "\n";
		}
		$emptyFieldCount = $this->sumCountArray($emptyFields);
		if ($emptyFieldCount > 0) {
			echo "Empty Fields ($emptyFieldCount found): ", $this->printCountArray($emptyFields), "\n";
		}
		/* Give the user some help if they have invalid results and didn't ask to look at them */
		if (!$this->verbose && $validCount != $count) {
			echo ConsoleText::RED_BOLD, "Invalid results are highlighted by using option -v, --verbose\n", 
				ConsoleText::NORMAL;
		}
	}
}

/* Get the command line options */
$shortoptions = "vc:m:o:s:";
$longopts = array("verbose","cache:","max:","output:","search:");
$options = getopt($shortoptions, $longopts);

/* Validate the command line options */
if (!$options) {
	$fatalError = true;
} else {
	/* Extract the DLM INFO filename (the last command line option) */
	$dlmFilename = $argv[$argc-1];
	if (!file_exists($dlmFilename)) {
		$dlmDefaultUsed = true;
		$dlmFilename = 'INFO';
	}
	if (!file_exists($dlmFilename)) {
		$fatalError = true;
		if (isset($dlmDefaultUsed)) {
			echo "DLM_INFO_file '" . $argv[$argc-1] . "' not found, 'INFO' used as DLM_INFO_file.\n";
		}
		echo "DLM_INFO_file '$dlmFilename' not found.\n\n";
	}

	/* Extract the verbose option */
	$verbose = (key_exists('v', $options) || key_exists('verbose', $options));

	/* Extract the output format option */
	$valid_output = array("ARRAY","JSON", "JSON_PRETTY");
	if (key_exists('o', $options)) {
		$output = strtoupper($options['o']);
	} else if (key_exists('output', $options)) {
		$output = strtoupper($options['output']);
	}
	if (!isset($output)) {
		$output = NULL;
	} else if (!in_array($output, $valid_output)) {
		echo "Invalid output format: $output\n";
		exit(1);
	}

	/* Extract the cache option */
	$cache_dir = "./cache/";
	if (key_exists('c', $options)) {
		$cache = $cache_dir . $options['c'];
	} else if (key_exists('cache', $options)) {
		$cache = $cache_dir . $options['cache'];
	}
	/* Make sure the cache directory exists */
	if (isset($cache) && !file_exists($cache_dir) && !mkdir($cache_dir, 0700, true)) {
		echo "Unable to make cache directory: $cache_dir\n";
		exit(1);
	}

	/* Extract the max results option */
	if (key_exists('m', $options)) {
		$maxresults = (int)$options['m'];
	} else if (key_exists('count', $options)) {
		$maxresults = (int)$options['max'];
	}
	if (!isset($maxresults)) {
		$maxresults = -1;
	}
	
	/* Extract the search string options */
	if (key_exists('s', $options)) {
		$query = $options['s'];
	} else if (key_exists('search', $options)) {
		$query = $options['search'];
	} else {
		$query = NULL;
	}
	/* Validate the search string (it must exist and there must only be one) */
	if (is_null($query)) {
		$fataError = true;
		echo "No search argument specified.\n\n";
	} else if (is_array($query)) {
		$query = $query[0];
		echo $argv[0] . " only accepts a single search parameter. '$query' used.\n\n";
	}
}

/* Return usage instructions if there's a fatal command line error */
if (isset($fatalError)) {
?>Usage: php DLMTester.php [-c cache_file] [-m max_results] [-o output_format] -s search_text DLM_INFO_file

	If DLM_INFO_file is not specified, then 'INFO' in the current directory will be used.

	-c, --cache: 	Save results to, or use (if files exists), a cache (in ./cache dir)
	-m, --max:	Max results, if search module contains public variable 'max_results'
	-o, --output:	Output format (default is PHP 'array'): array, JSON, JSON_pretty
	-s, --search: 	[MANDATORY] Search query to pass to the DLM module
	-v, --verbose:	Output is verbose; includes suspected errors

<?php
	exit;
}

if ($verbose) {
	echo "User-specified query: $query\n";
	echo "User-specified format: $output\n";
	if (isset($cache)) {
		echo "User-specified cache: $cache (", 
			(file_exists($cache) ? 
				"exists and will be used" : 
				"does not exist and will be created"), ")\n";
	}
}

$dlmInfo = new DLMInfo($dlmFilename);
if (!$dlmInfo->isWellFormed) {
	echo "DLM INFO file '" . $dlmInfo->filename . "' is NOT well formed.\n" . $dlmInfo->wellFormedErrors . "\n";
	exit;
}

/* Run the DLM Search Tester */
$tester = new DLMTester();
$tester->verbose = $verbose;
$tester->cache = $cache;
$tester->btSearch($dlmInfo, $query, $maxresults);

/* Dump the results */
switch ($output) {
	case "ARRAY":
		var_dump($tester->results);
		break;
	case "JSON":
		echo json_encode($tester->results);
		break;
	case "JSON_PRETTY":
		echo json_encode($tester->results, JSON_PRETTY_PRINT);
		break;		
}
echo "\n";
?>
