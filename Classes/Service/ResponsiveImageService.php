<?php

declare(strict_types=1);

namespace Madj2k\MediaUtils\Service;

use Madj2k\MediaUtils\Generator\SrcsetGenerator;
use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Extbase\Service\ImageService;

/**
 * Provides responsive image rendering operations.
 *
 * Handles:
 * - fallback image generation
 * - srcset generation
 * - SVG-safe rendering decisions
 * - file extension filtering
 */
final readonly class ResponsiveImageService
{
    public function __construct(
        private ImageService $imageService,
        private SrcsetGenerator $srcsetGenerator
    ) {
    }

    /**
     * Creates a processed fallback image.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area $cropArea Crop area
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options Render options
     *
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     */
    public function createFallbackImage(
        FileInterface $image,
        Area $cropArea,
        ImageRenderOptions $options
    ): FileInterface {
        $processingInstructions = [
            'width' => $options->width,
            'height' => $options->height,
            'crop' => $cropArea->isEmpty()
                ? null
                : $cropArea->makeAbsoluteBasedOnFile($image),
        ];

        if ($options->fileExtension !== null && $options->fileExtension !== '') {
            $processingInstructions['fileExtension'] = $options->fileExtension;
        }
        if ($options->minWidth !== null) {
            $processingInstructions['minWidth'] = $options->minWidth;
        }
        if ($options->minHeight !== null) {
            $processingInstructions['minHeight'] = $options->minHeight;
        }
        if ($options->maxWidth !== null) {
            $processingInstructions['maxWidth'] = $options->maxWidth;
        }
        if ($options->maxHeight !== null) {
            $processingInstructions['maxHeight'] = $options->maxHeight;
        }

        return $this->imageService->applyProcessingInstructions($image, $processingInstructions);
    }

    /**
     * Generates srcset images for one fallback width.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param int $referenceWidth Width used as reference for x-descriptors
     * @param array<int|string, int|string>|string $srcset Srcset definition
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $cropArea Crop area
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options Render options
     *
     * @return array<string, string>
     */
    public function generateSrcsetImages(
        FileInterface $image,
        int $referenceWidth,
        array|string $srcset,
        ?Area $cropArea,
        ImageRenderOptions $options
    ): array {
        return $this->srcsetGenerator->generateSrcsetImages(
            $image,
            $referenceWidth,
            $srcset,
            $cropArea,
            $options->absoluteUri,
            $options->fileExtension
        );
    }

    /**
     * Generates a HTML-ready srcset attribute.
     *
     * @param array<string, string> $srcsetImages Srcset images indexed by descriptor
     *
     * @return string
     */
    public function generateSrcsetAttribute(array $srcsetImages): string
    {
        return $this->srcsetGenerator->generateSrcsetAttribute($srcsetImages);
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

    /**
     * Returns whether the given image should bypass responsive processing.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param array<int, string>|string $ignoredExtensions Ignored file extensions
     *
     * @return bool
     */
    public function hasIgnoredFileExtension(
        FileInterface $image,
        array|string $ignoredExtensions = 'svg, gif'
    ): bool {
        if (!is_array($ignoredExtensions)) {
            $ignoredExtensions = array_map(
                'trim',
                explode(',', strtolower($ignoredExtensions))
            );
        } else {
            $ignoredExtensions = array_map('strtolower', $ignoredExtensions);
        }

        return in_array(
            strtolower((string)$image->getProperty('extension')),
            $ignoredExtensions,
            true
        );
    }

    /**
     * Returns whether the given image is an SVG image.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     *
     * @return bool
     */
    public function isSvg(FileInterface $image): bool
    {
        return strtolower((string)$image->getProperty('extension')) === 'svg';
    }
}
