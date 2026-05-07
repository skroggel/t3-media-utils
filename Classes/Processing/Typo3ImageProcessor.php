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
use TYPO3\CMS\Extbase\Service\ImageService;

/**
 * TYPO3-based image processor implementation.
 *
 * Uses TYPO3's ImageService for:
 * - single image processing
 * - batch image processing when available
 * - public image URI generation
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final readonly class Typo3ImageProcessor implements ImageProcessorInterface
{
    public function __construct(
        private ImageService $imageService
    ) {
    }

    /**
     * Applies processing instructions to one image.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param array<string, mixed> $processingInstructions TYPO3 processing instructions
     *
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     */
    public function applyProcessingInstructions(
        FileInterface $image,
        array $processingInstructions
    ): FileInterface {
        return $this->imageService->applyProcessingInstructions($image, $processingInstructions);
    }

    /**
     * Applies multiple processing instruction sets to one source image.
     *
     * Falls back to repeated single-image processing if TYPO3 does not provide
     * a native batch API.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param array<array-key, array<string, mixed>> $processingInstructionsBatch
     *
     * @return array<array-key, \TYPO3\CMS\Core\Resource\FileInterface>
     */
    public function applyProcessingInstructionsBatch(
        FileInterface $image,
        array $processingInstructionsBatch
    ): array {
        if (!$this->supportsBatchProcessing()) {
            $processedImages = [];

            foreach ($processingInstructionsBatch as $key => $processingInstructions) {
                $processedImages[$key] = $this->applyProcessingInstructions(
                    $image,
                    $processingInstructions
                );
            }

            return $processedImages;
        }

        /** @phpstan-ignore-next-line TYPO3 v14+ method is detected at runtime */
        return $this->imageService->applyProcessingInstructionsBatch(
            $image,
            $processingInstructionsBatch
        );
    }

    /**
     * Returns whether TYPO3 provides native batch image processing.
     *
     * @return bool
     */
    public function supportsBatchProcessing(): bool
    {
        return method_exists($this->imageService, 'applyProcessingInstructionsBatch');
    }

    /**
     * Returns the public URI of an image.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Image or processed image
     * @param bool $absoluteUri Whether an absolute URI should be generated
     *
     * @return string
     */
    public function getImageUri(FileInterface $image, bool $absoluteUri = false): string
    {
        return $this->imageService->getImageUri($image, $absoluteUri);
    }
}
