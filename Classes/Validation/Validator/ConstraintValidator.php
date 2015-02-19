<?php
namespace Evoweb\StoreFinder\Validation\Validator;
/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Sebastian Fischer <typo3@evoweb.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A Uservalidator
 */
class ConstraintValidator extends \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator
	implements \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	static protected $instancesCurrentlyUnderValidation;

	/**
	 * Object manager
	 *
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Configuration manager
	 *
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * Settings
	 *
	 * @var array
	 */
	protected $settings = NULL;

	/**
	 * Configuration of the framework
	 *
	 * @var array
	 */
	protected $frameworkConfiguration = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Error\Result
	 * @inject
	 */
	protected $result;

	/**
	 * Validator resolver
	 *
	 * @var \Evoweb\StoreFinder\Validation\ValidatorResolver
	 * @inject
	 */
	protected $validatorResolver;

	/**
	 * Name of the current field to validate
	 *
	 * @var string
	 */
	protected $currentPropertyName = '';

	/**
	 * Options for the current validation
	 *
	 * @var array
	 */
	protected $currentValidatorOptions = array();

	/**
	 * Model that gets validated currently
	 *
	 * @var object
	 */
	protected $model;


	/**
	 * Inject of configuration manager
	 *
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
		$this->settings = $this->configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
		);
		$this->frameworkConfiguration = $this->configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
		);
	}

	/**
	 * Validate object
	 *
	 * @param mixed $object
	 * @throws \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException
	 * @throws \InvalidArgumentException
	 * @return boolean|\TYPO3\CMS\Extbase\Error\Result
	 */
	public function validate($object) {
		$messages = new \TYPO3\CMS\Extbase\Error\Result();
		if (self::$instancesCurrentlyUnderValidation === NULL) {
			self::$instancesCurrentlyUnderValidation = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		}
		if ($object === NULL) {
			return $messages;
		}
		if (!$this->canValidate($object)) {
			$messages->addError(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('error_notvalidatable', 'StoreFinder'), 1301599551
			);
			return $messages;
		}
		if (self::$instancesCurrentlyUnderValidation->contains($object)) {
			return $messages;
		} else {
			self::$instancesCurrentlyUnderValidation->attach($object);
		}

		$this->model = $object;

		$propertyValidators = $this->getValidationRulesFromSettings();
		foreach ($propertyValidators as $propertyName => $validatorsNames) {
			if (!property_exists($object, $propertyName)) {
				$messages->addError(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('error_notexists', 'StoreFinder'), 1301599575);
			} else {
				$this->currentPropertyName = $propertyName;
				$propertyValue = $this->getPropertyValue($object, $propertyName);
				$this->checkProperty($propertyValue, (array) $validatorsNames, $messages->forProperty($propertyName));
			}
		}

		self::$instancesCurrentlyUnderValidation->detach($object);
		return $messages;
	}

	/**
	 * Checks if the specified property of the given object is valid, and adds
	 * found errors to the $messages object.
	 *
	 * @param mixed $value The value to be validated
	 * @param array $validatorNames Contains an array with validator names
	 * @param \TYPO3\CMS\Extbase\Error\Result $messages the result object to
	 * 		which the validation errors should be added
	 * @return void
	 */
	protected function checkProperty($value, $validatorNames, \TYPO3\CMS\Extbase\Error\Result $messages) {
		foreach ($validatorNames as $validatorName) {
			$messages->merge($this->getValidator($validatorName)->validate($value));
		}
	}

	/**
	 * Check if validator can validate object
	 *
	 * @param object $object
	 * @return boolean
	 */
	public function canValidate($object) {
		return ($object instanceof \Evoweb\StoreFinder\Domain\Model\Constraint);
	}

	/**
	 * Get validation rules from settings
	 *
	 * @return array
	 */
	protected function getValidationRulesFromSettings() {
		return $this->settings['validation'];
	}

	/**
	 * Parse the rule and instanciate an validator with the name and the options
	 *
	 * @param string $rule
	 * @throws \InvalidArgumentException
	 * @return \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
	 */
	protected function getValidator($rule) {
		$currentValidator = $this->parseRule($rule);
		$this->currentValidatorOptions = (array) $currentValidator['validatorOptions'];

		/** @var $validatorObject \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator */
		$validatorObject = $this->validatorResolver->createValidator(
			$currentValidator['validatorName'],
			$this->currentValidatorOptions
		);

		if (method_exists($validatorObject, 'setModel')) {
			/** @noinspection PhpUndefinedMethodInspection */
			$validatorObject->setModel($this->model);
		}
		if (method_exists($validatorObject, 'setPropertyName')) {
			/** @noinspection PhpUndefinedMethodInspection */
			$validatorObject->setPropertyName($this->currentPropertyName);
		}

		return $validatorObject;
	}

	/**
	 * Parse rule
	 *
	 * @param string $rule
	 * @return array
	 */
	protected function parseRule($rule) {
		$parsedRules = $this->validatorResolver->getParsedValidatorAnnotation($rule);

		return current($parsedRules['validators']);
	}

	/**
	 * Merge error into local errors
	 *
	 * @param array $errors
	 * @return void
	 */
	protected function ___mergeErrors($errors) {
		/** @var $error \TYPO3\CMS\Extbase\Validation\Error */
		foreach ($errors as $error) {
			$localizedFieldName = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($this->currentPropertyName, 'SfRegister');

			$errorMessage = $error->getMessage();
			$localizedErrorCode = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('error_' . $error->getCode(), 'SfRegister');
			$localizedErrorMessage = $localizedErrorCode ? $localizedErrorCode : $errorMessage;

			$markers = array_merge((array) $localizedFieldName, $this->currentValidatorOptions);
			$messageWithReplacedMarkers = vsprintf($localizedErrorMessage, $markers);

			$this->___addErrorForProperty($this->currentPropertyName, $messageWithReplacedMarkers, $error->getCode());
		}
	}

	/**
	 * Add an error with message and code to the property errors
	 *
	 * @param string $propertyName name of the property to add the error to
	 * @param string $message Message to be shwon
	 * @param string $code Error code to identify the error
	 * @return void
	 */
	protected function ___addErrorForProperty($propertyName, $message, $code) {
		if (!$this->result->forProperty($propertyName)->hasErrors()) {
			/** @var $error \TYPO3\CMS\Extbase\Validation\PropertyError */
			$error = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Validation\\PropertyError', $propertyName);
			$error->addErrors(array(
				$this->objectManager->get('TYPO3\CMS\\Extbase\\Validation\\Error', $message, $code)
			));
			$this->result->addError($error);
		}
	}
}
