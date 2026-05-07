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

namespace Madj2k\MediaUtils\Tests\Functional\Generator;

use Madj2k\MediaUtils\Generator\PlaceholderGenerator;
use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class PlaceholderGeneratorTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class PlaceholderGeneratorTest extends FunctionalTestCase
{
    /**
     * Extensions loaded for functional test execution.
     *
     * @var array<int, string>
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/media_utils',
    ];

    /**
     * Sets up functional test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages.csv');

        $targetDirectory = Environment::getPublicPath() . '/fileadmin/user_upload/';
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        copy(
            __DIR__ . '/../Fixtures/Files/test-image.jpg',
            $targetDirectory . 'test-image.jpg'
        );
    }

    /**
     * Ensures generated placeholder URI points to processed image.
     *
     * @return void
     */
    #[Test]
    public function generatePlaceholderReturnsProcessedImageUri(): void
    {
        $resourceFactory = $this->get(ResourceFactory::class);
        $imageService = $this->get(ImageService::class);

        $file = $resourceFactory->retrieveFileOrFolderObject(
            '1:/user_upload/test-image.jpg'
        );

        $subject = new PlaceholderGenerator($imageService);
        $options = new ImageRenderOptions(placeholderSize: 12);

        $result = $subject->generatePlaceholder(
            $file,
            $options
        );

        self::assertNotEmpty($result);
        self::assertStringContainsString('_processed_', $result);
    }

    /**
     * Ensures inline placeholders return base64 encoded image data.
     *
     * @return void
     */
    #[Test]
    public function generatePlaceholderReturnsInlineBase64Image(): void
    {
        $resourceFactory = $this->get(ResourceFactory::class);
        $imageService = $this->get(ImageService::class);

        $file = $resourceFactory->retrieveFileOrFolderObject(
            '1:/user_upload/test-image.jpg'
        );

        $subject = new PlaceholderGenerator($imageService);
        $options = new ImageRenderOptions(placeholderSize: 12, placeholderInline: true);

        $result = $subject->generatePlaceholder(
            $file,
            $options
        );

        self::assertStringStartsWith('data:image/', $result);
        self::assertStringContainsString(';base64,', $result);
    }
}
