<?php
namespace Madj2k\MediaUtils\ViewHelpers\File;

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

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class CropVariantClass
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CropVariantClassViewHelper extends AbstractViewHelper
{

    /**
     * Initialize arguments
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
		$this->registerArgument('file', \TYPO3\CMS\Core\Resource\FileReference::class, 'The fileReference-object', false);
		$this->registerArgument('bootstrap', 'bool', 'Whether to use bootstrap or normal classes', false);
	}


	/**
	 * @return string
	 */
    public function render(): string {

		/** @var \TYPO3\CMS\Core\Resource\FileReference $fileReference */
		$fileReference = $this->arguments['file'];
		$bootstrap = (bool) $this->arguments['bootstrap'];

		// get crop-property!
		if (
			($fileReference instanceof FileReference)
			&& ($cropVariants = $fileReference->getProperty('crop'))
			&& (is_string($cropVariants))
		){

			$firstVariant = (array) json_decode($cropVariants);
			if ($firstVariant) {

				$variantDetails = (array) current($firstVariant);
				if (
					($variantDetails)
					&& ($ratio = $variantDetails['selectedRatio'])
				){
					if ($bootstrap) {
						return 'ratio ratio-' . strtolower(preg_replace('#[^0-9a-zA-Z]#', 'x', $ratio));
					} else {
						return 'aspect-ratio-' . strtolower(preg_replace('#[^0-9a-zA-Z]#', 'x', $ratio));
					}
				}
			}
		}

		return '';
	}
}
