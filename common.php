<?php
namespace SplitCriteria\DLMHelper;
/**
	Appends the right string to the left string on a new line.
	
	@param left	a string, or NULL
	@param right	a string, or NULL
	@return	a string with right appended to left on a new line
*/
function appendOnNewLine(&$left, $right) {
	/* No need to append anything if right is empty */
	if (!empty($right)) {
		/* Append from an empty string if $left is NULL; add a new line if $left is not empty */
		$left = (is_null($left) ? "" : $left) . (empty($left) ? "" :  "\n") .  $right;
	}
}
?>
