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

namespace Madj2k\MediaUtils\Tests\Unit\Processing;

use Madj2k\MediaUtils\Processing\Typo3ImageProcessor;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class Typo3ImageProcessorTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class Typo3ImageProcessorTest extends UnitTestCase
{

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testApplyProcessingInstructionsDelegatesToImageService(): void
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
            ->with($image, ['width' => 320])
            ->willReturn($processedImage);

        /** @var Typo3ImageProcessor $subject */
        $subject = new Typo3ImageProcessor($imageService);

        self::assertSame($processedImage, $subject->applyProcessingInstructions($image, ['width' => 320]));
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testGetImageUriDelegatesToImageService(): void
    {
        /** @var FileInterface $image */
        $image = $this->createStub(FileInterface::class);

        /** @var ImageService $imageService */
        $imageService = $this->createMock(ImageService::class);
        $imageService
            ->expects(self::once())
            ->method('getImageUri')
            ->with($image, true)
            ->willReturn('https://example.org/image.jpg');

        /** @var Typo3ImageProcessor $subject */
        $subject = new Typo3ImageProcessor($imageService);

        self::assertSame('https://example.org/image.jpg', $subject->getImageUri($image, true));
    }
}
