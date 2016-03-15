<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// add hook for javascript compression
$GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler'] = 'BC\\BcBrotli\\Hook\\ResourceCompressHook->processJS';
// add hook for stylesheet compression
$GLOBALS['TYPO3_CONF_VARS']['FE']['cssCompressHandler'] = 'BC\\BcBrotli\\Hook\\ResourceCompressHook->processCSS';

// Backend AJAX Module
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler (
	'BcBrotli',
	'BC\\BcBrotli\\Module\\Module->main'
);