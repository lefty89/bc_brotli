<?php

namespace BC\BcBrotli\Compressor;

/**
 *
 * User: Lefty
 * Date: 31.01.2015
 * Time: 13:21
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;


/**
 * Class RenderController
 * @package BC\BcBrotli\Compressor
 */
class ResourceCompressor extends \TYPO3\CMS\Core\Resource\ResourceCompressor
{
	protected $htaccessTemplate = '<FilesMatch "\\.(js|css)(\\.gzip|br)?$">
	<IfModule mod_expires.c>
		ExpiresActive on
		ExpiresDefault "access plus 7 days"
	</IfModule>
	FileETag MTime Size
</FilesMatch>';

	/**
	 * Decides whether a client can deal with gzipped content or not and returns the according file name,
	 * based on HTTP_ACCEPT_ENCODING
	 *
	 * @param string $filename File name
	 * @return string $filename suffixed with '.gzip' or not - dependent on HTTP_ACCEPT_ENCODING
	 */
	protected function returnFileReference($filename)
	{
		// if the client accepts gzip|br and we can create compressed files, we give him the right version
		if ($this->createGzipped && strpos(GeneralUtility::getIndpEnv('HTTP_ACCEPT_ENCODING'), 'br') !== false) {
			$filename .= '.br';
		} else if ($this->createGzipped && strpos(GeneralUtility::getIndpEnv('HTTP_ACCEPT_ENCODING'), 'gzip') !== false) {
			$filename .= '.gzip';
		}

		return PathUtility::getRelativePath($this->rootPath, PATH_site) . $filename;
	}

	/**
	 * Compresses a javascript file
	 *
	 * @param string $filename Source filename, relative to requested page
	 * @return string Filename of the compressed file, relative to requested page
	 */
	public function compressJsFile($filename)
	{
		// generate the unique name of the file
		$filenameAbsolute = GeneralUtility::resolveBackPath($this->rootPath . $this->getFilenameFromMainDir($filename));
		if (@file_exists($filenameAbsolute)) {
			$fileStatus = stat($filenameAbsolute);
			$unique = $filenameAbsolute . $fileStatus['mtime'] . $fileStatus['size'];
		} else {
			$unique = $filenameAbsolute;
		}
		$pathinfo = PathUtility::pathinfo($filename);
		$targetFile = $this->targetDirectory . $pathinfo['filename'] . '-' . md5($unique) . '.js';
		// only create it, if it doesn't exist, yet
		if ($this->checkFileExists($targetFile)) {
			$contents = GeneralUtility::getUrl($filenameAbsolute);
			$this->writeFileAndCompressed($targetFile, $contents);
		}
		return $this->returnFileReference($targetFile);
	}

	/**
	 * Compresses a CSS file
	 *
	 * Options:
	 * baseDirectories If set, only include files below one of the base directories
	 *
	 * removes comments and whitespaces
	 * Adopted from https://github.com/drupal/drupal/blob/8.0.x/core/lib/Drupal/Core/Asset/CssOptimizer.php
	 *
	 * @param string $filename Source filename, relative to requested page
	 * @return string Compressed filename, relative to requested page
	 */
	public function compressCssFile($filename)
	{
		// generate the unique name of the file
		$filenameAbsolute = GeneralUtility::resolveBackPath($this->rootPath . $this->getFilenameFromMainDir($filename));
		if (@file_exists($filenameAbsolute)) {
			$fileStatus = stat($filenameAbsolute);
			$unique = $filenameAbsolute . $fileStatus['mtime'] . $fileStatus['size'];
		} else {
			$unique = $filenameAbsolute;
		}
		// make sure it is again the full filename
		$filename = PathUtility::stripPathSitePrefix($filenameAbsolute);

		$pathinfo = PathUtility::pathinfo($filenameAbsolute);
		$targetFile = $this->targetDirectory . $pathinfo['filename'] . '-' . md5($unique) . '.css';
		// only create it, if it doesn't exist, yet
		if ($this->checkFileExists($targetFile)) {
			$contents = $this->compressCssString(GeneralUtility::getUrl($filenameAbsolute));
			if (strpos($filename, $this->targetDirectory) === false) {
				$contents = $this->cssFixRelativeUrlPaths($contents, PathUtility::dirname($filename) . '/');
			}
			$this->writeFileAndCompressed($targetFile, $contents);
		}
		return $this->returnFileReference($targetFile);
	}

	/**
	 * Writes $contents into file $filename together with a gzipped version into $filename.gz
	 *
	 * @param string $filename Target filename
	 * @param string $contents File contents
	 * @return boolean
	 */
	protected function writeFileAndCompressed($filename, $contents)
	{
		// write uncompressed file
		GeneralUtility::writeFile(PATH_site . $filename, $contents);
		if ($this->createGzipped) {
			// create gzip compressed version
			GeneralUtility::writeFile(PATH_site . $filename . '.gzip', gzencode($contents, $this->gzipCompressionLevel));
			// create brotli compressed version
			$this->createBrotliVersion($filename, $contents);
		}
	}

	/**
	 * Uses brotli converter to create a br version
	 *
	 * @param string $filename Target filename
	 * @return boolean
	 */
	private function createBrotliVersion($filename)
	{
		// get folders
		$bin = GeneralUtility::getFileAbsFileName("EXT:bc_brotli/Resources/Private/Bin/bro");

		// prepare shell script
		$shell = sprintf("%s --quality %d --input %s --output %s",
			$bin,						 // full convert script path
			$this->gzipCompressionLevel, // compression level
			PATH_site.$filename,		 // input file
			PATH_site.$filename.'.br'	 // output file

		);
		system($shell, $errorCode);

		return ($errorCode === 0);
	}

	/**
	 * Uses brotli converter to create a br version
	 *
	 * @param string $targetFile
	 * @return boolean
	 */
	private function checkFileExists($targetFile)
	{
		return 	(!file_exists((PATH_site . $targetFile)) ||
				($this->createGzipped && !file_exists((PATH_site . $targetFile . '.gzip'))) ||
				($this->createGzipped && !file_exists((PATH_site . $targetFile . '.br'))));
	}
}