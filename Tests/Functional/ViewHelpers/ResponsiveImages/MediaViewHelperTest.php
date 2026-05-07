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

use Madj2k\MediaUtils\ViewHelpers\ResponsiveImages\MediaViewHelper;

/**
 * Class MediaViewHelperTest
 *
 * Functional tests for the responsive media ViewHelper.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class MediaViewHelperTest extends FunctionalTestCase
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
        $this->copyFixtureFilesToPublicStorage();
    }


    /**
     * Tests that image media is rendered through the responsive image pipeline.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
   #[Test]
    public function renderReturnsImageTagWithSrcsetForFileArgument(): void
    {
        /** @var FileInterface $file */
        $file = $this->getTestImage();

        /** @var MediaViewHelper $subject */
        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'alt' => 'Media alt',
            'class' => 'media-image',
            'width' => '640',
            'srcset' => '320,640',
            'sizes' => '(min-width: 640px) 640px, 100vw',
        ]));
        $subject->initialize();

        /** @var string $result */
        $result = $subject->render();

        self::assertStringStartsWith('<img ', $result);
        self::assertStringContainsString('class="media-image"', $result);
        self::assertStringContainsString('alt="Media alt"', $result);
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
        $file = $this->getTestImage();

        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'alt' => 'Media alt',
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
     * Tests that simple image media rendering adds height attribute by default.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderReturnsSimpleImageTagWithHeightAttributeByDefault(): void
    {
        $file = $this->getTestImage();

        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'alt' => 'Media alt',
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

        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $image,
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
     * Tests that breakpoint configuration renders picture markup for image media.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderReturnsPictureTagForBreakpointConfiguration(): void
    {
        /** @var FileInterface $file */
        $file = $this->getTestImage();

        /** @var MediaViewHelper $subject */
        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'alt' => 'Media picture alt',
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
        self::assertStringContainsString('alt="Media picture alt"', $result);
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

        /** @var MediaViewHelper $subject */
        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $image,
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
     * Tests that breakpoint configuration renders picture markup for image media with height attribute.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderReturnsVideoMarkupForVideoFile(): void
    {
        $file = $this->getTestVideo();

        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<video ', $result);
        self::assertStringContainsString('<source ', $result);
        self::assertStringContainsString('src="fileadmin/user_upload/test-video.mp4"', $result);
        self::assertStringContainsString('type="video/mp4"', $result);
        self::assertStringEndsWith('</video>', $result);
    }

    /**
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesGlobalVideoSettingsWhenNoVideoSettingsAreGiven(): void
    {
        $file = $this->getTestVideo();

        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'additionalConfig' => null,
            'settings' => [
                'txMediaUtils' => [
                    'responsiveImages' => [
                        'additionalConfig' => [
                            'autoplay' => 1,
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringContainsString('autoplay', $result);
    }

    /**
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesVideoVariantSettingsBeforeGlobalVideoSettings(): void
    {
        $file = $this->getTestVideo();

        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'additionalConfig' => null,
            'settingsVariant' => 'small',
            'settings' => [
                'media' => [
                    'small' => [
                        'video' => [
                            'additionalConfig' => [
                                'autoplay' => 1,
                            ],
                        ],
                    ],
                ],
                'txMediaUtils' => [
                    'responsiveImages' => [
                        'additionalConfig' => [
                            'autoplay' => 0,
                            'playsinline' => 1,
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringContainsString('autoplay', $result);
        self::assertStringNotContainsString('playsinline', $result);
    }

    /**
     * Tests that explicit media settings can be used to override the default settings.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesRegularVideoSettingsWhenNoExplicitMediaArgumentsAreGiven(): void
    {
        $file = $this->getTestVideo();

        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'additionalConfig' => null,
            'settings' => [
                'media' => [
                    'video' => [
                        'additionalConfig' => [
                            'autoplay' => 1,
                            'muted' => 1,
                            'playsinline' => 1,
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<video ', $result);
        self::assertStringContainsString('autoplay', $result);
        self::assertStringContainsString('muted', $result);
        self::assertStringContainsString('playsinline', $result);

    }


    /**
     * Tests that the render method applies the video variant settings when
     * no explicit media arguments are given.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesVideoVariantSettingsWhenNoExplicitMediaArgumentsAreGiven(): void
    {
        /** @var FileInterface $file */
        $file = $this->getTestVideo();

        /** @var MediaViewHelper $subject */
        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'settingsVariant' => 'small',
            'settings' => [
                'media' => [
                    'small' => [
                        'video' => [
                            'additionalConfig' => [
                                'autoplay' => 1,
                                'muted' => 1,
                                'loop' => 1,
                                'playsinline' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringContainsString('autoplay', $result);
        self::assertStringContainsString('muted', $result);
        self::assertStringContainsString('loop', $result);
        self::assertStringContainsString('playsinline', $result);

    }

    /**
     * Tests that the rendering process prioritizes video variant settings over regular video settings.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesVideoVariantSettingsBeforeRegularVideoSettings(): void
    {
        $file = $this->getTestVideo();

        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'additionalConfig' => null,
            'settingsVariant' => 'small',
            'settings' => [
                'media' => [
                    'small' => [
                        'video' => [
                            'additionalConfig' => [
                                'autoplay' => 1,
                                'playsinline' => 1,
                            ],
                        ],
                    ],
                    'video' => [
                        'additionalConfig' => [
                            'autoplay' => 0,
                            'loop' => 0,
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<video ', $result);
        self::assertStringContainsString('autoplay', $result);
        self::assertStringContainsString('playsinline', $result);
        self::assertStringNotContainsString('loop', $result);
    }

    /**
     * Tests that the render method utilizes video dimensions
     * specified in the variant settings properly.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesVideoDimensionsFromVariantSettings(): void
    {
        $file = $this->getTestVideo();

        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'width' => null,
            'height' => null,
            'settingsVariant' => 'small',
            'settings' => [
                'media' => [
                    'small' => [
                        'video' => [
                            'dimensions' => [
                                'width' => 1370,
                                'height' => 771,
                            ],
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<video ', $result);
        self::assertStringContainsString('width="1370"', $result);
        self::assertStringContainsString('height="771"', $result);
    }


    /**
     * Tests whether the render method uses explicit video dimensions provided
     * as arguments before falling back to dimensions defined in variant settings.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesExplicitVideoDimensionsBeforeVariantSettings(): void
    {
        $file = $this->getTestVideo();

        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'width' => 640,
            'height' => 360,
            'settingsVariant' => 'small',
            'settings' => [
                'media' => [
                    'small' => [
                        'video' => [
                            'dimensions' => [
                                'width' => 1370,
                                'height' => 771,
                            ],
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<video ', $result);
        self::assertStringContainsString('width="640"', $result);
        self::assertStringContainsString('height="360"', $result);
        self::assertStringNotContainsString('width="1370"', $result);
        self::assertStringNotContainsString('height="771"', $result);
    }

    /**
     * Ensures that explicit video arguments take precedence over regular video settings
     * when rendering a video.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesExplicitVideoArgumentsBeforeRegularVideoSettings(): void
    {
        /** @var FileInterface $file */
        $file = $this->getTestVideo();

        /** @var MediaViewHelper $subject */
        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'additionalConfig' => [
                'autoplay' => 0,
            ],
            'settings' => [
                'media' => [
                    'video' => [
                        'additionalConfig' => [
                            'autoplay' => 1,
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<video ', $result);
        self::assertStringNotContainsString('autoplay', $result);
    }


    /**
     * Ensures that explicit video arguments take precedence over variant-specific video settings
     * when rendering a video.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    #[Test]
    public function renderUsesExplicitVideoArgumentsBeforeVariantSettings(): void
    {
        /** @var FileInterface $file */
        $file = $this->getTestVideo();

        /** @var MediaViewHelper $subject */
        $subject = $this->get(MediaViewHelper::class);
        $subject->initializeArguments();
        $subject->setArguments($this->getDefaultArguments([
            'file' => $file,
            'additionalConfig' => [
                'autoplay' => 0,
            ],
            'settingsVariant' => 'small',
            'settings' => [
                'media' => [
                    'small' => [
                        'video' => [
                            'additionalConfig' => [
                                'autoplay' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ]));
        $subject->initialize();

        $result = $subject->render();

        self::assertStringStartsWith('<video ', $result);
        self::assertStringNotContainsString('autoplay', $result);
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
            'sizes' => '(min-width: %1$dpx) %1$dpx, 100vw',
            'breakpoints' => null,
            'lazyload' => false,
            'placeholderSize' => 0,
            'placeholderInline' => false,
            'ignoreFileExtensions' => 'svg, gif',
            'renderHeightAttribute' => false,
            'file' => null,
            'src' => '',
            'treatIdAsReference' => false,
            'additionalConfig' => [],
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
     * Returns the test video from public storage.
     *
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    private function getTestVideo(): FileInterface
    {
        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = $this->get(ResourceFactory::class);

        return $resourceFactory->retrieveFileOrFolderObject('1:/user_upload/test-video.mp4');
    }


    /**
     * Copies the file fixture into public storage.
     *
     * @return void
     */
    private function copyFixtureFilesToPublicStorage(): void
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
        copy(
            __DIR__ . '/../../Fixtures/Files/test-video.mp4',
            $targetDirectory . 'test-video.mp4'
        );
    }
}
