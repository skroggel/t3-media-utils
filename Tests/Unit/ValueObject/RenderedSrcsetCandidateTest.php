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

namespace Madj2k\MediaUtils\Tests\Unit\ValueObject;

use Madj2k\MediaUtils\ValueObject\RenderedSrcsetCandidate;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class RenderedSrcsetCandidateTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class RenderedSrcsetCandidateTest extends UnitTestCase
{

    /**
     * @return void
     */
    #[Test]
    public function testGettersReturnConstructorValues(): void
    {
        /** @var RenderedSrcsetCandidate $subject */
        $subject = new RenderedSrcsetCandidate('640w', '/fileadmin/image.jpg', 640);

        self::assertSame('640w', $subject->getDescriptor());
        self::assertSame('/fileadmin/image.jpg', $subject->getUri());
        self::assertSame(640, $subject->getProcessedWidth());
    }
}
