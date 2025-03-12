<?php

namespace Drupal\civicrm_attendance\Exception;

/**
 * Base exception class for all CiviCRM Attendance module exceptions.
 */
class CivicrmAttendanceException extends \Exception {

  /**
   * Additional context information for this exception.
   *
   * @var array
   */
  protected $context = [];

  /**
   * Constructs a CivicrmAttendanceException.
   *
   * @param string $message
   *   The Exception message.
   * @param array $context
   *   Additional context data for the exception.
   * @param int $code
   *   The Exception code.
   * @param \Throwable $previous
   *   The previous throwable used for exception chaining.
   */
  public function __construct($message = "", array $context = [], $code = 0, \Throwable $previous = NULL) {
    $this->context = $context;
    parent::__construct($message, $code, $previous);
  }

  /**
   * Get the context information for this exception.
   *
   * @return array
   *   The context data.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Add context information to this exception.
   *
   * @param string $key
   *   The context key.
   * @param mixed $value
   *   The context value.
   */
  public function addContext($key, $value) {
    $this->context[$key] = $value;
    return $this;
  }

  /**
   * Creates a loggable version of the exception with context information.
   *
   * @return string
   *   A string representation of the exception with context for logging.
   */
  public function toLogString() {
    $log_message = sprintf(
      "Exception: %s [%s]\nFile: %s:%d",
      $this->getMessage(),
      get_class($this),
      $this->getFile(),
      $this->getLine()
    );

    if (!empty($this->context)) {
      $log_message .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
    }

    return $log_message;
  }

}