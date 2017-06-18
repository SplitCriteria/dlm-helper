<?php
namespace BoogsDLM;
use \Exception;

include_once('common.php');

class DLMInfo {

	public $filename;	
	public $name;
	public $displayName;
	public $description;
	public $version;
	public $site;
	public $module;
	public $type;
	public $class;
	public $isWellFormed;
	public $wellFormedErrors;
	private $fatalError = false;
	public $workingDir;
	
	function __construct($filename) {
		try {
			$this->filename = $filename;
			if (!file_exists($filename)) {
				throw new Exception("File '" . $filename . "' does not exist.");
			}
			$this->workingDir = dirname($filename);	
			$handle = fopen($filename, "rb");
			if (!$handle) {
				throw new Exception("Unable to open file '" . $filename . "'.");
			}
			if (filesize($filename) == 0) {
				throw new Exception ("File '" . $filename . "' is empty.");
			}
			$contents = fread($handle, filesize($filename));
			if (!$contents) {
				throw new Exception("Unable to read contents of '" . $filename . "'.");
			}
			$data = json_decode($contents, true);
			if (is_null($data) || !$data) {
				throw new Exception("Unable to decode '" . $filename . "' (JSON: " . json_last_error_msg() . ")");
			}
			$this->name = $data['name'];
			$this->displayName = $data['displayname'];
			$this->description = $data['description'];
			$this->version = $data['version'];
			$this->site = $data['site'];
			$this->module = $data['module'];
			$this->type = $data['type'];
			$this->class = $data['class'];
			$this->determineIsWellFormed();
		} catch (Exception $error) {
			$this->fatalError = true;
			$this->isWellFormed = false;
			$this->wellFormedErrors = $error->getMessage();
		} finally {
			if ($handle) {
				fclose($handle);
			}
		}
	}

	private function determineIsWellFormed() {
		if ($this->fatalError) {
			return;
		}
		$wellFormed = &$this->isWellFormed;
		$errors = &$this->wellFormedErrors;
		$wellFormed = true;
		$errors = '';
		if (!(isSet($this->name) && isSet($this->version)
			&& isSet($this->module) && isSet($this->type)
			&& isSet($this->class))) {
			$wellFormed = false;
			$errors = "Mandatory keys not found (i.e. name, version, module, type, class).";
		}
		$modulefile = $this->workingDir . DIRECTORY_SEPARATOR . $this->module;
		if (!file_exists($modulefile)) {
			$wellFormed = false;
			appendOnNewLine($errors, "Module file '" . $this->module . "' not found.");
		}
		include_once($modulefile);
		try {
			$moduleclass = new $this->class;
		} catch (Exception $err) {
			$wellFormed = false;
			appendOnNewLine($errors, "Unable to instantiate module class '" . $this->class . "'");
		}
		if ($this->type != 'search') {
			$wellFormed = false;
			appendOnNewLine($errors, "Type should be 'search' not '" . $this->type . "'.");
		}		 
	}
}
?>
