# media_utils
This extension provides
* fully configurable responsive images for TYPO3
* some helpful ViewHelpers and includable partials for the usage with media-files.

## Features
* Supports `<picture>`, `srcset`, and `sizes`
* Configurable breakpoints
* Native and JavaScript lazy loading
* Backend configuration via `settings.definitions.yaml`
* Usage of processingInstructionsBatch in TYPO3 v14 (thanks to Ayke Halder for the improvement hint)
* Includes a media-partial for usage in own extensions
* Includes a configuration for responsive images with Bootstrap (picture-tag only)
* Some helpful ViewHelpers for usage with media-files
* Configuration is backwards-compatible with EXT:sms_responsive_images
* Fixes performance issue with upsizing of images of EXT:sms_responsive_images

## Installation
Just install the extension and include the desired configuration (e.g. with or without Bootstrap) set to your site-config.
```
name: your/site-package
label: 'Site Package (Default)'
dependencies:
  - madj2k/media-utils-bootstrap
```

Ensure that you add "webp" as allowed image extension in your `settings.php`
```
$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai,svg,webp';
```

## How to use
### Usage with EXT:content-blocks (friendsoftypo3/content-blocks)
The extension automatically includes the configuration for responsive images to EXT:content-blocks.
This way all your custom content elements have the according settings automaticllay available.

If you configure default-settings for your custom content elements, you can also include default-settings for your media files.
Just follow this structure for your TypoScript-configuration:
```
tt_content.VENDOR_your_element {
    settings {
    	[...]
        media {
            image {
                dimensions {
                    maxWidth = 650
                }
            }
            video {
                dimensions {
                    width = 1370
                    height= 771
                }
                additionalConfig {
                    playsinline = 1 // needed for Safari on Mobile
                    autoplay = 1
                    mute = 1
                    loop = 0
                    modestbranding = 1
                }
            }
		}
    }
}
```
Here you also have the ability to override the default settings, e.g. if you want to use different cropVariants for each breakpoint or no native lazy-loading:
```
tt_content.VENDOR_your_element {
    settings {

        [...]

        txMediaUtils.responsiveImages {

            loading = eager
            breakpoints {
                0 {
                    cropVariant = default
                }
                1 {
                    cropVariant = tablet
                }
                2 {
                    cropVariant = mobile
                }
            }
        }

    	[...]
    }
}
```
Then call the media-partial in your content-element like this:
```
<f:render partial="Utils/Media" arguments="{file: image, maxWidth: 1000, settings: settings}" />
```
If you e.g. set a explicit maxWidth this value will be used, otherwise the default value from your TypoScript-configuration will be used.
The defined configuration for responsive images is applied automatically.

There may be the case that you have multiple styles of one custom content element which require different default-settings for your media.
This can be handeled by extending your TypoScript-configuration by an additional sub-key to switch between the settings.
Example:
```
tt_content.VENDOR_your_element {
    settings {
    	[...]
        media {

            small {
                image {
                    dimensions {
                        maxWidth = 650
                    }
                }
                video {
                    dimensions {
                        width = 1370
                        height= 771
                    }
                    additionalConfig {
                        autoplay = 1
                        mute = 1
                        loop = 0
                        modestbranding = 1
                    }
                }
            }

            big {
                image {
                    dimensions {
                        maxWidth = 650
                    }
                }
                video {
                    dimensions {
                        width = 1370
                        height= 771
                    }
                    additionalConfig {
                        autoplay = 1
                        mute = 1
                        loop = 0
                        modestbranding = 1
                    }
                }
            }
		}
    }
}
```
Now you can call the media-partial in your content-element with the according key for the desired setting. The following call
would load the default-settings for the sub-key "small":
```
<f:render partial="Utils/Media" arguments="{file: image, maxWidth: 1000, settings: settings, settingsVariant: 'small'}" />
```
### Usage with EXT:mask (deprecated)
The extension automatically includes the configuration for responsive images to EXT:mask.
This way all your custom content elements have the according settings automaticllay available.

**This actually works exactly as described above for EXT:content-blocks (friendsoftypo3/content-blocks).**

