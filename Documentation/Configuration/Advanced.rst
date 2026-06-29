======================
Advanced configuration
======================

Reusable TypoScript lib
=======================

The extension provides the responsive image configuration as a TypoScript
``lib`` object:

..  code-block:: typoscript

    lib.txMediaUtils.responsiveImages

This is not a separate global settings API. It is a reusable TypoScript library
that can be copied into the settings of a custom Fluid template, content element
or extension.

Example for a custom extension or Fluid template:

..  code-block:: typoscript

    tx_myextension.settings.txMediaUtils.responsiveImages < lib.txMediaUtils.responsiveImages

Example for a content element:

..  code-block:: typoscript

    tt_content.vendor_element {
        settings.txMediaUtils.responsiveImages < lib.txMediaUtils.responsiveImages
    }


Other
=================
..  important::

    For more details on advanced configuration options see :doc:`../Usage/ContentBlocks`.

