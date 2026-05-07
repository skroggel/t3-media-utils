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

use Madj2k\MediaUtils\Service\MetadataService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class MetadataServiceTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class MetadataServiceTest extends UnitTestCase
{

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testAddMetadataAddsAltTitleAndDimensions(): void
    {
        /** @var TagBuilder $tag */
        $tag = new TagBuilder('img');

        /** @var FileInterface $image */
        $image = $this->createStub(FileInterface::class);
        $image
            ->method('getProperty')
            ->willReturnMap([
                ['alternative', ' Alternative text '],
                ['title', ' Image title '],
            ]);

        /** @var MetadataService $subject */
        $subject = new MetadataService();
        $subject->addMetadata($tag, $image);

        self::assertSame('Alternative text', $tag->getAttribute('alt'));
        self::assertSame('Image title', $tag->getAttribute('title'));
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testAddMetadataKeepsExistingAttributes(): void
    {
        /** @var TagBuilder $tag */
        $tag = new TagBuilder('img');
        $tag->addAttribute('alt', 'Existing alt');
        $tag->addAttribute('width', '320');

        /** @var FileInterface $image */
        $image = $this->createStub(FileInterface::class);
        $image
            ->method('getProperty')
            ->willReturnMap([
                ['alternative', 'New alt'],
                ['title', 'Title'],
            ]);

        /** @var MetadataService $subject */
        $subject = new MetadataService();
        $subject->addMetadata($tag, $image);

        self::assertSame('Existing alt', $tag->getAttribute('alt'));
    }
}
