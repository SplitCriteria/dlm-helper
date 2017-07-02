<?php
namespace SplitCriteria\DLMHelper;
class DLMModule {

	public $info;
	public $module;
	public $modulePath;
	public $isDLMfile;
	public $isINFOfile;
	public $isWellFormed;
	public $errors = array();

	function __construct($filename) {
		if (!file_exists($filename)) {
			$this->errors[] = "File '$filename' does not exist.";
			return;
		} else if (is_dir($filename)) {
			$this->errors[] = "File '$filename' is a directory.";
			return;
		}
		try {
			/* Create a module from the DLM file (.tar.gz format) */
			$module = new \PharData($filename);
			/* There should only be two files in the module */
			if (count($module) != 2) {
				$this->errors[] = "There should be exactly 2 files in the module (".count($module)." found).";
			}
			/* Make sure there is an INFO file in the DLM file */
			$infoFilename;
			foreach ($module as $file) {
				$names = explode(DIRECTORY_SEPARATOR, $file);
				if (count($names) > 0 && $names[count($names)-1] == "INFO") {
					$infoFilename = $file;
					break;
				}
			}
			if (!$infoFilename) {
				$this->errors[] = "No INFO file found in '$filename'.";
				return;
			}
			/* Read in the INFO file (JSON-encoded) into an array */
			$info = json_decode(file_get_contents($infoFilename), true);
			if (!$info) {
				$this->errors[] = "Error reading INFO file contents (check JSON syntax).";
				return;
			}
		} catch (\Exception $e) {
			$this->isDLMfile = false;
			/* Make the assumption that this is an INFO file. Start by
			   creating a "module" array with both the INFO and search file */
			$module = array();
			$module[] = realpath($filename);
			/* Read the INFO file and get the module name */
			$info = json_decode(file_get_contents($filename), true);
			if (!$info) {
				$this->errors[] = "Error reading INFO file contents (check JSON syntax).";
				return;
			}
			/* Check that the module name exists in the INFO file */
			$moduleFilename = $info['module'];
			if (!$moduleFilename) {
				$this->errors[] = "Key 'module' not found in INFO file.";
				return;
			} else {
				/* It's JSON and has a module name -- confindently say it's an INFO file */
				$this->isINFOfile = true;
			}
			/* Replace the INFO file name with the module name in the path */
			$path = explode(DIRECTORY_SEPARATOR, realpath($filename));
			$path[count($path)-1] = $moduleFilename;
			$module[] = implode(DIRECTORY_SEPARATOR, $path);
		}

		/* Check for the required JSON entries */
		$required = array("name", "displayname", "description", "version", "site", "module", "type", "class");
		foreach ($required as $reqEntry) {
			if (!key_exists($reqEntry, $info)) {
				$this->errors[] = "Key '$reqEntry' not found in INFO file.";
			} else if (empty($info[$reqEntry])) {
				$this->errors[] = "Key '$reqEntry' does not have a value.";
			}
		}
		/* Also check for extra JSON entries */
		foreach ($info as $key => $value) {
			if (!in_array($key, $required)) {
				$this->errors[] = "Unknown key '$key' found in INFO file.";
			}
		}
		/* Make sure the search.php module exists */
		$searchFilename;
		foreach ($module as $file) {
			$names = explode(DIRECTORY_SEPARATOR, $file);
			if (count($names) > 0 && $names[count($names)-1] == $info['module']) {
				$searchFilename = $file;
				break;
			}
		}
		/* Check the specific value: type == "search" */
		if ($info['type'] != 'search') {
			$this->errors[] = "The 'type' key's value should be 'search', found '${info['type']}' instead.";
		}
		if (!$searchFilename) {
			$this->errors[] = "Module '${info['module']}' not found.";
			return;
		}
		/* Check the module for the class name */
		$moduleContents = file_get_contents($searchFilename);
		if (!preg_match("/\s*class\s+".$info['class']."\s+{/", $moduleContents)) {
			$this->errors[] = "Class name '${info['class']}' not found in '${info['module']}' file.";
		}
		/* Make sure this is a PHP file */
		if (!preg_match("/<\?php/", $moduleContents)) {
			$this->errors[] = "Module '${info['module']}' does not appear to be a PHP file.";
		}
		/* All tests have been passed, save the INFO and module contents */
		$this->info = $info;
		$this->module = $moduleContents;
		$this->moduleFilename = $searchFilename;
		$this->isWellFormed = true;
	}

}
?>
