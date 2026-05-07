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

namespace Madj2k\MediaUtils\Tests\Unit\Service;

use Madj2k\MediaUtils\Generator\SrcsetGenerator;
use Madj2k\MediaUtils\Processing\ImageProcessorInterface;
use Madj2k\MediaUtils\Service\ResponsiveImageService;
use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ResponsiveImageServiceTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class ResponsiveImageServiceTest extends UnitTestCase
{

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testHasIgnoredFileExtensionMatchesCommaSeparatedListCaseInsensitive(): void
    {
        /** @var ImageService $imageService */
        $imageService = $this->createStub(ImageService::class);

        /** @var ImageProcessorInterface $imageProcessor */
        $imageProcessor = $this->createStub(ImageProcessorInterface::class);

        /** @var SrcsetGenerator $srcsetGenerator */
        $srcsetGenerator = new SrcsetGenerator($imageProcessor);

        /** @var FileInterface $image */
        $image = $this->createMock(FileInterface::class);
        $image
            ->expects(self::once())
            ->method('getProperty')
            ->with('extension')
            ->willReturn('SVG');

        /** @var ResponsiveImageService $subject */
        $subject = new ResponsiveImageService($imageService, $srcsetGenerator);

        self::assertTrue($subject->hasIgnoredFileExtension($image, 'svg, gif'));
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testIsSvgReturnsFalseForNonSvgExtension(): void
    {
        /** @var ImageService $imageService */
        $imageService = $this->createStub(ImageService::class);

        /** @var ImageProcessorInterface $imageProcessor */
        $imageProcessor = $this->createStub(ImageProcessorInterface::class);

        /** @var SrcsetGenerator $srcsetGenerator */
        $srcsetGenerator = new SrcsetGenerator($imageProcessor);

        /** @var FileInterface $image */
        $image = $this->createMock(FileInterface::class);
        $image
            ->expects(self::once())
            ->method('getProperty')
            ->with('extension')
            ->willReturn('jpg');

        /** @var ResponsiveImageService $subject */
        $subject = new ResponsiveImageService($imageService, $srcsetGenerator);

        self::assertFalse($subject->isSvg($image));
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function createFallbackImageUsesImageRenderOptionsForProcessingInstructions(): void
    {
        /** @var FileInterface $image */
        $image = $this->createStub(FileInterface::class);

        /** @var FileInterface $processedImage */
        $processedImage = $this->createStub(ProcessedFile::class);

        /** @var ImageService $imageService */
        $imageService = $this->createMock(ImageService::class);
        $imageService
            ->expects(self::once())
            ->method('applyProcessingInstructions')
            ->with(
                $image,
                [
                    'width' => '640',
                    'height' => '360',
                    'crop' => null,
                    'fileExtension' => 'webp',
                    'minWidth' => 320,
                    'minHeight' => 180,
                    'maxWidth' => 1280,
                    'maxHeight' => 720,
                ]
            )
            ->willReturn($processedImage);

        /** @var ImageProcessorInterface $imageProcessor */
        $imageProcessor = $this->createStub(ImageProcessorInterface::class);

        /** @var SrcsetGenerator $srcsetGenerator */
        $srcsetGenerator = new SrcsetGenerator($imageProcessor);

        /** @var ResponsiveImageService $subject */
        $subject = new ResponsiveImageService($imageService, $srcsetGenerator);

        $result = $subject->createFallbackImage(
            $image,
            Area::createEmpty(),
            new ImageRenderOptions(
                fileExtension: 'webp',
                width: '640',
                height: '360',
                minWidth: 320,
                minHeight: 180,
                maxWidth: 1280,
                maxHeight: 720
            )
        );

        self::assertSame($processedImage, $result);
    }
}
