<?php

namespace Drupal\Tests\civicrm_attendance\Unit\Exception;

use Drupal\civicrm_attendance\Exception\CivicrmAttendanceException;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the base CivicrmAttendanceException class.
 *
 * @group civicrm_attendance
 * @coversDefaultClass \Drupal\civicrm_attendance\Exception\CivicrmAttendanceException
 */
class CivicrmAttendanceExceptionTest extends UnitTestCase {

  /**
   * Tests the constructor and basic functionality.
   *
   * @covers ::__construct
   * @covers ::getContext
   */
  public function testConstructorAndContext() {
    // Create test data.
    $message = 'Test error message';
    $context = ['module' => 'civicrm_attendance', 'operation' => 'test'];
    $code = 123;
    $previous = new \Exception('Previous exception');
    
    // Create exception instance.
    $exception = new CivicrmAttendanceException(
      $message,
      $context,
      $code,
      $previous
    );
    
    // Verify the exception properties.
    $this->assertEquals($message, $exception->getMessage());
    $this->assertEquals($code, $exception->getCode());
    $this->assertSame($previous, $exception->getPrevious());
    $this->assertEquals($context, $exception->getContext());
  }
  
  /**
   * Tests adding context information.
   *
   * @covers ::addContext
   * @covers ::getContext
   */
  public function testAddContext() {
    // Create exception instance.
    $exception = new CivicrmAttendanceException('Test message');
    
    // Initial context should be empty.
    $this->assertEquals([], $exception->getContext());
    
    // Add context information.
    $exception->addContext('module', 'civicrm_attendance');
    $this->assertEquals(['module' => 'civicrm_attendance'], $exception->getContext());
    
    // Add more context information.
    $exception->addContext('operation', 'test');
    $expected = [
      'module' => 'civicrm_attendance',
      'operation' => 'test',
    ];
    $this->assertEquals($expected, $exception->getContext());
    
    // Overwrite existing context.
    $exception->addContext('module', 'updated_value');
    $expected['module'] = 'updated_value';
    $this->assertEquals($expected, $exception->getContext());
  }
  
  /**
   * Tests the toLogString method.
   *
   * @covers ::toLogString
   */
  public function testToLogString() {
    // Create exception with context.
    $exception = new CivicrmAttendanceException(
      'Test log message',
      ['context_key' => 'context_value']
    );
    
    // Get the log string.
    $log_string = $exception->toLogString();
    
    // Verify log string contains essential information.
    $this->assertStringContainsString('Test log message', $log_string);
    $this->assertStringContainsString('CivicrmAttendanceException', $log_string);
    $this->assertStringContainsString('context_key', $log_string);
    $this->assertStringContainsString('context_value', $log_string);
  }
  
  /**
   * Tests exception inheritance.
   */
  public function testExceptionInheritance() {
    $exception = new CivicrmAttendanceException('Test message');
    
    // Verify the exception extends the PHP Exception class.
    $this->assertInstanceOf('Exception', $exception);
    
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