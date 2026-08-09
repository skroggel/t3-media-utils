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

namespace Madj2k\MediaUtils\ValueObject;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class ImageRenderOptions
 *
 * Value object for responsive image rendering options.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final readonly class ImageRenderOptions
{


    /**
     * @param bool $absoluteUri Whether absolute URIs should be generated
     * @param bool $lazyload Whether js lazy loading should be enabled
     * @param ?string $loading Whether native loading type
     * @param string|null $decoding
     * @param string|null $fileExtension Target file extension
     * @param string|null $ignoreFileExtensions Ignored file extensions
     * @param bool $renderHeightAttribute Whether height attributes should be rendered in responsive contexts
     * @param bool $forceHeightAttribute Whether height attribute should always be rendered
     * @param string|null $width Target width
     * @param string|null $height Target height
     * @param int|null $minWidth Minimum width
     * @param int|null $minHeight Minimum height
     * @param int|null $maxWidth Maximum width
     * @param int|null $maxHeight Maximum height
     * @param string $cropVariant Crop variant identifier
     * @param string|null $srcset Srcset definition
     * @param string $sizes Sizes attribute pattern
     * @param array<int|string, array<string, mixed>> $breakpoints Breakpoint configuration
     * @param int|null $placeholderSize
     * @param bool $placeholderInline
     */
    public function __construct(
        public bool $absoluteUri = false,
        public bool $lazyload = false,
        public ?string $loading = null,
        public ?string $decoding = '',
        public ?string $fileExtension = null,
        public string $ignoreFileExtensions = 'svg, gif',
        public bool $renderHeightAttribute = false,
        public bool $forceHeightAttribute = false,
        public ?string $width = null,
        public ?string $height = null,
        public ?int $minWidth = null,
        public ?int $minHeight = null,
        public ?int $maxWidth = null,
        public ?int $maxHeight = null,
        public string $cropVariant = 'default',
        public ?string $srcset = null,
        public ?string $sizes = null,
        public array $breakpoints = [],
        public ?int $placeholderSize = null,
        public bool $placeholderInline = false,
    ) {
    }


    /**
     * Returns a copy with forced height rendering.
     *
     * @param bool $forceHeightAttribute Whether height attribute should always be rendered
     *
     * @return \Madj2k\MediaUtils\ValueObject\ImageRenderOptions
     */
    public function withForceHeightAttribute(bool $forceHeightAttribute): self
    {
        return new self(
            absoluteUri: $this->absoluteUri,
            lazyload: $this->lazyload,
            loading: $this->loading,
            decoding: $this->decoding,
            fileExtension: $this->fileExtension,
            ignoreFileExtensions: $this->ignoreFileExtensions,
            renderHeightAttribute: $this->renderHeightAttribute,
            forceHeightAttribute: $forceHeightAttribute,
            width: $this->width,
            height: $this->height,
            minWidth: $this->minWidth,
            minHeight: $this->minHeight,
            maxWidth: $this->maxWidth,
            maxHeight: $this->maxHeight,
            cropVariant: $this->cropVariant,
            srcset: $this->srcset,
            sizes: $this->sizes,
            breakpoints: $this->breakpoints,
            placeholderSize: $this->placeholderSize,
            placeholderInline: $this->placeholderInline,
        );
    }


    /**
     * Returns whether a srcset configuration is available.
     *
     * @return bool
     */
    public function hasSrcset(): bool
    {
        return (
            (!empty($this->srcset))
            && (!empty($this->sizes))
        );
    }


    /**
     * Returns whether breakpoint configuration is available.
     *
     * @return bool
     */
    public function hasBreakpoints(): bool
    {
        if (
            (isset($this->breakpoints))
            && (is_array($this->breakpoints))
        ){
            $cnt = 0;
            foreach ($this->breakpoints as $breakpoint) {

                if (
                    (! empty($breakpoint['media']))
                    && (! empty($breakpoint['srcset']))
                    && (! empty($breakpoint['sizes']))
                )  {
                    $cnt++;
                }
            }

            if (
                ($cnt == count($this->breakpoints))
                && ($cnt > 0)
            ){
                return true;
            }
        }

        return false;
    }
}
