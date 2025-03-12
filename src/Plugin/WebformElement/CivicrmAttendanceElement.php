<?php

namespace Drupal\civicrm_attendance\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a 'civicrm_attendance_element' element.
 *
 * @WebformElement(
 *   id = "civicrm_attendance_element",
 *   label = @Translation("CiviCRM Attendance Element"),
 *   description = @Translation("Provides a form element to manage event participation for related contacts."),
 *   category = @Translation("CiviCRM"),
 * )
 */
class CivicrmAttendanceElement extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'title' => '',
      'description' => '',
      'relationship_types' => [],
      'contact_subtypes' => [],
      'events' => [],
      'statuses' => [],
      'allow_bulk_operations' => TRUE,
      'show_relationship_info' => TRUE,
      'show_search' => TRUE,
      'require_all_patterns' => FALSE,
      'match_roles' => TRUE,
      'include_inactive_relationships' => FALSE,
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\civicrm_attendance\Service\CiviCrmApiService $civicrm_api */
    $civicrm_api = \Drupal::service('civicrm_attendance.civicrm_api');

    $form['civicrm'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CiviCRM Settings'),
    ];

    $relationship_types = $civicrm_api->getRelationshipTypes();
    $form['civicrm']['relationship_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Relationship types'),
      '#description' => $this->t('Select which relationship types to include.'),
      '#options' => $relationship_types,
      '#required' => TRUE,
    ];

    $contact_subtypes = $civicrm_api->getContactSubtypes();
    $form['civicrm']['contact_subtypes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Contact subtypes'),
      '#description' => $this->t('Select which contact subtypes to include. Only contacts with these subtypes will be shown.'),
      '#options' => $contact_subtypes,
      '#required' => FALSE,
    ];

    $events = $civicrm_api->getEvents();
    $event_options = [];
    foreach ($events as $event) {
      $event_options[$event['id']] = $event['title'];
    }

    $form['civicrm']['events'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Events'),
      '#description' => $this->t('Select which events to include.'),
      '#options' => $event_options,
      '#required' => TRUE,
    ];

    $statuses = $civicrm_api->getParticipantStatuses();
    $form['civicrm']['statuses'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Participant statuses'),
      '#description' => $this->t('Select which participant statuses to include.'),
      '#options' => $statuses,
      '#required' => TRUE,
    ];

    $form['display'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display Settings'),
    ];

    $form['display']['allow_bulk_operations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow bulk operations'),
      '#description' => $this->t('Allow users to set all statuses at once.'),
      '#default_value' => TRUE,
    ];

    $form['display']['show_relationship_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show relationship information'),
      '#description' => $this->t('Display relationship type and dates.'),
      '#default_value' => TRUE,
    ];

    $form['display']['show_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show search box'),
      '#description' => $this->t('Allow users to search contacts.'),
      '#default_value' => TRUE,
    ];
    
    $form['relationship_filtering'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Relationship Filtering Settings'),
      '#description' => $this->t('Advanced settings for filtering contacts based on institutional relationships.'),
    ];
    
    $form['relationship_filtering']['require_all_patterns'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require all relationship patterns'),
      '#description' => $this->t('If checked, contacts must match all the same relationship patterns as the current user, not just one.'),
      '#default_value' => FALSE,
    ];
    
    $form['relationship_filtering']['match_roles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Match relationship roles'),
      '#description' => $this->t('If checked, contacts must have the same role in relationships as the current user (e.g., if the user is an employee of an organization, only show other employees, not employers).'),
      '#default_value' => TRUE,
    ];
    
    $form['relationship_filtering']['include_inactive_relationships'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include inactive relationships'),
      '#description' => $this->t('If checked, inactive relationships will also be considered when matching contacts.'),
      '#default_value' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Attach the libraries.
    $element['#attached']['library'][] = 'civicrm_attendance/civicrm_attendance';

    // Add the theme hook.
    $element['#theme'] = 'civicrm_attendance_element';

    // Get the CiviCRM API service.
    /** @var \Drupal\civicrm_attendance\Service\CiviCrmApiService $civicrm_api */
    $civicrm_api = \Drupal::service('civicrm_attendance.civicrm_api');

    // Get the current user's contact ID.
    $contact_id = $civicrm_api->getCurrentContactId();

    // Get relationship types.
    $relationship_types = array_filter($element['#relationship_types']);
    $relationship_type_ids = array_keys($relationship_types);

    // Get contact subtypes.
    $contact_subtypes = array_filter($element['#contact_subtypes']);
    $contact_subtype_keys = array_keys($contact_subtypes);

    // Get related contacts with sophisticated relationship filtering.
    $filtering_options = [
      'relationship_type_ids' => $relationship_type_ids,
      'institution_subtypes' => $contact_subtype_keys,
      'require_all_patterns' => !empty($element['#require_all_patterns']),
      'match_roles' => !empty($element['#match_roles']),
      'include_inactive' => !empty($element['#include_inactive_relationships']),
    ];
    
    $element['#contacts'] = $civicrm_api->getPeerContacts($contact_id, $filtering_options);

    // Get events.
    $event_ids = array_keys(array_filter($element['#events']));
    $element['#event_list'] = [];
    if (!empty($event_ids)) {
      $events = $civicrm_api->getEvents();
      foreach ($event_ids as $event_id) {
        if (isset($events[$event_id])) {
          $element['#event_list'][$event_id] = $events[$event_id];
        }
      }
    }

    // Get statuses.
    $status_ids = array_keys(array_filter($element['#statuses']));
    $element['#status_list'] = [];
    if (!empty($status_ids)) {
      $statuses = $civicrm_api->getParticipantStatuses();
      foreach ($status_ids as $status_id) {
        if (isset($statuses[$status_id])) {
          $element['#status_list'][$status_id] = $statuses[$status_id];
        }
      }
    }

    // Load existing participant records.
    $element['#participant_records'] = [];
    foreach ($element['#contacts'] as $contact) {
      $element['#participant_records'][$contact['id']] = [];
      foreach ($element['#event_list'] as $event_id => $event) {
        $participant = $civicrm_api->getParticipant($contact['id'], $event_id);
        if (!empty($participant)) {
          $element['#participant_records'][$contact['id']][$event_id] = $participant;
        }
      }
    }

    // Set default value.
    if (empty($element['#default_value'])) {
      $element['#default_value'] = [];
    }

    // Set display settings.
    $element['#allow_bulk_operations'] = $element['#allow_bulk_operations'] ?? TRUE;
    $element['#show_relationship_info'] = $element['#show_relationship_info'] ?? TRUE;
    $element['#show_search'] = $element['#show_search'] ?? TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);
    $element['#element_validate'][] = [get_class($this), 'validateCivicrmAttendanceElement'];
  }

  /**
   * Form validation handler for CiviCRM Attendance Element.
   */
  public static function validateCivicrmAttendanceElement(&$element, FormStateInterface $form_state, &$complete_form) {
    // Validate that the submitted values match the expected structure.
    $value = $form_state->getValue($element['#parents']);
    if (!is_array($value)) {
      $form_state->setError($element, t('The submitted value is not a valid array.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    
    if (empty($value)) {
      return '';
    }

    $output = '';
    
    /** @var \Drupal\civicrm_attendance\Service\CiviCrmApiService $civicrm_api */
    $civicrm_api = \Drupal::service('civicrm_attendance.civicrm_api');
    
    // Get the event list.
    $event_ids = array_keys(array_filter($element['#events']));
    $events = [];
    if (!empty($event_ids)) {
      $all_events = $civicrm_api->getEvents(FALSE);
      foreach ($event_ids as $event_id) {
        if (isset($all_events[$event_id])) {
          $events[$event_id] = $all_events[$event_id];
        }
      }
    }
    
    // Get the statuses list.
    $statuses = $civicrm_api->getParticipantStatuses();
    
    // Format the participant data.
    $rows = [];
    foreach ($value as $contact_id => $contact_events) {
      foreach ($contact_events as $event_id => $status_id) {
        if (empty($status_id)) {
          continue;
        }
        
        // Get contact details.
        try {
          $civicrm_api->getCivicrm()->initialize();
          $contact = civicrm_api3('Contact', 'getsingle', [
            'id' => $contact_id,
            'return' => ['display_name'],
          ]);
          $contact_name = $contact['display_name'];
        }
        catch (\Exception $e) {
          $contact_name = t('Contact ID: @id', ['@id' => $contact_id]);
        }
        
        // Get event details.
        $event_name = isset($events[$event_id]) ? $events[$event_id]['title'] : t('Event ID: @id', ['@id' => $event_id]);
        
        // Get status details.
        $status_name = isset($statuses[$status_id]) ? $statuses[$status_id] : t('Status ID: @id', ['@id' => $status_id]);
        
        $rows[] = [
          'contact' => $contact_name,
          'event' => $event_name,
          'status' => $status_name,
        ];
      }
    }
    
    if (!empty($rows)) {
      $header = [
        'contact' => t('Contact'),
        'event' => t('Event'),
        'status' => t('Status'),
      ];
      
      $build = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => [
          'class' => ['civicrm-attendance-summary'],
        ],
      ];
      
      $output = \Drupal::service('renderer')->render($build);
    }
    
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    
    if (empty($value)) {
      return '';
    }

    $output = [];
    
    /** @var \Drupal\civicrm_attendance\Service\CiviCrmApiService $civicrm_api */
    $civicrm_api = \Drupal::service('civicrm_attendance.civicrm_api');
    
    // Get the event list.
    $event_ids = array_keys(array_filter($element['#events']));
    $events = [];
    if (!empty($event_ids)) {
      $all_events = $civicrm_api->getEvents(FALSE);
      foreach ($event_ids as $event_id) {
        if (isset($all_events[$event_id])) {
          $events[$event_id] = $all_events[$event_id];
        }
      }
    }
    
    // Get the statuses list.
    $statuses = $civicrm_api->getParticipantStatuses();
    
    // Format the participant data.
    foreach ($value as $contact_id => $contact_events) {
      foreach ($contact_events as $event_id => $status_id) {
        if (empty($status_id)) {
          continue;
        }
        
        // Get contact details.
        try {
          $civicrm_api->getCivicrm()->initialize();
          $contact = civicrm_api3('Contact', 'getsingle', [
            'id' => $contact_id,
            'return' => ['display_name'],
          ]);
          $contact_name = $contact['display_name'];
        }
        catch (\Exception $e) {
          $contact_name = t('Contact ID: @id', ['@id' => $contact_id]);
        }
        
        // Get event details.
        $event_name = isset($events[$event_id]) ? $events[$event_id]['title'] : t('Event ID: @id', ['@id' => $event_id]);
        
        // Get status details.
        $status_name = isset($statuses[$status_id]) ? $statuses[$status_id] : t('Status ID: @id', ['@id' => $status_id]);
        
        $output[] = t('Contact: @contact, Event: @event, Status: @status', [
          '@contact' => $contact_name,
          '@event' => $event_name,
          '@status' => $status_name,
        ]);
      }
    }
    
    return implode(PHP_EOL, $output);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $webform_submission->getElementData($element['#webform_key']);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = FALSE) {
    $value = $webform_submission->getElementData($element['#webform_key']);
    
    if (empty($value)) {
      return;
    }
    
    /** @var \Drupal\civicrm_attendance\Service\CiviCrmApiService $civicrm_api */
    $civicrm_api = \Drupal::service('civicrm_attendance.civicrm_api');
    
    // Create or update participant records.
    foreach ($value as $contact_id => $contact_events) {
      foreach ($contact_events as $event_id => $status_id) {
        if (empty($status_id)) {
          continue;
        }
        
        $civicrm_api->createParticipant($contact_id, $event_id, $status_id);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#theme' => 'civicrm_attendance_element',
      '#contacts' => [],
      '#event_list' => [],
      '#status_list' => [],
      '#participant_records' => [],
      '#allow_bulk_operations' => TRUE,
      '#show_relationship_info' => TRUE,
      '#show_search' => TRUE,
      '#require_all_patterns' => FALSE,
      '#match_roles' => TRUE,
      '#include_inactive_relationships' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    $title = $this->getAdminLabel($element);
    
    $selectors = [];
    $selectors[":input[name=\"{$element['#webform_key']}\"]"] = $title;
    
    return $selectors;
  }

}
