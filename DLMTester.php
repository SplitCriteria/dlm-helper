<?php
namespace SplitCriteria\DLMHelper;
use \Exception;

include_once('common.php');
include_once('DLMInfo.php');
include_once('DLMPlugin.php');
include_once('Cache.php');
include_once('Result.php');
include_once('ResultMetrics.php');

class DLMTester {

	private $isError;
	private $errorMsg;
	public $results;
	public $verbose = false;
	public $useCache;

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
		
		$url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
		$isResultFromCache = false;
		if ($this->useCache) {
			
			$cache = new \SplitCriteria\DLMHelper\Cache();
			if ($cache->isCached($url)) {
				$result = $cache->getLast();
				if (!$result) {
					echo "Unable to read cache file.\n";
					exit(1);
				}
				$isResultFromCache = true;
			} else {
				/* Not cached? Make the curl request and cache it */
				$result = curl_exec($curl);
				if (!$cache->put($url, $result)) {
					echo "Unable to cache curl result.\n";
				}
			}
		
		}  else {
			/* If cache flag isn't set, then just make the curl request */
			$result = curl_exec($curl);
		}

		if (!$result) {
			echo "Curl error: ", curl_error($curl), "\n";
		}
		if ($this->verbose) {
			echo "Query URL: $url", 
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

		/* Copy the raw results into an array of Result object */
		$results = array();
		foreach ($this->results as $result) {
			$results[] = new Result($result);
		}

		/* Print the results to the user */
		$count = ResultMetrics::count($results);
		$validCount = ResultMetrics::validCount($results);
		$emptyCount = ResultMetrics::getEmptyFieldTotal($results);
		$invalidFields = ResultMetrics::getInvalidFieldCount($results);
		$emptyFields = ResultMetrics::getEmptyFieldCount($results);

		echo "Invalid Fields\n"; var_dump($invalidFields); echo "\n";
		echo "Empty Fields\n"; var_dump($emptyFields); echo "\n";

		echo "Search module returned $count results ($validCount of $count appear to be valid).\n";
		if ($validCount < $count) {
			echo "Invalid Fields (", ($count - $validCount), " found): ", 
				ResultMetrics::echoFieldCountArray($invalidFields), "\n";
		}
		if ($emptyCount > 0) {
			echo "Empty Fields ($emptyCount found): ", 
				ResultMetrics::echoFieldCountArray($emptyFields), "\n";
		}
		/* Give the user some help if they have invalid results and didn't ask to look at them */
		if (!$this->verbose && $validCount != $count) {
			echo ConsoleText::RED_BOLD, "Invalid results are highlighted by using option -v, --verbose\n", 
				ConsoleText::NORMAL;
		}
	}
}

/* Get the command line options */
$shortoptions = "vcm:o:s:";
$longopts = array("verbose","cache","max:","output:","search:");
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
	$useCache = key_exists('c', $options) || key_exists('cache', $options);

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
?>Usage: php DLMTester.php [-cv] [-m max_results] [-o output_format] -s search_text DLM_INFO_file

	If DLM_INFO_file is not specified, then 'INFO' in the current directory will be used.

	-c, --cache: 	Save results to, or use if it exists, a cache (in ./cache dir)
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
$tester->useCache = $useCache;
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
