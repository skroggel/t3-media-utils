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
use Madj2k\MediaUtils\Service\MetadataService;
use Madj2k\MediaUtils\Service\ResponsiveImageService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ImageTagRendererTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class ImageTagRendererTest extends UnitTestCase
{


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderSimpleImageTagAddsHeightAttribute(): void
    {
        $originalImage = $this->createOriginalImage();
        $fallbackImage = $this->createFallbackImage(640, 480);
        $tag = new TagBuilder('img');

        $subject = $this->createSubject($fallbackImage, '/fallback.jpg');

        $result = $subject->renderSimpleImageTag(
            $originalImage,
            $fallbackImage,
            null,
            $tag,
            (new ImageRenderOptions())->withForceHeightAttribute(true)
        );

        self::assertSame($tag, $result);
        self::assertSame('/fallback.jpg', $tag->getAttribute('src'));
        self::assertSame('Alternative', $tag->getAttribute('alt'));
        self::assertSame('Title', $tag->getAttribute('title'));
        self::assertSame('640', $tag->getAttribute('width'));
        self::assertSame('480', $tag->getAttribute('height'));
    }

    /**
     * Tests that SVG images do not get rendered as webp
     *
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderSimpleImageReturnsFallbackImageWithHeightForIgnoredFileExtension(): void
    {
        $originalImage = $this->createOriginalImage(extension: 'svg');
        $fallbackImage = $this->createFallbackImage(640, 480);
        $tag = new TagBuilder('img');

        $subject = $this->createSubject($fallbackImage, '/file.svg');

        $tag = $subject->renderSimpleImageTag(
            $originalImage,
            $fallbackImage,
            null,
            $tag,
            new ImageRenderOptions(
                fileExtension: 'webp',
                ignoreFileExtensions: 'svg, gif',
            )
        );

        $result = $tag->render();

        self::assertStringStartsWith('<img', $result);
        self::assertStringContainsString('src="/file.svg"', $result);
        self::assertStringContainsString('width="640"', $result);
        self::assertStringContainsString('height="480"', $result);
        self::assertStringNotContainsString('.webp', $result);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderSimpleImageTagUsesNativeLazyload(): void
    {
        $originalImage = $this->createOriginalImage();
        $fallbackImage = $this->createFallbackImage(640, 480);
        $tag = new TagBuilder('img');

        $subject = $this->createSubject($fallbackImage, '/fallback.jpg');

        $result = $subject->renderSimpleImageTag(
            $originalImage,
            $fallbackImage,
            null,
            $tag,
            new ImageRenderOptions(
                loading: 'lazy',
            )
        );

        self::assertSame($tag, $result);
        self::assertSame('/fallback.jpg', $tag->getAttribute('src'));
        self::assertSame('lazy', $tag->getAttribute('loading'));
    }


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderSimpleImageTagUsesJsLazyload(): void
    {
        $originalImage = $this->createOriginalImage();
        $fallbackImage = $this->createFallbackImage(640, 480);
        $tag = new TagBuilder('img');

        $subject = $this->createSubject($fallbackImage, '/fallback.jpg');

        $result = $subject->renderSimpleImageTag(
            $originalImage,
            $fallbackImage,
            null,
            $tag,
            new ImageRenderOptions(
                lazyload: true,
            )
        );

        self::assertSame($tag, $result);
        self::assertSame('/fallback.jpg', $tag->getAttribute('data-src'));
        self::assertFalse($tag->hasAttribute('loading'));
    }


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderSimpleImageTagPrefersNativeLazyloadOverJsLazyload(): void
    {
        $originalImage = $this->createOriginalImage();
        $fallbackImage = $this->createFallbackImage(640, 480);
        $tag = new TagBuilder('img');

        $subject = $this->createSubject($fallbackImage, '/fallback.jpg');

        $result = $subject->renderSimpleImageTag(
            $originalImage,
            $fallbackImage,
            null,
            $tag,
            new ImageRenderOptions(
                lazyload: true,
                loading: 'lazy',
                placeholderSize: 32
            )
        );

        self::assertSame($tag, $result);
        self::assertSame('/fallback.jpg', $tag->getAttribute('src'));
        self::assertSame('lazy', $tag->getAttribute('loading'));
        self::assertFalse($tag->hasAttribute('data-src'));
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderImageTagWithSrcsetDoesNotAddHeightAttributeByDefault(): void
    {
        $originalImage = $this->createOriginalImage();
        $fallbackImage = $this->createFallbackImage(640, 480);
        $tag = new TagBuilder('img');

        $subject = $this->createSubject($fallbackImage, '/fallback.jpg');

        $subject->renderImageTagWithSrcset(
            $originalImage,
            $fallbackImage,
            Area::createEmpty(),
            null,
            $tag,
            new ImageRenderOptions(
                srcset: '320',
                sizes: '(min-width: %1$dpx) %1$dpx, 100vw'
            )
        );

        self::assertSame('/fallback.jpg', $tag->getAttribute('src'));
        self::assertSame('/image-320.jpg 320w', $tag->getAttribute('srcset'));
        self::assertSame('(min-width: 640px) 640px, 100vw', $tag->getAttribute('sizes'));
        self::assertSame('640', $tag->getAttribute('width'));
        self::assertFalse($tag->hasAttribute('height'));
    }


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderImageTagWithSrcsetAddsHeightAttributeWhenEnabled(): void
    {
        $originalImage = $this->createOriginalImage();
        $fallbackImage = $this->createFallbackImage(640, 480);
        $tag = new TagBuilder('img');

        $subject = $this->createSubject($fallbackImage, '/fallback.jpg');

        $subject->renderImageTagWithSrcset(
            $originalImage,
            $fallbackImage,
            Area::createEmpty(),
            null,
            $tag,
            new ImageRenderOptions(
                renderHeightAttribute: true,
                srcset: '320',
                sizes: '(min-width: %1$dpx) %1$dpx, 100vw'
            )
        );

        self::assertSame('640', $tag->getAttribute('width'));
        self::assertSame('480', $tag->getAttribute('height'));
    }



    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderImageTagWithSrcsetReturnsFallbackImageWithHeightForIgnoredFileExtension(): void
    {

        $originalImage = $this->createOriginalImage(extension: 'svg');
        $fallbackImage = $this->createFallbackImage(640, 480);
        $subject = $this->createSubject($fallbackImage, '/fallback.svg');

        $tag = $subject->renderImageTagWithSrcset(
            $originalImage,
            $fallbackImage,
            Area::createEmpty(),
            null,
            null,
            new ImageRenderOptions(
                fileExtension: 'webp',
                ignoreFileExtensions: 'svg, gif',
                srcset: '320',
                sizes: '(min-width: %1$dpx) %1$dpx, 100vw'
            )
        );

        $result = $tag->render();

        self::assertStringStartsWith('<img', $result);
        self::assertStringContainsString('src="/fallback.svg"', $result);
        self::assertStringContainsString('width="640"', $result);
        self::assertStringContainsString('height="480"', $result);
        self::assertStringNotContainsString('srcset', $result);
    }


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderImageTagWithSrcsetUsesNativeLazyload(): void
    {
        $originalImage = $this->createOriginalImage();
        $fallbackImage = $this->createFallbackImage(640, 480);
        $tag = new TagBuilder('img');

        $subject = $this->createSubject($fallbackImage, '/fallback.jpg');

        $subject->renderImageTagWithSrcset(
            $originalImage,
            $fallbackImage,
            Area::createEmpty(),
            null,
            $tag,
            new ImageRenderOptions(
                loading: 'lazy',
                srcset: '320',
                sizes: '(min-width: %1$dpx) %1$dpx, 100vw'
            )
        );

        self::assertSame('/fallback.jpg', $tag->getAttribute('src'));
        self::assertSame('/image-320.jpg 320w', $tag->getAttribute('srcset'));
        self::assertSame('lazy', $tag->getAttribute('loading'));

        $result = $tag->render();
        self::assertStringNotContainsString('data-src', $result);
    }


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderImageTagWithSrcsetUsesJsLazyload(): void
    {
        $originalImage = $this->createOriginalImage();
        $fallbackImage = $this->createFallbackImage(640, 480);
        $tag = new TagBuilder('img');

        $subject = $this->createSubject($fallbackImage, '/fallback.jpg');

        $subject->renderImageTagWithSrcset(
            $originalImage,
            $fallbackImage,
            Area::createEmpty(),
            null,
            $tag,
            new ImageRenderOptions(
                lazyload: true,
                srcset: '320',
                sizes: '(min-width: %1$dpx) %1$dpx, 100vw'
            )
        );

        self::assertSame('/fallback.jpg', $tag->getAttribute('data-src'));
        self::assertSame('/image-320.jpg 320w', $tag->getAttribute('data-srcset'));
        self::assertSame('(min-width: 640px) 640px, 100vw', $tag->getAttribute('data-sizes'));
        self::assertFalse($tag->hasAttribute('loading'));

        self::assertFalse($tag->hasAttribute('src'));
        self::assertFalse($tag->hasAttribute('srcset'));
        self::assertFalse($tag->hasAttribute('sizes'));
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function renderImageTagWithSrcsetPrefersNativeLazyloadOverJsLazyload(): void
    {
        $originalImage = $this->createOriginalImage();
        $fallbackImage = $this->createFallbackImage(640, 480);
        $tag = new TagBuilder('img');

        $subject = $this->createSubject($fallbackImage, '/fallback.jpg');

        $subject->renderImageTagWithSrcset(
            $originalImage,
            $fallbackImage,
            Area::createEmpty(),
            null,
            $tag,
            new ImageRenderOptions(
                lazyload: true,
                loading: 'lazy',
                srcset: '320',
                sizes: '(min-width: %1$dpx) %1$dpx, 100vw'
            )
        );

        self::assertSame('/fallback.jpg', $tag->getAttribute('src'));
        self::assertSame('/image-320.jpg 320w', $tag->getAttribute('srcset'));
        self::assertSame('(min-width: 640px) 640px, 100vw', $tag->getAttribute('sizes'));
        self::assertSame('lazy', $tag->getAttribute('loading'));

        self::assertFalse($tag->hasAttribute('data-src'));
        self::assertFalse($tag->hasAttribute('data-srcset'));
        self::assertFalse($tag->hasAttribute('data-sizes'));
    }

    /**
     * @param string $extension Extension
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function createOriginalImage(string $extension = 'jpg'): FileInterface
    {
        $image = $this->createStub(FileInterface::class);
        $image
            ->method('getProperty')
            ->willReturnMap([
                ['alternative', 'Alternative'],
                ['title', 'Title'],
                ['extension', $extension],
                ['width', 1280],
            ]);

        return $image;
    }


    /**
     * @param int $width Width
     * @param int $height Height
     *
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function createFallbackImage(int $width, int $height): FileInterface
    {
        $image = $this->createStub(FileInterface::class);
        $image
            ->method('getProperty')
            ->willReturnMap([
                ['width', $width],
                ['height', $height],
            ]);

        return $image;
    }


    /**
     * @param \TYPO3\CMS\Core\Resource\FileInterface $fallbackImage Fallback image
     * @param string $fallbackUri Fallback URI
     *
     * @return \Madj2k\MediaUtils\Renderer\ImageTagRenderer
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function createSubject(FileInterface $fallbackImage, string $fallbackUri): ImageTagRenderer
    {
        $imageService = $this->createStub(ImageService::class);
        $imageService
            ->method('getImageUri')
            ->willReturnMap([
                [$fallbackImage, false, $fallbackUri],
                [$fallbackImage, true, $fallbackUri],
            ]);

        $processedImage320 = $this->createFallbackImage(320, 240);

        $imageProcessor = $this->createStub(ImageProcessorInterface::class);
        $imageProcessor
            ->method('supportsBatchProcessing')
            ->willReturn(false);
        $imageProcessor
            ->method('applyProcessingInstructions')
            ->willReturn($processedImage320);
        $imageProcessor
            ->method('getImageUri')
            ->willReturnMap([
                [$processedImage320, false, '/image-320.jpg'],
                [$processedImage320, true, '/image-320.jpg'],
            ]);

        $responsiveImageService = new ResponsiveImageService(
            $imageService,
            new SrcsetGenerator($imageProcessor)
        );

        return new ImageTagRenderer(
            $responsiveImageService,
            new PlaceholderGenerator($imageService),
            new MetadataService()
        );
    }
}
