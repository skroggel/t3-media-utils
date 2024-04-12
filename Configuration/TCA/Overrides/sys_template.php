<?php

call_user_func(
    function($extKey)
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extKey,
<<<<<<< Updated upstream
            'Configuration/TypoScript/Bootstrap',
            'Responsive images bootstrap configuration (optional)'
=======
            'Configuration/TypoScript',
            'MediaUtils'
        );


        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extKey,
            'Configuration/TypoScript/Bootstrap',
            'MediaUtils - Responsive Images Bootstrap Configuration (optional)'
>>>>>>> Stashed changes
        );

    },
    'media_utils'
);
