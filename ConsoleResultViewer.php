<?php
namespace SplitCriteria\DLMHelper;

include_once('Result.php');
include_once('ResultViewer.php');
include_once('ConsoleText.php');

class ConsoleResultViewer {

	public static function echoResults(array $results) {
		if (empty($results)) {
			return false;
		}
		$count = 0;
		foreach ($results as $result) {
			if (get_class($result) == "SplitCriteria\DLMHelper\Result") {
				$count++;
				echo "Result #$count\n";
				echo ($result->isFieldValid(TITLE) ? ConsoleText::NORMAL : ConsoleText::RED_BOLD);
				echo "\tTitle: ", $result->get(TITLE), "\n";
				echo ($result->isFieldValid(DOWNLOAD) ? ConsoleText::NORMAL : ConsoleText::RED_BOLD);
				echo "\tTorrent URL: ", $result->get(DOWNLOAD), "\n";
				echo ($result->isFieldValid(SIZE) ? ConsoleText::NORMAL : ConsoleText::RED_BOLD);
				echo "\tSize: ", $result->get(SIZE), "\n";
				echo ($result->isFieldValid(DATE) ? ConsoleText::NORMAL : ConsoleText::RED_BOLD);
				echo "\tDate: ", $result->get(DATE), "\n";
				echo ($result->isFieldValid(PAGE) ? ConsoleText::NORMAL : ConsoleText::RED_BOLD);
				echo "\tDetails URL: ", $result->get(PAGE), "\n";
				echo ($result->isFieldValid(HASH) ? ConsoleText::NORMAL : ConsoleText::RED_BOLD);
				echo "\tHash: ", $result->get(HASH), "\n";
				echo ($result->isFieldValid(SEEDS) ? ConsoleText::NORMAL : ConsoleText::RED_BOLD);
				echo "\tSeeds: ", $result->get(SEEDS), "\n";
				echo ($result->isFieldValid(LEECHS) ? ConsoleText::NORMAL : ConsoleText::RED_BOLD);
				echo "\tLeechs: ", $result->get(LEECHS), "\n";
				echo ($result->isFieldValid(CATEGORY) ? ConsoleText::NORMAL : ConsoleText::RED_BOLD);
				echo "\tCategory: ", $result->get(CATEGORY), "\n";
			}
		}
		/* Set the text back to the default settings */
		echo ConsoleText::NORMAL;
	}
}
?>
