<?php

namespace Drupal\civicrm_attendance\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\civicrm_attendance\Service\CiviCrmApiService;

/**
 * Configure CiviCRM Attendance settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The CiviCRM API service.
   *
   * @var \Drupal\civicrm_attendance\Service\CiviCrmApiService
   */
  protected $civiCrmApi;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\civicrm_attendance\Service\CiviCrmApiService $civicrm_api
   *   The CiviCRM API service.
   */
  public function __construct(CiviCrmApiService $civicrm_api) {
    $this->civiCrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civicrm_attendance.civicrm_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_attendance_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['civicrm_attendance.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('civicrm_attendance.settings');

    $form['relationship_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Relationship Types'),
      '#open' => TRUE,
    ];

    $relationship_types = $this->civiCrmApi->getRelationshipTypes();
    $form['relationship_types']['default_relationship_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default Relationship Types'),
      '#description' => $this->t('Select the relationship types that should be selected by default when adding a CiviCRM Relationship Events element to a webform.'),
      '#options' => $relationship_types,
      '#default_value' => $config->get('default_relationship_types') ?: [],
    ];

    $form['contact_subtypes'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact Subtypes'),
      '#open' => TRUE,
    ];

    $contact_subtypes = $this->civiCrmApi->getContactSubtypes();
    $form['contact_subtypes']['default_contact_subtypes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default Contact Subtypes'),
      '#description' => $this->t('Select the contact subtypes that should be selected by default when adding a CiviCRM Relationship Events element to a webform.'),
      '#options' => $contact_subtypes,
      '#default_value' => $config->get('default_contact_subtypes') ?: [],
    ];

    $form['participant_statuses'] = [
      '#type' => 'details',
      '#title' => $this->t('Participant Statuses'),
      '#open' => TRUE,
    ];

    $participant_statuses = $this->civiCrmApi->getParticipantStatuses();
    $form['participant_statuses']['default_participant_statuses'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default Participant Statuses'),
      '#description' => $this->t('Select the participant statuses that should be available by default when adding a CiviCRM Relationship Events element to a webform.'),
      '#options' => $participant_statuses,
      '#default_value' => $config->get('default_participant_statuses') ?: [],
    ];

    $form['display_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Settings'),
      '#open' => TRUE,
    ];

    $form['display_settings']['show_relationship_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show relationship information'),
      '#description' => $this->t('Display relationship type and dates by default.'),
      '#default_value' => $config->get('show_relationship_info') ?: TRUE,
    ];

    $form['display_settings']['allow_bulk_operations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow bulk operations'),
      '#description' => $this->t('Allow users to set all statuses at once by default.'),
      '#default_value' => $config->get('allow_bulk_operations') ?: TRUE,
    ];

    $form['display_settings']['show_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show search box'),
      '#description' => $this->t('Show search box to filter contacts by default.'),
      '#default_value' => $config->get('show_search') ?: TRUE,
    ];

    $form['display_settings']['items_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Items per page'),
      '#description' => $this->t('Number of contacts to display per page. Set to 0 to show all contacts.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => $config->get('items_per_page') ?: 25,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('civicrm_attendance.settings')
      ->set('default_relationship_types', $form_state->getValue('default_relationship_types'))
      ->set('default_contact_subtypes', $form_state->getValue('default_contact_subtypes'))
      ->set('default_participant_statuses', $form_state->getValue('default_participant_statuses'))
      ->set('show_relationship_info', $form_state->getValue('show_relationship_info'))
      ->set('allow_bulk_operations', $form_state->getValue('allow_bulk_operations'))
      ->set('show_search', $form_state->getValue('show_search'))
      ->set('items_per_page', $form_state->getValue('items_per_page'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
