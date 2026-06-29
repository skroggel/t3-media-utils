===========
ViewHelpers
===========

Namespace
=========

Use the Media Utils namespace in Fluid templates:

..  code-block:: html

    <html xmlns:mediaUtils="http://typo3.org/ns/Madj2k/MediaUtils/ViewHelpers"
          data-namespace-typo3-fluid="true">

Responsive image ViewHelpers
============================

``mediaUtils:responsiveImages.image``
-------------------------------------

Renders responsive image markup for a FAL image or source path.

Required input:

* either ``image``
* or ``src``

Do not pass both at the same time.

Example:

..  code-block:: html

    <mediaUtils:responsiveImages.image
        image="{image}"
        width="1000"
        alt="{image.alternative}"
        title="{image.title}"
        settings="{settings}"
    />

``mediaUtils:responsiveImages.media``
-------------------------------------

Renders images through the responsive image pipeline and non-image media through
TYPO3's media renderer registry.

Required input:

* either ``file``
* or ``src``

Example:

..  code-block:: html

    <mediaUtils:responsiveImages.media
        file="{file}"
        width="1370"
        height="771"
        additionalConfig="{autoplay: 1, muted: 1, loop: 0, playsinline: 1}"
        settings="{settings}"
    />

Common responsive arguments
===========================

..  list-table::
    :header-rows: 1

    * - Argument
      - Description
    * - ``settings``
      - Settings array used for defaults.
    * - ``settingsVariant``
      - Optional settings variant key.
    * - ``alt`` / ``title``
      - Metadata attributes. If omitted, file metadata can be used.
    * - ``class`` / ``id`` / ``style``
      - HTML attributes.
    * - ``loading``
      - Native loading hint.
    * - ``decoding`` / ``fileDecoding``
      - Image decoding hint.
    * - ``fileExtension``
      - Target extension for processed images.
    * - ``crop`` / ``cropVariant``
      - Crop handling.
    * - ``width`` / ``height``
      - Target dimensions.
    * - ``minWidth`` / ``minHeight``
      - Minimum dimensions.
    * - ``maxWidth`` / ``maxHeight``
      - Maximum dimensions.
    * - ``absolute``
      - Generate absolute URLs.
    * - ``srcset`` / ``sizes``
      - Responsive ``img`` attributes.
    * - ``breakpoints``
      - ``picture`` breakpoint configuration.
    * - ``lazyload``
      - Enables JavaScript lazy-load attributes.
    * - ``placeholderSize``
      - Placeholder image width.
    * - ``placeholderInline``
      - Inline placeholder as data URI where possible.
    * - ``ignoreFileExtensions``
      - Extensions excluded from responsive processing.
    * - ``renderHeightAttribute``
      - Render height on fallback image tag.

File helper ViewHelpers
=======================

``mediaUtils:file.getProperty``
-------------------------------

Reads a property from a FAL ``FileReference`` or Extbase file reference.

..  code-block:: html

    <mediaUtils:file.getProperty file="{file}" property="mime_type" />

``mediaUtils:file.getReference``
--------------------------------

Loads FAL references for a table, field and UID.

..  code-block:: html

    <f:for each="{mediaUtils:file.getReference(table: 'pages', field: 'media', uid: pageUid)}"
           as="file">
        <f:render partial="Utils/Media" arguments="{file: file, settings: settings}" />
    </f:for>

``mediaUtils:file.cropVariantClass``
------------------------------------

Creates a CSS class from the first selected crop ratio of a file reference.

Without Bootstrap classes:

..  code-block:: html

    <mediaUtils:file.cropVariantClass file="{file}" />

With Bootstrap ratio classes:

..  code-block:: html

    <mediaUtils:file.cropVariantClass file="{file}" bootstrap="true" />

The Bootstrap output uses classes such as ``ratio ratio-16x9``.
