<?php

namespace BC\BcBrotli\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (C) 2016 Lefty (fb.lefty@web.de)
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this script. If not, see <http://www.gnu.org/licenses/>.
 *
 ***************************************************************/

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
    protected function getCompressor()
    {
        if ($this->compressor === null) {
            $this->compressor = GeneralUtility::makeInstance(ResourceCompressor::class);
        }

        return $this->compressor;
    }
}