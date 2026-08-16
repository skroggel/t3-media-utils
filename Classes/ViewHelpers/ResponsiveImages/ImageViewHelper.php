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
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\Exception;

/**
 * Renders responsive image markup.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
final class ImageViewHelper extends AbstractResponsiveImageViewHelper
{
    public function __construct(
        ImageService $imageService,
        ResponsiveImageService $responsiveImageService,
        ImageTagRenderer $imageTagRenderer,
        PictureRenderer $pictureRenderer
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

        $this->registerArgument('usemap', 'string', 'Client-side image-map', false, '');
        $this->registerArgument('ismap', 'bool', 'Server-side image-map', false, false);
        $this->registerArgument('longdesc', 'string', 'Long description URL', false, '');

        $this->registerArgument('src', 'string', 'Path, FAL combined identifier or uid', false, '');
        $this->registerArgument('treatIdAsReference', 'bool', 'Treat src as sys_file_reference uid', false, false);
        $this->registerArgument('image', 'object', 'FAL File or FileReference');
    }


    /**
     * Renders the image markup.
     *
     * @throws Exception
     * @return string
     */
    public function render(): string
    {
        $src = (string)($this->arguments['src'] ?? '');

        // set fallback if given
        if ($src === '') {
            $src = (string)($this->resolveSettingValue('fallback') ?? '');
        }

        if (($src === '' && $this->arguments['image'] === null)) {
            throw new Exception('You must either specify a string src or a File object.', 1517766588);
        }

        try {
            $image = $this->resolveImage($src);

            return $this->renderImageFile(
                $image,
                ['class', 'id', 'style', 'title', 'alt', 'usemap', 'longdesc']
            );
        } catch (ResourceDoesNotExistException $exception) {
            throw new Exception($exception->getMessage(), 1678270145, $exception);
        } catch (\UnexpectedValueException $exception) {
            throw new Exception($exception->getMessage(), 1678270146, $exception);
        } catch (\RuntimeException $exception) {
            throw new Exception($exception->getMessage(), 1678270147, $exception);
        } catch (\InvalidArgumentException $exception) {
            throw new Exception($exception->getMessage(), 1678270148, $exception);
        }
    }

}
