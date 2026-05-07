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

namespace Madj2k\MediaUtils\Tests\Unit\Renderer;

use Madj2k\MediaUtils\Generator\PlaceholderGenerator;
use Madj2k\MediaUtils\Generator\SrcsetGenerator;
use Madj2k\MediaUtils\Processing\ImageProcessorInterface;
use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use Madj2k\MediaUtils\Renderer\ImageTagRenderer;
use Madj2k\MediaUtils\Renderer\PictureRenderer;
use Madj2k\MediaUtils\Renderer\SourceTagRenderer;
use Madj2k\MediaUtils\Service\MetadataService;
use Madj2k\MediaUtils\Service\ResponsiveImageService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class PictureTagRendererTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class PictureTagRendererTest extends UnitTestCase
{

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderPictureTagDoesNotAddHeightAttributeByDefault(): void
    {
        $subject = $this->createSubject();

        $tag = $subject->renderPictureTag(
            $this->createOriginalImage('jpg'),
            $this->createFallbackImage(),
            CropVariantCollection::create(''),
            null,
            null,
            null,
            new ImageRenderOptions(
                breakpoints: [
                    0 => [
                        'srcset' => '320, 640',
                        'sizes' => '(min-width: %1$dpx) %1$dpx, 100vw',
                    ],
                ]
            )
        );

        $result = $tag->render();

        self::assertStringStartsWith('<picture', $result);
        self::assertStringContainsString('<source', $result);
        self::assertStringContainsString('srcset="/image-320.jpg 320w, /image-640.jpg 640w"', $result);
        self::assertStringContainsString('<img src="/fallback.jpg"', $result);
        self::assertStringContainsString('width="640"', $result);
        self::assertStringNotContainsString('height="480"', $result);
        self::assertStringNotContainsString('data-srcset', $result);
    }


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderPictureTagAddsHeightAttributeWhenEnabled(): void
    {
        $subject = $this->createSubject();

        $tag = $subject->renderPictureTag(
            $this->createOriginalImage('jpg'),
            $this->createFallbackImage(),
            CropVariantCollection::create(''),
            null,
            null,
            null,
            new ImageRenderOptions(
                renderHeightAttribute: true,
                breakpoints: [
                    0 => [
                        'srcset' => '320, 640',
                    ],
                ]
            )
        );

        $result = $tag->render();

        self::assertStringStartsWith('<picture', $result);
        self::assertStringContainsString('width="640"', $result);
        self::assertStringContainsString('height="480"', $result);
    }


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderPictureTagReturnsFallbackImageWithHeightForIgnoredFileExtension(): void
    {
        $subject = $this->createSubject('/fallback.svg');

        $tag = $subject->renderPictureTag(
            $this->createOriginalImage('svg'),
            $this->createFallbackImage(),
            CropVariantCollection::create(''),
            null,
            null,
            null,
            new ImageRenderOptions(
                breakpoints: [
                    0 => [
                        'srcset' => '320, 640',
                    ],
                ]
            )
        );

        $result = $tag->render();

        self::assertStringStartsWith('<img', $result);
        self::assertStringContainsString('src="/fallback.svg"', $result);
        self::assertStringContainsString('width="640"', $result);
        self::assertStringContainsString('height="480"', $result);
        self::assertStringNotContainsString('<picture', $result);
        self::assertStringNotContainsString('<source', $result);
    }


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderPictureTagUsesNativeLazyload(): void
    {
        $subject = $this->createSubject();

        $tag = $subject->renderPictureTag(
            $this->createOriginalImage('jpg'),
            $this->createFallbackImage(),
            CropVariantCollection::create(''),
            null,
            null,
            null,
            new ImageRenderOptions(
                loading: 'lazy',
                breakpoints: [
                    0 => [
                        'srcset' => '320, 640',
                    ],
                ]
            )
        );

        $result = $tag->render();

        self::assertStringContainsString('loading="lazy"', $result);
        self::assertStringNotContainsString('data-src', $result);
        self::assertStringNotContainsString('lazyload', $result);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderPictureTagUsesJsLazyload(): void
    {
        $subject = $this->createSubject();

        $tag = $subject->renderPictureTag(
            $this->createOriginalImage('jpg'),
            $this->createFallbackImage(),
            CropVariantCollection::create(''),
            null,
            null,
            null,
            new ImageRenderOptions(
                lazyload: true,
                breakpoints: [
                    0 => [
                        'srcset' => '320, 640',
                    ],
                ]
            )
        );

        // self::assertSame('/fallback.jpg', $tag->getAttribute('data-src'));
        self::assertFalse($tag->hasAttribute('loading'));
        self::assertFalse($tag->hasAttribute('src'));
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderPictureTagPrefersNativeLazyloadOverJsLazyload(): void
    {
        $subject = $this->createSubject();

        $tag = $subject->renderPictureTag(
            $this->createOriginalImage('jpg'),
            $this->createFallbackImage(),
            CropVariantCollection::create(''),
            null,
            null,
            null,
            new ImageRenderOptions(
                lazyload: true,
                loading: 'lazy',
                breakpoints: [
                    0 => [
                        'srcset' => '320, 640',
                    ],
                ]
            )
        );

        $result = $tag->render();

        self::assertStringContainsString('loading="lazy"', $result);
        self::assertStringNotContainsString('data-src', $result);
        self::assertStringNotContainsString('lazyload', $result);
    }

    /**
     * @param string $extension File extension
     *
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function createOriginalImage(string $extension): FileInterface
    {
        $originalImage = $this->createStub(FileInterface::class);
        $originalImage
            ->method('getProperty')
            ->willReturnMap([
                ['alternative', 'Alternative'],
                ['title', 'Title'],
                ['extension', $extension],
                ['width', 1280],
            ]);

        return $originalImage;
    }


    /**
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function createFallbackImage(): FileInterface
    {
        $fallbackImage = $this->createStub(FileInterface::class);
        $fallbackImage
            ->method('getProperty')
            ->willReturnMap([
                ['width', 640],
                ['height', 480],
            ]);

        return $fallbackImage;
    }


    /**
     * @param string $fallbackUri Fallback URI
     *
     * @return \Madj2k\MediaUtils\Renderer\PictureRenderer
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function createSubject(string $fallbackUri = '/fallback.jpg'): PictureRenderer
    {
        $processedImage320 = $this->createStub(FileInterface::class);
        $processedImage320
            ->method('getProperty')
            ->willReturnMap([
                ['width', 320],
            ]);

        $processedImage640 = $this->createStub(FileInterface::class);
        $processedImage640
            ->method('getProperty')
            ->willReturnMap([
                ['width', 640],
            ]);

        $imageService = $this->createStub(ImageService::class);
        $imageService
            ->method('getImageUri')
            ->willReturnCallback(
                static function (FileInterface $image) use ($processedImage320, $processedImage640, $fallbackUri): string {
                    if ($image === $processedImage320) {
                        return '/image-320.jpg';
                    }

                    if ($image === $processedImage640) {
                        return '/image-640.jpg';
                    }

                    return $fallbackUri;
                }
            );

        $imageProcessor = $this->createStub(ImageProcessorInterface::class);
        $imageProcessor
            ->method('supportsBatchProcessing')
            ->willReturn(false);
        $imageProcessor
            ->method('applyProcessingInstructions')
            ->willReturnCallback(
                static function (FileInterface $image, array $processingInstructions) use ($processedImage320, $processedImage640): FileInterface {
                    if (($processingInstructions['width'] ?? null) === 320) {
                        return $processedImage320;
                    }

                    return $processedImage640;
                }
            );
        $imageProcessor
            ->method('getImageUri')
            ->willReturnCallback(
                static function (FileInterface $image) use ($processedImage320, $processedImage640): string {
                    if ($image === $processedImage320) {
                        return '/image-320.jpg';
                    }

                    if ($image === $processedImage640) {
                        return '/image-640.jpg';
                    }

                    return '';
                }
            );

        $srcsetGenerator = new SrcsetGenerator($imageProcessor);
        $responsiveImageService = new ResponsiveImageService($imageService, $srcsetGenerator);

        return new PictureRenderer(
            $responsiveImageService,
            new SourceTagRenderer($srcsetGenerator),
            new ImageTagRenderer(
                $responsiveImageService,
                new PlaceholderGenerator($imageService),
                new MetadataService()
            )
        );
    }
}
