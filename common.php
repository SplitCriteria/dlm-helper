<?php
namespace BoogsDLM;
use \Exception;

function appendOnNewLine(&$left, &$right) {
	/* No need to append anything if right is empty */
	if (!empty($right)) {
		/* Append from an empty string if $left is NULL; add a new line if $left is not empty */
		$left = (is_null($left) ? "" : $left) . (empty($left) ? "" :  "\n") .  $right;
	}
}
?>
