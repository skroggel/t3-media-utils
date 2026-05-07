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

namespace Madj2k\MediaUtils\Tests\Functional\ViewHelpers\ResponsiveImages;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

use Madj2k\MediaUtils\ViewHelpers\ResponsiveImages\ImageViewHelper;

/**
 * Class ImageViewHelperTest
 *
 * Functional tests for the responsive image ViewHelper.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class ImageViewHelperTest extends FunctionalTestCase
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

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/pages.csv');
        $this->copyFixtureImageToPublicStorage();
    }


    /**
     * Tests that the ViewHelper renders responsive image markup with srcset.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderReturnsImageTagWithSrcsetForImageArgument(): void
    {
        /** @var FileInterface $image */
        $image = $this->getTestImage();

        /** @var ImageViewHelper $subject */
        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $image,
            'alt' => 'Responsive image alt',
            'title' => 'Responsive image title',
            'class' => 'responsive-image',
            'loading' => 'lazy',
            'decoding' => 'async',
            'width' => '640',
            'srcset' => '320, 640',
            'sizes' => '(min-width: 640px) 640px, 100vw',
        ]));
        $subject->initialize();

        /** @var string $result */
        $result = $subject->render();

        self::assertStringStartsWith('<img ', $result);
        self::assertStringContainsString('class="responsive-image"', $result);
        self::assertStringContainsString('alt="Responsive image alt"', $result);
        self::assertStringContainsString('title="Responsive image title"', $result);
        self::assertStringContainsString('loading="lazy"', $result);
        self::assertStringContainsString('decoding="async"', $result);
        self::assertStringContainsString('srcset="', $result);
        self::assertStringContainsString('320w', $result);
        self::assertStringContainsString('640w', $result);
        self::assertStringContainsString('sizes="(min-width: 640px) 640px, 100vw"', $result);
        self::assertStringNotContainsString('height="360"', $result);
    }


    /**
     * Tests that srcset rendering can add the height attribute when enabled.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderReturnsImageTagWithSrcsetAndHeightAttributeWhenEnabled(): void
    {
        $image = $this->getTestImage();

        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $image,
            'alt' => 'Responsive image alt',
            'width' => '640',
            'srcset' => '640',
            'sizes' => '(min-width: 640px) 640px, 100vw',
            'renderHeightAttribute' => true,
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<img ', $result);
        self::assertStringContainsString('width="640"', $result);
        self::assertStringContainsString('height="360"', $result);
    }

    /**
     * Tests that simple image rendering adds height attribute by default.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderReturnsSimpleImageTagWithHeightAttributeByDefault(): void
    {
        $image = $this->getTestImage();

        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $image,
            'alt' => 'Simple image alt',
            'width' => '640',
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<img ', $result);
        self::assertStringContainsString('width="640"', $result);
        self::assertStringContainsString('height="360"', $result);
        self::assertStringNotContainsString('srcset=', $result);
    }

    /**
     * Tests that SVG image rendering keeps the original SVG and does not add srcset.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderReturnsSimpleImageTagForSvg(): void
    {
        $image = $this->getTestImage('svg');

        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $image,
            'alt' => 'SVG image alt',
            'width' => '640',
            'srcset' => '640',
            'sizes' => '(min-width: 640px) 640px, 100vw',
            'fileExtension' => 'webp',
            'ignoreFileExtensions' => 'svg,gif',
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<img ', $result);
        self::assertStringContainsString('.svg', $result);
        self::assertStringNotContainsString('.webp', $result);
        self::assertStringNotContainsString('srcset=', $result);
    }

    /**
     * Tests that breakpoint configuration renders picture markup.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderReturnsPictureTagForBreakpointConfiguration(): void
    {
        /** @var FileInterface $image */
        $image = $this->getTestImage();

        /** @var ImageViewHelper $subject */
        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $image,
            'alt' => 'Picture alt',
            'width' => '640',
            'breakpoints' => [
                'desktop' => [
                    'media' => '(min-width: 640px)',
                    'srcset' => '640',
                    'sizes' => '640px',
                ],
            ],
        ]));
        $subject->initialize();

        /** @var string $result */
        $result = $subject->render();

        self::assertStringStartsWith('<picture>', $result);
        self::assertStringContainsString('<source ', $result);
        self::assertStringContainsString('media="(min-width: 640px)"', $result);
        self::assertStringContainsString('<img ', $result);
        self::assertStringContainsString('alt="Picture alt"', $result);
        self::assertStringNotContainsString('height="360"', $result);
        self::assertStringEndsWith('</picture>', $result);
    }


    /**
     * Tests that picture rendering can add the fallback image height attribute when enabled.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderReturnsPictureTagWithHeightAttributeWhenEnabled(): void
    {
        $image = $this->getTestImage();

        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $image,
            'alt' => 'Picture alt',
            'width' => '640',
            'breakpoints' => [
                'desktop' => [
                    'media' => '(min-width: 640px)',
                    'srcset' => '640',
                    'sizes' => '640px',
                ],
            ],
            'renderHeightAttribute' => true,
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<picture>', $result);
        self::assertStringContainsString('width="640"', $result);
        self::assertStringContainsString('height="360"', $result);
    }

    /**
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesGlobalResponsiveImageSettingsWhenNoImageSettingsAreGiven(): void
    {
        $file = $this->getTestImage();

        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $file,
            'alt' => 'Global settings alt',
            'width' => '640',
            'srcset' => null,
            'sizes' => null,
            'settings' => [
                'txMediaUtils' => [
                    'responsiveImages' => [
                        'srcset' => '640',
                        'sizes' => '640px',
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringContainsString('640w', $result);
        self::assertStringContainsString('sizes="640px"', $result);
    }

    /**
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesVariantSettingsBeforeGlobalResponsiveImageSettings(): void
    {
        $file = $this->getTestImage();

        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $file,
            'alt' => 'Variant override alt',
            'width' => '640',
            'srcset' => null,
            'sizes' => null,
            'settingsVariant' => 'small',
            'settings' => [
                'media' => [
                    'small' => [
                        'image' => [
                            'srcset' => '320',
                            'sizes' => '320px',
                        ]
                    ],
                ],
                'txMediaUtils' => [
                    'responsiveImages' => [
                        'srcset' => '640',
                        'sizes' => '640px',
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringContainsString('320w', $result);
        self::assertStringContainsString('sizes="320px"', $result);
        self::assertStringNotContainsString('640w', $result);
    }

    /**
     * Tests that the ViewHelper renders regular image markup with fallback settings
     * when no explicit image arguments (such as srcset and sizes) are provided.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesRegularImageSettingsWhenNoExplicitImageArgumentsAreGiven(): void
    {
        /** @var FileInterface $file */
        $file = $this->getTestImage();

        /** @var ImageViewHelper $subject */
        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $file,
            'alt' => 'Regular image alt',
            'width' => '640',
            'srcset' => null,
            'sizes' => null,
            'settings' => [
                'media' => [
                    'image' => [
                        'srcset' => '640',
                        'sizes' => '640px',
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringContainsString('srcset=', $result);
        self::assertStringContainsString('640w', $result);
        self::assertStringContainsString('sizes="640px"', $result);
        self::assertStringContainsString('alt="Regular image alt"', $result);
    }

    /**
     * Tests that the ViewHelper prioritizes explicitly defined image arguments over
     * regular image settings when rendering responsive image markup.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesExplicitImageArgumentsBeforeRegularImageSettings(): void
    {
        /** @var FileInterface $file */
        $file = $this->getTestImage();

        /** @var ImageViewHelper $subject */
        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $file,
            'alt' => 'Explicit image alt',
            'width' => '640',
            'srcset' => '320',
            'sizes' => '320px',
            'settings' => [
                'media' => [
                    'image' => [
                        'srcset' => '640',
                        'sizes' => '640px',
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringContainsString('320w', $result);
        self::assertStringContainsString('sizes="320px"', $result);
        self::assertStringNotContainsString('640w', $result);
    }


    /**
     * Tests that the ViewHelper uses image variant settings from provided configuration
     * when no explicit image arguments are defined.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesImageVariantSettingsWhenNoExplicitImageArgumentsAreGiven(): void
    {
        /** @var FileInterface $file */
        $file = $this->getTestImage();

        /** @var ImageViewHelper $subject */
        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $file,
            'alt' => 'Variant image alt',
            'width' => '640',
            'settingsVariant' => 'small',
            'settings' => [
                'media' => [
                    'small' => [
                        'image' => [
                            'srcset' => '640',
                            'sizes' => '640px',
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringContainsString('srcset=', $result);
        self::assertStringContainsString('640w', $result);
        self::assertStringContainsString('sizes="640px"', $result);
        self::assertStringContainsString('alt="Variant image alt"', $result);
    }


    /**
     * Tests that the ViewHelper prioritizes explicit image arguments over variant settings.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesExplicitImageArgumentsBeforeVariantSettings(): void
    {
        /** @var FileInterface $file */
        $file = $this->getTestImage();

        /** @var ImageViewHelper $subject */
        $subject = $this->get(ImageViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'image' => $file,
            'alt' => 'Explicit image alt',
            'width' => '640',
            'settingsVariant' => 'small',
            'srcset' => '320',
            'sizes' => '320px',
            'settings' => [
                'media' => [
                    'small' => [
                        'image' => [
                            'srcset' => '640',
                            'sizes' => '640px',
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringContainsString('320w', $result);
        self::assertStringContainsString('sizes="320px"', $result);
        self::assertStringNotContainsString('640w', $result);
    }

    /**
     * Returns default ViewHelper arguments.
     *
     * @param array<string, mixed> $arguments Overriding arguments
     * @return array<string, mixed>
     */
    private function getDefaultArguments(array $arguments = []): array
    {
        return array_replace([
            'alt' => '',
            'title' => '',
            'class' => '',
            'id' => '',
            'style' => '',
            'loading' => '',
            'decoding' => '',
            'fileExtension' => null,
            'crop' => null,
            'cropVariant' => 'default',
            'width' => null,
            'height' => null,
            'minWidth' => null,
            'minHeight' => null,
            'maxWidth' => null,
            'maxHeight' => null,
            'absolute' => false,
            'srcset' => null,
            'sizes' => null,
            'breakpoints' => null,
            'lazyload' => false,
            'placeholderSize' => 0,
            'placeholderInline' => false,
            'ignoreFileExtensions' => 'svg, gif',
            'renderHeightAttribute' => false,
            'usemap' => '',
            'ismap' => false,
            'longdesc' => '',
            'src' => '',
            'treatIdAsReference' => false,
            'image' => null,
        ], $arguments);
    }


    /**
     * Returns the test image from public storage.
     *
     * @param string $extension
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    private function getTestImage($extension = 'jpg'): FileInterface
    {
        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = $this->get(ResourceFactory::class);

        return $resourceFactory->retrieveFileOrFolderObject('1:/user_upload/test-image.' . $extension);
    }


    /**
     * Copies the image fixture into public storage.
     *
     * @return void
     */
    private function copyFixtureImageToPublicStorage(): void
    {
        /** @var string $targetDirectory */
        $targetDirectory = Environment::getPublicPath() . '/fileadmin/user_upload/';

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        copy(
            __DIR__ . '/../../Fixtures/Files/test-image.jpg',
            $targetDirectory . 'test-image.jpg'
        );
        copy(
            __DIR__ . '/../../Fixtures/Files/test-image.svg',
            $targetDirectory . 'test-image.svg'
        );
    }
}
