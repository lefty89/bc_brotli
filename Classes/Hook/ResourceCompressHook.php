<?php

/**
 *
 * User: Lefty
 * Date: 31.01.2015
 * Time: 13:21
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
use BC\BcBrotli\Compressor\ResourceCompressor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RenderController
 * @package BC\BcBrotli\Hook
 */
class ResourceCompressHook
{
	/**
	 * the compressor class
	 *
	 * @var \BC\BcBrotli\Compressor\ResourceCompressor
	 */
	protected $compressor = null;

	/**
	 * @param array $files
	 */
	public function processJS(&$files)
	{
		// compress js libraries
		$files['jsLibs'] = $this->getCompressor()->compressJsFiles($files['jsLibs']);
		// compress js files
		$files['jsFiles'] = $this->getCompressor()->compressJsFiles($files['jsFiles']);
		// compress css libraries
		$files['jsFooterFiles'] = $this->getCompressor()->compressJsFiles($files['jsFooterFiles']);
	}

	/**
	 * @param array $files
	 */
	public function processCSS(&$files)
	{
		// compress css libraries
		$files['cssLibs'] = $this->getCompressor()->compressCssFiles($files['cssLibs']);
		// compress css files
		$files['cssFiles'] = $this->getCompressor()->compressCssFiles($files['cssFiles']);
	}

	/**
	 * Returns instance of ResourceCompressor
	 *
	 * @return \BC\BcBrotli\Compressor\ResourceCompressor
	 */
	protected function getCompressor() {

		if ($this->compressor === NULL) {
			$this->compressor = GeneralUtility::makeInstance(ResourceCompressor::class);
		}
		return $this->compressor;
	}
}