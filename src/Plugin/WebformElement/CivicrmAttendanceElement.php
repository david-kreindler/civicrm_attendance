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
      'contact_subtypes' => '',  // Now a string (select) instead of array (checkboxes)
      'events' => [],
      'statuses' => [],
      'allow_bulk_operations' => TRUE,
      'show_relationship_info' => TRUE,
      'show_search' => TRUE,
      'include_inactive_relationships' => FALSE,
      'event_start_date' => '',
      'event_end_date' => '',
      'pagination' => TRUE,
      'items_per_page' => 25,
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
      '#description' => $this->t('Select which relationship types to include. The module will first find a contact (e.g., "Team") of the selected subtype that has one of these relationships with the current user, then find all other contacts who have any of these relationships with that same "Team" contact.'),
      '#options' => $relationship_types,
      '#required' => TRUE,
    ];

    $contact_subtypes = $civicrm_api->getContactSubtypes();
    $form['civicrm']['contact_subtypes'] = [
      '#type' => 'select',
      '#title' => $this->t('Contact subtype'),
      '#description' => $this->t('Select a contact subtype. The module will first find a contact of this subtype (e.g., "Team") that has a relationship with the current user, then find all contacts that have relationships with that same "Team" contact.'),
      '#options' => $contact_subtypes,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select -'),
    ];

    $events = $civicrm_api->getEvents();
    $event_options = [];
    foreach ($events as $event) {
      $event_options[$event['id']] = $event['title'];
    }

    $form['civicrm']['events'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Events'),
      '#description' => $this->t('Select which events to include in the attendance tracking form. Users will be able to set attendance status for these events.'),
      '#options' => $event_options,
      '#required' => TRUE,
    ];

    $statuses = $civicrm_api->getParticipantStatuses();
    $form['civicrm']['statuses'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Participant statuses'),
      '#description' => $this->t('Select which participant statuses users can assign to contacts. These will appear in the dropdown for each participant record.'),
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
    
    $form['display']['pagination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable pagination'),
      '#description' => $this->t('Enable pagination for large contact sets. Recommended for better performance when dealing with many contacts.'),
      '#default_value' => TRUE,
    ];
    
    $form['display']['items_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Contacts per page'),
      '#description' => $this->t('Number of contacts to display per page when pagination is enabled.'),
      '#default_value' => 25,
      '#min' => 5,
      '#max' => 250,
      '#states' => [
        'visible' => [
          ':input[name="properties[display][pagination]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    
    $form['relationship_filtering'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Relationship Filtering Settings'),
      '#description' => $this->t('Advanced settings for filtering contacts based on relationships.'),
    ];
    
    $form['relationship_filtering']['include_inactive_relationships'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include inactive relationships'),
      '#description' => $this->t('If checked, inactive relationships will also be considered when matching contacts.'),
      '#default_value' => FALSE,
    ];
    
    // Add date range options for events
    $form['event_filtering'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Event Filtering Settings'),
      '#description' => $this->t('Settings for filtering events by date range. This applies to all events selected above, and can be used to limit displayed events to a specific time period.'),
    ];
    
    $form['event_filtering']['event_start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event start date'),
      '#description' => $this->t('Only include events on or after this date. Leave blank for no start date restriction.'),
      '#default_value' => '',
    ];
    
    $form['event_filtering']['event_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event end date'),
      '#description' => $this->t('Only include events on or before this date. Leave blank for no end date restriction.'),
      '#default_value' => '',
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

    // Get contact subtype (now a single value from select, not array from checkboxes).
    $contact_subtype = !empty($element['#contact_subtypes']) ? $element['#contact_subtypes'] : '';
    $contact_subtype_keys = !empty($contact_subtype) ? [$contact_subtype] : [];

    // Get related contacts with relationship filtering.
    $filtering_options = [
      'relationship_type_ids' => $relationship_type_ids,
      'contact_subtypes' => $contact_subtype_keys,
      'include_inactive' => !empty($element['#include_inactive_relationships']),
      'use_pagination' => !empty($element['#pagination']),
      'items_per_page' => !empty($element['#items_per_page']) ? $element['#items_per_page'] : 25,
      'page' => isset($_GET['page']) ? (int) $_GET['page'] : 1,
    ];
    
    // Ensure page is at least 1
    if ($filtering_options['page'] < 1) {
      $filtering_options['page'] = 1;
    }
    
    $contacts_data = $civicrm_api->getPeerContacts($contact_id, $filtering_options);
    
    // Extract pagination metadata if it exists
    if (isset($contacts_data['pagination_metadata'])) {
      $element['#pagination_metadata'] = $contacts_data['pagination_metadata'];
      unset($contacts_data['pagination_metadata']);
      $element['#contacts'] = array_values($contacts_data);
    } else {
      $element['#contacts'] = $contacts_data;
    }

    // Get events, applying date range filtering if specified.
    $event_ids = array_keys(array_filter($element['#events']));
    $element['#event_list'] = [];
    if (!empty($event_ids)) {
      // Apply date range filtering if configured
      $start_date = !empty($element['#event_start_date']) ? $element['#event_start_date'] : NULL;
      $end_date = !empty($element['#event_end_date']) ? $element['#event_end_date'] : NULL;
      
      $events = $civicrm_api->getEvents(TRUE, $start_date, $end_date);
      
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
    $element['#pagination'] = $element['#pagination'] ?? TRUE;
    $element['#items_per_page'] = $element['#items_per_page'] ?? 25;
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
      '#include_inactive_relationships' => FALSE,
      '#event_start_date' => '',
      '#event_end_date' => '',
      '#pagination' => TRUE,
      '#items_per_page' => 25,
      '#pagination_metadata' => [
        'current_page' => 1,
        'items_per_page' => 25,
        'total_count' => 0,
        'total_pages' => 0,
      ],
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
