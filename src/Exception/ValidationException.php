<?php

namespace Drupal\civicrm_attendance\Exception;

/**
 * Exception thrown when input validation fails.
 */
class ValidationException extends CivicrmAttendanceException {

  /**
   * The validation errors.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * The field that failed validation.
   *
   * @var string
   */
  protected $field;

  /**
   * Constructs a ValidationException.
   *
   * @param string $message
   *   The exception message.
   * @param string $field
   *   The field that failed validation.
   * @param array $errors
   *   The validation errors.
   * @param array $context
   *   Additional context information.
   * @param \Exception|null $previous
   *   The previous exception, if any.
   * @param int $code
   *   The exception code.
   */
  public function __construct(
    $message,
    $field = '',
    array $errors = [],
    array $context = [],
    \Exception $previous = NULL,
    $code = 0
  ) {
    $this->field = $field;
    $this->errors = $errors;

    // Add errors to context
    $context['field'] = $field;
    $context['errors'] = $errors;

    parent::__construct($message, $context, $code, $previous);
  }

  /**
   * Get the validation errors.
   *
   * @return array
   *   The validation errors.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Get the field that failed validation.
   *
   * @return string
   *   The field name.
   */
  public function getField() {
    return $this->field;
  }

  /**
   * Returns a string representation of the validation errors.
   *
   * @return string
   *   A string representation of the validation errors.
   */
  public function getErrorsAsString() {
    $error_strings = [];
    foreach ($this->errors as $field => $errors) {
      if (is_array($errors)) {
        foreach ($errors as $error) {
          $error_strings[] = sprintf('%s: %s', $field, $error);
        }
      }
      else {
        $error_strings[] = sprintf('%s: %s', $field, $errors);
      }
    }
    return implode('; ', $error_strings);
  }

  /**
   * Creates a new ValidationException for a field validation error.
   *
   * @param string $field
   *   The field that failed validation.
   * @param string $error
   *   The validation error message.
   * @param array $context
   *   Additional context information.
   *
   * @return static
   *   A new ValidationException.
   */
  public static function invalidField($field, $error, array $context = []) {
    return new static(
      sprintf('Validation failed for field "%s": %s', $field, $error),
      $field,
      [$field => $error],
      $context
    );
  }

  /**
   * Creates a new ValidationException for a required field error.
   *
   * @param string $field
   *   The required field that was missing.
   * @param array $context
   *   Additional context information.
   *
   * @return static
   *   A new ValidationException.
   */
  public static function requiredField($field, array $context = []) {
    return new static(
      sprintf('Required field "%s" is missing or empty', $field),
      $field,
      [$field => 'This field is required'],
      $context
    );
  }

  /**
   * Creates a new ValidationException for multiple validation errors.
   *
   * @param array $errors
   *   An array of validation errors, keyed by field name.
   * @param array $context
   *   Additional context information.
   *
   * @return static
   *   A new ValidationException.
   */
  public static function multipleErrors(array $errors, array $context = []) {
    return new static(
      'Multiple validation errors occurred',
      '',
      $errors,
      $context
    );
  }

}