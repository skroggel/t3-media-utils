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

/**
 * Represents one rendered srcset candidate after image processing.
 *
 * Contains:
 * - final descriptor
 * - generated URI
 * - actual processed width
 *
 * This value object is immutable.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final readonly class RenderedSrcsetCandidate
{
    /**
     * @param string $descriptor Final descriptor
     * @param string $uri Generated image URI
     * @param int $processedWidth Actual processed image width
     */
    public function __construct(
        private string $descriptor,
        private string $uri,
        private int $processedWidth
    ) {
    }

    /**
     * Returns final descriptor.
     *
     * Example:
     * - 400w
     * - 2x
     *
     * @return string
     */
    public function getDescriptor(): string
    {
        return $this->descriptor;
    }

    /**
     * Returns generated image URI.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Returns actual processed image width.
     *
     * May differ from requested width when upscaling is disabled.
     *
     * @return int
     */
    public function getProcessedWidth(): int
    {
        return $this->processedWidth;
    }
}
