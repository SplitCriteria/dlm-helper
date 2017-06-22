<?php
namespace SplitCriteria\DLMHelper;
use \Exception;

class Cache {

	const DEBUG = true;
	const DEFAULT_CACHE_DIR = "cache";
	const KEY_FILENAME = "keys";

	private $dir;
	private $keyfilename;
	private $data;
	private $keys;
	private $target;

	function __construct($cacheDir = self::DEFAULT_CACHE_DIR) {

		/* Get the full path if a relative path is passed (e.g. "../cachedir") */
		$this->dir = realpath($cacheDir);
		/* Make sure the parameter is a directory */
		if (file_exists($this->dir) && !is_dir($this->dir)) {
			/* Otherwise use the default directory */
			$this->dir = realpath(self::DEFAULT_CACHE_DIR);
		}
		/* If the directory doesn't exist, then create it */
		if (!file_exists($this->dir)) {
			mkdir($this->dir, 0740, true);
		}
	
		$this->keyfilename = $this->dir . DIRECTORY_SEPARATOR . self::KEY_FILENAME;

		if (self::DEBUG) {
			echo "Cache directory: ", $this->dir, "\n";
			echo "Key file: ", $this->keyfilename, "\n";
		}

		date_default_timezone_set("UTC");
		
		/* If the key file doesn't exist, create it */
		if (!file_exists($this->keyfilename)) {
			$this->eraseData();
			$this->writeKeyFile();
		}

		/* Read in the key file */
		$this->readKeyFile();
	}

	private function eraseData() {
		$this->data = array("create" => date("r"), "keys" => array());
	}

	private function readKeyFile() {
		$this->data = json_decode(file_get_contents($this->keyfilename), true);
		$this->keys = &$this->data['keys'];
	}

	private function writeKeyFile() {
		$result = file_put_contents($this->keyfilename, json_encode($this->data, 
			self::DEBUG ? JSON_PRETTY_PRINT : 0));
		if (self::DEBUG) {
			if ($result) {
				echo "Wrote keyfile '", $this->keyfilename, "'.\n";
			} else {
				echo "Error writing keyfile '", $this->keyfilename, "'.\n";
			}
		}
		return $result;
	}

	public function isCached($key) {
		if (array_key_exists($key, $this->keys)) { 
			$this->target = $this->keys[$key];
			if (file_exists($this->dir . DIRECTORY_SEPARATOR . $this->target)) {
				return true;
			} else {
				/* This shouldn't occur (i.e. a key existing but the value
				   file not existing). If it does, then delete the key/value
				   and update the key file. */
				if (self::DEBUG) {
					echo "Target not found (deleting key): ", 
						$this->dir, DIRECTORY_SEPARATOR, $this->target, "\n";
				}
				unset($this->keys[$key]);
				$this->writeKeyFile();
			}
		}
		/* Key doesn't exist, so unset the target and return failure */
		unset($this->target);
		return false;
	}

	public function put($key, $value) {
		/* If the value is already cached, remove it */
		if ($this->isCached($key)) {
			if (self::DEBUG) {
				echo "Key '$key' exists; replacing previous value.\n";
			}
			unset($this->keys[$key]);
		}
		/* Add the key/value pair */
		$shortHash = substr(md5($key), 0, 8);
		$this->keys[$key] = $shortHash;
		$valueFile = $this->dir . DIRECTORY_SEPARATOR . $shortHash; 
		if (file_put_contents($valueFile, $value)) {
			if (self::DEBUG) {
				echo "Value file '$valueFile' written.\n";
			}
			return $this->writeKeyFile();
		} else {
			if (self::DEBUG) {
				echo "Error writing value file '$valueFile'.\n";
			}
			return false;
		}
	}

	public function get($key) {
		if ($this->isCached($key)) {
			if (self::DEBUG) {
				echo "Searched cache. Key '$key' found.\n";
			}
			return $this->getLast();
		}
		if (self::DEBUG) {
			echo "Searched cache. Key '$key' NOT found.\n";
		}
		return false;
	}

	public function getLast() {
		if (isset($this->target)) {
			$valueFile = $this->dir . DIRECTORY_SEPARATOR . $this->target;
			if (self::DEBUG) {
				echo "In getLast() -- cache target '", $this->target, "' set.\n";
				echo "Cache file '$valueFile' does ", 
					(file_exists($valueFile) ? "" : "NOT "), "exist.\n";
			}
			return file_get_contents($valueFile);
		}
		if (self::DEBUG) {
			echo "In getLast() -- cache target '", $this->target, "' NOT set.\n";
		}
		return false;
	}

	public function drop($key) {
		if ($this->isCached($key)) {
			unlink($this->dir . DIRECTORY_SEPARATOR . $this->target);
			unset($this->keys[$key]);
			$this->writeKeyFile();
			unset($this->target);
			return true;
		}
		return false;
	}

	public function dropAll() {
		if (self::DEBUG) {
			echo "Dropping all cache files...\n";
		}
		/* Delete all the cache files */
		$isError = false;
		foreach ($this->keys as $key => $value) {
			$valueFile = $this->dir . DIRECTORY_SEPARATOR . $value;
			if (unlink($valueFile)) {
				if (self::DEBUG) { 
					echo "\t deleted '$valueFile'\n";
				}
			} else {
				$isError = true;
				if (self::DEBUG) {
					echo "\t error deleting '$valueFile'\n";
				}
			}
		}

		/* Delete all the keys */
		$this->data['keys'] = array();
		$this->keys = &$this->data['keys'];
		$this->writeKeyFile();

		return $isError;
	}

}

?>
