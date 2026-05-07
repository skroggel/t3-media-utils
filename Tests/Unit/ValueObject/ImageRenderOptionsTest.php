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

use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ImageRenderOptionsTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class ImageRenderOptionsTest extends UnitTestCase
{

    /**
     * @return void
     */
    #[Test]
    public function hasSrcsetReturnsTrueForNonEmptySrcsetConfiguration(): void
    {
        $subject = new ImageRenderOptions(
            srcset: '320',
            sizes: '(min-width: %1$dpx) %1$dpx, 100vw'
        );

        self::assertTrue($subject->hasSrcset());
    }


    /**
     * @return void
     */
    #[Test]
    public function hasSrcsetReturnsFalseForEmptySrcsetConfiguration(): void
    {
        $subject = new ImageRenderOptions(
            srcset: '',
            sizes: '(min-width: %1$dpx) %1$dpx, 100vw'
        );

        self::assertFalse($subject->hasSrcset());
    }


    /**
     * @return void
     */
    #[Test]
    public function hasBreakpointsReturnsTrueForNonEmptyBreakpointConfiguration(): void
    {
        $subject = new ImageRenderOptions(
            breakpoints: [
                'desktop' => [
                    'srcset' => '640',
                    'media' => '(min-width: 640px)',
                    'sizes' => '100vw'
                ],
            ]
        );

        self::assertTrue($subject->hasBreakpoints());
    }

    /**
     * @return void
     */
    #[Test]
    public function hasBreakpointsReturnsFalseForIncompleteBreakpointConfiguration(): void
    {
        $subject = new ImageRenderOptions(
            breakpoints: [
                'desktop' => [
                    'srcset' => '640',
                ],
            ]
        );

        self::assertFalse($subject->hasBreakpoints());
    }

    /**
     * @return void
     */
    #[Test]
    public function hasBreakpointsReturnsFalseForEmptyBreakpointConfiguration(): void
    {
        $subject = new ImageRenderOptions(
            breakpoints: []
        );

        self::assertFalse($subject->hasBreakpoints());
    }

    /**
     * @return void
     */
    #[Test]
    public function withForceHeightAttributeReturnsCopyWithChangedHeightFlag(): void
    {
        $subject = new ImageRenderOptions(
            renderHeightAttribute: false,
            width: '640'
        );

        $result = $subject->withForceHeightAttribute(true);

        self::assertNotSame($subject, $result);
        self::assertFalse($subject->forceHeightAttribute);
        self::assertTrue($result->forceHeightAttribute);
        self::assertSame('640', $result->width);
    }
}
