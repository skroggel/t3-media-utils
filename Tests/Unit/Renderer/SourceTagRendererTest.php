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

use Madj2k\MediaUtils\Generator\SrcsetGenerator;
use Madj2k\MediaUtils\Processing\ImageProcessorInterface;
use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use Madj2k\MediaUtils\Renderer\SourceTagRenderer;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class SourceTagRendererTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class SourceTagRendererTest extends UnitTestCase
{


    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testRenderSourceTagAddsSrcsetMediaAndSizesForWidthDescriptors(): void
    {
        $image = $this->createStub(FileInterface::class);
        $image
            ->method('getProperty')
            ->willReturnMap([
                ['width', 640],
            ]);

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

        $cropArea = Area::createEmpty();

        $imageProcessor = $this->createMock(ImageProcessorInterface::class);
        $imageProcessor
            ->method('supportsBatchProcessing')
            ->willReturn(false);
        $imageProcessor
            ->expects(self::exactly(2))
            ->method('applyProcessingInstructions')
            ->willReturnMap([
                [$image, ['width' => 320, 'crop' => null], $processedImage320],
                [$image, ['width' => 640, 'crop' => null], $processedImage640],
            ]);
        $imageProcessor
            ->method('getImageUri')
            ->willReturnMap([
                [$processedImage320, false, '/image-320.jpg'],
                [$processedImage640, false, '/image-640.jpg'],
            ]);

        $subject = new SourceTagRenderer(new SrcsetGenerator($imageProcessor));

        $tag = $subject->renderSourceTag(
            $image,
            640,
            '320, 640',
            '(min-width: 640px)',
            '(min-width: %1$dpx) %1$dpx, 100vw',
            $cropArea,
            new ImageRenderOptions()
        );

        self::assertSame('/image-320.jpg 320w, /image-640.jpg 640w', $tag->getAttribute('srcset'));
        self::assertSame('(min-width: 640px)', $tag->getAttribute('media'));
        self::assertSame('(min-width: 640px) 640px, 100vw', $tag->getAttribute('sizes'));
        self::assertFalse($tag->hasAttribute('data-srcset'));
    }
}
