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

use Madj2k\MediaUtils\Processing\ImageProcessorInterface;
use Madj2k\MediaUtils\ValueObject\RenderedSrcsetCandidate;
use Madj2k\MediaUtils\ValueObject\SrcsetCandidate;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generates processed srcset candidates for responsive images.
 *
 * Handles:
 * - normalization of srcset descriptors
 * - prevention of unnecessary upscaling
 * - duplicate removal for normalized width descriptors
 * - TYPO3 batch processing when available
 * - fallback to single processing on older TYPO3 versions
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final readonly class SrcsetGenerator
{
    public function __construct(
        private ImageProcessorInterface $imageProcessor
    ) {
    }

    /**
     * Generates processed srcset images indexed by descriptor.
     *
     * Example output:
     *
     * [
     *   '400w' => '/fileadmin/_processed_/image_400.jpg',
     *   '800w' => '/fileadmin/_processed_/image_800.jpg',
     * ]
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param int $referenceWidth Width used as reference for x-descriptors
     * @param string $srcset Srcset descriptors
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $cropArea Crop area
     * @param bool $absoluteUri Whether absolute URIs should be generated
     * @param string|null $fileExtension Target file extension
     *
     * @return array<string, string>
     */
    public function generateSrcsetImages(
        FileInterface $image,
        int $referenceWidth,
        string $srcset,
        ?Area $cropArea = null,
        bool $absoluteUri = false,
        ?string $fileExtension = null
    ): array {
        $renderedCandidates = $this->generateRenderedCandidates(
            $image,
            $referenceWidth,
            $srcset,
            $cropArea,
            $absoluteUri,
            $fileExtension
        );

        $images = [];
        foreach ($renderedCandidates as $candidate) {
            $images[$candidate->getDescriptor()] = $candidate->getUri();
        }

        ksort($images, SORT_NATURAL);

        return $images;
    }

    /**
     * Generates a HTML-ready srcset attribute string.
     *
     * @param array<string, string> $srcsetImages Srcset images indexed by descriptor
     *
     * @return string
     */
    public function generateSrcsetAttribute(array $srcsetImages): string
    {
        ksort($srcsetImages, SORT_NATURAL);

        $parts = [];
        foreach ($srcsetImages as $descriptor => $uri) {
            $parts[] = $this->sanitizeSrcsetUrl($uri) . ' ' . $descriptor;
        }

        return implode(', ', $parts);
    }

    /**
     * Generates rendered srcset candidates.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param int $referenceWidth Width used as reference for x-descriptors
     * @param string $srcset Srcset descriptors
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $cropArea Crop area
     * @param bool $absoluteUri Whether absolute URIs should be generated
     * @param string|null $fileExtension Target file extension
     *
     * @return list<RenderedSrcsetCandidate>
     */
    public function generateRenderedCandidates(
        FileInterface $image,
        int $referenceWidth,
        string $srcset,
        ?Area $cropArea = null,
        bool $absoluteUri = false,
        ?string $fileExtension = null
    ): array {
        $cropArea ??= Area::createEmpty();

        $candidates = $this->normalizeCandidates(
            $image,
            $referenceWidth,
            $srcset
        );

        if ($candidates === []) {
            return [];
        }

        if ($this->imageProcessor->supportsBatchProcessing()) {
            return $this->renderCandidatesBatch(
                $image,
                $candidates,
                $cropArea,
                $absoluteUri,
                $fileExtension
            );
        }

        return $this->renderCandidatesLegacy(
            $image,
            $candidates,
            $cropArea,
            $absoluteUri,
            $fileExtension
        );
    }

    /**
     * Normalizes raw srcset descriptors into immutable candidate objects.
     *
     * Supported input examples:
     * - [200, 400]
     * - ['200w', '400w']
     * - ['1x', '2x']
     * - '200, 400'
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param int $referenceWidth Width used as reference for x-descriptors
     * @param array<int|string, int|string>|string $srcset Raw srcset input
     *
     * @return list<\Madj2k\MediaUtils\ValueObject\SrcsetCandidate>
     */
    private function normalizeCandidates(
        FileInterface $image,
        int $referenceWidth,
        array|string $srcset
    ): array {

        if (!is_array($srcset)) {
            $srcset = GeneralUtility::trimExplode(',', $srcset, true);
        }

        $originalWidth = (int)($image->getProperty('width') ?? 0);
        $normalizedCandidates = [];
        $seenWidthDescriptors = [];

        foreach ($srcset as $value) {
            $rawDescriptor = trim((string)$value);
            if ($rawDescriptor === '') {
                continue;
            }

            $mode = substr($rawDescriptor, -1);

            switch ($mode) {
                case 'x':
                    $multiplier = (float)substr($rawDescriptor, 0, -1);
                    if ($multiplier <= 0.0) {
                        continue 2;
                    }

                    $targetWidth = (int)round($referenceWidth * $multiplier);
                    $descriptor = $rawDescriptor;
                    break;

                case 'w':
                    $targetWidth = (int)substr($rawDescriptor, 0, -1);
                    if ($targetWidth <= 0) {
                        continue 2;
                    }

                    $targetWidth = $this->limitWidthToOriginal(
                        $targetWidth,
                        $originalWidth
                    );
                    $descriptor = $targetWidth . 'w';

                    if (isset($seenWidthDescriptors[$descriptor])) {
                        continue 2;
                    }
                    $seenWidthDescriptors[$descriptor] = true;
                    break;

                default:
                    $targetWidth = (int)$rawDescriptor;
                    if ($targetWidth <= 0) {
                        continue 2;
                    }

                    $targetWidth = $this->limitWidthToOriginal(
                        $targetWidth,
                        $originalWidth
                    );
                    $descriptor = $targetWidth . 'w';
                    $mode = 'w';

                    if (isset($seenWidthDescriptors[$descriptor])) {
                        continue 2;
                    }
                    $seenWidthDescriptors[$descriptor] = true;
            }

            $normalizedCandidates[] = new SrcsetCandidate(
                $descriptor,
                $mode,
                $targetWidth
            );
        }

        return $normalizedCandidates;
    }

    /**
     * Renders candidates one by one.
     *
     * This path is used on TYPO3 versions without native batch processing.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param list<\Madj2k\MediaUtils\ValueObject\SrcsetCandidate> $candidates Normalized candidates
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area $cropArea Crop area
     * @param bool $absoluteUri Whether absolute URIs should be generated
     * @param string|null $fileExtension Target file extension
     *
     * @return list<\Madj2k\MediaUtils\ValueObject\RenderedSrcsetCandidate>
     */
    private function renderCandidatesLegacy(
        FileInterface $image,
        array $candidates,
        Area $cropArea,
        bool $absoluteUri,
        ?string $fileExtension
    ): array {
        $renderedCandidates = [];

        foreach ($candidates as $candidate) {
            $processingInstructions = $this->buildProcessingInstructions(
                $image,
                $candidate->getTargetWidth(),
                $cropArea,
                $fileExtension
            );

            $processedImage = $this->imageProcessor->applyProcessingInstructions(
                $image,
                $processingInstructions
            );

            $processedWidth = (int)($processedImage->getProperty('width') ?? 0);
            $descriptor = $this->resolveRenderedDescriptor($candidate, $processedWidth);

            $renderedCandidates[] = new RenderedSrcsetCandidate(
                $descriptor,
                $this->imageProcessor->getImageUri($processedImage, $absoluteUri),
                $processedWidth
            );
        }

        return $this->deduplicateRenderedCandidates($renderedCandidates);
    }

    /**
     * Renders candidates using TYPO3 batch processing.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param list<\Madj2k\MediaUtils\ValueObject\SrcsetCandidate> $candidates Normalized candidates
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area $cropArea Crop area
     * @param bool $absoluteUri Whether absolute URIs should be generated
     * @param string|null $fileExtension Target file extension
     *
     * @return list<\Madj2k\MediaUtils\ValueObject\RenderedSrcsetCandidate>
     */
    private function renderCandidatesBatch(
        FileInterface $image,
        array $candidates,
        Area $cropArea,
        bool $absoluteUri,
        ?string $fileExtension
    ): array {
        $processingInstructionsBatch = [];
        $candidateMap = [];

        foreach ($candidates as $index => $candidate) {
            $processingInstructionsBatch[$index] = $this->buildProcessingInstructions(
                $image,
                $candidate->getTargetWidth(),
                $cropArea,
                $fileExtension
            );
            $candidateMap[$index] = $candidate;
        }

        $processedImages = $this->imageProcessor->applyProcessingInstructionsBatch(
            $image,
            $processingInstructionsBatch
        );

        $renderedCandidates = [];
        foreach ($processedImages as $index => $processedImage) {
            if (!isset($candidateMap[$index])) {
                continue;
            }

            $candidate = $candidateMap[$index];
            $processedWidth = (int)($processedImage->getProperty('width') ?? 0);
            $descriptor = $this->resolveRenderedDescriptor($candidate, $processedWidth);

            $renderedCandidates[] = new RenderedSrcsetCandidate(
                $descriptor,
                $this->imageProcessor->getImageUri($processedImage, $absoluteUri),
                $processedWidth
            );
        }

        return $this->deduplicateRenderedCandidates($renderedCandidates);
    }

    /**
     * Builds TYPO3 processing instructions for one target width.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param int $targetWidth Requested width
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area $cropArea Crop area
     * @param string|null $fileExtension Target file extension
     *
     * @return array<string, mixed>
     */
    private function buildProcessingInstructions(
        FileInterface $image,
        int $targetWidth,
        Area $cropArea,
        ?string $fileExtension
    ): array {
        $processingInstructions = [
            'width' => $targetWidth,
            'crop' => $cropArea->isEmpty()
                ? null
                : $cropArea->makeAbsoluteBasedOnFile($image),
        ];

        if (!empty($fileExtension)) {
            $processingInstructions['fileExtension'] = $fileExtension;
        }

        return $processingInstructions;
    }

    /**
     * Prevents generating width candidates larger than the original image.
     *
     * This avoids unnecessary processing work for image sizes that can never
     * be returned as distinct files.
     *
     * @param int $candidateWidth Requested candidate width
     * @param int $originalWidth Original image width
     *
     * @return int
     */
    private function limitWidthToOriginal(
        int $candidateWidth,
        int $originalWidth
    ): int {
        if ($originalWidth > 0 && $candidateWidth > $originalWidth) {
            return $originalWidth;
        }

        return $candidateWidth;
    }

    /**
     * Resolves the final descriptor after processing.
     *
     * Width descriptors may change when TYPO3 returns an image smaller than
     * requested, for example when upscaling is disabled.
     *
     * @param \Madj2k\MediaUtils\ValueObject\SrcsetCandidate $candidate Source candidate
     * @param int $processedWidth Actual processed width
     *
     * @return string
     */
    private function resolveRenderedDescriptor(
        SrcsetCandidate $candidate,
        int $processedWidth
    ): string {
        if (
            $candidate->isWidthDescriptor()
            && $processedWidth > 0
            && $processedWidth !== $candidate->getTargetWidth()
        ) {
            return $processedWidth . 'w';
        }

        return $candidate->getDescriptor();
    }

    /**
     * Removes duplicate rendered candidates by final descriptor.
     *
     * This can happen when multiple requested widths collapse to the same
     * processed width.
     *
     * @param list<\Madj2k\MediaUtils\ValueObject\RenderedSrcsetCandidate> $renderedCandidates Rendered candidates
     *
     * @return list<\Madj2k\MediaUtils\ValueObject\RenderedSrcsetCandidate>
     */
    private function deduplicateRenderedCandidates(array $renderedCandidates): array
    {
        $uniqueCandidates = [];

        foreach ($renderedCandidates as $candidate) {
            $uniqueCandidates[$candidate->getDescriptor()] = $candidate;
        }

        return array_values($uniqueCandidates);
    }

    /**
     * Sanitizes one URL for use in the srcset attribute.
     *
     * @param string $url URL to sanitize
     *
     * @return string
     */
    private function sanitizeSrcsetUrl(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
