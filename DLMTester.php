<?php
namespace BoogsDLM;
use \Exception;

include_once('common.php');
include_once('DLMInfo.php');
include_once('DLMPlugin.php');

class DLMEmulator {

	private $isError;
	private $errorMsg;
	public $results;
	public $verbose = false;
	public $cache;
	
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
	if (!isset($output) || !in_array($output, $valid_output)) {
		$output = $valid_output[0];
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
?>Usage: DLMEmulator [-c cache_file] [-m max_results] [-o output_format] -s search_text DLM_INFO_file

	If DLM_INFO_file is not specified, then 'INFO' in the current directory will be used.

	-c, --cache: 	Save results to, or use (if files exists), a cache (in ./cache dir)
	-m, --max:	Max results, if search module contains public variable 'max_results'
	-o, --output:	Output format (default is PHP 'array'): array, JSON, JSON_pretty
	-s, --search: 	[MANDATORY] Search query to pass to the DLM module
	-v, --verbose:	Output is verbose

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

/* Run the DLM Search Emulator */
$emulator = new DLMEmulator();
$emulator->verbose = $verbose;
$emulator->cache = $cache;
$emulator->btSearch($dlmInfo, $query, $maxresults);

/* Dump the results */
switch ($output) {
	case "ARRAY":
		var_dump($emulator->results);
		break;
	case "JSON":
		echo json_encode($emulator->results);
		break;
	case "JSON_PRETTY":
		echo json_encode($emulator->results, JSON_PRETTY_PRINT);
		break;
		
}
echo "\n";
?>
