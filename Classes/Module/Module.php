<?php

namespace BC\BcBrotli\Module;

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

use ZipArchive;
use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for binary creation
 *
 * @author Lefty (fb.lefty@web.de)
 * @package TYPO3
 * @subpackage bc_brotli
 */
class Module
{
    const STATE_WARNING = 3;
    const STATE_LOADING = 2;
    const STATE_SUCCESS = 1;
    const STATE_ERROR = 0;

    /**
     * the current step
     *
     * @var int
     */
    private $step = 0;

    /**
     * git version
     *
     * @var string
     */
    private $git_version = '';

    /**
     * compiler version
     *
     * @var string
     */
    private $gcc_version = '';

    /**
     * git repository path
     *
     * @var string
     */
    private $repository = 'https://github.com/google/brotli';

    /**
     * git branch
     *
     * @var string
     */
    private $branch = 'master';

    /**
     * directory where the source is cloned
     *
     * @var string
     */
    private $tempDir = "typo3temp/Cache/Data/bc_brotli/";

    /**
     * @cosntruct
     */
    public function __construct()
    {
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
    private function msgStepNext($message)
    {
        $this->sendMessage(++$this->step, Module::STATE_LOADING, $message);
    }

    /**
     * sends finish signal for last step
     *
     * @param int $state
     * @param string $message
     */
    private function msgStepFinished($state = Module::STATE_SUCCESS, $message = "")
    {
        $this->sendMessage($this->step, $state, $message);
    }

    /**
     * gets the source with git
     *
     * @param string $dir
     * @return string
     * @throws \Exception
     */
    private function getSourceAsGit($dir)
    {
        $this->msgStepNext("Cloning repository: $this->repository.git");

        if (is_dir($dir) && (chdir($dir))) {

            // cloneing git source
            exec(sprintf("git clone %s .", $this->repository . '.git'), $output, $cloneErrorCode);

            if ($cloneErrorCode === 0) {
                $this->msgStepFinished();
                $this->msgStepNext("Checkout: $this->branch");

                // cloneing git source
                exec(sprintf("git checkout %s", $this->branch), $output, $checkoutErrorCode);
                if ($checkoutErrorCode === 0) {
                    $this->msgStepFinished();

                    return $dir . '/tools';
                }
            }
        }

        throw new Exception();
    }

    /**
     * gets the source with git
     *
     * @param string $dir
     * @return string
     * @throws \Exception
     */
    private function getSourceAsZip($dir)
    {
        $this->msgStepNext("Download: $this->repository/archive/$this->branch.zip");

        // download zip archive from github
        if ((is_dir($dir) && (file_put_contents("$dir/$this->branch.zip",
                fopen("$this->repository/archive/$this->branch.zip", 'r'))))
        ) {
            $this->msgStepFinished();
            $zip = new ZipArchive;
            $res = $zip->open("$dir/$this->branch.zip");
            if ($res === true) {
                $zip->extractTo($dir);
                $zip->close();

                return $dir . "/brotli-$this->branch/tools";
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
    private function getBrotliSource($dir)
    {
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
    private function compileBrotliBinary($binaryPath)
    {
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
    private function moveBrotliBinary($binary)
    {
        $this->msgStepNext("Move binary to extension directory");

        if ((file_exists($binary)) && rename($binary,
                GeneralUtility::getFileAbsFileName("EXT:bc_brotli/Resources/Private/Bin/bro"))
        ) {
            $this->msgStepFinished();

            return;
        }

        throw new Exception();
    }

    /**
     * gets the installed git version
     */
    private function getGitVersion()
    {
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
    private function getCompilerVersion()
    {
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
    private function tempdir()
    {
        $this->msgStepNext("Creating temporary folder");

        /** @var string $name */
        $name = uniqid();

        /** @var string $full */
        $full = GeneralUtility::getFileAbsFileName($this->tempDir) . $name;

        if (!is_dir($full) && (mkdir($full, 0777, true))) {
            $this->msgStepFinished(Module::STATE_SUCCESS, "Temporary folder created: $name");

            return $full;
        }

        throw new Exception();
    }

    /**
     * removes folder and its content
     *
     * @param $dir
     * @return bool
     */
    private function recurseRmDir($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
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
    private function sendMessage($step, $state, $message = "")
    {
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
    private function closeConnection()
    {
        ob_end_clean();

        echo "event: closing\n";
        echo "data: \n\n";

        flush();
        ob_end_flush();
    }

    /**
     * main function
     */
    public function main()
    {
        // bug?
        $this->sendMessage(10, 0, "BUG");

        try {
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
        } catch (Exception $e) {
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