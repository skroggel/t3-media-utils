<?php
defined('TYPO3_MODE') || defined('TYPO3')|| die('Access denied.');

call_user_func(
    function($extKey)
    {

        //=================================================================
        // XClasses
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\Sitegeist\ResponsiveImages\Utility\ResponsiveImagesUtility::class] = [
            'className' => \Madj2k\MediaUtils\Utility\ResponsiveImagesUtility::class
        ];

    },
    'media_utils'
);
