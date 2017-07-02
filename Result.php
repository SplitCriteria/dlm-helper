<?php
namespace SplitCriteria\DLMHelper;

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

	const REGX_URL = "/^(https?:\/\/)?([\w\.\-\?\[\]\$\(\)\*\+\/#@!&',:;~=_%]+)+$/";
	const REGX_MAGNET = "/^magnet:\?xt=urn:(\w+):([a-zA-Z0-9]{40})&dn=([\w\.\-\?\[\]\$\(\)\*\+\/#@!&',:;~=_%]+)$";
	const REGX_HASH = "/^[a-zA-Z0-9]{40}$/";
	const REGX_INT = "/^[0-9]+$/";
	
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
			switch ($field) {
				/* Numeric fields "incorrectly" reported as empty are checked */
				case SEEDS:
				case LEECHS:
				case SIZE:
					return $this->result[$field] != "0" &&
						empty($this->result[$field]);
				default:
					return empty($this->result[$field]);
			}
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
			
		switch ($field) {
			case TITLE:
				/* Valid titles just need to have some text */
				return !empty($this->result[TITLE]);
			case DOWNLOAD:
				return !empty($this->result[DOWNLOAD]) &&
					(preg_match(self::REGX_URL, $this->result[DOWNLOAD]) || 
					preg_match(self::REGX_MAGNET, $this->result[DOWNLOAD]));
			case SIZE:
				return !empty($this->result[SIZE]) &&
					(is_int($this->result[SIZE]) || 
					is_float($this->result[SIZE]));
			case DATE:
				/* Check with php function strtotime and DateTime::createFromFormat -- since
				   strtotime doesn't understand all date formats. */
				$result = @strtotime($this->result[DATE]);
				return (($result && $result != -1)
					|| @\DateTime::createFromFormat("m-d-Y", $this->result[DATE])
					|| @\DateTime::createFromFormat("m/d/Y", $this->result[DATE]));
			case PAGE:
				return !empty($this->result[PAGE]) &&
					preg_match(self::REGX_URL, $this->result[PAGE]);
			case HASH:
				return !empty($this->result[HASH]) &&
					preg_match(self::REGX_HASH, $this->result[HASH]);
			case SEEDS:
				return is_int($this->result[SEEDS]) || 
					$this->result[SEEDS] == "0" ||
					preg_match(self::REGX_INT, $this->result[SEEDS]);
			case LEECHS:
				return is_int($this->result[LEECHS]) || 
					$this->result[LEECHS] == "0" ||
					preg_match(self::REGX_INT, $this->result[LEECHS]);
			case CATEGORY:
				/* All category results are valid */
				return true;
		}
		
	}

}

?>
