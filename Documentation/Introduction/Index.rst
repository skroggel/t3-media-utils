============
Introduction
============

What does it do?
================

Media Utils provides responsive image rendering for TYPO3 and bundles reusable
Fluid partials and ViewHelpers for working with image and media files.

It can render classic responsive ``img`` markup with ``srcset`` and ``sizes``
or ``picture`` markup with breakpoint-specific ``source`` tags. The output is
configured through TYPO3 site sets and TypoScript settings.

Main features
=============

* Responsive image rendering with ``srcset`` and ``sizes``.
* Optional ``picture`` tag rendering with configurable breakpoints.
* Native lazy loading via ``loading="lazy"``.
* JavaScript lazy loading support via ``data-src`` and ``data-srcset``.
* Optional placeholder images, including inline base64 placeholders.
* Configurable output file extension, for example WebP.
* Configurable ignored file extensions, for example SVG and GIF.
* Width limits to avoid unnecessary image upsizing.
* TYPO3 v14 batch image processing when available.
* Reusable ``Utils/Media`` partial for images and videos.
* Own Fluid ViewHelpers under the ``Madj2k\MediaUtils`` namespace.
* File helper ViewHelpers for FAL properties, relations and crop-variant CSS classes.
* Default and Bootstrap site sets.
* Backend-editable settings via ``settings.definitions.yaml``.
* Compatibility with projects migrating from ``EXT:sms_responsive_images``.

Rendering concept
=================

The extension separates the rendering into small services:

* the ViewHelpers collect arguments and settings,
* the responsive image service prepares image options,
* generators create ``srcset`` candidates and placeholders,
* renderers build ``img``, ``source`` and ``picture`` tags,
* TYPO3's renderer registry is used for non-image media files.

This keeps Fluid templates concise while still allowing detailed control over
individual images or media elements.
