<?php

call_user_func(
    function($extKey)
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extKey,
            'Configuration/TypoScript',
            'MediaUtils'
        );
        
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extKey,
            'Configuration/TypoScript/Bootstrap',
            'MediaUtils - Responsive Images Bootstrap Configuration (optional)'
        );

    },
    'media_utils'
);
