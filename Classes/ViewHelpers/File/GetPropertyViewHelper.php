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
use \TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class GetProperty
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>
 * @package Madj2k_MediaUtils
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GetPropertyViewHelper extends AbstractViewHelper
{

    /**
     * Initialize arguments
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
		$this->registerArgument('file', 'mixed', 'The fileReference-object', false);
		$this->registerArgument('property', 'string', 'The property to load', false);

	}


	/**
	 * @param array $arguments
	 * @param \Closure $renderChildrenClosure
	 * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return string
	 */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {

		/** @var \TYPO3\CMS\Core\Resource\FileReference $fileReference */
		$fileReference = $arguments['file'];

		/** @var string $property */
		$property = $arguments['property']?: 'mime_type';

		if (
			($fileReference instanceof FileReference)
			|| (is_subclass_of($fileReference, FileReference::class))
			|| (is_subclass_of($fileReference, ExtbaseFileReference::class))
		){
			// special treatment because of encapsulation in Extbase
			$referenceObject = $fileReference;
			if (is_subclass_of($fileReference, ExtbaseFileReference::class)) {
				$referenceObject = $fileReference->getOriginalResource();
			}

			$getter = 'get' . GeneralUtility::underscoredToUpperCamelCase($property);
			if (method_exists($referenceObject, $getter)) {
				return $referenceObject->$getter();
			}

			if ($referenceObject->hasProperty($property)) {
				return $referenceObject->getProperty($property);
			}
		}

		return '';
	}

}
