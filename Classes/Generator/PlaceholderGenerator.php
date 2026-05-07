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

namespace Madj2k\MediaUtils\Generator;

use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;

/**
 * Generates placeholder images for lazyloaded responsive images.
 *
 * Supports:
 * - processed placeholder images
 * - inline base64 placeholders
 * - absolute URIs
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final readonly class PlaceholderGenerator
{
    public function __construct(
        private ImageService $imageService
    ) {
    }

    /**
     * Generates a placeholder image URI.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options Render options
     * @return string
     */
    public function generatePlaceholder(
        FileInterface $image,
        ImageRenderOptions $options
    ): string {

        $processingInstructions = [
            'width' => $options->placeholderSize,
        ];

        if (! empty($options->fileExtension)) {
            $processingInstructions['fileExtension'] = $options->fileExtension;
        }

        $placeholderImage = $this->imageService->applyProcessingInstructions(
            $image,
            $processingInstructions
        );

        // No inline placeholder if the image is not processed at all
        if (
            (!$options->placeholderInline)
            || ($placeholderImage->usesOriginalFile())
        ){
            return $this->imageService->getImageUri(
                $placeholderImage,
                $options->absoluteUri
            );
        }

        return $this->generateInlinePlaceholder($placeholderImage);
    }

    /**
     * Generates inline base64 placeholder data.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Processed placeholder image
     *
     * @return string
     */
    private function generateInlinePlaceholder(FileInterface $image): string
    {
        $publicUrl = $image->getPublicUrl();

        if (!$publicUrl) {
            return '';
        }

        $absolutePath = GeneralUtility::getFileAbsFileName($publicUrl);

        if (!$absolutePath || !is_file($absolutePath)) {
            return '';
        }

        $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';
        $content = file_get_contents($absolutePath);

        if ($content === false) {
            return '';
        }

        return sprintf(
            'data:%s;base64,%s',
            $mimeType,
            base64_encode($content)
        );
    }
}
