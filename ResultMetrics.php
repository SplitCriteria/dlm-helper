<?php
namespace SplitCriteria\DLMHelper;

include_once('Result.php');

class ResultMetrics {

	public static function count($results) {
		if (empty($results)) {
			return false;
		}
		$count = 0;
		foreach ($results as $result) {
			if (get_class($result) == "SplitCriteria\DLMHelper\Result") {
				$count++;
			}
		}
		return $count;
	}

	public static function validCount($results) {
		if (empty($results)) {
			return false;
		}
		$count = 0;
		foreach ($results as $result) {
			if (get_class($result) == "SplitCriteria\DLMHelper\Result") {
				if ($result->isResultValid()) {
					$count++;
				}
			}
		}
		return $count;
	}

	public static function getEmptyFieldTotal($results) {
		if (empty($results)) {
			return false;
		}
		$count = 0;
		foreach ($results as $result) {
			if (get_class($result) == "SplitCriteria\DLMHelper\Result") {
				$count += ($result->isEmpty(TITLE) ? 1 : 0);
				$count += ($result->isEmpty(DOWNLOAD) ? 1 : 0);
				$count += ($result->isEmpty(SIZE) ? 1 : 0);
				$count += ($result->isEmpty(DATE) ? 1 : 0);
				$count += ($result->isEmpty(PAGE) ? 1 : 0);
				$count += ($result->isEmpty(HASH) ? 1 : 0);
				$count += ($result->isEmpty(SEEDS) ? 1 : 0);
				$count += ($result->isEmpty(LEECHS) ? 1 : 0);
				$count += ($result->isEmpty(CATEGORY) ? 1 : 0);
			}
		}
		return $count;
	}

	public static function getEmptyFieldCount($results) {
		if (empty($results)) {
			return false;
		}
		$count = array(TITLE => 0, DOWNLOAD => 0, SIZE => 0, DATE => 0,
			PAGE => 0, HASH => 0, SEEDS => 0, LEECHS => 0, CATEGORY => 0);
		foreach ($results as $result) {
			if (get_class($result) == "SplitCriteria\DLMHelper\Result") {
				$count[TITLE] += ($result->isEmpty(TITLE) ? 1 : 0);
				$count[DOWNLOAD] += ($result->isEmpty(DOWNLOAD) ? 1 : 0);
				$count[SIZE] += ($result->isEmpty(SIZE) ? 1 : 0);
				$count[DATE] += ($result->isEmpty(DATE) ? 1 : 0);
				$count[PAGE] += ($result->isEmpty(PAGE) ? 1 : 0);
				$count[HASH] += ($result->isEmpty(HASH) ? 1 : 0);
				$count[SEEDS] += ($result->isEmpty(SEEDS) ? 1 : 0);
				$count[LEECHS] += ($result->isEmpty(LEECHS) ? 1 : 0);
				$count[CATEGORY] += ($result->isEmpty(CATEGORY) ? 1 : 0);
			}
		}
		return $count;
	}

	public static function getInvalidFieldTotal($results) {
		if (empty($results)) {
			return false;
		}
		$count = 0;
		foreach ($results as $result) {
			if (get_class($result) == "SplitCriteria\DLMHelper\Result") {
				$count += ($result->isFieldValid(TITLE) ? 0 : 1);
				$count += ($result->isFieldValid(DOWNLOAD) ? 0 : 1);
				$count += ($result->isFieldValid(SIZE) ? 0 : 1);
				$count += ($result->isFieldValid(DATE) ? 0 : 1);
				$count += ($result->isFieldValid(PAGE) ? 0 : 1);
				$count += ($result->isFieldValid(HASH) ? 0 : 1);
				$count += ($result->isFieldValid(SEEDS) ? 0 : 1);
				$count += ($result->isFieldValid(LEECHS) ? 0 : 1);
				$count += ($result->isFieldValid(CATEGORY) ? 0 : 1);
			}
		}
		return $count;
	}

	public static function getInvalidFieldCount($results) {
		if (empty($results)) {
			return false;
		}
		$count = array(TITLE => 0, DOWNLOAD => 0, SIZE => 0, DATE => 0,
			PAGE => 0, HASH => 0, SEEDS => 0, LEECHS => 0, CATEGORY => 0);
		foreach ($results as $result) {
			if (get_class($result) == "SplitCriteria\DLMHelper\Result") {
				$count[TITLE] += ($result->isFieldValid(TITLE) ? 0 : 1);
				$count[DOWNLOAD] += ($result->isFieldValid(DOWNLOAD) ? 0 : 1);
				$count[SIZE] += ($result->isFieldValid(SIZE) ? 0 : 1);
				$count[DATE] += ($result->isFieldValid(DATE) ? 0 : 1);
				$count[PAGE] += ($result->isFieldValid(PAGE) ? 0 : 1);
				$count[HASH] += ($result->isFieldValid(HASH) ? 0 : 1);
				$count[SEEDS] += ($result->isFieldValid(SEEDS) ? 0 : 1);
				$count[LEECHS] += ($result->isFieldValid(LEECHS) ? 0 : 1);
				$count[CATEGORY] += ($result->isFieldValid(CATEGORY) ? 0 : 1);
			}
		}
		return $count;
	}

	public static function echoFieldCountArray($ca) {
		echo $ca[TITLE] > 0 ? ($ca[TITLE] . "x Title ") : "", 
			$ca[DOWNLOAD] > 0 ? ($ca[DOWNLOAD] . "x Download ") : "",
			$ca[SIZE] > 0 ? ($ca[SIZE] . "x Size ") : "",
			$ca[DATE] > 0 ? ($ca[DATE] . "x Date ") : "",
			$ca[PAGE] > 0 ? ($ca[PAGE] . "x Page ") : "",
			$ca[HASH] > 0 ? ($ca[HASH] . "x Hash ") : "",
			$ca[SEEDS] > 0 ? ($ca[SEEDS] . "x Seeds ") : "",
			$ca[LEECHS] > 0 ? ($ca[LEECHS] . "x Leechs ") : "",
			$ca[CATEGORY] > 0 ? ($ca[CATEGORY] . "x Category ") : "";
	}
}

?>
