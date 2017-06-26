<?php
namespace SplitCriteria\DLMHelper;

include_once('TestOptions.php');
include_once('TestResults.php');

abstract class ResultViewer {

	private $options;
	private $results;

	public function __construct(TestOptions $options, TestResults $results) {
		$this->options = $options;
		$this->results = $results;
	}

	final public function callPrintResults() {
		$this->printResults($this->options, $this->results);
	}

	public abstract function printResults(TestOptions $options, TestResults $results);

}

?>
