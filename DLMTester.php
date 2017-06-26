<?php
namespace SplitCriteria\DLMHelper;
use \Exception;

include_once('common.php');
include_once('DLMInfo.php');
include_once('DLMPlugin.php');
include_once('Cache.php');
include_once('Result.php');
include_once('ResultMetrics.php');
include_once('ConsoleResultViewer.php');
include_once('HTMLResultViewer.php');
include_once('TestOptions.php');
include_once('TestResults.php');

class DLMTester {

	private $results;

	public function btSearch(TestOptions $options, DLMInfo $info) {
		
		$this->results = new TestResults();

		/* Set up the local variables */
		$error = &$this->results->isError;
		$msg = &$this->results->errorMessages;
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
		if ($options->isVerbose) {
			$searchClass->verbose = true;
		}
		$searchClass->max_results = $options->maxResults;

		/* Test the curl prepare function */
		$curl = curl_init();
		$prepareFunc = "prepare";
		$searchClass->$prepareFunc($curl, $options->searchString);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		
		$this->results->queryURL = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
		$this->results->wasCacheUsed = false;
		if ($options->useCache) {
			
			$cache = new \SplitCriteria\DLMHelper\Cache();
			if ($cache->isCached($this->results->queryURL)) {
				$this->results->curlResponse = $cache->getLast();
				if (!$this->results->curlResponse) {
					$error = true;
					appendOnNewLine($msg, "Unable to read cache file.");
					exit(1);
				}
				$this->results->wasCacheUsed = true;
			} else {
				/* Not cached? Make the curl request and cache it */
				$this->results->curlResponse = curl_exec($curl);
				if (!$cache->put($this->results->queryURL, $this->results->curlResponse)) {
					$error = true;
					appendOnNewLine($msg, "Unable to cache curl result.");
				}
			}
		
		} else {
			/* If cache flag isn't set, then just make the curl request */
			$this->results->curlResponse = curl_exec($curl);
		}

		if (!$this->results->curlResponse) {
			$error = true;
			appendOnNewLine($msg, "Curl error: " . curl_error($curl));
		}
		if (!$isResultFromCache) {
			$this->results->curlResponseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		}
		$this->results->curlResponseLength = strlen($this->results->curlResponse);

		curl_close($curl);
		
		/* Test the parse function */
		$parseFunc = "parse";
		$plugin = new DLMPlugin();
		$output = $searchClass->$parseFunc($plugin, $this->results->curlResponse);

		/* Copy the raw results into an array of Result objects */
		$this->results->results = array();
		foreach ($plugin->results as $result) {
			$this->results->results[] = new Result($result);
		}

		/* Give the user some help if they have invalid results and didn't ask to look at them */
		if (!$options->isVerbose && ResultMetrics::validCount($this->results->results) 
			!= ResultMetrics::count($this->results->results)) {
			$this->results->isError = true;
			appendOnNewLine($msg, "Invalid results are highlighted by using option -v, --verbose");
		}

		return $this->results;
	}
}

/* Get the command line options */
$shortoptions = "vcm:o:s:";
$longopts = array("verbose","cache","max:","output:","search:");
$options = getopt($shortoptions, $longopts);
$testOptions = new TestOptions();

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
	$testOptions->targetINFOFile = $dlmFilename;

	/* Extract the verbose option */
	$testOptions->isVerbose = (key_exists('v', $options) || key_exists('verbose', $options));

	/* Extract the output format option */
	$valid_output = array("TEXT", "HTML");
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
	$testOptions->useCache = key_exists('c', $options) || key_exists('cache', $options);

	/* Extract the max results option */
	$testOptions->maxResults = -1; /* Default value */
	if (key_exists('m', $options)) {
		$testOptions->maxResults = (int)$options['m'];
	} else if (key_exists('count', $options)) {
		$testOptions->maxResults = (int)$options['max'];
	}
	
	/* Extract the search string options */
	if (key_exists('s', $options)) {
		$testOptions->searchString = $options['s'];
	} else if (key_exists('search', $options)) {
		$testOptions->searchString = $options['search'];
	}

	/* Validate the search string (it must exist and there must only be one) */
	if (is_null($testOptions->searchString)) {
		$fataError = true;
		echo "No search argument specified.\n\n";
	} else if (is_array($testOptions->searchString)) {
		$testOptions->searchString = $query[0];
		echo $argv[0] . " only accepts a single search parameter. '$query' used.\n\n";
	}
}

/* Return usage instructions if there's a fatal command line error */
if (isset($fatalError)) {
?>Usage: php DLMTester.php [-cv] [-m max_results] [-o output_format] -s search_text DLM_INFO_file

	If DLM_INFO_file is not specified, then 'INFO' in the current directory will be used.

	-c, --cache: 	Save results to, or use if it exists, a cache (in ./cache dir)
	-m, --max:	Max results, if search module contains public variable 'max_results'
	-o, --output:	Output format: html (default), text
	-s, --search: 	[MANDATORY] Search query to pass to the DLM module
	-v, --verbose:	Output is verbose; includes suspected errors

<?php
	exit;
}

$dlmInfo = new DLMInfo($testOptions->targetINFOFile);
if (!$dlmInfo->isWellFormed) {
	echo "DLM INFO file '" . $dlmInfo->filename . "' is NOT well formed.\n" . $dlmInfo->wellFormedErrors . "\n";
	exit;
}

/* Run the DLM Search Tester */
$tester = new DLMTester();
$results = $tester->btSearch($testOptions, $dlmInfo);

/* Dump the results */
switch ($output) {
	case "TEXT":
		$resultViewer = new ConsoleResultViewer($testOptions, $results);
		break;
	case "HTML":
	default:
		$resultViewer = new HTMLResultViewer($testOptions, $results);
		break;
}

$resultViewer->callPrintResults();

?>
