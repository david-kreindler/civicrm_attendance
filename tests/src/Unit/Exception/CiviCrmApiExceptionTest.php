<?php

namespace Drupal\Tests\civicrm_attendance\Unit\Exception;

use Drupal\civicrm_attendance\Exception\CiviCrmApiException;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the CiviCrmApiException class.
 *
 * @group civicrm_attendance
 * @coversDefaultClass \Drupal\civicrm_attendance\Exception\CiviCrmApiException
 */
class CiviCrmApiExceptionTest extends UnitTestCase {

  /**
   * Tests the constructor and getter methods.
   *
   * @covers ::__construct
   * @covers ::getEntity
   * @covers ::getAction
   * @covers ::getParams
   */
  public function testConstructorAndGetters() {
    // Create test data.
    $message = 'Test API error message';
    $entity = 'Contact';
    $action = 'get';
    $params = ['id' => 123, 'return' => ['display_name']];
    $previous = new \Exception('Previous exception');
    
    // Create exception instance.
    $exception = new CiviCrmApiException(
      $message,
      $entity,
      $action,
      $params,
      $previous
    );
    
    // Verify the exception properties.
    $this->assertEquals($message, $exception->getMessage());
    $this->assertEquals($entity, $exception->getEntity());
    $this->assertEquals($action, $exception->getAction());
    $this->assertEquals($params, $exception->getParams());
    $this->assertSame($previous, $exception->getPrevious());
  }
  
  /**
   * Tests the formatting of the error message.
   *
   * @covers ::__construct
   */
  public function testMessageFormatting() {
    // Create test data.
    $message = 'Test API error message';
    $entity = 'Contact';
    $action = 'get';
    $params = ['id' => 123, 'return' => ['display_name']];
    
    // Create exception instance.
    $exception = new CiviCrmApiException(
      $message,
      $entity,
      $action,
      $params
    );
    
    // Verify the message includes entity and action.
    $this->assertStringContainsString($entity, $exception->getMessage());
    $this->assertStringContainsString($action, $exception->getMessage());
  }
  
  /**
   * Tests exception inheritance.
   *
   * @covers ::__construct
   */
  public function testExceptionInheritance() {
    // Create test data.
    $message = 'Test API error message';
    $entity = 'Contact';
    $action = 'get';
    $params = ['id' => 123];
    
    // Create exception instance.
    $exception = new CiviCrmApiException(
      $message,
      $entity,
      $action,
      $params
    );
    
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
