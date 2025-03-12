<?php

namespace Drupal\civicrm_attendance\Service;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Service for interacting with the CiviCRM API.
 *
 * This service manages all interactions with the CiviCRM API for the Attendance module.
 * It provides methods for retrieving contacts, relationships, events, and participant
 * records, as well as creating and updating participation statuses.
 *
 * The service implements robust error handling and logging to ensure reliable
 * operation and troubleshooting capabilities.
 */
class CiviCrmApiService {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new CiviCrmApiService object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    Civicrm $civicrm,
    ConfigFactoryInterface $config_factory,
    LoggerChannelInterface $logger
  ) {
    $this->currentUser = $current_user;
    $this->civicrm = $civicrm;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * Get the CiviCRM contact ID for the current user.
   *
   * @return int|false
   *   The contact ID or FALSE if not found.
   *
   * @throws \Drupal\civicrm_attendance\Exception\CiviCrmApiException
   *   When CiviCRM fails to initialize or when API communication fails.
   */
  public function getCurrentContactId() {
    try {
      $this->civicrm->initialize();
      $contact_id = \CRM_Core_Session::getLoggedInContactID();
      
      if (empty($contact_id)) {
        $contact_id = $this->getContactIdByUserId($this->currentUser->id());
        
        if (empty($contact_id)) {
          // Log a more informative message and still return false
          $this->logger->warning('No CiviCRM contact associated with user ID @uid', [
            '@uid' => $this->currentUser->id(),
          ]);
          return FALSE;
        }
      }
      
      return $contact_id;
    }
    catch (\Exception $e) {
      $error_message = 'Failed to get current contact ID: ' . $e->getMessage();
      $this->logger->error('@error', [
        '@error' => $error_message,
      ]);
      
      // Re-throw a custom exception if we need to handle it elsewhere
      if (defined('DRUPAL_TEST_IN_PROGRESS') && DRUPAL_TEST_IN_PROGRESS) {
        throw new \Drupal\civicrm_attendance\Exception\CiviCrmApiException(
          'CiviCRM API Error: ' . $error_message,
          'Contact',
          'getCurrentContactId',
          [],
          $e
        );
      }
      
      return FALSE;
    }
  }

  /**
   * Get the CiviCRM contact ID for a user ID.
   *
   * @param int $user_id
   *   The Drupal user ID.
   *
   * @return int|false
   *   The contact ID or FALSE if not found.
   */
  public function getContactIdByUserId($user_id) {
    try {
      $this->civicrm->initialize();
      
      $result = civicrm_api3('UFMatch', 'get', [
        'uf_id' => $user_id,
      ]);
      
      if (!empty($result['values'])) {
        $first = reset($result['values']);
        return $first['contact_id'];
      }
      
      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get contact ID for user ID @uid: @error', [
        '@uid' => $user_id,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Get available CiviCRM events, optionally filtered by date range.
   *
   * @param bool $active_only
   *   Whether to return only active events.
   * @param string|null $start_date
   *   Optional start date for filtering (YYYY-MM-DD format).
   * @param string|null $end_date
   *   Optional end date for filtering (YYYY-MM-DD format).
   *
   * @return array
   *   An array of events.
   */
  public function getEvents($active_only = TRUE, $start_date = NULL, $end_date = NULL) {
    try {
      $this->civicrm->initialize();
      
      $params = [
        'options' => ['limit' => 0],
        'return' => ['id', 'title', 'start_date', 'end_date', 'is_active'],
      ];
      
      if ($active_only) {
        $params['is_active'] = 1;
      }
      
      // Add date range filters if provided
      if (!empty($start_date)) {
        $params['start_date'] = ['>=' => $start_date];
      }
      
      if (!empty($end_date)) {
        // Filter events that start before or on the end date
        $params['start_date'] = array_merge($params['start_date'] ?? [], ['<=' => $end_date]);
      }
      
      $result = civicrm_api3('Event', 'get', $params);
      
      $events = [];
      foreach ($result['values'] as $event) {
        $events[$event['id']] = $event;
      }
      
      return $events;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get events: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Get participant statuses.
   *
   * @return array
   *   An array of participant statuses.
   */
  public function getParticipantStatuses() {
    try {
      $this->civicrm->initialize();
      
      $result = civicrm_api3('ParticipantStatusType', 'get', [
        'is_active' => 1,
        'options' => ['limit' => 0],
      ]);
      
      $statuses = [];
      foreach ($result['values'] as $status) {
        $statuses[$status['id']] = $status['label'];
      }
      
      return $statuses;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get participant statuses: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Get relationship types.
   *
   * @return array
   *   An array of relationship types.
   */
  public function getRelationshipTypes() {
    try {
      $this->civicrm->initialize();
      
      $result = civicrm_api3('RelationshipType', 'get', [
        'is_active' => 1,
        'options' => ['limit' => 0],
      ]);
      
      $types = [];
      foreach ($result['values'] as $type) {
        $types[$type['id']] = $type['label_a_b'];
      }
      
      return $types;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get relationship types: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Get contact subtypes.
   *
   * @return array
   *   An array of contact subtypes.
   */
  public function getContactSubtypes() {
    try {
      $this->civicrm->initialize();
      
      $result = civicrm_api3('ContactType', 'get', [
        'is_active' => 1,
        'parent_id' => ['IS NOT NULL' => 1],
        'options' => ['limit' => 0],
      ]);
      
      $subtypes = [];
      foreach ($result['values'] as $subtype) {
        $subtypes[$subtype['name']] = $subtype['label'];
      }
      
      return $subtypes;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get contact subtypes: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Get participant records for a contact and event.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param int $event_id
   *   The CiviCRM event ID.
   *
   * @return array
   *   The participant record or empty array if not found.
   */
  public function getParticipant($contact_id, $event_id) {
    try {
      $this->civicrm->initialize();
      
      $result = civicrm_api3('Participant', 'get', [
        'contact_id' => $contact_id,
        'event_id' => $event_id,
        'options' => ['limit' => 1],
      ]);
      
      if (!empty($result['values'])) {
        return reset($result['values']);
      }
      
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get participant record: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Create or update a participant record for a specific contact and event.
   *
   * This method handles both creation of new participant records and updates 
   * to existing ones. It performs parameter validation before making API calls 
   * and provides detailed logging for both successful operations and failures.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID of the participant.
   * @param int $event_id
   *   The CiviCRM event ID the contact is participating in.
   * @param int $status_id
   *   The participant status ID to assign (e.g., Registered, Attended, etc.).
   *
   * @return array|false
   *   The complete participant record array on success, or FALSE on failure.
   *
   * @throws \Drupal\civicrm_attendance\Exception\ParticipantException
   *   When a validation error occurs with the participant data.
   * @throws \Drupal\civicrm_attendance\Exception\CiviCrmApiException
   *   When the CiviCRM API returns an error during testing.
   */
  public function createParticipant($contact_id, $event_id, $status_id) {
    try {
      // Validate input parameters
      if (empty($contact_id)) {
        $this->logger->error('Cannot create participant: Contact ID is required');
        return FALSE;
      }
      
      if (empty($event_id)) {
        $this->logger->error('Cannot create participant: Event ID is required');
        return FALSE;
      }
      
      if (empty($status_id)) {
        $this->logger->error('Cannot create participant: Status ID is required');
        return FALSE;
      }
      
      $this->civicrm->initialize();
      
      // Check if a participant record already exists.
      $participant = $this->getParticipant($contact_id, $event_id);
      
      $params = [
        'contact_id' => $contact_id,
        'event_id' => $event_id,
        'status_id' => $status_id,
        'register_date' => date('YmdHis'), // Current date/time in CiviCRM format
        'source' => 'CiviCRM Attendance Module',
      ];
      
      $action = 'created';
      if (!empty($participant)) {
        // Update existing participant.
        $params['id'] = $participant['id'];
        $action = 'updated';
      }
      
      $result = civicrm_api3('Participant', 'create', $params);
      
      if (!empty($result['values'])) {
        $this->logger->notice('Successfully @action participant record ID: @id for contact: @contact_id, event: @event_id', [
          '@action' => $action,
          '@id' => reset($result['values'])['id'],
          '@contact_id' => $contact_id,
          '@event_id' => $event_id,
        ]);
        return reset($result['values']);
      }
      
      $this->logger->warning('Participant record creation returned empty result for contact: @contact_id, event: @event_id', [
        '@contact_id' => $contact_id,
        '@event_id' => $event_id,
      ]);
      return FALSE;
    }
    catch (\Exception $e) {
      $error_message = 'Failed to create participant record: ' . $e->getMessage();
      $context = [
        '@error' => $error_message,
        '@contact_id' => $contact_id,
        '@event_id' => $event_id,
        '@status_id' => $status_id,
      ];
      $this->logger->error('Failed to create participant record: @error. Contact: @contact_id, Event: @event_id, Status: @status_id', $context);
      
      // Re-throw for testing environments
      if (defined('DRUPAL_TEST_IN_PROGRESS') && DRUPAL_TEST_IN_PROGRESS) {
        throw new \Drupal\civicrm_attendance\Exception\CiviCrmApiException(
          $error_message,
          'Participant',
          'create',
          [
            'contact_id' => $contact_id,
            'event_id' => $event_id,
            'status_id' => $status_id,
          ],
          $e
        );
      }
      
      return FALSE;
    }
  }

  /**
   * Get peer contacts who have the same relationships to the same contacts as the current user.
   *
   * This method finds contacts who have the same relationship types to the same 
   * contacts of specified subtypes as the current user. It implements a sophisticated
   * relationship chain filtering algorithm that identifies contacts through their
   * indirect relationship to the current user.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID to use as the starting point.
   * @param array $options
   *   An array of filtering options:
   *   - relationship_type_ids: Array of relationship type IDs to filter by.
   *   - contact_subtypes: Array of contact subtypes to filter by.
   *   - include_inactive: Whether to include inactive relationships (default: FALSE).
   *   - contact_types: Contact types to include in results (default: ['Individual']).
   *   - limit: Maximum number of contacts to return (default: 0 for all).
   *
   * @return array
   *   An array of contact data with matching relationship patterns.
   *   Each contact contains standard CiviCRM contact fields plus relationship information.
   *
   * @throws \Drupal\civicrm_attendance\Exception\CiviCrmApiException
   *   When the CiviCRM API returns an error during testing.
   */
  public function getPeerContacts($contact_id, array $options = []) {
    // Default options
    $default_options = [
      'relationship_type_ids' => [],
      'contact_subtypes' => [],
      'include_inactive' => FALSE,
      'contact_types' => ['Individual'],
      'limit' => 0,
      'page' => 1,
      'items_per_page' => 25,
      'use_pagination' => TRUE,
      'count_total' => TRUE,
    ];
    
    $options = array_merge($default_options, $options);
    
    try {
      $this->civicrm->initialize();
      
      // Step 1: Get the user's relationships with contacts of specified subtypes
      $user_relationships = $this->getUserRelationshipTypes(
        $contact_id, 
        $options['relationship_type_ids'], 
        $options['contact_subtypes'],
        $options['include_inactive']
      );
      
      // If user doesn't have any relationships with contacts of the specified subtypes, return empty array
      if (empty($user_relationships)) {
        return [];
      }
      
      // Step 2: Find all contacts who have the same relationships to the same contacts
      $matching_contacts = $this->findContactsWithRelationships(
        $contact_id,
        $user_relationships,
        $options
      );
      
      // If pagination is enabled and metadata exists, keep it in the result
      if ($options["use_pagination"] && isset($matching_contacts["pagination_metadata"])) {
        $pagination_metadata = $matching_contacts["pagination_metadata"];
        unset($matching_contacts["pagination_metadata"]);
        
        // Rebuild contacts array with numeric indexes
        $contacts = [];
        foreach ($matching_contacts as $contact) {
          $contacts[] = $contact;
        }
        
        // Add pagination metadata back
        $contacts["pagination_metadata"] = $pagination_metadata;
        return $contacts;
      }
      
      // Otherwise, just return contacts with numeric indexes
      return array_values($matching_contacts);
    }
    catch (\Exception $e) {
      $error_message = 'Failed to get participant contacts: ' . $e->getMessage();
      $this->logger->error('@error', [
        '@error' => $error_message,
        '@contact_id' => $contact_id,
        '@options' => json_encode($options),
      ]);
      
      // Re-throw for testing environments
      if (defined('DRUPAL_TEST_IN_PROGRESS') && DRUPAL_TEST_IN_PROGRESS) {
        throw new \Drupal\civicrm_attendance\Exception\CiviCrmApiException(
          $error_message,
          'Contact', 
          'getPeerContacts',
          [
            'contact_id' => $contact_id,
            'options' => $options,
          ],
          $e
        );
      }
      
      return [];
    }
  }
  
  /**
   * Find contacts of specified subtype that the user has relationships with.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param array $relationship_type_ids
   *   An array of relationship type IDs to filter by.
   * @param array $contact_subtypes
   *   An array of contact subtypes to filter by.
   * @param bool $include_inactive
   *   Whether to include inactive relationships.
   *
   * @return array
   *   An array of contacts of the specified subtype that the user has relationships with.
   *   Each item contains: id, display_name, relationship_type_id
   */
  protected function getUserRelationshipTypes($contact_id, array $relationship_type_ids, array $contact_subtypes, $include_inactive = FALSE) {
    $subtype_contacts = [];
    
    // If no relationship types or subtypes specified, we can't filter
    if (empty($relationship_type_ids) || empty($contact_subtypes)) {
      return $subtype_contacts;
    }
    
    try {
      // Get all the user's relationships of the specified types (where user is contact_id_a)
      $params_a = [
        'contact_id_a' => $contact_id,
        'relationship_type_id' => ['IN' => $relationship_type_ids],
        'options' => ['limit' => 0],
        'return' => ['id', 'relationship_type_id', 'contact_id_b', 'is_active', 'start_date', 'end_date'],
      ];
      
      if (!$include_inactive) {
        $params_a['is_active'] = 1;
      }
      
      $user_relationships_a = civicrm_api3('Relationship', 'get', $params_a);
      
      // Get all the user's relationships of the specified types (where user is contact_id_b)
      $params_b = [
        'contact_id_b' => $contact_id,
        'relationship_type_id' => ['IN' => $relationship_type_ids],
        'options' => ['limit' => 0],
        'return' => ['id', 'relationship_type_id', 'contact_id_a', 'is_active', 'start_date', 'end_date'],
      ];
      
      if (!$include_inactive) {
        $params_b['is_active'] = 1;
      }
      
      $user_relationships_b = civicrm_api3('Relationship', 'get', $params_b);
      
      // Process relationships where user is contact_id_a
      foreach ($user_relationships_a['values'] as $relationship) {
        $related_contact_id = $relationship['contact_id_b'];
        $this->processSubtypeContact($related_contact_id, $relationship['relationship_type_id'], $contact_subtypes, $subtype_contacts);
      }
      
      // Process relationships where user is contact_id_b
      foreach ($user_relationships_b['values'] as $relationship) {
        $related_contact_id = $relationship['contact_id_a'];
        $this->processSubtypeContact($related_contact_id, $relationship['relationship_type_id'], $contact_subtypes, $subtype_contacts);
      }
      
      return $subtype_contacts;
    }
    catch (\Exception $e) {
      $error_message = 'Failed to get contact subtype relationships: ' . $e->getMessage();
      $context = [
        '@error' => $error_message,
        '@contact_id' => $contact_id,
        '@relationship_type_ids' => implode(',', $relationship_type_ids),
        '@contact_subtypes' => implode(',', $contact_subtypes),
      ];
      $this->logger->error('Failed to get contact subtype relationships: @error. Contact: @contact_id', $context);
      
      // Re-throw for testing environments
      if (defined('DRUPAL_TEST_IN_PROGRESS') && DRUPAL_TEST_IN_PROGRESS) {
        throw new \Drupal\civicrm_attendance\Exception\CiviCrmApiException(
          $error_message,
          'Relationship',
          'getUserRelationshipTypes',
          [
            'contact_id' => $contact_id,
            'relationship_type_ids' => $relationship_type_ids,
            'contact_subtypes' => $contact_subtypes,
          ],
          $e
        );
      }
      
      return [];
    }
  }
  
  /**
   * Process a contact to check if it has the specified subtype and add it to the result list.
   *
   * @param int $contact_id
   *   The contact ID to check.
   * @param int $relationship_type_id
   *   The relationship type ID.
   * @param array $contact_subtypes
   *   The contact subtypes to filter by.
   * @param array &$subtype_contacts
   *   The array to store results in.
   */
  private function processSubtypeContact($contact_id, $relationship_type_id, array $contact_subtypes, array &$subtype_contacts) {
    try {
      $related_contact = civicrm_api3('Contact', 'getsingle', [
        'id' => $contact_id,
        'return' => ['id', 'contact_sub_type', 'display_name'],
      ]);
      
      if (!empty($related_contact['contact_sub_type'])) {
        $contact_subtypes_arr = is_array($related_contact['contact_sub_type']) 
          ? $related_contact['contact_sub_type'] 
          : [$related_contact['contact_sub_type']];
        
        foreach ($contact_subtypes_arr as $subtype) {
          if (in_array($subtype, $contact_subtypes)) {
            // Add this contact to our results if not already there
            $key = $contact_id . '_' . $relationship_type_id;
            if (!isset($subtype_contacts[$key])) {
              $subtype_contacts[$key] = [
                'id' => $contact_id,
                'display_name' => $related_contact['display_name'],
                'relationship_type_id' => $relationship_type_id,
                'contact_subtype' => $subtype
              ];
            }
            break;
          }
        }
      }
    }
    catch (\Exception $e) {
      // Skip if we can't get contact details
    }
  }
  
  /**
   * Find contacts who have the specified relationship types with the same
   * contacts of specified subtype as the user.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID (to exclude from results).
   * @param array $subtype_contacts
   *   The contacts of specified subtype that the user has relationships with.
   * @param array $options
   *   The filtering options.
   *
   * @return array
   *   An array of matching contacts.
   */
  protected function findContactsWithRelationships($contact_id, array $subtype_contacts, array $options) {
    $matching_contacts = [];
    
    try {
      // If user has no relationships with contacts of the specified subtypes, return empty
      if (empty($subtype_contacts)) {
        return $matching_contacts;
      }
      
      // Extract the unique IDs of the specific contacts and relationship types
      $subtype_contact_ids = [];
      $relationship_type_ids = [];
      
      foreach ($subtype_contacts as $contact) {
        $subtype_contact_ids[] = $contact['id'];
        $relationship_type_ids[] = $contact['relationship_type_id'];
      }
      
      // Get unique IDs
      $subtype_contact_ids = array_unique($subtype_contact_ids);
      $relationship_type_ids = array_unique($relationship_type_ids);
      
      // Set up pagination parameters
      $api_options = ['sort' => 'sort_name'];
      
      // If pagination is enabled, set the appropriate options
      if ($options['use_pagination']) {
        $api_options['limit'] = $options['items_per_page'];
        $api_options['offset'] = ($options['page'] - 1) * $options['items_per_page'];
      } elseif ($options['limit'] > 0) {
        $api_options['limit'] = $options['limit'];
      } else {
        $api_options['limit'] = 0; // No limit
      }
      
      // Count total available contacts if required
      $total_count = 0;
      if ($options['count_total']) {
        $count_params = [
          'contact_type' => ['IN' => (array) $options['contact_types']],
          'is_deleted' => 0,
          'options' => ['limit' => 0],
          'return' => 'id',
        ];
        $count_result = civicrm_api3('Contact', 'getcount', $count_params);
        $total_count = $count_result;
      }
      
      // Get contacts in the system that match the specified contact types with pagination
      $contact_params = [
        'contact_type' => ['IN' => (array) $options['contact_types']],
        'is_deleted' => 0,
        'options' => $api_options,
        'return' => ['id', 'display_name', 'sort_name', 'email', 'contact_type', 'contact_sub_type'],
      ];
      
      $all_contacts = civicrm_api3('Contact', 'get', $contact_params);
      
      // Store pagination metadata only if pagination is enabled
      if ($options['use_pagination']) {
        $matching_contacts['pagination_metadata'] = [
          'current_page' => $options['page'],
          'items_per_page' => $options['items_per_page'],
          'total_count' => $total_count,
          'total_pages' => $total_count > 0 ? ceil($total_count / $options['items_per_page']) : 0,
        ];
      }
      
      // For each contact, check if they have relationships to the same subtype contacts as the user
      foreach ($all_contacts['values'] as $potential_contact) {
        // Skip the current user
        if ($potential_contact['id'] == $contact_id) {
          continue;
        }
        
        // Initialize array to track which relationships the contact has with the subtype contacts
        $matched_relationships = [];
        
        // For each subtype contact the user has a relationship with, check if this contact has a relationship too
        foreach ($subtype_contacts as $subtype_contact) {
          // Check for relationships where the contact is contact_id_a and subtype contact is contact_id_b
          try {
            $params_a = [
              'contact_id_a' => $potential_contact['id'],
              'contact_id_b' => $subtype_contact['id'],
              'relationship_type_id' => ['IN' => $relationship_type_ids],
              'options' => ['limit' => 1],
            ];
            
            if (!$options['include_inactive']) {
              $params_a['is_active'] = 1;
            }
            
            $relationship_a = civicrm_api3('Relationship', 'get', $params_a);
            
            if (!empty($relationship_a['values'])) {
              $relationship = reset($relationship_a['values']);
              
              // Get relationship type details for better display
              $rel_type = civicrm_api3('RelationshipType', 'getsingle', [
                'id' => $relationship['relationship_type_id'],
              ]);
              
              // Add this relationship to the list of matches
              $key = $relationship['relationship_type_id'] . '_' . $subtype_contact['id'];
              $matched_relationships[$key] = [
                'relationship_id' => $relationship['id'],
                'relationship_type_id' => $relationship['relationship_type_id'],
                'relationship_name' => $rel_type['label_a_b'],
                'is_contact_a' => TRUE,
                'contact_subtype' => $subtype_contact['contact_subtype'],
                'start_date' => $relationship['start_date'] ?? NULL,
                'end_date' => $relationship['end_date'] ?? NULL,
                'related_contact' => [
                  'id' => $subtype_contact['id'],
                  'display_name' => $subtype_contact['display_name'],
                ],
              ];
              
              // We found a relationship, no need to check the reverse direction
              continue;
            }
            
            // Check for relationships where the contact is contact_id_b and subtype contact is contact_id_a
            $params_b = [
              'contact_id_b' => $potential_contact['id'],
              'contact_id_a' => $subtype_contact['id'],
              'relationship_type_id' => ['IN' => $relationship_type_ids],
              'options' => ['limit' => 1],
            ];
            
            if (!$options['include_inactive']) {
              $params_b['is_active'] = 1;
            }
            
            $relationship_b = civicrm_api3('Relationship', 'get', $params_b);
            
            if (!empty($relationship_b['values'])) {
              $relationship = reset($relationship_b['values']);
              
              // Get relationship type details for better display
              $rel_type = civicrm_api3('RelationshipType', 'getsingle', [
                'id' => $relationship['relationship_type_id'],
              ]);
              
              // Add this relationship to the list of matches
              $key = $relationship['relationship_type_id'] . '_' . $subtype_contact['id'];
              $matched_relationships[$key] = [
                'relationship_id' => $relationship['id'],
                'relationship_type_id' => $relationship['relationship_type_id'],
                'relationship_name' => $rel_type['label_b_a'],
                'is_contact_a' => FALSE,
                'contact_subtype' => $subtype_contact['contact_subtype'],
                'start_date' => $relationship['start_date'] ?? NULL,
                'end_date' => $relationship['end_date'] ?? NULL,
                'related_contact' => [
                  'id' => $subtype_contact['id'],
                  'display_name' => $subtype_contact['display_name'],
                ],
              ];
            }
          }
          catch (\Exception $e) {
            // Skip if we can't get relationship details
            continue;
          }
        }
        
        // If contact has at least one matching relationship, include them in results
        if (!empty($matched_relationships)) {
          $matching_contacts[$potential_contact['id']] = [
            'id' => $potential_contact['id'],
            'display_name' => $potential_contact['display_name'],
            'sort_name' => $potential_contact['sort_name'],
            'email' => $potential_contact['email'] ?? '',
            'contact_type' => $potential_contact['contact_type'],
            'contact_sub_type' => $potential_contact['contact_sub_type'] ?? [],
            'relationships' => $matched_relationships,
          ];
        }
      }
      
      return $matching_contacts;
    }
    catch (\Exception $e) {
      $error_message = 'Failed to find contacts with relationships: ' . $e->getMessage();
      $context = [
        '@error' => $error_message,
        '@contact_id' => $contact_id,
        '@subtype_contacts_count' => count($subtype_contacts),
      ];
      $this->logger->error('Failed to find contacts with relationships: @error. Contact: @contact_id', $context);
      
      // Re-throw for testing environments
      if (defined('DRUPAL_TEST_IN_PROGRESS') && DRUPAL_TEST_IN_PROGRESS) {
        throw new \Drupal\civicrm_attendance\Exception\CiviCrmApiException(
          $error_message,
          'Contact',
          'findContactsWithRelationships',
          [
            'contact_id' => $contact_id,
            'subtype_contacts_count' => count($subtype_contacts),
          ],
          $e
        );
      }
      
      return [];
    }
  }
}
