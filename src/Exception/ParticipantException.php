<?php

namespace Drupal\civicrm_attendance\Exception;

/**
 * Exception thrown when there are issues with participant records.
 */
class ParticipantException extends CivicrmAttendanceException {

  /**
   * The CiviCRM contact ID.
   *
   * @var int
   */
  protected $contactId;

  /**
   * The CiviCRM event ID.
   *
   * @var int
   */
  protected $eventId;

  /**
   * The participant status ID.
   *
   * @var int
   */
  protected $statusId;

  /**
   * Constructs a ParticipantException.
   *
   * @param string $message
   *   The exception message.
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param int $event_id
   *   The CiviCRM event ID.
   * @param int $status_id
   *   The participant status ID.
   * @param array $context
   *   Additional context information.
   * @param \Exception|null $previous
   *   The previous exception, if any.
   * @param int $code
   *   The exception code.
   */
  public function __construct(
    $message,
    $contact_id = 0,
    $event_id = 0,
    $status_id = 0,
    array $context = [],
    \Exception $previous = NULL,
    $code = 0
  ) {
    $this->contactId = $contact_id;
    $this->eventId = $event_id;
    $this->statusId = $status_id;

    // Add IDs to context
    $context['contact_id'] = $contact_id;
    $context['event_id'] = $event_id;
    $context['status_id'] = $status_id;

    parent::__construct($message, $context, $code, $previous);
  }

  /**
   * Get the CiviCRM contact ID.
   *
   * @return int
   *   The contact ID.
   */
  public function getContactId() {
    return $this->contactId;
  }

  /**
   * Get the CiviCRM event ID.
   *
   * @return int
   *   The event ID.
   */
  public function getEventId() {
    return $this->eventId;
  }

  /**
   * Get the participant status ID.
   *
   * @return int
   *   The status ID.
   */
  public function getStatusId() {
    return $this->statusId;
  }

  /**
   * Creates a new ParticipantException for a "not found" error.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param int $event_id
   *   The CiviCRM event ID.
   *
   * @return static
   *   A new ParticipantException.
   */
  public static function participantNotFound($contact_id, $event_id) {
    return new static(
      sprintf('Participant record not found for contact ID %d and event ID %d', $contact_id, $event_id),
      $contact_id,
      $event_id
    );
  }

  /**
   * Creates a new ParticipantException for a creation error.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param int $event_id
   *   The CiviCRM event ID.
   * @param int $status_id
   *   The participant status ID.
   * @param string $message
   *   The error message.
   * @param \Exception|null $previous
   *   The previous exception, if any.
   *
   * @return static
   *   A new ParticipantException.
   */
  public static function creationFailed($contact_id, $event_id, $status_id, $message = '', \Exception $previous = NULL) {
    $error_message = 'Failed to create participant record';
    if (!empty($message)) {
      $error_message .= ': ' . $message;
    }
    
    return new static(
      $error_message,
      $contact_id,
      $event_id,
      $status_id,
      [],
      $previous
    );
  }

  /**
   * Creates a new ParticipantException for an update error.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param int $event_id
   *   The CiviCRM event ID.
   * @param int $status_id
   *   The participant status ID.
   * @param string $message
   *   The error message.
   * @param \Exception|null $previous
   *   The previous exception, if any.
   *
   * @return static
   *   A new ParticipantException.
   */
  public static function updateFailed($contact_id, $event_id, $status_id, $message = '', \Exception $previous = NULL) {
    $error_message = 'Failed to update participant record';
    if (!empty($message)) {
      $error_message .= ': ' . $message;
    }
    
    return new static(
      $error_message,
      $contact_id,
      $event_id,
      $status_id,
      [],
      $previous
    );
  }

}