<?php

use TYPO3\CMS\Backend\Utility\BackendUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Class for updating the db
 */
class ext_update {

	/**
	 * create new converter from source if not already exists
	 *
	 * @return string HTML
	 */
	function main()	{

		$content = '';

		// add javascript and html
		$this->printTemplate($content);
		$this->printScripts($content);

		return $content;
	}

	/**
	 * prints the update form
	 *
	 * @return string
	 */
	function printScripts(&$content) {

		// variables
		$data  = json_encode(array(
			'url' => BackendUtility::getAjaxUrl('BcBrotli'))
		);

		// javascript logic
		$logic = file_get_contents(GeneralUtility::getFileAbsFileName("EXT:bc_brotli/Resources/Private/JS/module.js"));

		$content .= <<<EOT
		<script type='text/javascript'>
		var PARAMS = {$data};
			{$logic}
		</script>
EOT;
	}

	/**
	 * prints the update form
	 *
	 * @return string
	 */
	function printTemplate(&$content) {
		$content .= <<<EOT
		<div id="update-log" class="row hidden">
			<div class="col-md-12">
				<div class="panel panel-default">
					<div class="panel-heading">Event Log</div>
					<div class="panel-body">
						<ul id="result" class="list-group"></ul>
					</div>
				</div>
			</div>
		</div>

		<button id="update-button" class="btn btn-default btn-lg">
			Process update
		</button>
EOT;
	}

	/**
	 * required for extension manager
	 * @return bool
	 */
	function access() {
		return TRUE;
	}
}