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

use Madj2k\MediaUtils\Renderer\ImageTagRenderer;
use Madj2k\MediaUtils\Renderer\PictureRenderer;
use Madj2k\MediaUtils\Service\ResponsiveImageService;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3Fluid\Fluid\Core\Exception;

/**
 * Renders media markup with responsive image support.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class MediaViewHelper extends AbstractResponsiveImageViewHelper
{
    public function __construct(
        ImageService $imageService,
        ResponsiveImageService $responsiveImageService,
        ImageTagRenderer $imageTagRenderer,
        PictureRenderer $pictureRenderer,
        private readonly RendererRegistry $rendererRegistry
    ) {
        parent::__construct($imageService, $responsiveImageService, $imageTagRenderer, $pictureRenderer);
    }


    /**
     * Initializes all supported arguments.
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerCommonArguments();

        $this->registerArgument('file', 'object', 'File or FileReference');
        $this->registerArgument('src', 'string', 'Path, FAL combined identifier or uid', false, '');
        $this->registerArgument('treatIdAsReference', 'bool', 'Treat src as sys_file_reference uid', false, false);
        $this->registerArgument('additionalConfig', 'array', 'Additional renderer configuration', false, null);
    }


    /**
     * Renders media markup.
     *
     * @throws Exception
     * @return string
     */
    public function render(): string
    {
        $src = (string)($this->arguments['src'] ?? '');
        $file = $this->arguments['file'];

        if (($src === '' && $file === null) || ($src !== '' && $file !== null)) {
            throw new Exception('You must either specify a string src or a File object.', 1714472001);
        }

        try {
            $media = $this->resolveFile($src);

            if ($this->isImageFile($media)) {
                return $this->renderImageMedia($media);
            }

            return $this->renderNonImageMedia($media);

        } catch (ResourceDoesNotExistException $e) {
            throw new Exception($e->getMessage(), 1714472003, $e);
        } catch (\UnexpectedValueException $e) {
            throw new Exception($e->getMessage(), 1714472004, $e);
        } catch (\RuntimeException $e) {
            throw new Exception($e->getMessage(), 1714472005, $e);
        } catch (\InvalidArgumentException $e) {
            throw new Exception($e->getMessage(), 1714472006, $e);
        }
    }


    /**
     * Resolves the source file from ViewHelper arguments.
     *
     * @param string $src Source argument
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     */
    protected function resolveFile(string $src): FileInterface
    {
        return $this->imageService->getImage(
            $src,
            $this->arguments['file'],
            (bool)$this->arguments['treatIdAsReference']
        );
    }


    /**
     * Returns whether the given file is an image.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file File object
     * @return bool
     */
    protected function isImageFile(FileInterface $file): bool
    {
        return str_starts_with((string)$file->getMimeType(), 'image/');
    }


    /**
     * Renders image media using the responsive image pipeline.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $image Source image
     * @return string
     */
    protected function renderImageMedia(FileInterface $image): string
    {
        $this->settingsScope = 'image';

        return $this->renderImageFile(
            $image,
            ['class', 'id', 'style', 'title', 'alt']
        );
    }


    /**
     * Renders non-image media using TYPO3's renderer registry.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file Source file
     * @return string
     */
    protected function renderNonImageMedia(FileInterface $file): string
    {
        $this->settingsScope = 'video';

        $renderer = $this->rendererRegistry->getRenderer($file);

        if ($renderer === null) {
            return '';
        }

        $renderOptions = $this->buildRenderOptions();
        $width = $renderOptions->width ?? '';
        $height = $renderOptions->height ?? '';

        $options = $this->applyNativeVideoAttributes(
            $this->resolveArgument('additionalConfig') ?: [],
        );

        return $renderer->render($file, $width, $height, $options);
    }


    /**
     * Applies supported native video attributes to renderer options.
     *
     * @param null|array<string, mixed> $options Renderer options
     * @return array<string, mixed>
     */
    protected function applyNativeVideoAttributes(array $options = []): array
    {

        $attributes = $this->applyDefaultVideoAttributes();
        foreach (['controls', 'autoplay', 'muted', 'loop'] as $attribute) {
            if (isset($options[$attribute])) {
                $attributes[$attribute] = $options[$attribute];
                unset($options[$attribute]);
            }
        }

        $attributes['additionalConfig'] = [];
        foreach ($options as $attribute => $value) {
            $attributes['additionalConfig'][$attribute] = $value;
        }
        return $attributes;
    }


    /**
     * Returns default native video attributes from direct ViewHelper arguments.
     *
     * @return array<string, string>
     */
    protected function applyDefaultVideoAttributes(): array
    {
        $attributes = [];

        foreach (['class', 'dir', 'id', 'lang', 'style', 'title', 'accesskey', 'tabindex', 'onclick', 'controlsList', 'preload'] as $attribute) {
            $value = $this->normalizeStringArgument($this->arguments[$attribute] ?? null);

            if ($value !== null) {
                $attributes[$attribute] = $value;
            }
        }

        return $attributes;
    }
}
