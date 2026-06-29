===============================
Available settings
===============================

Configuration sources
=====================

The extension ships TYPO3 site sets with ready-to-use responsive image
configuration. These sets expose their options through
:file:`settings.definitions.yaml`, so project defaults can be adjusted in the
TYPO3 backend.

Available site sets
===================

..  list-table::
    :header-rows: 1

    * - Site set
      - Purpose
    * - ``madj2k/media-utils``
      - Default responsive image configuration.
    * - ``madj2k/media-utils-bootstrap``
      - Bootstrap-oriented breakpoint configuration for ``picture`` output.

Backend settings
================

After including one of the shipped site sets, the responsive image configuration
can be adjusted in the TYPO3 backend under :guilabel:`Settings > MediaUtils`.

Use these backend settings for project-wide defaults such as lazy loading,
placeholder handling, ``srcset`` values, breakpoint definitions, ignored file
extensions and the target image file extension.

Available responsive image options
==================================

..  list-table::
    :header-rows: 1

    * - Option
      - Description
    * - ``loading``
      - Native image loading hint, for example ``lazy`` or ``eager``.
    * - ``lazyload``
      - Enables JavaScript-style lazy loading using ``data-src`` and ``data-srcset``.
    * - ``placeholder.size``
      - Width of the generated placeholder image.
    * - ``placeholder.inline``
      - Embeds the placeholder as inline base64 data URI where possible.
    * - ``srcset``
      - Width or density descriptors used for ``srcset`` generation.
    * - ``sizes``
      - Pattern for the generated ``sizes`` attribute.
    * - ``breakpoints``
      - Breakpoint configuration for ``picture`` output.
    * - ``renderHeightAttribute``
      - Controls whether the fallback image tag should render a height attribute.
    * - ``ignoreFileExtensions``
      - Comma-separated list of extensions that should not be processed responsively.
    * - ``fileExtension``
      - Target extension for processed images, for example ``webp``.
    * - ``class``
      - Default CSS class for rendered image tags.

Default set
===========

The default set renders classic responsive image markup using ``srcset`` and
``sizes``. Its default ``srcset`` is:

..  code-block:: text

    400, 600, 800, 1000, 1200

SVG and GIF files are ignored by default because responsive image processing is
usually not useful for these formats.

Bootstrap set
=============

The Bootstrap set is configured for ``picture`` markup. It defines desktop,
tablet and mobile breakpoints and can render WebP output by default.

Example structure:

..  code-block:: typoscript

    settings.txMediaUtils.responsiveImages {
        loading = lazy
        breakpoints {
            0 {
                cropVariant = default
                media = (min-width: 992px)
                srcset = 1200, 1400, 1920, 2800, 3840
                sizes = (min-width: %1$dpx) %1$dpx, 100vw
            }
            1 {
                cropVariant = default
                media = (min-width: 576px)
                srcset = 768, 992
                sizes = (min-width: %1$dpx) %1$dpx, 100vw
            }
            2 {
                cropVariant = default
                media = (min-width: 0px)
                srcset = 390, 576
                sizes = (min-width: %1$dpx) %1$dpx, 100vw
            }
        }
    }
