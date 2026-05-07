<?php
declare(strict_types=1);

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

namespace Madj2k\MediaUtils\Renderer;

use Madj2k\MediaUtils\Generator\PlaceholderGenerator;
use Madj2k\MediaUtils\Service\MetadataService;
use Madj2k\MediaUtils\Service\ResponsiveImageService;
use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Renders img tags for responsive images.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final readonly class ImageTagRenderer
{


    /**
     * @param \Madj2k\MediaUtils\Service\ResponsiveImageService $responsiveImageService Responsive image service
     * @param \Madj2k\MediaUtils\Generator\PlaceholderGenerator $placeholderGenerator Placeholder generator
     * @param \Madj2k\MediaUtils\Service\MetadataService $metadataService Metadata service
     */
    public function __construct(
        private ResponsiveImageService $responsiveImageService,
        private PlaceholderGenerator $placeholderGenerator,
        private MetadataService $metadataService
    ) {
    }


    /**
     * Renders a simple img tag.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $originalImage Source image
     * @param \TYPO3\CMS\Core\Resource\FileInterface $fallbackImage Processed fallback image
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $focusArea Focus area
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder|null $tag Existing tag
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions|null $options Render options
     *
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder
     */
    public function renderSimpleImageTag(
        FileInterface $originalImage,
        FileInterface $fallbackImage,
        ?Area $focusArea = null,
        ?TagBuilder $tag = null,
        ?ImageRenderOptions $options = null
    ): TagBuilder {
        $options ??= new ImageRenderOptions();
        $tag ??= GeneralUtility::makeInstance(TagBuilder::class, 'img');

        // add height if we have an image-extension that has to be excluded from responsive images
        if ($this->responsiveImageService->hasIgnoredFileExtension($originalImage, $options->ignoreFileExtensions)) {
            $options = $options->withForceHeightAttribute(true);
        }

        $this->applySrcAttribute($tag, $fallbackImage, $options);
        $this->applyDimensionAttributes($tag, $fallbackImage, $options);
        $this->applyNativeImageAttributes($tag, $options);

        $this->metadataService->addMetadata(
            $tag,
            $originalImage,
            $fallbackImage,
            $focusArea
        );

        return $tag;
    }


    /**
     * Renders an img tag with srcset.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $originalImage Source image
     * @param \TYPO3\CMS\Core\Resource\FileInterface $fallbackImage Processed fallback image
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area $cropArea Crop area
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $focusArea Focus area
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder|null $tag Existing tag
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions|null $options Render options
     *
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder
     */
    public function renderImageTagWithSrcset(
        FileInterface $originalImage,
        FileInterface $fallbackImage,
        Area $cropArea,
        ?Area $focusArea = null,
        ?TagBuilder $tag = null,
        ?ImageRenderOptions $options = null
    ): TagBuilder {
        $options ??= new ImageRenderOptions();

        $tag = $this->renderSimpleImageTag(
            $originalImage,
            $fallbackImage,
            $focusArea,
            $tag,
            $options
        );

        if (
            ($this->responsiveImageService->hasIgnoredFileExtension($originalImage, $options->ignoreFileExtensions))
            || (!$options->hasSrcset())
        ){
            return $tag;
        }

        $srcsetImages = $this->responsiveImageService->generateSrcsetImages(
            $originalImage,
            (int)($fallbackImage->getProperty('width') ?? 0),
            $options->srcset,
            $cropArea,
            $options
        );

        $this->applySrcSetAttribute($tag, $srcsetImages, $options);
        $this->applySizesAttribute($tag, $fallbackImage, $options);

        return $tag;
    }


    /**
     * Applies JS lazy loading.
     *
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder $tag Tag builder
     * @param \TYPO3\CMS\Core\Resource\FileInterface $fallbackImage Processed fallback image
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options Render options
     * @return void
     */
    private function applySrcAttribute(
        TagBuilder $tag,
        FileInterface $fallbackImage,
        ImageRenderOptions $options
    ): void {

        $imageUri = $this->responsiveImageService->getImageUri(
            $fallbackImage,
            $options->absoluteUri
        );

        $useJsLazyload = $options->lazyload && $options->loading !== 'lazy';
        if ($useJsLazyload) {
            $tag->addAttribute('data-src', $imageUri);

            if ($options->placeholderSize > 0) {
                $tag->addAttribute(
                    'src',
                    $this->placeholderGenerator->generatePlaceholder(
                        $fallbackImage,
                        $options
                    )
                );
            }
        } else {
            $tag->addAttribute('src', $imageUri);
        }
    }


    /**
     * Applies JS lazy loading for srcset attribute.
     *
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder $tag Tag builder
     * @param string[] $srcsetImages
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options Render options
     * @return void
     */
    private function applySrcSetAttribute(
        TagBuilder $tag,
        array $srcsetImages,
        ImageRenderOptions $options,
    ): void {

        $useJsLazyload = $options->lazyload && $options->loading !== 'lazy';
        $attributeName = $useJsLazyload ? 'data-srcset' : 'srcset';

        $tag->addAttribute(
            $attributeName,
            $this->responsiveImageService->generateSrcsetAttribute($srcsetImages)
        );

    }

    /**
     * Applies JS lazy loading for sizes attribute.
     *
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder $tag Tag builder
     * @param \TYPO3\CMS\Core\Resource\FileInterface $fallbackImage Processed fallback image
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options Render options
     * @return void
     */
    private function applySizesAttribute(
        TagBuilder $tag,
        FileInterface $fallbackImage,
        ImageRenderOptions $options,
    ): void {

        $useJsLazyload = $options->lazyload && $options->loading !== 'lazy';
        $attributeName = $useJsLazyload ? 'data-sizes' : 'sizes';

        $tag->addAttribute(
            $attributeName,
            sprintf(
                $options->sizes,
                (int)$fallbackImage->getProperty('width')
            )
        );

    }


    /**
     * Adds width and optional height attributes.
     *
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder $tag Tag builder
     * @param \TYPO3\CMS\Core\Resource\FileInterface $fallbackImage Processed fallback image
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options Render options
     *
     * @return void
     */
    private function applyDimensionAttributes(
        TagBuilder $tag,
        FileInterface $fallbackImage,
        ImageRenderOptions $options
    ): void {
        $tag->addAttribute(
            'width',
            (string)$fallbackImage->getProperty('width')
        );

        if ($options->forceHeightAttribute || $options->renderHeightAttribute) {
           $tag->addAttribute(
                'height',
                (string)$fallbackImage->getProperty('height')
            );
        }
    }

    /**
     * Applies supported native image attributes to the current tag.
     *
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder $tag Tag builder
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options Render options
     *
     * @return void
     */
    private function applyNativeImageAttributes(TagBuilder $tag, ImageRenderOptions $options): void
    {
        $loading = $options->loading;
        if (in_array($loading, ['lazy', 'eager', 'auto'], true)) {
            $tag->addAttribute('loading', $loading);
        }

        $decoding = $options->decoding;
        if (in_array($decoding, ['sync', 'async', 'auto'], true)) {
            $tag->addAttribute('decoding', $decoding);
        }
    }
}
