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
		
		$result = curl_exec($curl);
		if (!$result) {
			echo "Curl error: " . curl_error($curl) . "\n";
		}
		if ($this->verbose) {
			echo "Query URL: " . curl_getinfo($curl, CURLINFO_EFFECTIVE_URL) . "\n";
			echo "Website response code: " . curl_getinfo($curl, CURLINFO_RESPONSE_CODE) . "\n";
		}
		curl_close($curl);
		
		/* Test the parse function */
		$parseFunc = "parse";
		$plugin = new DLMPlugin();
		$searchClass->$parseFunc($plugin, $result);
		$this->results = $plugin->results;

		/* Check the results */
		date_default_timezone_set("UTC"); /* Needed to use strtotime() without a warning */
		$totalCount = count($this->results);
		$validCount = 0;
		$regxURL = "/^(https?:\/\/)?([\w\.\-\?\[\]\$\(\)\*\+\/#@!&',:;~=_%]+)+$/";
		$regxMagnet = "/^magnet:\?xt=urn:(\w+):([a-zA-Z0-9]{40})&dn=([\w\.\-\?\[\]\$\(\)\*\+\/#@!&',:;~=_%]+)$";
		$regxHash = "/^[a-zA-Z0-9]{40}$/";
		$regxInt = "/^[0-9]+$/";
		$emptyFields = $this->createCountArray();
		$invalidFields = $this->createCountArray();
		/* Check for properly formatted fields */
		foreach ($this->results as $result) {
			/* Assume the result is valid */
			$invalid = false;
			/* Check the title field -- basically could be anything */
			if (empty($result['title'])) {
				$emptyFields['title']++;
				$invalidFields['title']++;
				$invalid = true;
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
			}
			/* Check the size field (integer or float are valid) */
			if (empty($result['size'])) {
				$emptyFields['size']++;
			} else if (!(is_int($result['size']) || is_float($result['size']))) {
				$invalidFields['size']++;
				$invalid = true;
			}
			/* Check the date/time field -- check with php strtotime function */
			if (empty($result['date'])) {
				$emptyFields['date']++;
			} /* else if (!strtotime($result['date'])) {
				$invalidFields['date']++;
				$invalid = true;
			} */
			/* Check the page URL */
			if (empty($result['page'])) {
				$emptyFields['page']++;
			} else if (!preg_match($regxURL, $result['page'])) {
				$invalidFields['page']++;
				$invalid = true;
			}
			/* Check the hash variable */
			if (empty($result['hash'])) {
				$emptyFields['hash']++;
			} else if (!preg_match($regxHash, $result['hash'])) {
				$invalidFields['hash']++;
				$invalid = true;
			}
			/* Check the seeds and leechs fields */
			if (empty($result['seeds'])) {
				$emptyFields['seeds']++;
			} else if (!(is_int($result['seeds']) || preg_match($regxInt, $result['seeds']))) {
				$invalidFields['seeds']++;
				$invalid = true;
			} 
			if (empty($result['leechs'])) {
				$emptyFields['leechs']++;
			} else if (!(is_int($result['leechs']) || preg_match($regxInt, $result['leechs']))) {
				$invalidFields['leechs']++;
				$invalid = true;
			}
			/* The category field really doesn't have a restriction */
			if (empty($result['category'])) {
				$emptyFields['category']++;
			} 
			/* Total results */
			if (!$invalid) {
				$validCount++;
			}
		}
		/* Print the results to the user */
		echo "Search module returned $totalCount results ($validCount of $totalCount appear to be valid).\n";
		if ($validCount < $totalCount) {
			echo "Invalid Fields (", $this->sumCountArray($invalidFields), " found): ", $this->printCountArray($invalidFields), "\n";
		}
		$emptyFieldCount = $this->sumCountArray($emptyFields);
		if ($emptyFieldCount > 0) {
			echo "Empty Fields ($emptyFieldCount found): ", $this->printCountArray($emptyFields), "\n";
		}
	}
}

/* Get the command line options */
$shortoptions = "vc:o:s:";
$longopts = array("verbose","count:","output:","search:");
$options = getopt($shortoptions, $longopts);

/* Validate the command line options */
if (!$options) {
	$fatalError = true;
} else {
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

	$verbose = (key_exists('v', $options) || key_exists('verbose', $options));

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

	if (key_exists('c', $options)) {
		$maxresults = (int)$options['c'];
	} else if (key_exists('count', $options)) {
		$maxresults = (int)$options['count'];
	}
	if (!isset($maxresults)) {
		$maxresults = -1;
	}
	
	if (key_exists('s', $options)) {
		$query = $options['s'];
	} else if (key_exists('search', $options)) {
		$query = $options['search'];
	} else {
		$query = NULL;
	}
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
?>Usage: DLMEmulator [-c max_results] [-o output_format] -s search_text DLM_INFO_file

	If DLM_INFO_file is not specified, then 'INFO' in the current directory will be used.

	-c, --count:	Max results, if search module contains public variable 'max_results'
	-o, --output:	Output format (default is PHP 'array'): array, JSON, JSON_pretty
	-s, --search: 	[MANDATORY] Search query to pass to the DLM module
	-v, --verbose:	Output is verbose

<?php
	exit;
}

if ($verbose) {
	echo "User-specified query: $query\n";
	echo "User-specified format: $output\n";
}

$dlmInfo = new DLMInfo($dlmFilename);
if (!$dlmInfo->isWellFormed) {
	echo "DLM INFO file '" . $dlmInfo->filename . "' is NOT well formed.\n" . $dlmInfo->wellFormedErrors . "\n";
	exit;
}

/* Run the DLM Search Emulator */
$emulator = new DLMEmulator();
$emulator->verbose = $verbose;
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
