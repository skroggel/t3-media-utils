========================
Other usages
========================

Usage in Fluid templates
========================

When using a manually configured ``FLUIDTEMPLATE``, add the partial path and
merge the responsive image settings:

..  code-block:: typoscript

    lib.siteDefault.stage = FLUIDTEMPLATE
    lib.siteDefault.stage {
        file = EXT:site_default/Resources/Private/Templates/Stage.html
        partialRootPaths.10 = EXT:site_default/Resources/Private/Partials/
        partialRootPaths.1712245685 = {$plugin.tx_mediautils.view.partialRootPath}

        dataProcessing {
            10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
            10 {
                references {
                    table = pages
                    data = levelfield: -1, media, slide
                }
                as = media
            }
        }

        settings.txMediaUtils.responsiveImages < lib.txMediaUtils.responsiveImages
        settings.txMediaUtils.responsiveImages.loading = eager
    }

Usage in custom extensions
==========================

Add the Media Utils partial path:

..  code-block:: typoscript

    tx_myextension {
        view {
            partialRootPaths {
                1712245685 = {$plugin.tx_mediautils.view.partialRootPath}
            }
        }
    }

Merge the global defaults into your extension settings:

..  code-block:: typoscript

    tx_myextension.settings.txMediaUtils.responsiveImages < lib.txMediaUtils.responsiveImages

Render the partial:

..  code-block:: html

    <f:render partial="Utils/Media"
              arguments="{file: image, width: 1000, settings: settings}" />

Direct ViewHelper usage
=======================

Use the extension's own ViewHelpers. Do not use the old namespace of ``sitegeist/sms-responsive-images``.

..  code-block:: html

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:mediaUtils="http://typo3.org/ns/Madj2k/MediaUtils/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <mediaUtils:responsiveImages.media
        file="{file}"
        width="{dimensions.width}"
        height="{dimensions.height}"
        alt="{file.alternative}"
        title="{file.title}"
        srcset="{settings.txMediaUtils.responsiveImages.srcset}"
        lazyload="{settings.txMediaUtils.responsiveImages.lazyload}"
        placeholderSize="{settings.txMediaUtils.responsiveImages.placeholder.size}"
        placeholderInline="{settings.txMediaUtils.responsiveImages.placeholder.inline}"
        sizes="{settings.txMediaUtils.responsiveImages.sizes}"
        breakpoints="{settings.txMediaUtils.responsiveImages.breakpoints}"
        ignoreFileExtensions="{settings.txMediaUtils.responsiveImages.ignoreFileExtensions}"
        fileExtension="{settings.txMediaUtils.responsiveImages.fileExtension}"
        loading="{settings.txMediaUtils.responsiveImages.loading}"
        decoding="async"
    />

    </html>
