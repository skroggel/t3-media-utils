<?php

call_user_func(
    function($extKey)
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extKey,
            'Configuration/TypoScript/Bootstrap',
            'Responsive images bootstrap configuration (optional)'
        );

    },
    'media_utils'
);
