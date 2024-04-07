# media_utils
This extension provides some performance-fixes and improvements for the extension sms_responsive_images (sitegeist/sms-responsive-images) and some helpful ViewHelper for the usage with media-files.

## Features
* Fixes performance issue with upsizing of images of sms_responsive_images
* Includes a media-partial for usage in own extensions with fallback to the default settings of sms_responsive_images for your website
* Includes a TypoScript-configuration for responsive images of sms_responsive_images with Bootstrap (picture-tag only)
* Some helpful ViewHelpers for usage with media-files

## Installation
Just install the extension. There is no further configuration needed.
If you want to use TypoScript-configuration for responsive images with Bootstrap, just include the corresponding configuration into your root-template.
Make sure you include it AFTER the TypoScript-configuration of sms_responsive_images (sitegeist/sms-responsive-images).


## Usage of responsive images in your own extension with defined configuration
There are basically two ways:

### Use the partial of this extension and override only the values you need to override (recommended)
1) Extend the partialPaths accordingly:
```
tx_myextension {
    view {
        partialRootPaths {
            1712245685 = {$plugin.tx_mediautils.view.partialRootPath}
        }
    }
```
2) Merge the global settings into your extension settings:
```
tx_myextension.settings.tx_smsresponsiveimages < lib.contentElement.settings.tx_smsresponsiveimages
```
3) Use the partial accordingly
```
<f:render partial="ResponsiveImage" arguments="{image: image, width: 1000}" />
```

### Use the normal ViewHelper and insert the values of the TypoScript-lib into each parameter
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
