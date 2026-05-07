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

use Madj2k\MediaUtils\Generator\SrcsetGenerator;
use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Renders source tags for picture elements.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final readonly class SourceTagRenderer
{

    /**
     * @param \Madj2k\MediaUtils\Generator\SrcsetGenerator $srcsetGenerator Srcset generator
     */
    public function __construct(
        private SrcsetGenerator $srcsetGenerator
    ) {
    }

    /**
     * Renders one source tag for a picture element.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $originalImage Source image
     * @param int $referenceWidth Width of the fallback image
     * @param array<int|string, int|string>|string $srcset Srcset definition
     * @param string $mediaQuery Media query
     * @param string $sizesQuery Sizes query
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $cropArea Crop area
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions|null $options Render options
     *
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder
     */
    public function renderSourceTag(
        FileInterface $originalImage,
        int $referenceWidth,
        array|string $srcset,
        string $mediaQuery = '',
        string $sizesQuery = '',
        ?Area $cropArea = null,
        ?ImageRenderOptions $options = null
    ): TagBuilder {
        $options ??= new ImageRenderOptions();
        $cropArea ??= Area::createEmpty();

        $srcsetImages = $this->srcsetGenerator->generateSrcsetImages(
            $originalImage,
            $referenceWidth,
            $srcset,
            $cropArea,
            $options->absoluteUri,
            $options->fileExtension
        );

        $srcsetMode = substr((string)(array_key_first($srcsetImages) ?? 'w'), -1);

        $sourceTag = GeneralUtility::makeInstance(TagBuilder::class, 'source');
        $sourceTag->addAttribute(
            'srcset',
            $this->srcsetGenerator->generateSrcsetAttribute($srcsetImages)
        );

        if ($mediaQuery !== '') {
            $sourceTag->addAttribute('media', $mediaQuery);
        }

        if ($srcsetMode === 'w' && $sizesQuery !== '') {
            $sourceTag->addAttribute('sizes', sprintf($sizesQuery, $referenceWidth));
        }

        return $sourceTag;
    }
}
