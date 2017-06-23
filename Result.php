<?php
namespace SplitCriteria\DLMHelper;
use \Exception;

define("TITLE", "title");
define("DOWNLOAD", "download");
define("SIZE", "size");
define("DATE", "date");
define("PAGE", "page");
define("HASH", "hash");
define("SEEDS", "seeds");
define("LEECHS", "leechs");
define("CATEGORY", "category");

class Result {

	private $result;

	public function __construct($ra) {
		$this->result = $this->createEmptyResult();
		if (!empty($ra)) {
			$this->result[TITLE] = isset($ra[TITLE]) ? $ra[TITLE] : NULL;
			$this->result[DOWNLOAD] = isset($ra[DOWNLOAD]) ? $ra[DOWNLOAD] : NULL;
			$this->result[SIZE] = isset($ra[SIZE]) ? $ra[SIZE] : NULL;
			$this->result[DATE] = isset($ra[DATE]) ? $ra[DATE] : NULL;
			$this->result[PAGE] = isset($ra[PAGE]) ? $ra[PAGE] : NULL;
			$this->result[HASH] = isset($ra[HASH]) ? $ra[HASH] : NULL;
			$this->result[SEEDS] = isset($ra[SEEDS]) ? $ra[SEEDS] : NULL;
			$this->result[LEECHS] = isset($ra[LEECHS]) ? $ra[LEECHS] : NULL;
			$this->result[CATEGORY] = isset($ra[CATEGORY]) ? $ra[CATEGORY] : NULL;
		}
	}

	private function createEmptyResult() {
		return array(
			TITLE => NULL,
			DOWNLOAD => NULL,
			SIZE => NULL,
			DATE => NULL,
			PAGE => NULL,
			HASH => NULL,
			SEEDS => NULL,
			LEECHS => NULL,
			CATEGORY => NULL);
	}

	private static function isValidField($field) {
		return (!empty($field) &&
			($field == TITLE || $field == DOWNLOAD ||
			$field == SIZE || $field == DATE ||
			$field == PAGE || $field == HASH ||
			$field == SEEDS || $field == LEECHS ||
			$field == CATEGORY));
	}

	public static function copyOf(Result $src) {
		if (!empty($src)) {
			return array(
				TITLE => $src[TITLE],
				DOWNLOAD => $src[DOWNLOAD],
				SIZE => $src[SIZE],
				DATE => $src[DATE],
				PAGE => $src[PAGE],
				HASH => $src[HASH],
				SEEDS => $src[SEEDS],
				LEECHS => $src[LEECHS],
				CATEGORY => $src[CATEGORY]);
		}
		return false;
	}

	public function set($field, $value) {
		if (Result::isValidField($field)) {
			$this->result[$field] = $value;
			return true;
		} else {
			return false; 
		}
	}

	public function get($field) {
		if (Result::isValidField($field)) {
			return $this->result[$field];
		} else {
			return false;
		}
	}

	public function isEmpty($field) {
		if (Result::isValidField($field)) {
			return empty($this->result[$field]);
		} else {
			return false;
		}
	}

	public function isResultValid() {
		return ($this->isFieldValid(TITLE) && $this->isFieldValid(DOWNLOAD) &&
			$this->isFieldValid(SIZE) && $this->isFieldValid(DATE) &&
			$this->isFieldValid(PAGE) && $this->isFieldValid(HASH) &&
			$this->isFieldValid(SEEDS) && $this->isFieldValid(LEECHS) && 
			$this->isFieldValid(CATEGORY));
	}
	
	public function isFieldValid($field) {
		if (!Result::isValidField($field)) {
			return false;
		}
		
		$regxURL = "/^(https?:\/\/)?([\w\.\-\?\[\]\$\(\)\*\+\/#@!&',:;~=_%]+)+$/";
		$regxMagnet = "/^magnet:\?xt=urn:(\w+):([a-zA-Z0-9]{40})&dn=([\w\.\-\?\[\]\$\(\)\*\+\/#@!&',:;~=_%]+)$";
		$regxHash = "/^[a-zA-Z0-9]{40}$/";
		$regxInt = "/^[0-9]+$/";
		
		switch ($field) {
			case TITLE:
				/* Valid titles just need to have some text */
				return !empty($this->result[TITLE]);
			case DOWNLOAD:
				return !empty($this->result[DOWNLOAD]) &&
					(preg_match($regxURL, $this->result[DOWNLOAD]) || 
					preg_match($regxMagnet, $this->result[DOWNLOAD]));
			case SIZE:
				return !empty($this->result[SIZE]) &&
					(is_int($this->result[SIZE]) || 
					is_float($this->result[SIZE]));
			case DATE:
				/* Set a default timezone to suppress warnings */
				date_default_timezone_set("UTC");
				/* Check with php function strtotime and DateTime::createFromFormat -- since
				   strtotime doesn't understand all date formats. */
				$result = strtotime($this->result[DATE]);
				return (($result && $result != -1)
					|| \DateTime::createFromFormat("m-d-Y", $this->result[DATE])
					|| \DateTime::createFromFormat("m/d/Y", $this->result[DATE]));
			case PAGE:
				return !empty($this->result[PAGE]) &&
					preg_match($regxURL, $this->result[PAGE]);
			case HASH:
				return !empty($this->result[HASH]) &&
					preg_match($regxHash, $this->result[HASH]);
			case SEEDS:
				return !empty($this->result[SEEDS]) &&
					(is_int($this->result[SEEDS]) 
					|| preg_match($regxInt, $this->result[SEEDS]));
			case LEECHS:
				return !empty($this->result[LEECHS]) &&
					(is_int($this->result[LEECHS]) 
					|| preg_match($regxInt, $this->result[LEECHS]));
			case CATEGORY:
				/* All category results are valid */
				return true;
		}
		
	}

}

?>
