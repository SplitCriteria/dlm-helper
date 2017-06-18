<?php
namespace BoogsDLM;
use \Exception;

/**
	Appends the right string to the left string on a new line.
	
	@param left	a string, or NULL
	@param right	a string, or NULL
	@return	a string with right appended to left on a new line
*/
function appendOnNewLine(&$left, &$right) {
	/* No need to append anything if right is empty */
	if (!empty($right)) {
		/* Append from an empty string if $left is NULL; add a new line if $left is not empty */
		$left = (is_null($left) ? "" : $left) . (empty($left) ? "" :  "\n") .  $right;
	}
}

/**
	Determines if a date is valid by using several methods.

	@param date	a date string
	@return bool
*/
function isDateValid($date) {
	/* Set a default timezone to suppress warnings */
	date_default_timezone_set("UTC");
	/* Assume the date is invalid */
	$isValid = false;
	/* Check with php function strtotime and DateTime::createFromFormat -- since
	   strtotime doesn't understand all date formats. */
	$result = strtotime($date);
	if (($result && $result != -1)
		|| \DateTime::createFromFormat("m-d-Y", $date)
		|| \DateTime::createFromFormat("m/d/Y", $date)) {
		$isValid = true;
	}
	return $isValid;
}
?>
