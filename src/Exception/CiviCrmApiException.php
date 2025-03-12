<?php

namespace Drupal\civicrm_attendance\Exception;

/**
 * Exception thrown when CiviCRM API operations fail.
 */
class CiviCrmApiException extends CivicrmAttendanceException {

  /**
   * The CiviCRM API entity type (e.g., 'Contact', 'Participant').
   *
   * @var string
   */
  protected $entity;

  /**
   * The CiviCRM API action (e.g., 'get', 'create').
   *
   * @var string
   */
  protected $action;

  /**
   * The parameters that were passed to the API call.
   *
   * @var array
   */
  protected $params;

  /**
   * Constructs a CiviCrmApiException.
   *
   * @param string $message
   *   The exception message.
   * @param string $entity
   *   The CiviCRM API entity type.
   * @param string $action
   *   The CiviCRM API action.
   * @param array $params
   *   The parameters that were passed to the API call.
   * @param \Exception|null $previous
   *   The previous exception, if any.
   * @param int $code
   *   The exception code.
   */
  public function __construct(
    $message,
    $entity = '',
    $action = '',
    array $params = [],
    \Exception $previous = NULL,
    $code = 0
  ) {
    $this->entity = $entity;
    $this->action = $action;
    // Remove any sensitive data before storing
    $this->params = $this->sanitizeParams($params);

    // Add context to the message
    $context = '';
    if (!empty($entity) && !empty($action)) {
      $context = " [Entity: $entity, Action: $action]";
    }

    parent::__construct($message . $context, $code, $previous);
  }

  /**
   * Get the CiviCRM entity type.
   *
   * @return string
   *   The entity type.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Get the CiviCRM action.
   *
   * @return string
   *   The action.
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * Get the parameters that were passed to the API call.
   *
   * @return array
   *   The parameters.
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * Sanitize parameters to remove sensitive data.
   *
   * @param array $params
   *   The parameters to sanitize.
   *
   * @return array
   *   The sanitized parameters.
   */
  protected function sanitizeParams(array $params) {
    $sensitive_keys = [
      'api_key',
      'password',
      'token',
      'secret',
      'credentials',
      'auth',
    ];

    $sanitized = $params;
    foreach ($sensitive_keys as $key) {
      if (isset($sanitized[$key])) {
        $sanitized[$key] = '***REDACTED***';
      }
    }

    // Recursively sanitize nested arrays
    foreach ($sanitized as $key => $value) {
      if (is_array($value)) {
        $sanitized[$key] = $this->sanitizeParams($value);
      }
    }

    return $sanitized;
  }

  /**
   * Creates a new CiviCrmApiException from an API error.
   *
   * @param string $message
   *   The error message.
   * @param string $entity
   *   The CiviCRM API entity type.
   * @param string $action
   *   The CiviCRM API action.
   * @param array $params
   *   The parameters that were passed to the API call.
   * @param \Exception|null $previous
   *   The previous exception, if any.
   *
   * @return static
   *   A new CiviCrmApiException.
   */
  public static function fromApiError(
    $message,
    $entity = '',
    $action = '',
    array $params = [],
    \Exception $previous = NULL
  ) {
    return new static($message, $entity, $action, $params, $previous);
  }

}
