<?php

namespace Drupal\Tests\civicrm_attendance\Unit\Plugin\WebformElement;

use Drupal\civicrm_attendance\Plugin\WebformElement\CivicrmAttendanceElement;
use Drupal\civicrm_attendance\Service\CiviCrmApiService;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\WebformSubmissionInterface;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the CiviCRM Attendance webform element.
 *
 * @group civicrm_attendance
 * @coversDefaultClass \Drupal\civicrm_attendance\Plugin\WebformElement\CivicrmAttendanceElement
 */
class CivicrmAttendanceElementTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The webform element under test.
   *
   * @var \Drupal\civicrm_attendance\Plugin\WebformElement\CivicrmAttendanceElement
   */
  protected $element;

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $translation;

  /**
   * The mocked CiviCRM API service.
   *
   * @var \Drupal\civicrm_attendance\Service\CiviCrmApiService|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $civiCrmApi;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the translation service.
    $this->translation = $this->prophesize(TranslationInterface::class);
    $this->translation->translate(Argument::any())->willReturnArgument(0);
    $this->translation->translateString(Argument::any())->willReturn('Translated string');

    // Mock the CiviCRM API service.
    $this->civiCrmApi = $this->prophesize(CiviCrmApiService::class);

    // Create the element instance.
    $this->element = new CivicrmAttendanceElement([], 'civicrm_attendance_element', []);
    $this->element->setStringTranslation($this->translation->reveal());

    // Set up the container with the CiviCRM API service.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->translation->reveal());
    $container->set('civicrm_attendance.civicrm_api', $this->civiCrmApi->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests the default properties.
   *
   * @covers ::getDefaultProperties
   */
  public function testGetDefaultProperties() {
    $properties = $this->element->getDefaultProperties();

    // Check for required properties specific to the element.
    $this->assertArrayHasKey('relationship_types', $properties);
    $this->assertArrayHasKey('contact_subtypes', $properties);
    $this->assertArrayHasKey('events', $properties);
    $this->assertArrayHasKey('statuses', $properties);
    $this->assertArrayHasKey('allow_bulk_operations', $properties);
    $this->assertArrayHasKey('show_relationship_info', $properties);
    $this->assertArrayHasKey('show_search', $properties);
    $this->assertArrayHasKey('include_inactive_relationships', $properties);
    $this->assertArrayHasKey('event_start_date', $properties);
    $this->assertArrayHasKey('event_end_date', $properties);

    // Verify some default values.
    $this->assertEquals(TRUE, $properties['allow_bulk_operations']);
    $this->assertEquals(TRUE, $properties['show_relationship_info']);
    $this->assertEquals(TRUE, $properties['show_search']);
    $this->assertEquals(FALSE, $properties['include_inactive_relationships']);
    $this->assertEquals('', $properties['event_start_date']);
    $this->assertEquals('', $properties['event_end_date']);
  }

  /**
   * Tests the form building method.
   *
   * @covers ::form
   */
  public function testForm() {
    // Mock the required CiviCRM API service methods.
    $relationship_types = [
      1 => 'Parent of',
      2 => 'Employer of',
    ];
    $this->civiCrmApi->getRelationshipTypes()->willReturn($relationship_types);

    $contact_subtypes = [
      'Student' => 'Student',
      'Staff' => 'Staff',
    ];
    $this->civiCrmApi->getContactSubtypes()->willReturn($contact_subtypes);

    $events = [
      [
        'id' => 1,
        'title' => 'Test Event 1',
      ],
      [
        'id' => 2,
        'title' => 'Test Event 2',
      ],
    ];
    $this->civiCrmApi->getEvents()->willReturn($events);

    $participant_statuses = [
      1 => 'Registered',
      2 => 'Attended',
    ];
    $this->civiCrmApi->getParticipantStatuses()->willReturn($participant_statuses);

    // Build the form.
    $form = [];
    $form_state = new FormState();
    $result = $this->element->form($form, $form_state);

    // Check that the form contains the expected fieldsets.
    $this->assertArrayHasKey('civicrm', $result);
    $this->assertArrayHasKey('display', $result);
    $this->assertArrayHasKey('relationship_filtering', $result);
    $this->assertArrayHasKey('event_filtering', $result);

    // Check relationship types field.
    $this->assertArrayHasKey('relationship_types', $result['civicrm']);
    $this->assertEquals('checkboxes', $result['civicrm']['relationship_types']['#type']);
    $this->assertEquals($relationship_types, $result['civicrm']['relationship_types']['#options']);
    $this->assertEquals(TRUE, $result['civicrm']['relationship_types']['#required']);

    // Check contact subtypes field.
    $this->assertArrayHasKey('contact_subtypes', $result['civicrm']);
    $this->assertEquals('select', $result['civicrm']['contact_subtypes']['#type']);
    $this->assertEquals($contact_subtypes, $result['civicrm']['contact_subtypes']['#options']);
    $this->assertEquals(TRUE, $result['civicrm']['contact_subtypes']['#required']);

    // Check events field.
    $this->assertArrayHasKey('events', $result['civicrm']);
    $this->assertEquals('checkboxes', $result['civicrm']['events']['#type']);
    $this->assertEquals(TRUE, $result['civicrm']['events']['#required']);
    // Event options should be formatted with ID as key and title as value.
    $expected_event_options = [
      1 => 'Test Event 1',
      2 => 'Test Event 2',
    ];
    $this->assertEquals($expected_event_options, $result['civicrm']['events']['#options']);

    // Check statuses field.
    $this->assertArrayHasKey('statuses', $result['civicrm']);
    $this->assertEquals('checkboxes', $result['civicrm']['statuses']['#type']);
    $this->assertEquals($participant_statuses, $result['civicrm']['statuses']['#options']);
    $this->assertEquals(TRUE, $result['civicrm']['statuses']['#required']);

    // Check display settings.
    $this->assertEquals('checkbox', $result['display']['allow_bulk_operations']['#type']);
    $this->assertEquals(TRUE, $result['display']['allow_bulk_operations']['#default_value']);
    $this->assertEquals('checkbox', $result['display']['show_relationship_info']['#type']);
    $this->assertEquals(TRUE, $result['display']['show_relationship_info']['#default_value']);
    $this->assertEquals('checkbox', $result['display']['show_search']['#type']);
    $this->assertEquals(TRUE, $result['display']['show_search']['#default_value']);

    // Check relationship filtering settings.
    $this->assertEquals('checkbox', $result['relationship_filtering']['include_inactive_relationships']['#type']);
    $this->assertEquals(FALSE, $result['relationship_filtering']['include_inactive_relationships']['#default_value']);

    // Check event filtering settings.
    $this->assertEquals('date', $result['event_filtering']['event_start_date']['#type']);
    $this->assertEquals('date', $result['event_filtering']['event_end_date']['#type']);
  }

  /**
   * Tests the prepare method.
   *
   * @covers ::prepare
   */
  public function testPrepare() {
    // Create a mock for the webform submission.
    $webform_submission = $this->prophesize(WebformSubmissionInterface::class)->reveal();

    // Mock the CiviCRM API service methods.
    $current_contact_id = 123;
    $this->civiCrmApi->getCurrentContactId()->willReturn($current_contact_id);

    // Mock related contacts information.
    $contacts = [
      [
        'id' => 456,
        'display_name' => 'Test Contact 1',
        'relationship_type' => 'Parent of',
      ],
      [
        'id' => 789,
        'display_name' => 'Test Contact 2',
        'relationship_type' => 'Employer of',
      ],
    ];
    $relationship_type_ids = [1, 2];
    $contact_subtype = 'Student';
    $options = [
      'relationship_type_ids' => $relationship_type_ids,
      'contact_subtypes' => [$contact_subtype],
      'include_inactive' => FALSE,
    ];
    
    $this->civiCrmApi->getPeerContacts($current_contact_id, $options)->willReturn($contacts);

    // Mock events retrieval.
    $events = [
      1 => [
        'id' => 1,
        'title' => 'Test Event 1',
        'start_date' => '2025-01-01',
      ],
      2 => [
        'id' => 2,
        'title' => 'Test Event 2',
        'start_date' => '2025-02-01',
      ],
    ];
    $start_date = '2025-01-01';
    $end_date = '2025-12-31';
    $this->civiCrmApi->getEvents(TRUE, $start_date, $end_date)->willReturn($events);

    // Mock participant statuses.
    $statuses = [
      1 => 'Registered',
      2 => 'Attended',
    ];
    $this->civiCrmApi->getParticipantStatuses()->willReturn($statuses);

    // Mock participant record retrieval.
    $participant = [
      'id' => 101,
      'contact_id' => 456,
      'event_id' => 1,
      'status_id' => 1,
    ];
    $this->civiCrmApi->getParticipant(456, 1)->willReturn($participant);
    $this->civiCrmApi->getParticipant(456, 2)->willReturn([]);
    $this->civiCrmApi->getParticipant(789, 1)->willReturn([]);
    $this->civiCrmApi->getParticipant(789, 2)->willReturn([]);

    // Create the element to prepare.
    $element = [
      '#relationship_types' => [1 => 1, 2 => 2],
      '#contact_subtypes' => 'Student',
      '#events' => [1 => 1, 2 => 2],
      '#statuses' => [1 => 1, 2 => 2],
      '#allow_bulk_operations' => TRUE,
      '#show_relationship_info' => TRUE,
      '#show_search' => TRUE,
      '#include_inactive_relationships' => FALSE,
      '#event_start_date' => $start_date,
      '#event_end_date' => $end_date,
      '#webform_key' => 'test_element',
    ];

    // Call the prepare method.
    $this->element->prepare($element, $webform_submission);

    // Check that the element contains the expected prepared data.
    $this->assertEquals('civicrm_attendance_element', $element['#theme']);
    $this->assertArrayHasKey('#attached', $element);
    $this->assertContains('civicrm_attendance/civicrm_attendance', $element['#attached']['library']);

    // Check contacts data.
    $this->assertEquals($contacts, $element['#contacts']);

    // Check event list.
    $this->assertEquals($events, $element['#event_list']);

    // Check participant records.
    $this->assertArrayHasKey('#participant_records', $element);
    $this->assertArrayHasKey(456, $element['#participant_records']);
    $this->assertArrayHasKey(1, $element['#participant_records'][456]);
    $this->assertEquals($participant, $element['#participant_records'][456][1]);
  }

  /**
   * Tests the format methods.
   *
   * @covers ::formatHtmlItem
   * @covers ::formatTextItem
   */
  public function testFormatMethods() {
    // Create a mock for the webform submission.
    $webform_submission = $this->prophesize(WebformSubmissionInterface::class);
    
    // Create the element.
    $element = [
      '#webform_key' => 'test_element',
      '#events' => [1 => 1, 2 => 2],
    ];
    
    // Define the expected value to return from the submission.
    $value = [
      456 => [
        1 => 1, // Contact 456, Event 1, Status 1 (Registered)
        2 => 2, // Contact 456, Event 2, Status 2 (Attended)
      ],
      789 => [
        1 => 2, // Contact 789, Event 1, Status 2 (Attended)
      ],
    ];
    
    // Make the submission return our test value.
    $webform_submission->getElementData('test_element')->willReturn($value);

    // Mock the CiviCRM API service methods needed for formatting.
    $this->civiCrmApi->getEvents(FALSE)->willReturn([
      1 => ['id' => 1, 'title' => 'Test Event 1'],
      2 => ['id' => 2, 'title' => 'Test Event 2'],
    ]);
    
    $this->civiCrmApi->getParticipantStatuses()->willReturn([
      1 => 'Registered',
      2 => 'Attended',
    ]);
    
    // For the formatHtmlItem test, we need to mock parts of the Drupal renderer.
    // Since we can't easily test the rendering in a unit test, we'll verify
    // that the method returns a non-empty string for valid input.
    
    // Test the formatTextItem method with valid input.
    $result = $this->element->formatTextItem($element, $webform_submission->reveal());
    
    // Check that the result is a non-empty string.
    $this->assertIsString($result);
    $this->assertNotEmpty($result);
    
    // Test the formatTextItem method with empty input.
    $webform_submission->getElementData('test_element')->willReturn([]);
    $result = $this->element->formatTextItem($element, $webform_submission->reveal());
    $this->assertEquals('', $result);
  }

  /**
   * Tests the validation handler.
   *
   * @covers ::validateCivicrmAttendanceElement
   */
  public function testElementValidation() {
    // Create a form state.
    $form_state = new FormState();
    
    // Set up an element with a parent path.
    $element = [
      '#parents' => ['civicrm_attendance'],
    ];
    
    // Test with valid array input.
    $form_state->setValue(['civicrm_attendance'], [
      456 => [1 => 1, 2 => 2],
    ]);
    
    // Call the validate handler.
    CivicrmAttendanceElement::validateCivicrmAttendanceElement($element, $form_state, []);
    
    // Check that no errors were set.
    $this->assertFalse($form_state->hasAnyErrors());
    
    // Test with invalid input (not an array).
    $form_state = new FormState();
    $form_state->setValue(['civicrm_attendance'], 'not_an_array');
    
    // Call the validate handler.
    CivicrmAttendanceElement::validateCivicrmAttendanceElement($element, $form_state, []);
    
    // Check that an error was set.
    $this->assertTrue($form_state->hasAnyErrors());
  }

}
