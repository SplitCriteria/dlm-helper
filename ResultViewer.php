<?php
namespace SplitCriteria\DLMHelper;

interface ResultViewer {

	public function __construct(Result $result);
	public function echoResult();

}

?>
