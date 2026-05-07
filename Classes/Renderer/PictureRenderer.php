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

use Madj2k\MediaUtils\Service\ResponsiveImageService;
use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Renders picture markup for responsive images with breakpoints.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final readonly class PictureRenderer
{
    /**
     * Default breakpoint configuration.
     *
     * @var array<string, mixed>
     */
    private const BREAKPOINT_PROTOTYPE = [
        'cropVariant' => 'default',
        'media' => '',
        'sizes' => '',
        'srcset' => [],
    ];


    /**
     * @param \Madj2k\MediaUtils\Service\ResponsiveImageService $responsiveImageService Responsive image service
     * @param \Madj2k\MediaUtils\Renderer\SourceTagRenderer $sourceTagRenderer Source tag renderer
     * @param \Madj2k\MediaUtils\Renderer\ImageTagRenderer $imageTagRenderer Image tag renderer
     */
    public function __construct(
        private ResponsiveImageService $responsiveImageService,
        private SourceTagRenderer $sourceTagRenderer,
        private ImageTagRenderer $imageTagRenderer
    ) {
    }


    /**
     * Renders a picture tag for the configured breakpoints.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $originalImage Source image
     * @param \TYPO3\CMS\Core\Resource\FileInterface $fallbackImage Fallback image
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection $cropVariantCollection Crop variants
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $focusArea Focus area
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder|null $tag Picture tag
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder|null $fallbackTag Fallback img tag
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions|null $options Render options
     *
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder
     */
    public function renderPictureTag(
        FileInterface $originalImage,
        FileInterface $fallbackImage,
        CropVariantCollection $cropVariantCollection,
        ?Area $focusArea = null,
        ?TagBuilder $tag = null,
        ?TagBuilder $fallbackTag = null,
        ?ImageRenderOptions $options = null
    ): TagBuilder {
        $options ??= new ImageRenderOptions();
        $tag ??= GeneralUtility::makeInstance(TagBuilder::class, 'picture');
        $fallbackTag ??= GeneralUtility::makeInstance(TagBuilder::class, 'img');

        if ($this->responsiveImageService->hasIgnoredFileExtension($originalImage, $options->ignoreFileExtensions)) {
            return $this->imageTagRenderer->renderSimpleImageTag(
                $originalImage,
                $fallbackImage,
                $focusArea,
                $fallbackTag,
                $options->withForceHeightAttribute(true)
            );
        }

        $normalizedBreakpoints = $this->normalizeBreakpoints($options->breakpoints);
        if ($normalizedBreakpoints === []) {
            return $this->imageTagRenderer->renderSimpleImageTag(
                $originalImage,
                $fallbackImage,
                $focusArea,
                $fallbackTag,
                $options->withForceHeightAttribute(true)
            );
        }

        $fallbackTag = $this->imageTagRenderer->renderSimpleImageTag(
            $originalImage,
            $fallbackImage,
            $focusArea,
            $fallbackTag,
            $options
        );

        $referenceWidth = (int)($fallbackImage->getProperty('width') ?? 0);
        $sourceTags = [];

        foreach ($normalizedBreakpoints as $breakpoint) {
            $cropArea = $cropVariantCollection->getCropArea((string)$breakpoint['cropVariant']);

            $sourceTags[] = $this->sourceTagRenderer->renderSourceTag(
                $originalImage,
                $referenceWidth,
                $breakpoint['srcset'],
                (string)$breakpoint['media'],
                (string)$breakpoint['sizes'],
                $cropArea,
                $options
            )->render();
        }

        $tag->setContent(implode('', $sourceTags) . $fallbackTag->render());

        return $tag;
    }


    /**
     * Normalizes breakpoint configuration.
     *
     * @param array<int|string, mixed> $breakpoints Raw breakpoint configuration
     *
     * @return list<array<string, mixed>>
     */
    private function normalizeBreakpoints(array $breakpoints): array
    {
        $normalizedBreakpoints = [];

        foreach ($breakpoints as $media => $configuration) {
            if (!is_array($configuration) || array_is_list($configuration)) {
                $configuration = ['srcset' => $configuration];
            }

            $breakpoint = array_replace(self::BREAKPOINT_PROTOTYPE, $configuration);

            if (is_string($media) && !is_numeric($media)) {
                $breakpoint['media'] = $breakpoint['media'] ?: sprintf('(min-width: %s)', $media);
            }

            if (empty($breakpoint['srcset'])) {
                continue;
            }

            $normalizedBreakpoints[] = $breakpoint;
        }

        return $normalizedBreakpoints;
    }
}
