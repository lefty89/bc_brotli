<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// add hook for javascript compression
$GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler'] = 'EXT:bc_brotli/Classes/Hook/ResourceCompressHook.php:ResourceCompressHook->processJS';
// add hook for stylesheet compression
$GLOBALS['TYPO3_CONF_VARS']['FE']['cssCompressHandler'] = 'EXT:bc_brotli/Classes/Hook/ResourceCompressHook.php:ResourceCompressHook->processCSS';

// Backend AJAX Module
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler (
	'BcBrotli',
	'EXT:bc_brotli/Classes/Module/Module.php:Module->main'
);