<?php

namespace Drupal\Tests\civicrm_attendance\Unit\Exception;

use Drupal\civicrm_attendance\Exception\ParticipantException;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the ParticipantException class.
 *
 * @group civicrm_attendance
 * @coversDefaultClass \Drupal\civicrm_attendance\Exception\ParticipantException
 */
class ParticipantExceptionTest extends UnitTestCase {

  /**
   * Tests the constructor and getter methods.
   *
   * @covers ::__construct
   * @covers ::getContactId
   * @covers ::getEventId
   * @covers ::getStatusId
   */
  public function testConstructorAndGetters() {
    // Create test data.
    $message = 'Test participant error';
    $contact_id = 123;
    $event_id = 456;
    $status_id = 2;
    $context = ['source' => 'unit test'];
    $previous = new \Exception('Previous exception');
    $code = 100;
    
    // Create exception instance.
    $exception = new ParticipantException(
      $message,
      $contact_id,
      $event_id,
      $status_id,
      $context,
      $previous,
      $code
    );
    
    // Verify the exception properties.
    $this->assertEquals($message, $exception->getMessage());
    $this->assertEquals($contact_id, $exception->getContactId());
    $this->assertEquals($event_id, $exception->getEventId());
    $this->assertEquals($status_id, $exception->getStatusId());
    $this->assertEquals($code, $exception->getCode());
    $this->assertSame($previous, $exception->getPrevious());
    
    // Verify IDs are added to context.
    $expected_context = $context;
    $expected_context['contact_id'] = $contact_id;
    $expected_context['event_id'] = $event_id;
    $expected_context['status_id'] = $status_id;
    $this->assertEquals($expected_context, $exception->getContext());
  }
  
  /**
   * Tests the participantNotFound static constructor.
   *
   * @covers ::participantNotFound
   */
  public function testParticipantNotFound() {
    $contact_id = 123;
    $event_id = 456;
    
    // Create exception using static constructor.
    $exception = ParticipantException::participantNotFound($contact_id, $event_id);
    
    // Verify the exception properties.
    $this->assertEquals($contact_id, $exception->getContactId());
    $this->assertEquals($event_id, $exception->getEventId());
    $this->assertEquals(0, $exception->getStatusId()); // Default value
    
    // Verify the message contains the IDs.
    $this->assertStringContainsString((string) $contact_id, $exception->getMessage());
    $this->assertStringContainsString((string) $event_id, $exception->getMessage());
    $this->assertStringContainsString('not found', $exception->getMessage());
  }
  
  /**
   * Tests the creationFailed static constructor.
   *
   * @covers ::creationFailed
   */
  public function testCreationFailed() {
    $contact_id = 123;
    $event_id = 456;
    $status_id = 2;
    $error_message = 'API error occurred';
    $previous = new \Exception('Previous exception');
    
    // Create exception using static constructor with additional message.
    $exception = ParticipantException::creationFailed(
      $contact_id,
      $event_id,
      $status_id,
      $error_message,
      $previous
    );
    
    // Verify the exception properties.
    $this->assertEquals($contact_id, $exception->getContactId());
    $this->assertEquals($event_id, $exception->getEventId());
    $this->assertEquals($status_id, $exception->getStatusId());
    $this->assertSame($previous, $exception->getPrevious());
    
    // Verify the message contains the custom error.
    $this->assertStringContainsString('Failed to create', $exception->getMessage());
    $this->assertStringContainsString($error_message, $exception->getMessage());
    
    // Test without additional message.
    $exception = ParticipantException::creationFailed($contact_id, $event_id, $status_id);
    $this->assertStringContainsString('Failed to create', $exception->getMessage());
  }
  
  /**
   * Tests the updateFailed static constructor.
   *
   * @covers ::updateFailed
   */
  public function testUpdateFailed() {
    $contact_id = 123;
    $event_id = 456;
    $status_id = 2;
    $error_message = 'API error occurred';
    $previous = new \Exception('Previous exception');
    
    // Create exception using static constructor with additional message.
    $exception = ParticipantException::updateFailed(
      $contact_id,
      $event_id,
      $status_id,
      $error_message,
      $previous
    );
    
    // Verify the exception properties.
    $this->assertEquals($contact_id, $exception->getContactId());
    $this->assertEquals($event_id, $exception->getEventId());
    $this->assertEquals($status_id, $exception->getStatusId());
    $this->assertSame($previous, $exception->getPrevious());
    
    // Verify the message contains the custom error.
    $this->assertStringContainsString('Failed to update', $exception->getMessage());
    $this->assertStringContainsString($error_message, $exception->getMessage());
    
    // Test without additional message.
    $exception = ParticipantException::updateFailed($contact_id, $event_id, $status_id);
    $this->assertStringContainsString('Failed to update', $exception->getMessage());
  }
  
  /**
   * Tests exception inheritance.
   */
  public function testExceptionInheritance() {
    $exception = new ParticipantException('Test message', 1, 2, 3);
    
    // Verify the exception extends the base CivicrmAttendanceException.
    $this->assertInstanceOf('Drupal\civicrm_attendance\Exception\CivicrmAttendanceException', $exception);
    
    // Verify exception can be caught as a regular Exception.
    try {
      throw $exception;
      $this->fail('Exception should have been thrown');
    }
    catch (\Exception $e) {
      $this->assertSame($exception, $e);
    }
  }

}