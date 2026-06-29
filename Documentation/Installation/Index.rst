============
Installation
============

Requirements
============

..  list-table::
    :header-rows: 1

    * - Component
      - Requirement
    * - PHP
      - 8.3 or later
    * - TYPO3
      - 13.4 LTS or 14.4 LTS
    * - PHP extension
      - ``fileinfo``

Composer installation
=====================

Install the extension with Composer:

..  code-block:: bash

    composer require madj2k/t3-media-utils

Site set
========

Include one of the shipped site sets in your site package configuration.

Default configuration:

..  code-block:: yaml

    name: your/site-package
    label: 'Site Package'
    dependencies:
      - madj2k/media-utils

Bootstrap-oriented ``picture`` configuration:

..  code-block:: yaml

    name: your/site-package
    label: 'Site Package'
    dependencies:
      - madj2k/media-utils-bootstrap

The Bootstrap set depends on the default set and provides breakpoint-oriented
``picture`` configuration for common Bootstrap breakpoints.

Allow WebP output
=================

When you want to render processed images as WebP, add ``webp`` to the allowed
TYPO3 image file extensions in :file:`config/system/settings.php` or the
respective project configuration:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] =
        'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai,svg,webp';

Template paths
==============

The extension ships reusable partials under:

..  code-block:: text

    EXT:media_utils/Resources/Private/Partials/

The site sets register the default TypoScript configuration. For custom
extensions or manually configured Fluid templates, add the partial root path
explicitly when necessary.
