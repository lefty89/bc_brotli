<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;


class Module {

	const STATE_WARNING = 3;
	const STATE_LOADING = 2;
	const STATE_SUCCESS = 1;
	const STATE_ERROR   = 0;

	/**
	 * the current step
	 *
	 * @var int
	 */
	protected $step = 0;

	/**
	 * git version
	 *
	 * @var string
	 */
	protected $git_version = '';

	/**
	 * compiler version
	 *
	 * @var string
	 */
	protected $gcc_version = '';

	/**
	 * git repository path
	 *
	 * @var string
	 */
	protected $repository =  'https://github.com/google/brotli';

	/**
	 * git branch
	 *
	 * @var string
	 */
	protected $branch =  'master';

	/**
	 * directory where the source is cloned
	 *
	 * @var string
	 */
	protected $tempDir = "typo3temp/Cache/Data/bc_brotli/";

	/**
	 * @cosntruct
	 */
	function __construct() {

		// set correct headers
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		header('Vary: Accept-Encoding');

		// get variables from ext_conf_template.php
		$extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['bc_brotli']);

		// sets the repository
		$this->repository = $extConfig['repository'];
		// sets the branch
		$this->branch = $extConfig['branch'];
	}

	/**
	 * sends the next step
	 *
	 * @param string $message
	 */
	function msgStepNext($message)	{
		$this->sendMessage(++$this->step, Module::STATE_LOADING, $message);
	}

	/**
	 * sends finish signal for last step
	 *
	 * @param int $state
	 * @param string $message
	 */
	function msgStepFinished($state = Module::STATE_SUCCESS, $message = "")	{
		$this->sendMessage($this->step, $state, $message);
	}


	/**
	 * gets the source with git
	 */
	function getSourceAsGit($dir)	{

		$this->msgStepNext("Cloning repository: $this->repository.git");

		if (is_dir($dir) && (chdir($dir))) {

			// cloneing git source
			exec(sprintf("git clone %s .", $this->repository.'.git'), $output, $cloneErrorCode);

			if ($cloneErrorCode === 0) {
				$this->msgStepFinished();
				$this->msgStepNext("Checkout: $this->branch");

				// cloneing git source
				exec(sprintf("git checkout %s", $this->branch), $output, $checkoutErrorCode);
				if ($checkoutErrorCode === 0) {
					$this->msgStepFinished();
					return $dir.'/tools';
				}
			}
		}

		throw new Exception();
	}

	/**
	 * gets the source with git
	 */
	function getSourceAsZip($dir)	{

		$this->msgStepNext("Download: $this->repository/archive/$this->branch.zip");

		// download zip archive from github
		if ((is_dir($dir) && (file_put_contents("$dir/$this->branch.zip", fopen("$this->repository/archive/$this->branch.zip", 'r'))))) {
			$this->msgStepFinished();
			$zip = new ZipArchive;
			$res = $zip->open("$dir/$this->branch.zip");
			if ($res === TRUE) {
				$zip->extractTo($dir);
				$zip->close();
				return $dir."/brotli-$this->branch/tools";
			}
		}

		throw new Exception();
	}


	/**
	 * get source via git or downloaded zip folder
	 *
	 * @param string $dir
	 * @return string
	 * @throws \Exception
	 */
	function getBrotliSource($dir)	{

		// get the path from the downloaded source
		return (!empty($this->git_version)) ?
			$this->getSourceAsGit($dir) :
			$this->getSourceAsZip($dir);
	}

	/**
	 * compile brotli binary
	 *
	 * @param string $binaryPath
	 * @throws \Exception
	 */
	function compileBrotliBinary($binaryPath)	{

		$this->msgStepNext("Compile source code");

		if (chdir($binaryPath)) {
			// compile binary
			exec("make", $output, $makeErrorCode);

			$this->msgStepFinished();

			if ($makeErrorCode === 0) {
				$this->msgStepFinished();
				return;
			}
		}

		throw new Exception();
	}

	/**
	 * move binary to extension folder
	 *
	 * @param string $binary
	 * @throws \Exception
	 */
	function moveBrotliBinary($binary)	{

		$this->msgStepNext("Move binary to extension directory");

		if ((file_exists($binary)) && rename($binary, GeneralUtility::getFileAbsFileName("EXT:bc_brotli/Resources/Private/Bin/bro"))) {
			$this->msgStepFinished();
			return;
		}

		throw new Exception();
	}

	/**
	 * gets the installed git version
	 */
	function getGitVersion() {

		$this->msgStepNext("Checking Git version");

		// execute shell command
		exec('git --version', $version, $errorCode);

		if ($errorCode === 0) {
			// git found
			if (preg_match('/git version (\d\.\d\.\d)/', implode(' ', $version), $output)) {
				$this->git_version = $output[1];
				$this->msgStepFinished(Module::STATE_SUCCESS, "Git Version: $this->git_version");
				return;
			}
		}

		$this->msgStepFinished(Module::STATE_WARNING, "Git Version: Unknown");
	}

	/**
	 * gets the installed git version
	 *
	 * @throws \Exception
	 */
	function getCompilerVersion() {

		$this->msgStepNext("Checking GCC version");

		// execute shell command
		exec('gcc --version', $version, $errorCode);

		if ($errorCode === 0) {
			// git found
			if (preg_match('/gcc.+\s(\d\.\d\.\d)\s/', implode(' ', $version), $output)) {
				$this->gcc_version = $output[1];
				$this->msgStepFinished(Module::STATE_SUCCESS, "GCC version: $this->gcc_version");
				return;
			}
		}

		throw new Exception('gcc was not found');
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	function tempdir() {

		$this->msgStepNext("Creating temporary folder");

		/** @var string $name */
		$name = uniqid();

		/** @var string $full */
		$full = GeneralUtility::getFileAbsFileName($this->tempDir).$name;

		if (!is_dir($full) && (mkdir($full, 0777, true))) {
			$this->msgStepFinished(Module::STATE_SUCCESS, "Temporary folder created: $name");
			return $full;
		}

		throw new Exception();
	}

	/**
	 * removes fodler and its content
	 *
	 * @param $dir
	 * @return bool
	 */
	function recurseRmDir($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? $this->recurseRmDir("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}

	/**
	 * sends a state update to the client
	 *
	 * @param int $step
	 * @param int $state
	 * @param string $message
	 */
	function sendMessage($step, $state, $message = "") {

		//ob_start();
		ob_end_clean();

		echo "data: {\n";
		echo "data: \"msg\": \"$message\",\n";
		echo "data: \"step\": $step,\n";
		echo "data: \"state\": $state\n";
		echo "data: }\n\n";

		flush();
		ob_end_flush();
	}

	/**
	 * closes the connection
	 */
	function closeConnection() {

		ob_end_clean();

		echo "event: closing\n";
		echo "data: \n\n";

		flush();
		ob_end_flush();
	}

	/**
	 * main function
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj
	 */
	function main($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL)
	{
		// bug?
		$this->sendMessage(10, 0, "BUG");

		try
		{
			// check git version
			$this->getGitVersion();

			// check compiler version
			$this->getCompilerVersion();

			/** @var string $dir */
			$dir = $this->tempdir();

			/** @var string $binaryPath */
			$binaryPath = $this->getBrotliSource($dir);

			// compile source code
			$this->compileBrotliBinary($binaryPath);

			// move converter to extension path
			$this->moveBrotliBinary("$binaryPath/bro");
		}
		catch (Exception $e) {
			$this->msgStepFinished(Module::STATE_ERROR, $e->getMessage());
		}

		// removes source code dir
		if (!empty($dir)) {
			$this->msgStepNext("Cleanup");
			$this->msgStepFinished((int)$this->recurseRmDir($dir), "");
		}

		// closes the connection
		$this->closeConnection();
	}

}