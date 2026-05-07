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
 * Represents one normalized srcset candidate.
 *
 * A srcset candidate consists of:
 * - descriptor (e.g. "400w", "2x")
 * - descriptor mode ("w" or "x")
 * - calculated target width in pixels
 *
 * This value object is immutable.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final readonly class SrcsetCandidate
{
    /**
     * @param string $descriptor Normalized descriptor, e.g. "400w" or "2x"
     * @param string $mode Descriptor mode: "w" or "x"
     * @param int $targetWidth Calculated target width in pixels
     */
    public function __construct(
        private string $descriptor,
        private string $mode,
        private int $targetWidth
    ) {
    }

    /**
     * Returns normalized descriptor.
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
     * Returns descriptor mode.
     *
     * Allowed values:
     * - w = width descriptor
     * - x = density descriptor
     *
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Returns calculated target width in pixels.
     *
     * @return int
     */
    public function getTargetWidth(): int
    {
        return $this->targetWidth;
    }

    /**
     * Returns true if this candidate uses width descriptor mode.
     *
     * Example:
     * 400w
     *
     * @return bool
     */
    public function isWidthDescriptor(): bool
    {
        return $this->mode === 'w';
    }

    /**
     * Returns true if this candidate uses density descriptor mode.
     *
     * Example:
     * 2x
     *
     * @return bool
     */
    public function isDensityDescriptor(): bool
    {
        return $this->mode === 'x';
    }
}
