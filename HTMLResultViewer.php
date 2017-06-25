<?php
namespace SplitCriteria\DLMHelper;

include_once('Result.php');
include_once('ResultViewer.php');

class HTMLResultViewer implements ResultViewer {

	private static function echoResultTitle(Result $result, $count) {	
		$content = "Result #$count";
		echo ($result->isResultValid() ? "<h1>$content</h1>" : "<h1 class=\"result_error\">$content</h1>");
	}
	
	private static function echoResultField(Result $result, $field) {
		echo "<span class=\"data\">";
		$content = $result->get($field);
		echo ($result->isFieldValid($field) ? $content : "<em>$content</em>");
		echo "</span>";
	}

	public static function echoResults(array $results) {
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>DLM Test Results</title>
		<style>
		 	* {
				margin: 0;
				padding: 0;
			}
			.bordered {
				border: 2px solid black;
				border-radius: 5px;
				margin: 5px;
			}
			#summary_pane {
				width: 200px;
				position: fixed;
				left: 0;
				top: 0;
				z-index: 1;
				background-color: white;
				overflow: hidden;
			}
			.bordered h1:first-child {
				text-align: center;
				color: black;
				background-color: lightgray;
				border-bottom: 1px solid black;
				border-top-left-radius: 3px;
				border-top-right-radius: 3px;
			}
			#summary_pane p {
				padding: 5px;
				border-top: 1px dashed black;
			}
			#summary_pane p:first-of-type {
				border-top: none;
			}
			#summary_pane ul {
				margin-bottom: 5px;
			}
			#summary_pane li {
				margin-left: 15px;
				list-style-type: none;
			}
			#summary_pane input {
				margin-left: 15px;
				margin-bottom: 5px;
				margin-right: 5px;
			}
			#results_pane {
				position: absolute;
				left: 209px;
				width: 500px;
				z-index: 0;
			}
			.result {
				overflow: hidden;
			}
			.result * {
				padding: 2px;
			}
			.result h1.result_error {
				color: white;
				background-color: red;
			}
			.hide {
				display: none;
			}
			.result td {
				vertical-align: top;
			}
			.result tr td:first-child {
				min-width: 120px;
			}
			em {
				color: red;
				font-weight: bold;
				font-style: normal;
			}
		</style>
		<script>
			function toggleHide(checkbox) {
				var results = document.getElementsByClassName("result");
				for (var i = 0; i < results.length; i++) {
					var error = results[i].getElementsByClassName("result_error");
					if (error.length == 0 && checkbox.checked) {
						results[i].className = "bordered result hide";
					} else {
						results[i].className = "bordered result";
					}
				}
			}
		</script>
	</head>
	<body>
		<?php
		if (empty($results)) {
			return false;
		}

		$count = ResultMetrics::count($results);
		$validCount = ResultMetrics::validCount($results);
		$emptyCount = ResultMetrics::getEmptyFieldTotal($results);
		$invalidFields = ResultMetrics::getInvalidFieldCount($results);
		$emptyFields = ResultMetrics::getEmptyFieldCount($results);
		?>

		<section id="summary_pane" class="bordered" >
			<h1>Summary</h1>
			<p>Valid: <?php echo "$validCount of $count"; ?></p>
			<p>Invalid Fields (total: <?php echo ($count - $validCount); ?>):</p>
			<ul>
				<?php echo ($invalidFields[TITLE] > 0 ? 
					"<li>Title: ${invalidFields[TITLE]}</li>" : ""); ?>
				<?php echo ($invalidFields[DOWNLOAD] > 0 ? 
					"<li>Download URL: ${invalidFields[DOWNLOAD]}</li>" : ""); ?>
				<?php echo ($invalidFields[SIZE] > 0 ? 
					"<li>Size: ${invalidFields[SIZE]}</li>" : ""); ?>
				<?php echo ($invalidFields[DATE] > 0 ? 
					"<li>Date: ${invalidFields[DATE]}</li>" : ""); ?>
				<?php echo ($invalidFields[PAGE] > 0 ? 
					"<li>Details URL: ${invalidFields[PAGE]}</li>" : ""); ?>
				<?php echo ($invalidFields[HASH] > 0 ? 
					"<li>Hash: ${invalidFields[HASH]}</li>" : ""); ?>
				<?php echo ($invalidFields[SEEDS] > 0 ? 
					"<li>Seeds: ${invalidFields[SEEDS]}</li>" : ""); ?>
				<?php echo ($invalidFields[LEECHS] > 0 ? 
					"<li>Leechs: ${invalidFields[LEECHS]}</li>" : ""); ?>
				<?php echo ($invalidFields[CATEGORY] > 0 ? 
					"<li>Category: ${invalidFields[CATEGORY]}</li>" : ""); ?>
			</ul>
			<p>Empty Fields (total: <?php echo $emptyCount; ?>):</p>
			<ul>
				<?php echo ($emptyFields[TITLE] > 0 ? 
					"<li>Title: ${emptyFields[TITLE]}</li>" : ""); ?>
				<?php echo ($emptyFields[DOWNLOAD] > 0 ? 
					"<li>Download URL: ${emptyFields[DOWNLOAD]}</li>" : ""); ?>
				<?php echo ($emptyFields[SIZE] > 0 ? 
					"<li>Size: ${emptyFields[SIZE]}</li>" : ""); ?>
				<?php echo ($emptyFields[DATE] > 0 ? 
					"<li>Date: ${emptyFields[DATE]}</li>" : ""); ?>
				<?php echo ($emptyFields[PAGE] > 0 ? 
					"<li>Details URL: ${emptyFields[PAGE]}</li>" : ""); ?>
				<?php echo ($emptyFields[HASH] > 0 ? 
					"<li>Hash: ${emptyFields[HASH]}</li>" : ""); ?>
				<?php echo ($emptyFields[SEEDS] > 0 ? 
					"<li>Seeds: ${emptyFields[SEEDS]}</li>" : ""); ?>
				<?php echo ($emptyFields[LEECHS] > 0 ? 
					"<li>Leechs: ${emptyFields[LEECHS]}</li>" : ""); ?>
				<?php echo ($emptyFields[CATEGORY] > 0 ? 
					"<li>Category: ${emptyFields[CATEGORY]}</li>" : ""); ?>
			</ul>
			<p>Options:</p>
			<input type="checkbox" name="hide_valid" checked 
				onclick="toggleHide(this)" 
				onload="doit(this)" >Hide Valid Results</input>
		</section>
		
		<section id="results_pane">
		<?php $resultCount = 0;
		foreach ($results as $result) {
			if (get_class($result) == "SplitCriteria\DLMHelper\Result") {
				$resultCount++; ?>
			<section class="bordered result<?php echo ($result->isResultValid() ? " hide" : ""); ?>">
			<?php self::echoResultTitle($result, $resultCount); ?>
			<table>
				<tr><td>Title</td><td><?php self::echoResultField($result, TITLE); ?></td></tr>
				<tr><td>Download URL</td><td><?php self::echoResultField($result, DOWNLOAD); ?></td></tr>
				<tr><td>Size</td><td><?php self::echoResultField($result, SIZE); ?></td></tr>
				<tr><td>Date</td><td><?php self::echoResultField($result, DATE); ?></td></tr>
				<tr><td>Details URL</td><td><?php self::echoResultField($result, PAGE); ?></td></tr>
				<tr><td>Hash</td><td><?php self::echoResultField($result, HASH); ?></td></tr>
				<tr><td>Seeds</td><td><?php self::echoResultField($result, SEEDS); ?></td></tr>
				<tr><td>Leechs</td><td><?php self::echoResultField($result, LEECHS); ?></td></tr>
				<tr><td>Category</td><td><?php self::echoResultField($result, CATEGORY); ?></td></tr>
			</table>
			</section>
		<?php }
		}
		?>
		</section>
	</body>
</html>
<?php
	}

}

?>
