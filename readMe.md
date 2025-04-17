# media_utils
This extension provides
* some performance-fixes and improvements for EXT:sms_responsive_images (sitegeist/sms-responsive-images) and
* some helpful ViewHelpers and includable partials for the usage with media-files.

## Features
* Fixes performance issue with upsizing of images of EXT:sms_responsive_images
* Includes a media-partial for usage in own extensions with fallback to the default settings of EXT:sms_responsive_images for your website
* Includes a TypoScript-configuration for responsive images of EXT:sms_responsive_images with Bootstrap (picture-tag only)
* Some helpful ViewHelpers for usage with media-files

## Installation
Just install the extension and include the Typoscript-configuration.
Make sure you include it AFTER the TypoScript-configuration of EXT:sms_responsive_images (sitegeist/sms-responsive-images).
If you want to use TypoScript-configuration for responsive images** **with Bootstrap, just include the corresponding configuration into your root-template.

Ensure that you add "webp" as allowed image extension in your settings.php
```
$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai,svg,webp';
```

## How to use
### Usage with EXT:Mask
The extension automatically includes the configuration for responsive images to EXT:mask.
This way all your custom content elements have the according settings automaticllay available.

If you configure default-settings for your custom content elements, you can also include default-settings for your media files.
Just follow this structure for your TypoScript-configuration:
```
tt_content.mask_your_element {
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
Here you also have the ability to override the default settings, e.g. if you want to use different cropVariants for each breakpoint:
```
tt_content.mask_your_element {
    settings {

        [...]

        tx_smsresponsiveimages {

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
tt_content.mask_your_element {
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

            big {
                image {
                    dimensions {
                        maxWidth = 650
                    }
                }
                video {
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
}
```
Now you can call the media-partial in your content-element with the according key for the desired setting. The following call
would load the default-settings for the sub-key "small":
```
<f:render partial="Utils/Media" arguments="{file: image, maxWidth: 1000, settings: settings, settingsVariant: 'small'}" />
```
### Usage in TypoScript-based FluidTemplates
The usage does not differ much from the usage with EXT:mask.
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

            settings.tx_smsresponsiveimages < lib.contentElement.settings.tx_smsresponsiveimages
            settings.tx_smsresponsiveimages {

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
tx_myextension.settings.tx_smsresponsiveimages < lib.contentElement.settings.tx_smsresponsiveimages
```
3) Use the partial accordingly and add additional settings if you want (see above)
```
<f:render partial="Utils/Media" arguments="{image: image, width: 1000, settings: settings}" />
```

#### Use the normal ViewHelper and insert the values of the TypoScript-lib into each parameter
```
<html xmlns:sms="http://typo3.org/ns/Sitegeist/SmsResponsiveImages/ViewHelpers" data-namespace-typo3-fluid="true">
<sms:media
	class="image-embed-item{f:if(condition: settings.tx_smsresponsiveimages.class, then: ' {lib.contentElement.settings.tx_smsresponsiveimages.class}')}"
	file="{file}"
	width="{dimensions.width}"
	height="{dimensions.height}"
	alt="{file.alternative}"
	title="{file.title}"
	srcset="{lib.contentElement.settings.tx_smsresponsiveimages.srcset}"
	lazyload="{lib.contentElement.settings.tx_smsresponsiveimages.lazyload}"
	placeholderSize="{lib.contentElement.settings.tx_smsresponsiveimages.placeholder.size}"
	placeholderInline="{lib.contentElement.settings.tx_smsresponsiveimages.placeholder.inline}"
	sizes="{lib.contentElement.settings.tx_smsresponsiveimages.sizes}"
	breakpoints="{lib.contentElement.settings.tx_smsresponsiveimages.breakpoints}"
	ignoreFileExtensions="{lib.contentElement.settings.tx_smsresponsiveimages.ignoreFileExtensions}"
	fileExtension="{lib.contentElement.settings.tx_smsresponsiveimages.fileExtension}"
	loading="{lib.contentElement.settings.media.lazyLoading}"
	decoding="{lib.contentElement.settings.media.imageDecoding}"
/>
</html>
```
