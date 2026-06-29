=========
Migration
=========

From EXT:sms_responsive_images
==============================

Media Utils is designed to be compatible with projects that previously used
``EXT:sms_responsive_images``. The Composer package declares a replacement for
``sitegeist/sms-responsive-images`` and the TYPO3 extension metadata declares a
conflict with the old extension key.

Obsolete sitegeist/sms-responsive-images ViewHelper references
========================================

Do not use the old namespace anymore:

..  code-block:: html

    xmlns:sms="http://typo3.org/ns/Sitegeist/SmsResponsiveImages/ViewHelpers"

Use the Media Utils namespace instead:

..  code-block:: html

    xmlns:mediaUtils="http://typo3.org/ns/Madj2k/MediaUtils/ViewHelpers"

Old direct usage:

..  code-block:: html

    <sms:media file="{file}" />

New direct usage:

..  code-block:: html

    <mediaUtils:responsiveImages.media file="{file}" settings="{settings}" />

Recommended migration path
==========================

1. Include the ``madj2k/media-utils`` or ``madj2k/media-utils-bootstrap`` site set.
2. Configure the responsive image defaults in the backend under
   :guilabel:`Settings > MediaUtils`.
3. Keep existing ``lib.txMediaUtils.responsiveImages`` integrations. This is the
   documented global TypoScript object for Media Utils.
4. Replace old ``sitegeist/sms-responsive-images`` ViewHelper namespaces with the Media Utils namespace
   when templates are touched.
5. Prefer the bundled ``Utils/Media`` partial for new implementations.
6. Keep compatibility aliases only for existing templates that cannot be migrated
   immediately.

Compatibility with older sitegeist/sms-responsive-images-based implementations
========================================================

Version 14 is compatible with older implementations that previously used Media
Utils together with ``sitegeist/sms-responsive-images``, for example projects based on
version 13 plus ``sitegeist/sms-responsive-images``. Existing settings below
``lib.txMediaUtils.responsiveImages`` and the legacy
``tx_smsresponsiveimages`` settings are still considered during rendering, so
projects can be upgraded first and templates can be migrated step by step.

For new code, use the Media Utils ViewHelpers and namespace directly:

..  code-block:: html

    xmlns:mediaUtils="http://typo3.org/ns/Madj2k/MediaUtils/ViewHelpers"

Deprecated Mask usage
=====================

The extension still adds partial paths to ``lib.maskContentElement`` for legacy
projects. New implementations should prefer ``EXT:content-blocks`` or direct
Fluid template integration.

README audit notes
==================

During documentation creation, the README was checked against the current
extension source. The following points were updated in this manual:

* ``sitegeist/sms-responsive-images`` ViewHelper examples were removed and replaced with Media Utils ViewHelpers.
* Direct ViewHelper examples now use ``Madj2k\MediaUtils`` namespaces.
* The documented global TypoScript object remains ``lib.txMediaUtils.responsiveImages``.
* Features visible in the source but not fully described in the README were added,
  including placeholder generation, focus-area metadata, TYPO3 renderer registry
  usage for non-image media and file helper ViewHelpers.
