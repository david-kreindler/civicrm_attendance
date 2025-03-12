<?php

namespace Drupal\Tests\civicrm_attendance\Unit\Form;

use Drupal\civicrm_attendance\Form\SettingsForm;
use Drupal\civicrm_attendance\Service\CiviCrmApiService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the CiviCRM Attendance settings form.
 *
 * @group civicrm_attendance
 * @coversDefaultClass \Drupal\civicrm_attendance\Form\SettingsForm
 */
class SettingsFormTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The mocked CiviCRM API service.
   *
   * @var \Drupal\civicrm_attendance\Service\CiviCrmApiService|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $civiCrmApi;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  /**
   * The form object under test.
   *
   * @var \Drupal\civicrm_attendance\Form\SettingsForm
   */
  protected $form;

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $translation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the CiviCRM API service.
    $this->civiCrmApi = $this->prophesize(CiviCrmApiService::class);

    // Mock the config factory.
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);

    // Mock the translation service.
    $this->translation = $this->prophesize(TranslationInterface::class);
    $this->translation->translate(Argument::any())->willReturnArgument(0);
    $this->translation->translateString(Argument::any())->willReturn('Translated string');

    // Create the settings form.
    $this->form = new SettingsForm($this->civiCrmApi->reveal());
    $this->form->setConfigFactory($this->configFactory->reveal());
    $this->form->setStringTranslation($this->translation->reveal());

    // Set up the container.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->translation->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests the form ID.
   *
   * @covers ::getFormId
   */
  public function testGetFormId() {
    $this->assertEquals('civicrm_attendance_settings', $this->form->getFormId());
  }

  /**
   * Tests the editable config names.
   *
   * @covers ::getEditableConfigNames
   */
  public function testGetEditableConfigNames() {
    $method = new \ReflectionMethod($this->form, 'getEditableConfigNames');
    $method->setAccessible(TRUE);
    $this->assertEquals(['civicrm_attendance.settings'], $method->invoke($this->form));
  }

  /**
   * Tests the form builder.
   *
   * @covers ::buildForm
   */
  public function testBuildForm() {
    // Mock the immutable config.
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('default_relationship_types')->willReturn([1, 2]);
    $config->get('default_contact_subtypes')->willReturn(['Student']);
    $config->get('default_participant_statuses')->willReturn([1, 2]);
    $config->get('show_relationship_info')->willReturn(TRUE);
    $config->get('allow_bulk_operations')->willReturn(TRUE);
    $config->get('show_search')->willReturn(TRUE);
    $config->get('items_per_page')->willReturn(25);

    // Set up the config factory to return the mocked config.
    $this->configFactory->get('civicrm_attendance.settings')->willReturn($config);

    // Mock the CiviCRM API service methods.
    $this->civiCrmApi->getRelationshipTypes()->willReturn([
      1 => 'Parent of',
      2 => 'Employer of',
    ]);
    
    $this->civiCrmApi->getContactSubtypes()->willReturn([
      'Student' => 'Student',
      'Staff' => 'Staff',
    ]);
    
    $this->civiCrmApi->getParticipantStatuses()->willReturn([
      1 => 'Registered',
      2 => 'Attended',
    ]);

    // Build the form.
    $form_state = new FormState();
    $form = [];
    $form = $this->form->buildForm($form, $form_state);

    // Assert that the form contains the expected elements.
    $this->assertArrayHasKey('relationship_types', $form);
    $this->assertArrayHasKey('contact_subtypes', $form);
    $this->assertArrayHasKey('participant_statuses', $form);
    $this->assertArrayHasKey('display_settings', $form);
    
    // Check relationship types fieldset
    $this->assertEquals('details', $form['relationship_types']['#type']);
    $this->assertArrayHasKey('default_relationship_types', $form['relationship_types']);
    $this->assertEquals('checkboxes', $form['relationship_types']['default_relationship_types']['#type']);
    $this->assertEquals([1, 2], $form['relationship_types']['default_relationship_types']['#default_value']);
    
    // Check contact subtypes fieldset
    $this->assertEquals('details', $form['contact_subtypes']['#type']);
    $this->assertArrayHasKey('default_contact_subtypes', $form['contact_subtypes']);
    $this->assertEquals('checkboxes', $form['contact_subtypes']['default_contact_subtypes']['#type']);
    $this->assertEquals(['Student'], $form['contact_subtypes']['default_contact_subtypes']['#default_value']);
    
    // Check participant statuses fieldset
    $this->assertEquals('details', $form['participant_statuses']['#type']);
    $this->assertArrayHasKey('default_participant_statuses', $form['participant_statuses']);
    $this->assertEquals('checkboxes', $form['participant_statuses']['default_participant_statuses']['#type']);
    $this->assertEquals([1, 2], $form['participant_statuses']['default_participant_statuses']['#default_value']);
    
    // Check display settings
    $this->assertEquals('details', $form['display_settings']['#type']);
    $this->assertEquals('checkbox', $form['display_settings']['show_relationship_info']['#type']);
    $this->assertEquals(TRUE, $form['display_settings']['show_relationship_info']['#default_value']);
    $this->assertEquals('checkbox', $form['display_settings']['allow_bulk_operations']['#type']);
    $this->assertEquals(TRUE, $form['display_settings']['allow_bulk_operations']['#default_value']);
    $this->assertEquals('checkbox', $form['display_settings']['show_search']['#type']);
    $this->assertEquals(TRUE, $form['display_settings']['show_search']['#default_value']);
    $this->assertEquals('number', $form['display_settings']['items_per_page']['#type']);
    $this->assertEquals(25, $form['display_settings']['items_per_page']['#default_value']);
  }

  /**
   * Tests the form submission.
   *
   * @covers ::submitForm
   */
  public function testSubmitForm() {
    // Create a form state with values.
    $form_state = new FormState();
    $form_state->setValues([
      'default_relationship_types' => [1, 3],
      'default_contact_subtypes' => ['Student', 'Staff'],
      'default_participant_statuses' => [1, 3],
      'show_relationship_info' => FALSE,
      'allow_bulk_operations' => FALSE,
      'show_search' => FALSE,
      'items_per_page' => 50,
    ]);

    // Mock the config object that is returned by the editable config factory.
    $config = $this->prophesize(\Drupal\Core\Config\Config::class);
    $config->set('default_relationship_types', [1, 3])->willReturn($config->reveal());
    $config->set('default_contact_subtypes', ['Student', 'Staff'])->willReturn($config->reveal());
    $config->set('default_participant_statuses', [1, 3])->willReturn($config->reveal());
    $config->set('show_relationship_info', FALSE)->willReturn($config->reveal());
    $config->set('allow_bulk_operations', FALSE)->willReturn($config->reveal());
    $config->set('show_search', FALSE)->willReturn($config->reveal());
    $config->set('items_per_page', 50)->willReturn($config->reveal());
    $config->save()->willReturn($config->reveal());

    // Set up the config factory.
    $this->configFactory->getEditable('civicrm_attendance.settings')->willReturn($config->reveal());
    
    // Submit the form.
    $form = [];
    $this->form->submitForm($form, $form_state);
    
    // Check that the config values were set and saved.
    $config->set('default_relationship_types', [1, 3])->shouldHaveBeenCalled();
    $config->set('default_contact_subtypes', ['Student', 'Staff'])->shouldHaveBeenCalled();
    $config->set('default_participant_statuses', [1, 3])->shouldHaveBeenCalled();
    $config->set('show_relationship_info', FALSE)->shouldHaveBeenCalled();
    $config->set('allow_bulk_operations', FALSE)->shouldHaveBeenCalled();
    $config->set('show_search', FALSE)->shouldHaveBeenCalled();
    $config->set('items_per_page', 50)->shouldHaveBeenCalled();
    $config->save()->shouldHaveBeenCalled();
  }

}