### Usage in TypoScript-based FluidTemplates
The usage does not differ much from the usage with EXT:content-blocks (friendsoftypo3/content-blocks).
You simply have to include the corresponding settings and the partial-path into your TypoScript.
Here you also have the ability to override the default settings, e.g. if you want to use different cropVariants for each breakpoint.
```
lib.siteDefault {

    fluidTemplates {

        stage = FLUIDTEMPLATE
        stage {

            file = EXT:site_default/Resources/Private/FluidTemplates/Templates/Stage.html
            partialRootPaths.0 = EXT:site_default/Resources/Private/FluidTemplates/Partials/Stage/
            partialRootPaths.1 = EXT:site_default/Resources/Private/Mask/Frontend/Partials/
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

            variables {
                pageTitle = TEXT
                pageTitle.data = page:title
            }

            settings.txMediaUtils.responsiveImages < lib.txMediaUtils.responsiveImages
            settings.txMediaUtils.responsiveImages {

                loading = eager
                breakpoints {
                    0 {
                        cropVariant = default
                    }
                    1 {
                        cropVariant = tablet
                    }
                    2 {
                        cropVariant = mobile
                    }
                }
            }
        }
    }
}
```

### Usage for images in your own extension
There are basically two ways:

#### Use the partial of this extension and override only the values you need to override (recommended)
1) Extend the partialPaths accordingly:
```
tx_myextension {
    view {
        partialRootPaths {
            1712245685 = {$plugin.tx_mediautils.view.partialRootPath}
        }
    }
}
```
2) Merge the global settings into your extension settings:
```
tx_myextension.settings.txMediaUtils.responsiveImages < lib.txMediaUtils.responsiveImages
```
3) Use the partial accordingly and add additional settings if you want (see above)
```
<f:render partial="Utils/Media" arguments="{file: image, width: 1000, settings: settings}" />
```

#### Use the normal ViewHelper and insert the values of the TypoScript-lib into each parameters (not really convenient - but works)
```
<html xmlns:sms="http://typo3.org/ns/Sitegeist/SmsResponsiveImages/ViewHelpers" data-namespace-typo3-fluid="true">
<sms:media
	class="image-embed-item{f:if(condition: settings.txMediaUtils.class, then: ' {lib.txMediaUtils.responsiveImages.class}')}"
	file="{file}"
	width="{dimensions.width}"
	height="{dimensions.height}"
	alt="{file.alternative}"
	title="{file.title}"
	srcset="{lib.txMediaUtils.responsiveImages.srcset}"
	lazyload="{lib.txMediaUtils.responsiveImages.lazyload}"
	placeholderSize="{lib.txMediaUtils.responsiveImages.placeholder.size}"
	placeholderInline="{lib.txMediaUtils.responsiveImages.placeholder.inline}"
	sizes="{lib.txMediaUtils.responsiveImages.sizes}"
	breakpoints="{lib.txMediaUtils.responsiveImages.breakpoints}"
	ignoreFileExtensions="{lib.txMediaUtils.responsiveImages.ignoreFileExtensions}"
	fileExtension="{lib.txMediaUtils.responsiveImages.fileExtension}"
	loading="{lib.txMediaUtils.responsiveImages.settings.media.lazyLoading}"
	decoding="{lib.txMediaUtils.responsiveImages.settings.media.imageDecoding}"
/>
</html>
```


# Testing
* Single Method
```
ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.xml \
vendor/madj2k/t3-media-utils/Tests/Unit/Whatever/Whatever.php \
--filter MethodName
```
```
ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.functional.xml \
vendor/madj2k/t3-media-utils/Tests/Functional/Whatever/Whatever.php \
--filter MethodName
```
* Single File
```
ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.xml \
vendor/madj2k/t3-media-utils/Tests/Unit/Whatever/Whatever.php
```
```
ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.functional.xml \
vendor/madj2k/t3-media-utils/Tests/Functional/Whatever/Whatever.php
```
* All files
```
ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.xml
```
```
ddev exec vendor/bin/phpunit -c vendor/madj2k/t3-media-utils/phpunit.functional.xml
```

# Generate the documentation as HTML
Execute in the extension-root:
```
docker run --rm --pull always -v "$(pwd)":/project -it ghcr.io/typo3-documentation/render-guides:latest --config=Documentation
```
