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

namespace Madj2k\MediaUtils\ViewHelpers\ResponsiveImages;

use Madj2k\MediaUtils\ValueObject\ImageRenderOptions;
use Madj2k\MediaUtils\Renderer\ImageTagRenderer;
use Madj2k\MediaUtils\Renderer\PictureRenderer;
use Madj2k\MediaUtils\Service\ResponsiveImageService;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Base class for responsive image-related ViewHelpers.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class AbstractResponsiveImageViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'img';


    /**
     * Settings scope for media specific defaults.
     *
     * @var string
     */
    protected string $settingsScope = 'image';


    /**
     * @param \TYPO3\CMS\Extbase\Service\ImageService $imageService
     * @param \Madj2k\MediaUtils\Service\ResponsiveImageService $responsiveImageService
     * @param \Madj2k\MediaUtils\Renderer\ImageTagRenderer $imageTagRenderer
     * @param \Madj2k\MediaUtils\Renderer\PictureRenderer $pictureRenderer
     */
    public function __construct(
        protected readonly ImageService $imageService,
        protected readonly ResponsiveImageService $responsiveImageService,
        protected readonly ImageTagRenderer $imageTagRenderer,
        protected readonly PictureRenderer $pictureRenderer
    ) {
        parent::__construct();
    }


    /**
     * Registers common HTML and responsive-image arguments.
     *
     * @return void
     */
    protected function registerCommonArguments(): void
    {
        $this->registerArgument('settings', 'array', 'Responsive image default settings', false, []);
        $this->registerArgument('settingsVariant', 'string', 'Responsive image settings variant', false, null);

        $this->registerArgument('alt', 'string', 'Alternative text', false, null);
        $this->registerArgument('title', 'string', 'Title attribute', false, null);
        $this->registerArgument('class', 'string', 'CSS classes', false, null);
        $this->registerArgument('id', 'string', 'HTML id', false, null);
        $this->registerArgument('style', 'string', 'Inline styles', false, null);
        $this->registerArgument('loading', 'string', 'Native lazy-loading hint', false, null);
        $this->registerArgument('decoding', 'string', 'Decoding hint', false, null);
        $this->registerArgument('fileDecoding', 'string', 'Legacy alias for decoding hint', false, null);
        $this->registerArgument('fileExtension', 'string', 'Target file extension', false, null);

        $this->registerArgument('crop', 'string|bool', 'Override cropping', false, null);
        $this->registerArgument('cropVariant', 'string', 'Crop variant identifier', false, null);

        $this->registerArgument('width', 'string', 'Target width', false, null);
        $this->registerArgument('height', 'string', 'Target height', false, null);
        $this->registerArgument('minWidth', 'int', 'Minimum width', false, null);
        $this->registerArgument('minHeight', 'int', 'Minimum height', false, null);
        $this->registerArgument('maxWidth', 'int', 'Maximum width', false, null);
        $this->registerArgument('maxHeight', 'int', 'Maximum height', false, null);
        $this->registerArgument('absolute', 'bool', 'Generate absolute URLs', false, null);

        // this are our custom arguments!
        $this->registerArgument('srcset', 'string', 'Srcset definition', false, null);
        $this->registerArgument('sizes', 'string', 'Sizes attribute pattern', false, null);
        $this->registerArgument('breakpoints', 'array', 'Breakpoint configuration', false, null);
        $this->registerArgument('lazyload', '?bool', 'Generate native lazyload markup', false, null);
        $this->registerArgument('placeholderSize', 'int', 'Placeholder width', false, null);
        $this->registerArgument('placeholderInline', '?bool', 'Inline placeholder', false, null);
        $this->registerArgument('ignoreFileExtensions', 'string', 'Excluded extensions', false, null);
        $this->registerArgument('renderHeightAttribute', '?bool', 'Render height attribute on fallback image tag', false, null);
    }


    /**
     * Applies explicit HTML attributes to the current tag.
     *
     * @param array<int, string> $attributes Attributes to apply
     *
     * @return void
     */
    protected function applyTagAttributes(array $attributes = ['class', 'id', 'style', 'title', 'alt']): void
    {
        foreach ($attributes as $attribute) {
            if (!empty($this->arguments[$attribute])) {
                $this->tag->addAttribute($attribute, (string)$this->arguments[$attribute]);
            }
        }
    }


    /**
     * Resolves crop information for the current image.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     *
     * @return array{0: CropVariantCollection, 1: Area, 2: Area|null}
     */
    protected function resolveCropData(FileInterface $image): array
    {
        $cropValue = $this->resolveArgument('crop');

        if ($cropValue === null && $image->hasProperty('crop') && $image->getProperty('crop')) {
            $cropValue = $image->getProperty('crop');
        }

        if ($cropValue === false) {
            $cropValue = '';
        }

        $cropVariantCollection = CropVariantCollection::create((string)$cropValue);
        $cropVariant = $this->buildRenderOptions()->cropVariant;
        $cropArea = $cropVariantCollection->getCropArea($cropVariant);

        $focusArea = null;
        if (!$this->tag->hasAttribute('data-focus-area')) {
            $focusArea = $cropVariantCollection->getFocusArea($cropVariant);
        }

        return [$cropVariantCollection, $cropArea, $focusArea];
    }


    /**
     * Renders an image file through the responsive image pipeline.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Image file
     * @param array<int, string> $tagAttributes Tag attributes to apply
     * @return string
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    protected function renderImageFile(
        FileInterface $image,
        array $tagAttributes = ['class', 'id', 'style', 'title', 'alt']
    ): string {

        $this->applyTagAttributes($tagAttributes);

        if (!empty($this->arguments['ismap'] ?? false)) {
            $this->tag->addAttribute('ismap', 'ismap');
        }

        $options = $this->buildRenderOptions();

        if (!$this->isKnownFileExtension($options->fileExtension)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The extension %s is not specified in %s as a valid image file extension and can not be processed.',
                    (string)$options->fileExtension,
                    '$GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\']'
                ),
                1714472002
            );
        }

        [$cropVariantCollection, $cropArea, $focusArea] = $this->resolveCropData($image);

        if (!$this->responsiveImageService->hasIgnoredFileExtension($image, $options->ignoreFileExtensions)) {
            if ($options->hasBreakpoints()) {
                return $this->renderPicture(
                    $image,
                    $cropVariantCollection,
                    $cropArea,
                    $focusArea,
                    $options
                );
            }

            if ($options->hasSrcset()) {
                return $this->renderImageWithSrcset(
                    $image,
                    $cropArea,
                    $focusArea,
                    $options
                );
            }
        }

        return $this->renderSimpleImage(
            $image,
            $cropArea,
            $focusArea,
            $options
        );
    }

    /**
     * Creates the fallback image for img and picture rendering.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area $cropArea Crop area
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions|null $options Render options
     *
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     */
    protected function createFallbackImage(
        FileInterface $image,
        Area $cropArea,
        ?ImageRenderOptions $options = null
    ): FileInterface {
        $options ??= $this->buildRenderOptions();

        return $this->responsiveImageService->createFallbackImage(
            $image,
            $cropArea,
            $options
        );
    }


    /**
     * Renders a simple img tag.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area $cropArea Crop area
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $focusArea Focus area
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options
     * @return string
     */
    protected function renderSimpleImage(
        FileInterface $image,
        Area $cropArea,
        ?Area $focusArea,
        ImageRenderOptions $options
    ): string
    {
        $fallbackImage = $image;
        if (!$this->responsiveImageService->hasIgnoredFileExtension($image, $options->ignoreFileExtensions)) {
            $fallbackImage = $this->createFallbackImage($image, $cropArea, $options);
        }

        $this->tag = $this->imageTagRenderer->renderSimpleImageTag(
            $image,
            $fallbackImage,
            $focusArea,
            $this->tag,
            $options->withForceHeightAttribute(true)
        );

        return $this->tag->render();
    }


    /**
     * Renders an img tag with srcset.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area $cropArea Crop area
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $focusArea Focus area
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options
     * @return string
     */
    protected function renderImageWithSrcset(
        FileInterface $image,
        Area $cropArea,
        ?Area $focusArea,
        ImageRenderOptions $options
    ): string {

        $fallbackImage = $this->createFallbackImage($image, $cropArea, $options);

        $this->tag = $this->imageTagRenderer->renderImageTagWithSrcset(
            $image,
            $fallbackImage,
            $cropArea,
            $focusArea,
            $this->tag,
            $options
        );

        return $this->tag->render();
    }


    /**
     * Renders a picture element with source tags.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection $cropVariantCollection Crop variants
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area $cropArea Crop area for fallback image
     * @param \TYPO3\CMS\Core\Imaging\ImageManipulation\Area|null $focusArea Focus area
     * @param \Madj2k\MediaUtils\ValueObject\ImageRenderOptions $options
     * @return string
     */
    protected function renderPicture(
        FileInterface $image,
        CropVariantCollection $cropVariantCollection,
        Area $cropArea,
        ?Area $focusArea,
        ImageRenderOptions $options
    ): string {

        $fallbackImage = $this->createFallbackImage($image, $cropArea, $options);

        /** @var TagBuilder $pictureTag */
        $pictureTag = GeneralUtility::makeInstance(TagBuilder::class, 'picture');
        $fallbackTag = $this->tag;

        $this->tag = $this->pictureRenderer->renderPictureTag(
            $image,
            $fallbackImage,
            $cropVariantCollection,
            $focusArea,
            $pictureTag,
            $fallbackTag,
            $options
        );

        return $this->tag->render();
    }


    /**
     * Builds render options from ViewHelper arguments.
     *
     * @return \Madj2k\MediaUtils\ValueObject\ImageRenderOptions
     */
    protected function buildRenderOptions(): ImageRenderOptions
    {
        return new ImageRenderOptions(
            absoluteUri: (bool)($this->arguments['absolute'] ?? false),
            lazyload: (bool)$this->resolveArgument(name: 'lazyload'),
            loading: $this->resolveArgument(name: 'loading'),
            decoding: $this->resolveArgument(name: 'decoding'),
            fileExtension: $this->normalizeStringArgument($this->resolveArgument(name: 'fileExtension')),
            ignoreFileExtensions: $this->resolveArgument(name: 'ignoreFileExtensions'),
            renderHeightAttribute: (bool)$this->resolveArgument(name: 'renderHeightAttribute'),
            width: $this->normalizeStringArgument($this->resolveArgument(name: 'width', settingKey: 'dimensions.width')),
            height: $this->normalizeStringArgument($this->resolveArgument(name: 'height', settingKey:'dimensions.height')),
            minWidth: $this->normalizeIntArgument($this->resolveArgument(name: 'minWidth', settingKey:'dimensions.minWidth')),
            minHeight: $this->normalizeIntArgument($this->resolveArgument(name: 'minHeight', settingKey:'dimensions.minHeight')),
            maxWidth: $this->normalizeIntArgument($this->resolveArgument(name: 'maxWidth', settingKey:'dimensions.maxWidth')),
            maxHeight: $this->normalizeIntArgument($this->resolveArgument(name: 'maxHeight', settingKey:'dimensions.maxHeight')),
            cropVariant: (string)$this->resolveArgument(name: 'cropVariant'),
            srcset: $this->resolveArgument(name: 'srcset'),
            sizes: (string)$this->resolveArgument(name: 'sizes'),
            breakpoints: (array)$this->resolveArgument(name: 'breakpoints'),
            placeholderSize: $this->normalizeIntArgument($this->resolveArgument(name: 'placeholderSize', settingKey:'placeholder.size')),
            placeholderInline: (bool)$this->resolveArgument(name: 'placeholderInline', settingKey:'placeholder.inline'),
        );
    }


    /**
     * Resolves an argument using explicit ViewHelper value first and configured defaults second.
     *
     * @param string $name Argument name
     * @param string|null $settingKey Optional setting key
     * @return mixed
     */
    protected function resolveArgument(string $name, ?string $settingKey = null): mixed
    {
        $value = $this->arguments[$name] ?? null;
        if ($this->hasUsableArgumentValue($value)) {
            return $value;
        }

        $settingValue = $this->resolveSettingValue($settingKey ?? $name);

        if ($this->hasUsableArgumentValue($settingValue)) {
            return $settingValue;
        }

        if ($this->hasUsableArgumentValue($value)) {
            return $value;
        }

        return null;
    }


    /**
     * Resolves a setting value from configured settings.
     *
     * @param string $settingKey Setting key
     * @return mixed
     */
    protected function resolveSettingValue(string $settingKey): mixed
    {
        $settings = $this->arguments['settings'] ?? [];
        if (!is_array($settings) || $settings === []) {
            return null;
        }

        $settingsVariant = $this->normalizeStringArgument(
            $this->arguments['settingsVariant'] ?? null
        );

        $resolved = null;

        $paths = [
            sprintf('media.%s.%s', $this->settingsScope, $settingKey),
        ];

        if ($settingsVariant !== null) {
            $paths[] = sprintf(
                'media.%s.%s.%s',
                $settingsVariant,
                $this->settingsScope,
                $settingKey
            );
        }

        // check for path and merge configurations!
        foreach ($paths as $path) {
            $value = $this->getArrayValueByPath($settings, $path);

            if (is_array($value)) {
                $resolved = array_replace(
                    is_array($resolved) ? $resolved : [],
                    $value
                );
                continue;
            }

            if ($this->hasUsableArgumentValue($value)) {
                return $value;
            }
        }

        if ($resolved !== null) {
            return $resolved;
        }

        // this paths won't be merged
        // tx_smsresponsiveimages overrides txMediaUtils because this is the old default behaviour
        foreach ([
                     sprintf('tx_smsresponsiveimages.%s', $settingKey),
                     sprintf('txMediaUtils.responsiveImages.%s', $settingKey),
                 ] as $path) {
            $value = $this->getArrayValueByPath($settings, $path);

            if ($this->hasUsableArgumentValue($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Resolves the source image from ViewHelper arguments.
     *
     * @param string $src Source argument
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     */
    protected function resolveImage(string $src): FileInterface
    {
        return $this->imageService->getImage(
            $src,
            $this->arguments['image'],
            (bool)$this->arguments['treatIdAsReference']
        );
    }

    /**
     * Returns a nested array value by dot-separated path.
     *
     * @param array<string, mixed> $array Source array
     * @param string $path Dot-separated path
     * @return mixed
     */
    protected function getArrayValueByPath(array $array, string $path): mixed
    {
        $parts = GeneralUtility::trimExplode('.', $path, true);
        $value = $array;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }


    /**
     * Returns whether a value should be treated as explicitly configured.
     *
     * @param mixed $value Value
     * @return bool
     */
    protected function hasUsableArgumentValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return $value !== null && $value !== '';
    }


    /**
     * Returns whether a requested file extension is known to TYPO3.
     *
     * @param mixed $fileExtension File extension argument
     * @return bool
     */
    protected function isKnownFileExtension(mixed $fileExtension): bool
    {
        $fileExtension = $this->normalizeStringArgument($fileExtension);

        if ($fileExtension === null) {
            return true;
        }

        return GeneralUtility::inList((string)$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension);
    }


    /**
     * Normalizes optional integer arguments.
     *
     * Empty strings and null values are converted to null.
     *
     * @param mixed $value Raw argument value
     * @return int|null
     */
    protected function normalizeIntArgument(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }


    /**
     * Normalizes optional string arguments.
     *
     * Empty strings are converted to null.
     *
     * @param mixed $value Raw argument value
     * @return string|null
     */
    protected function normalizeStringArgument(mixed $value): ?string
    {
        $value = trim((string)$value);

        return $value === '' ? null : $value;
    }
}
