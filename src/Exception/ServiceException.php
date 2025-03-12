<?php

namespace Drupal\civicrm_attendance\Exception;

/**
 * Exception thrown when a service encounters an error.
 */
class ServiceException extends CivicrmAttendanceException {

  /**
   * The service ID or name.
   *
   * @var string
   */
  protected $serviceId;

  /**
   * The method that was called.
   *
   * @var string
   */
  protected $method;

  /**
   * Constructs a ServiceException.
   *
   * @param string $message
   *   The exception message.
   * @param string $service_id
   *   The service ID or name.
   * @param string $method
   *   The method that was called.
   * @param array $context
   *   Additional context information.
   * @param \Exception|null $previous
   *   The previous exception, if any.
   * @param int $code
   *   The exception code.
   */
  public function __construct(
    $message,
    $service_id = '',
    $method = '',
    array $context = [],
    \Exception $previous = NULL,
    $code = 0
  ) {
    $this->serviceId = $service_id;
    $this->method = $method;

    // Add service information to context
    $context['service_id'] = $service_id;
    $context['method'] = $method;

    parent::__construct($message, $context, $code, $previous);
  }

  /**
   * Get the service ID or name.
   *
   * @return string
   *   The service ID or name.
   */
  public function getServiceId() {
    return $this->serviceId;
  }

  /**
   * Get the method that was called.
   *
   * @return string
   *   The method name.
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * Creates a new ServiceException for a service initialization error.
   *
   * @param string $service_id
   *   The service ID or name.
   * @param string $message
   *   The error message.
   * @param \Exception|null $previous
   *   The previous exception, if any.
   *
   * @return static
   *   A new ServiceException.
   */
  public static function initializationFailed($service_id, $message = '', \Exception $previous = NULL) {
    $error_message = sprintf('Failed to initialize service "%s"', $service_id);
    if (!empty($message)) {
      $error_message .= ': ' . $message;
    }
    
    return new static(
      $error_message,
      $service_id,
      'initialize',
      [],
      $previous
    );
  }

  /**
   * Creates a new ServiceException for a method call error.
   *
   * @param string $service_id
   *   The service ID or name.
   * @param string $method
   *   The method that was called.
   * @param string $message
   *   The error message.
   * @param array $context
   *   Additional context information.
   * @param \Exception|null $previous
   *   The previous exception, if any.
   *
   * @return static
   *   A new ServiceException.
   */
  public static function methodCallFailed($service_id, $method, $message = '', array $context = [], \Exception $previous = NULL) {
    $error_message = sprintf('Failed to call method "%s" on service "%s"', $method, $service_id);
    if (!empty($message)) {
      $error_message .= ': ' . $message;
    }
    
    return new static(
      $error_message,
      $service_id,
      $method,
      $context,
      $previous
    );
  }

  /**
   * Creates a new ServiceException for a dependency error.
   *
   * @param string $service_id
   *   The service ID or name.
   * @param string $dependency
   *   The name of the missing dependency.
   * @param \Exception|null $previous
   *   The previous exception, if any.
   *
   * @return static
   *   A new ServiceException.
   */
  public static function dependencyNotFound($service_id, $dependency, \Exception $previous = NULL) {
    return new static(
      sprintf('Dependency "%s" not found for service "%s"', $dependency, $service_id),
      $service_id,
      'construct',
      ['dependency' => $dependency],
      $previous
    );
  }

}