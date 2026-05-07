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

namespace Madj2k\MediaUtils\Tests\Functional\Processing;

use Madj2k\MediaUtils\Processing\Typo3ImageProcessor;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class Typo3ImageProcessorTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class Typo3ImageProcessorTest extends FunctionalTestCase
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
     * Sets up the functional test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages.csv');

        /** @var string $targetDirectory */
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
     * Tests that multiple processing instruction sets are applied to the same source image.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function applyProcessingInstructionsBatchReturnsProcessedFilesForEachInstruction(): void
    {
        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = $this->get(ResourceFactory::class);

        /** @var FileInterface $file */
        $file = $resourceFactory->retrieveFileOrFolderObject(
            '1:/user_upload/test-image.jpg'
        );

        /** @var Typo3ImageProcessor $subject */
        $subject = $this->get(Typo3ImageProcessor::class);

        /** @var array<string, array<string, int>> $processingInstructionsBatch */
        $processingInstructionsBatch = [
            '320w' => [
                'width' => 320,
            ],
            '640w' => [
                'width' => 640,
            ],
        ];

        /** @var array<string, FileInterface> $result */
        $result = $subject->applyProcessingInstructionsBatch(
            $file,
            $processingInstructionsBatch
        );

        self::assertCount(2, $result);
        self::assertArrayHasKey('320w', $result);
        self::assertArrayHasKey('640w', $result);

        self::assertInstanceOf(ProcessedFile::class, $result['320w']);
        self::assertInstanceOf(ProcessedFile::class, $result['640w']);

        self::assertSame(320, $result['320w']->getProperty('width'));
        self::assertSame(640, $result['640w']->getProperty('width'));
    }
}
