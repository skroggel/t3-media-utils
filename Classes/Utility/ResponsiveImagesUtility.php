<?php
namespace Madj2k\MediaUtils\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Class ResponsiveImagesUtility
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */

class ResponsiveImagesUtility extends \Sitegeist\ResponsiveImages\Utility\ResponsiveImagesUtility
{

	/**
	 * Renders different image sizes for use in a srcset attribute
	 *
	 * Input:
	 *   1: $srcset = [200, 400]
	 *   2: $srcset = ['200w', '400w']
	 *   3: $srcset = ['1x', '2x']
	 *   4: $srcset = '200, 400'
	 *
	 * Output:
	 *   1+2+4: ['200w' => 'path/to/image@200w.jpg', '400w' => 'path/to/image@200w.jpg']
	 *   3: ['1x' => 'path/to/image@1x.jpg', '2x' => 'path/to/image@2x.jpg']
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $image
	 * @param int $defaultWidth
	 * @param array|string $srcset
	 * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $cropArea
	 * @param bool $absoluteUri
	 * @param string|null $fileExtension
	 * @return array
	 */
    public function generateSrcsetImages(
        FileInterface $image,
        int $defaultWidth,
        $srcset,
        Area $cropArea = null,
        bool $absoluteUri = false,
        ?string $fileExtension = null
    ): array {
        $cropArea = $cropArea ?: Area::createEmpty();

        // Convert srcset input to array
        if (!is_array($srcset)) {
            $srcset = GeneralUtility::trimExplode(',', $srcset);
        }

        $images = [];
        foreach ($srcset as $widthDescriptor) {
            $widthDescriptor = (string) $widthDescriptor;
            // Determine image width
            $srcsetMode = substr($widthDescriptor, -1);
            switch ($srcsetMode) {
                case 'x':
                    $candidateWidth = (int) ($defaultWidth * (float) substr($widthDescriptor, 0, -1));
                    break;

                case 'w':
                    $candidateWidth = (int) substr($widthDescriptor, 0, -1);
                    break;

                default:
                    $candidateWidth = (int) $widthDescriptor;
                    $srcsetMode = 'w';
                    $widthDescriptor = $candidateWidth . 'w';
            }

			/* SK: fix for image-upscaling.
			 * Also possible via [GFX][processor_allowUpscaling]=false (see below) but would create unnecessary workload by
			 * rendering images that are not used at the end of the day
			 */
			if (
				($maxImageWidth = $image->getProperty('width'))
				&& ($candidateWidth > $maxImageWidth)
			){
				// set maximum available with as new candidate if not already done so
				if ($srcsetMode == 'w') {
					$candidateWidth = (int) $maxImageWidth;
					$widthDescriptor = $maxImageWidth . 'w';
					if ($images[$widthDescriptor]) {
						continue;
					}
				}
			}

            // Generate image
            $processingInstructions = [
                'width' => $candidateWidth,
                'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image),
            ];

            if (!empty($fileExtension)) {
                $processingInstructions['fileExtension'] = $fileExtension;
            }
            $processedImage = $this->imageService->applyProcessingInstructions($image, $processingInstructions);

            // If processed file isn't as wide as it should be ([GFX][processor_allowUpscaling] set to false)
            // then use final width of the image as widthDescriptor if not input case 3 is used
            $processedWidth = $processedImage->getProperty('width');
            if ($srcsetMode === 'w' && $processedWidth !== $candidateWidth) {
                $widthDescriptor = $processedWidth . 'w';

                /* SK: cancel further processing
                 * May be reached if upscaling is set to false AND no with set via parameters
                 */
                break;
            }

            $images[$widthDescriptor] = $this->imageService->getImageUri($processedImage, $absoluteUri);
        }

        return $images;
    }


    /**
     * Creates a simple image tag
     *
     * @param  FileInterface $image
     * @param  FileInterface $fallbackImage
     * @param  TagBuilder    $tag
     * @param  Area          $focusArea
     * @param  bool          $absoluteUri
     * @param  bool          $lazyload
     * @param  int           $placeholderSize
     * @param  bool          $placeholderInline
     *
     * @return TagBuilder
     */
    public function createSimpleImageTag(
        FileInterface $originalImage,
        FileInterface $fallbackImage = null,
        TagBuilder $tag = null,
        Area $focusArea = null,
        bool $absoluteUri = false,
        bool $lazyload = false,
        int $placeholderSize = 0,
        bool $placeholderInline = false,
        ?string $fileExtension = null
    ): TagBuilder {

        if ($this->hasIgnoredFileExtension($originalImage, $ignoreFileExtensions, $fileExtension)) {
            parent::createSimpleImageTag(
                $originalImage,
                $fallbackImage,
                $fallbackTag,
                $focusArea,
                $absoluteUri,
                $lazyload,
                $placeholderSize,
                $placeholderInline,
                null // remove file extension to prevent processing in some constellations!
            );
        }
    }


	/**
	 * Generates the content for a srcset attribute from an array of image urls
	 *
	 * Input:
	 * [
	 *   '200w' => 'path/to/image@200w.jpg',
	 *   '400w' => 'path/to/image@400w.jpg'
	 * ]
	 *
	 * Output:
	 * 'path/to/image@200w.jpg 200w, path/to/image@400w.jpg 400w'
	 *
	 * @param  array   $srcsetImages
	 * @return string
     */
	public function generateSrcsetAttribute(array $srcsetImages): string
	{
		$srcsetString = [];

		// SK: add some sorting - ascending by image size
		ksort($srcsetImages, SORT_NUMERIC);

		foreach ($srcsetImages as $widthDescriptor => $imageCandidate) {
			$srcsetString[] = $this->sanitizeSrcsetUrl($imageCandidate) . ' ' . $widthDescriptor;
		}
		return implode(', ', $srcsetString);
	}


	/**
	 * Check if the image has a file format that can't be cropped
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $image
	 * @param string $ignoreFileExtensions
	 * @param string|null $fileExtension
	 * @return bool
	 */
	public function hasIgnoredFileExtension(
		FileInterface $image,
		$ignoreFileExtensions = 'svg, gif',
		?string $fileExtension = null
	): bool {
		$ignoreFileExtensions = (is_array($ignoreFileExtensions))
			? $ignoreFileExtensions
			: GeneralUtility::trimExplode(',', $ignoreFileExtensions);

		/** SK: this is nonsense because this way SVGs can't be skipped if webp is set as fileExtension
		if (!empty($fileExtension)) {
			return in_array($fileExtension, $ignoreFileExtensions);
		} else {
			return in_array($image->getProperty('extension'), $ignoreFileExtensions);
		}*/
		return in_array($image->getProperty('extension'), $ignoreFileExtensions);
	}
}
