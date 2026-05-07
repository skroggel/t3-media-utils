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

namespace Madj2k\MediaUtils\Tests\Unit\Generator;

use Madj2k\MediaUtils\Generator\SrcsetGenerator;
use Madj2k\MediaUtils\Processing\ImageProcessorInterface;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class SrcsetGeneratorTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class SrcsetGeneratorTest extends UnitTestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testGenerateSrcsetAttributeSortsAndEscapesUrls(): void
    {
        /** @var ImageProcessorInterface $imageProcessor */
        $imageProcessor = $this->createStub(ImageProcessorInterface::class);

        /** @var SrcsetGenerator $subject */
        $subject = new SrcsetGenerator($imageProcessor);

        self::assertSame(
            '/image-320.jpg 320w, /image.php?x=1&amp;y=2 640w',
            $subject->generateSrcsetAttribute([
                '640w' => '/image.php?x=1&y=2',
                '320w' => '/image-320.jpg',
            ])
        );
    }


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testGenerateSrcsetImagesLimitsWidthCandidatesToOriginalWidthAndRemovesDuplicates(): void
    {
        /** @var FileInterface $image */
        $image = $this->createConfiguredStub(FileInterface::class, [
            'getProperty' => 800,
        ]);

        /** @var FileInterface $processedImage */
        $processedImage = $this->createConfiguredStub(FileInterface::class, [
            'getProperty' => 800,
        ]);

        /** @var ImageProcessorInterface $imageProcessor */
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);
        $imageProcessor
            ->method('supportsBatchProcessing')
            ->willReturn(false);
        $imageProcessor
            ->expects(self::once())
            ->method('applyProcessingInstructions')
            ->with($image, ['width' => 800, 'crop' => null])
            ->willReturn($processedImage);
        $imageProcessor
            ->expects(self::once())
            ->method('getImageUri')
            ->with($processedImage, false)
            ->willReturn('/processed-800.jpg');

        /** @var SrcsetGenerator $subject */
        $subject = new SrcsetGenerator($imageProcessor);

        self::assertSame(
            ['800w' => '/processed-800.jpg'],
            $subject->generateSrcsetImages($image, 400, '1200, 1200w')
        );
    }


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testGenerateSrcsetImagesKeepsDensityDescriptors(): void
    {
        /** @var FileInterface $image */
        $image = $this->createConfiguredStub(FileInterface::class, [
            'getProperty' => 1000,
        ]);

        /** @var FileInterface $processedImage */
        $processedImage = $this->createConfiguredStub(FileInterface::class, [
            'getProperty' => 800,
        ]);

        /** @var ImageProcessorInterface $imageProcessor */
        $imageProcessor = $this->createMock(ImageProcessorInterface::class);
        $imageProcessor
            ->method('supportsBatchProcessing')
            ->willReturn(false);
        $imageProcessor
            ->expects(self::once())
            ->method('applyProcessingInstructions')
            ->with($image, ['width' => 800, 'crop' => null])
            ->willReturn($processedImage);
        $imageProcessor
            ->expects(self::once())
            ->method('getImageUri')
            ->with($processedImage, true)
            ->willReturn('https://example.org/processed-800.jpg');

        /** @var SrcsetGenerator $subject */
        $subject = new SrcsetGenerator($imageProcessor);

        self::assertSame(
            ['2x' => 'https://example.org/processed-800.jpg'],
            $subject->generateSrcsetImages($image, 400, '2x', null, true)
        );
    }
}
