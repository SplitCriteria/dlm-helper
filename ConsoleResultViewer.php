<?php
namespace SplitCriteria\DLMHelper;

include_once('Result.php');
include_once('ResultViewer.php');
include_once('ConsoleText.php');

class ConsoleResultViewer implements ResultViewer {

	public static function echoResults(array $results) {
		if (empty($results)) {
			return false;
		}
		
		/* Print the results to the user */
		$count = ResultMetrics::count($results);
		$validCount = ResultMetrics::validCount($results);
		$emptyCount = ResultMetrics::getEmptyFieldTotal($results);
		$invalidFields = ResultMetrics::getInvalidFieldCount($results);
		$emptyFields = ResultMetrics::getEmptyFieldCount($results);

		echo "Search module returned $count results ($validCount of $count appear to be valid).\n";
		if ($validCount < $count) {
			echo "Invalid Fields (", ($count - $validCount), " found): ", 
				ResultMetrics::echoFieldCountArray($invalidFields), "\n";
		}
		if ($emptyCount > 0) {
			echo "Empty Fields ($emptyCount found): ", 
				ResultMetrics::echoFieldCountArray($emptyFields), "\n";
		}
		
		$resultCount = 0;
		foreach ($results as $result) {
			if (get_class($result) == "SplitCriteria\DLMHelper\Result") {
				$resultCount++;
				echo "Result #$resultCount\n";
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
