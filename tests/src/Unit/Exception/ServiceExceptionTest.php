<?php

namespace Drupal\Tests\civicrm_attendance\Unit\Exception;

use Drupal\civicrm_attendance\Exception\ServiceException;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the ServiceException class.
 *
 * @group civicrm_attendance
 * @coversDefaultClass \Drupal\civicrm_attendance\Exception\ServiceException
 */
class ServiceExceptionTest extends UnitTestCase {

  /**
   * Tests the constructor and getter methods.
   *
   * @covers ::__construct
   * @covers ::getServiceId
   * @covers ::getMethod
   */
  public function testConstructorAndGetters() {
    // Create test data.
    $message = 'Test service error';
    $service_id = 'civicrm_api_service';
    $method = 'getContacts';
    $context = ['param1' => 'value1'];
    $previous = new \Exception('Previous exception');
    $code = 101;
    
    // Create exception instance.
    $exception = new ServiceException(
      $message,
      $service_id,
      $method,
      $context,
      $previous,
      $code
    );
    
    // Verify the exception properties.
    $this->assertEquals($message, $exception->getMessage());
    $this->assertEquals($service_id, $exception->getServiceId());
    $this->assertEquals($method, $exception->getMethod());
    $this->assertEquals($code, $exception->getCode());
    $this->assertSame($previous, $exception->getPrevious());
    
    // Verify service information is added to context.
    $expected_context = $context;
    $expected_context['service_id'] = $service_id;
    $expected_context['method'] = $method;
    $this->assertEquals($expected_context, $exception->getContext());
  }
  
  /**
   * Tests the initializationFailed static constructor.
   *
   * @covers ::initializationFailed
   */
  public function testInitializationFailed() {
    $service_id = 'civicrm_api_service';
    $error_message = 'Configuration missing';
    $previous = new \Exception('Previous exception');
    
    // Create exception using static constructor with additional message.
    $exception = ServiceException::initializationFailed(
      $service_id,
      $error_message,
      $previous
    );
    
    // Verify the exception properties.
    $this->assertEquals($service_id, $exception->getServiceId());
    $this->assertEquals('initialize', $exception->getMethod());
    $this->assertSame($previous, $exception->getPrevious());
    
    // Verify the message contains the custom error.
    $this->assertStringContainsString('Failed to initialize', $exception->getMessage());
    $this->assertStringContainsString($service_id, $exception->getMessage());
    $this->assertStringContainsString($error_message, $exception->getMessage());
    
    // Test without additional message.
    $exception = ServiceException::initializationFailed($service_id);
    $this->assertStringContainsString('Failed to initialize', $exception->getMessage());
    $this->assertStringContainsString($service_id, $exception->getMessage());
  }
  
  /**
   * Tests the methodCallFailed static constructor.
   *
   * @covers ::methodCallFailed
   */
  public function testMethodCallFailed() {
    $service_id = 'civicrm_api_service';
    $method = 'getParticipants';
    $error_message = 'Invalid parameters';
    $context = ['filter' => 'active'];
    $previous = new \Exception('Previous exception');
    
    // Create exception using static constructor with additional message.
    $exception = ServiceException::methodCallFailed(
      $service_id,
      $method,
      $error_message,
      $context,
      $previous
    );
    
    // Verify the exception properties.
    $this->assertEquals($service_id, $exception->getServiceId());
    $this->assertEquals($method, $exception->getMethod());
    $this->assertSame($previous, $exception->getPrevious());
    
    // Verify context is properly set.
    $expected_context = array_merge(
      $context,
      ['service_id' => $service_id, 'method' => $method]
    );
    $this->assertEquals($expected_context, $exception->getContext());
    
    // Verify the message contains the custom error.
    $this->assertStringContainsString('Failed to call method', $exception->getMessage());
    $this->assertStringContainsString($service_id, $exception->getMessage());
    $this->assertStringContainsString($method, $exception->getMessage());
    $this->assertStringContainsString($error_message, $exception->getMessage());
    
    // Test without additional message.
    $exception = ServiceException::methodCallFailed($service_id, $method);
    $this->assertStringContainsString('Failed to call method', $exception->getMessage());
    $this->assertStringContainsString($service_id, $exception->getMessage());
    $this->assertStringContainsString($method, $exception->getMessage());
  }
  
  /**
   * Tests the dependencyNotFound static constructor.
   *
   * @covers ::dependencyNotFound
   */
  public function testDependencyNotFound() {
    $service_id = 'civicrm_api_service';
    $dependency = 'civicrm_connection';
    $previous = new \Exception('Previous exception');
    
    // Create exception using static constructor.
    $exception = ServiceException::dependencyNotFound(
      $service_id,
      $dependency,
      $previous
    );
    
    // Verify the exception properties.
    $this->assertEquals($service_id, $exception->getServiceId());
    $this->assertEquals('construct', $exception->getMethod());
    $this->assertSame($previous, $exception->getPrevious());
    
    // Verify context contains dependency information.
    $expected_context = [
      'service_id' => $service_id,
      'method' => 'construct',
      'dependency' => $dependency,
    ];
    $this->assertEquals($expected_context, $exception->getContext());
    
    // Verify the message contains both service and dependency.
    $this->assertStringContainsString('Dependency', $exception->getMessage());
    $this->assertStringContainsString($service_id, $exception->getMessage());
    $this->assertStringContainsString($dependency, $exception->getMessage());
  }
  
  /**
   * Tests exception inheritance.
   */
  public function testExceptionInheritance() {
    $exception = new ServiceException('Test message', 'service', 'method');
    
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