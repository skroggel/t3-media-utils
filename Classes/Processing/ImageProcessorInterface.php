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

namespace Madj2k\MediaUtils\Processing;

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Provides image processing operations required for responsive image rendering.
 *
 * This abstraction isolates TYPO3 image processing APIs from the rendering
 * services and improves testability.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface ImageProcessorInterface
{
     /**
     * Applies processing instructions to one image.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param array<string, mixed> $processingInstructions TYPO3 processing instructions
     *
     * @return \TYPO3\CMS\Core\Resource\FileInterface Processed image
     */
    public function applyProcessingInstructions(
        FileInterface $image,
        array $processingInstructions
    ): FileInterface;

    /**
     * Applies multiple processing instruction sets to one source image.
     *
     * Implementations may either use TYPO3 batch processing or fall back to
     * repeated single-image processing.
     *
     * @param FileInterface $image Source image
     * @param array<array-key, array<string, mixed>> $processingInstructionsBatch
     *
     * @return array<array-key, FileInterface>
     */
    public function applyProcessingInstructionsBatch(
        FileInterface $image,
        array $processingInstructionsBatch
    ): array;

    /**
     * Returns whether batch processing is supported by the implementation.
     *
     * @return bool
     */
    public function supportsBatchProcessing(): bool;

    /**
     * Returns the public URI of an image.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Image or processed image
     * @param bool $absoluteUri Whether an absolute URI should be generated
     *
     * @return string
     */
    public function getImageUri(FileInterface $image, bool $absoluteUri = false): string;
}
