<?php

namespace Drupal\Tests\civicrm_attendance\Unit\Exception;

use Drupal\civicrm_attendance\Exception\ValidationException;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the ValidationException class.
 *
 * @group civicrm_attendance
 * @coversDefaultClass \Drupal\civicrm_attendance\Exception\ValidationException
 */
class ValidationExceptionTest extends UnitTestCase {

  /**
   * Tests the constructor and getter methods.
   *
   * @covers ::__construct
   * @covers ::getErrors
   * @covers ::getField
   */
  public function testConstructorAndGetters() {
    // Create test data.
    $message = 'Test validation error';
    $field = 'contact_id';
    $errors = ['contact_id' => 'Invalid format'];
    $context = ['source' => 'form_submission'];
    $previous = new \Exception('Previous exception');
    $code = 422;
    
    // Create exception instance.
    $exception = new ValidationException(
      $message,
      $field,
      $errors,
      $context,
      $previous,
      $code
    );
    
    // Verify the exception properties.
    $this->assertEquals($message, $exception->getMessage());
    $this->assertEquals($field, $exception->getField());
    $this->assertEquals($errors, $exception->getErrors());
    $this->assertEquals($code, $exception->getCode());
    $this->assertSame($previous, $exception->getPrevious());
    
    // Verify field and errors are added to context.
    $expected_context = $context;
    $expected_context['field'] = $field;
    $expected_context['errors'] = $errors;
    $this->assertEquals($expected_context, $exception->getContext());
  }
  
  /**
   * Tests the getErrorsAsString method.
   *
   * @covers ::getErrorsAsString
   */
  public function testGetErrorsAsString() {
    // Test with simple errors.
    $errors = [
      'contact_id' => 'Invalid format',
      'event_id' => 'Not found',
    ];
    $exception = new ValidationException('Test errors', '', $errors);
    $error_string = $exception->getErrorsAsString();
    
    // Both errors should be in the string with field names.
    $this->assertStringContainsString('contact_id: Invalid format', $error_string);
    $this->assertStringContainsString('event_id: Not found', $error_string);
    $this->assertStringContainsString(';', $error_string); // Errors should be separated
    
    // Test with nested array errors
    $nested_errors = [
      'contact_id' => ['Invalid format', 'Must be positive'],
      'event_id' => 'Not found',
    ];
    $exception = new ValidationException('Test nested', '', $nested_errors);
    $error_string = $exception->getErrorsAsString();
    
    // All errors should be in the string with field names.
    $this->assertStringContainsString('contact_id: Invalid format', $error_string);
    $this->assertStringContainsString('contact_id: Must be positive', $error_string);
    $this->assertStringContainsString('event_id: Not found', $error_string);
  }
  
  /**
   * Tests the invalidField static constructor.
   *
   * @covers ::invalidField
   */
  public function testInvalidField() {
    $field = 'contact_id';
    $error = 'Must be an integer';
    $context = ['source' => 'api'];
    
    // Create exception using static constructor.
    $exception = ValidationException::invalidField($field, $error, $context);
    
    // Verify the exception properties.
    $this->assertEquals($field, $exception->getField());
    $this->assertEquals([$field => $error], $exception->getErrors());
    
    // Verify context contains source.
    $expected_context = array_merge(
      $context,
      ['field' => $field, 'errors' => [$field => $error]]
    );
    $this->assertEquals($expected_context, $exception->getContext());
    
    // Verify the message contains field and error.
    $this->assertStringContainsString($field, $exception->getMessage());
    $this->assertStringContainsString($error, $exception->getMessage());
    $this->assertStringContainsString('Validation failed', $exception->getMessage());
  }
  
  /**
   * Tests the requiredField static constructor.
   *
   * @covers ::requiredField
   */
  public function testRequiredField() {
    $field = 'status_id';
    $context = ['form_id' => 'test_form'];
    
    // Create exception using static constructor.
    $exception = ValidationException::requiredField($field, $context);
    
    // Verify the exception properties.
    $this->assertEquals($field, $exception->getField());
    $this->assertEquals([$field => 'This field is required'], $exception->getErrors());
    
    // Verify the message indicates required field.
    $this->assertStringContainsString($field, $exception->getMessage());
    $this->assertStringContainsString('required', $exception->getMessage());
    $this->assertStringContainsString('missing', $exception->getMessage());
  }
  
  /**
   * Tests the multipleErrors static constructor.
   *
   * @covers ::multipleErrors
   */
  public function testMultipleErrors() {
    $errors = [
      'contact_id' => 'Invalid format',
      'event_id' => 'Not found',
      'status_id' => 'Invalid value',
    ];
    $context = ['operation' => 'create_participant'];
    
    // Create exception using static constructor.
    $exception = ValidationException::multipleErrors($errors, $context);
    
    // Verify the exception properties.
    $this->assertEquals('', $exception->getField()); // No specific field
    $this->assertEquals($errors, $exception->getErrors());
    
    // Verify the message mentions multiple errors.
    $this->assertStringContainsString('Multiple validation errors', $exception->getMessage());
    
    // Verify errors are in the error string.
    $error_string = $exception->getErrorsAsString();
    $this->assertStringContainsString('contact_id: Invalid format', $error_string);
    $this->assertStringContainsString('event_id: Not found', $error_string);
    $this->assertStringContainsString('status_id: Invalid value', $error_string);
  }
  
  /**
   * Tests exception inheritance.
   */
  public function testExceptionInheritance() {
    $exception = new ValidationException('Test message', 'field', ['field' => 'error']);
    
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