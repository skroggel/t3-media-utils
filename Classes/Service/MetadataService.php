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

namespace Madj2k\MediaUtils\Service;

use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Adds image metadata to img tags.
 *
 * Handles:
 * - alt attribute
 * - title attribute
 * - width attribute
 * - optional height attribute
 * - optional focus area metadata
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final readonly class MetadataService
{

    /**
     * Adds metadata attributes to an image tag.
     *
     * Existing explicitly set tag attributes are preserved.
     *
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder $tag Image tag
     * @param \TYPO3\CMS\Core\Resource\FileInterface $originalImage Original source image
     * @param \TYPO3\CMS\Core\Resource\FileInterface|null $processedImage Processed fallback image
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $focusArea Focus area
     * @param bool $renderHeightAttribute Whether height attribute should be rendered
     *
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder
     */
    public function addMetadata(
        TagBuilder $tag,
        FileInterface $originalImage,
        ?FileInterface $processedImage = null,
        ?Area $focusArea = null,
    ): TagBuilder {

        $this->addAltAttribute($tag, $originalImage);
        $this->addTitleAttribute($tag, $originalImage);
        $this->addFocusAreaAttribute($tag, $focusArea);

        return $tag;
    }


    /**
     * Adds the alt attribute if not already present.
     *
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder $tag Image tag
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     *
     * @return void
     */
    private function addAltAttribute(
        TagBuilder $tag,
        FileInterface $image
    ): void {
        if ($tag->hasAttribute('alt')) {
            return;
        }

        $alternative = trim((string)($image->getProperty('alternative') ?? ''));

        $tag->addAttribute('alt', $alternative);
    }


    /**
     * Adds the title attribute if not already present.
     *
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder $tag Image tag
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     *
     * @return void
     */
    private function addTitleAttribute(
        TagBuilder $tag,
        FileInterface $image
    ): void {
        if ($tag->hasAttribute('title')) {
            return;
        }

        $title = trim((string)($image->getProperty('title') ?? ''));

        if ($title !== '') {
            $tag->addAttribute('title', $title);
        }
    }


    /**
     * Adds focus area metadata if available and not already set.
     *
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder $tag Image tag
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $focusArea Focus area
     *
     * @return void
     */
    private function addFocusAreaAttribute(
        TagBuilder $tag,
        ?Area $focusArea
    ): void {
        if ($focusArea === null || $tag->hasAttribute('data-focus-area')) {
            return;
        }

        if ($focusArea->isEmpty()) {
            return;
        }

        $tag->addAttribute(
            'data-focus-area',
            sprintf(
                '%F,%F,%F,%F',
                $focusArea->getOffsetLeft(),
                $focusArea->getOffsetTop(),
                $focusArea->getWidth(),
                $focusArea->getHeight()
            )
        );
    }
}
