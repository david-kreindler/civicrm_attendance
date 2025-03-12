<?php

namespace Drupal\civicrm_attendance\Service;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Service for interacting with the CiviCRM API.
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
   */
  public function getCurrentContactId() {
    try {
      $this->civicrm->initialize();
      $contact_id = \CRM_Core_Session::getLoggedInContactID();
      
      if (empty($contact_id)) {
        $contact_id = $this->getContactIdByUserId($this->currentUser->id());
      }
      
      return $contact_id;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get current contact ID: @error', [
        '@error' => $e->getMessage(),
      ]);
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
   * Get contacts who share relationship patterns with the given contact ID.
   *
   * This method identifies contacts who have the same relationship types
   * to contacts of specified subtypes as the current user, rather than
   * contacts who have a direct relationship with the current user.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param array $relationship_type_ids
   *   An array of relationship type IDs to filter by.
   * @param array $contact_subtypes
   *   An array of contact subtypes to filter by.
   * @param bool $include_direct_relationships
   *   Whether to include contacts with direct relationships to the user.
   *
   * @return array
   *   An array of contact data.
   *
   * @deprecated Use getPeerContacts() instead for more advanced filtering options.
   */
  public function getRelatedContacts($contact_id, array $relationship_type_ids = [], array $contact_subtypes = [], $include_direct_relationships = FALSE) {
    try {
      $this->civicrm->initialize();
      
      // Step 1: Find what relationships the current user has with contacts of specified subtypes
      $user_relationships_to_subtypes = [];
      
      if (!empty($contact_subtypes)) {
        // Get all the user's relationships of the specified types
        $user_relationships = civicrm_api3('Relationship', 'get', [
          'contact_id_a' => $contact_id,
          'relationship_type_id' => ['IN' => $relationship_type_ids],
          'is_active' => 1,
          'options' => ['limit' => 0],
        ]);
        
        // For each relationship, check if the contact at the other end has one of the specified subtypes
        foreach ($user_relationships['values'] as $relationship) {
          $related_contact_id = $relationship['contact_id_b'];
          
          try {
            $related_contact = civicrm_api3('Contact', 'getsingle', [
              'id' => $related_contact_id,
              'return' => ['id', 'contact_sub_type'],
            ]);
            
            if (!empty($related_contact['contact_sub_type'])) {
              $contact_subtypes_arr = is_array($related_contact['contact_sub_type']) 
                ? $related_contact['contact_sub_type'] 
                : [$related_contact['contact_sub_type']];
              
              foreach ($contact_subtypes_arr as $subtype) {
                if (in_array($subtype, $contact_subtypes)) {
                  // Record that the user has this type of relationship with a contact of this subtype
                  $key = $relationship['relationship_type_id'] . '_' . $subtype;
                  $user_relationships_to_subtypes[$key] = [
                    'relationship_type_id' => $relationship['relationship_type_id'],
                    'contact_subtype' => $subtype,
                  ];
                  break;
                }
              }
            }
          }
          catch (\Exception $e) {
            // Skip if we can't get contact details
            continue;
          }
        }
        
        // If the user doesn't have any relationships with contacts of the specified subtypes,
        // we can't match any contacts against that pattern
        if (empty($user_relationships_to_subtypes)) {
          return [];
        }
      }
      else {
        // If no subtypes specified, we can't filter based on shared relationship patterns
        return [];
      }
      
      // Step 2: Find all contacts who have the same relationship types 
      // to contacts of the same subtypes as the current user
      $matching_contacts = [];
      
      // Get all contacts in the system (this could be optimized with pagination for large systems)
      $all_contacts = civicrm_api3('Contact', 'get', [
        'contact_type' => 'Individual', // Assuming we're looking for individuals
        'is_deleted' => 0,
        'options' => ['limit' => 0],
        'return' => ['id', 'display_name', 'sort_name', 'email', 'contact_type', 'contact_sub_type'],
      ]);
      
      // For each contact, check if they have the same relationship patterns
      foreach ($all_contacts['values'] as $potential_contact) {
        // Skip the current user
        if ($potential_contact['id'] == $contact_id) {
          continue;
        }
        
        $contact_relationships = civicrm_api3('Relationship', 'get', [
          'contact_id_a' => $potential_contact['id'],
          'relationship_type_id' => ['IN' => $relationship_type_ids],
          'is_active' => 1,
          'options' => ['limit' => 0],
        ]);
        
        // Check if this contact has at least one matching relationship pattern
        $has_matching_pattern = FALSE;
        foreach ($contact_relationships['values'] as $relationship) {
          $related_id = $relationship['contact_id_b'];
          
          try {
            $related_contact = civicrm_api3('Contact', 'getsingle', [
              'id' => $related_id,
              'return' => ['id', 'contact_sub_type'],
            ]);
            
            if (!empty($related_contact['contact_sub_type'])) {
              $subtypes_arr = is_array($related_contact['contact_sub_type']) 
                ? $related_contact['contact_sub_type'] 
                : [$related_contact['contact_sub_type']];
              
              foreach ($subtypes_arr as $subtype) {
                $key = $relationship['relationship_type_id'] . '_' . $subtype;
                
                if (isset($user_relationships_to_subtypes[$key])) {
                  // This contact has the same relationship type to the same contact subtype as the user
                  $has_matching_pattern = TRUE;
                  
                  // Get the relationship type label
                  $rel_type = civicrm_api3('RelationshipType', 'getsingle', [
                    'id' => $relationship['relationship_type_id'],
                  ]);
                  
                  // Add this contact to our results with the relationship details
                  $matching_contacts[$potential_contact['id']] = [
                    'id' => $potential_contact['id'],
                    'display_name' => $potential_contact['display_name'],
                    'sort_name' => $potential_contact['sort_name'],
                    'email' => $potential_contact['email'] ?? '',
                    'contact_type' => $potential_contact['contact_type'],
                    'contact_sub_type' => $potential_contact['contact_sub_type'] ?? [],
                    'relationship' => [
                      'id' => $relationship['id'],
                      'relationship_type_id' => $relationship['relationship_type_id'],
                      'relationship_type' => $rel_type['label_a_b'],
                      'start_date' => $relationship['start_date'] ?? NULL,
                      'end_date' => $relationship['end_date'] ?? NULL,
                    ],
                  ];
                  break 2; // We found a match, move on to the next contact
                }
              }
            }
          }
          catch (\Exception $e) {
            // Skip if we can't get contact details
            continue;
          }
        }
      }
      
      return array_values($matching_contacts);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get related contacts: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Get available CiviCRM events.
   *
   * @param bool $active_only
   *   Whether to return only active events.
   *
   * @return array
   *   An array of events.
   */
  public function getEvents($active_only = TRUE) {
    try {
      $this->civicrm->initialize();
      
      $params = [
        'options' => ['limit' => 0],
        'return' => ['id', 'title', 'start_date', 'end_date', 'is_active'],
      ];
      
      if ($active_only) {
        $params['is_active'] = 1;
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
   * Create or update a participant record.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param int $event_id
   *   The CiviCRM event ID.
   * @param int $status_id
   *   The participant status ID.
   *
   * @return array|false
   *   The participant record or FALSE on failure.
   */
  public function createParticipant($contact_id, $event_id, $status_id) {
    try {
      $this->civicrm->initialize();
      
      // Check if a participant record already exists.
      $participant = $this->getParticipant($contact_id, $event_id);
      
      $params = [
        'contact_id' => $contact_id,
        'event_id' => $event_id,
        'status_id' => $status_id,
      ];
      
      if (!empty($participant)) {
        // Update existing participant.
        $params['id'] = $participant['id'];
      }
      
      $result = civicrm_api3('Participant', 'create', $params);
      
      if (!empty($result['values'])) {
        return reset($result['values']);
      }
      
      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to create participant record: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Get peer contacts based on institutional relationships.
   *
   * This method provides a more sophisticated filtering mechanism to identify
   * contacts who have the same types of institutional relationships as the current user.
   * It does not rely on direct relationships between contacts.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param array $options
   *   An array of filtering options:
   *   - relationship_type_ids: Array of relationship type IDs to filter by.
   *   - institution_subtypes: Array of institution contact subtypes to filter by.
   *   - require_all_patterns: If TRUE, contacts must match all the user's 
   *     relationship patterns, not just one.
   *   - match_roles: If TRUE, also match the "role" within relationships 
   *     (i.e., if the user is contact_id_a in a relationship, 
   *     peer contacts must also be contact_id_a).
   *   - include_inactive: Whether to include inactive relationships.
   *   - contact_types: Contact types to include (default: 'Individual').
   *   - limit: Maximum number of contacts to return (default: 0 for all).
   *
   * @return array
   *   An array of contact data with matching relationship patterns.
   */
  public function getPeerContacts($contact_id, array $options = []) {
    // Default options
    $default_options = [
      'relationship_type_ids' => [],
      'institution_subtypes' => [],
      'require_all_patterns' => FALSE,
      'match_roles' => TRUE,
      'include_inactive' => FALSE,
      'contact_types' => ['Individual'],
      'limit' => 0,
    ];
    
    $options = array_merge($default_options, $options);
    
    try {
      $this->civicrm->initialize();
      
      // Step 1: Find the user's relationship patterns with specified institution subtypes
      $user_relationship_patterns = $this->getUserRelationshipPatterns(
        $contact_id, 
        $options['relationship_type_ids'], 
        $options['institution_subtypes'],
        $options['include_inactive']
      );
      
      // If no patterns found, return empty array
      if (empty($user_relationship_patterns)) {
        return [];
      }
      
      // Step 2: Find all contacts with matching relationship patterns
      $matching_contacts = $this->findContactsWithMatchingPatterns(
        $contact_id,
        $user_relationship_patterns,
        $options
      );
      
      return array_values($matching_contacts);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get peer contacts: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }
  
  /**
   * Get the user's relationship patterns with specified institution subtypes.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param array $relationship_type_ids
   *   An array of relationship type IDs to filter by.
   * @param array $institution_subtypes
   *   An array of institution contact subtypes to filter by.
   * @param bool $include_inactive
   *   Whether to include inactive relationships.
   *
   * @return array
   *   An array of relationship patterns.
   */
  protected function getUserRelationshipPatterns($contact_id, array $relationship_type_ids, array $institution_subtypes, $include_inactive = FALSE) {
    $patterns = [];
    
    // If no relationship types or subtypes specified, we can't filter
    if (empty($relationship_type_ids) || empty($institution_subtypes)) {
      return $patterns;
    }
    
    try {
      // Get all the user's relationships of the specified types
      $params = [
        'contact_id_a' => $contact_id,
        'relationship_type_id' => ['IN' => $relationship_type_ids],
        'options' => ['limit' => 0],
        'return' => ['id', 'relationship_type_id', 'contact_id_b', 'is_active', 'start_date', 'end_date'],
      ];
      
      if (!$include_inactive) {
        $params['is_active'] = 1;
      }
      
      $user_relationships = civicrm_api3('Relationship', 'get', $params);
      
      if (empty($user_relationships['values'])) {
        // Also check for relationships where user is contact_id_b
        $params['contact_id_a'] = ['IS NULL' => 1];
        $params['contact_id_b'] = $contact_id;
        
        $user_relationships = civicrm_api3('Relationship', 'get', $params);
      }
      
      // For each relationship, check if the contact at the other end has one of the specified subtypes
      foreach ($user_relationships['values'] as $relationship) {
        $is_contact_a = isset($relationship['contact_id_a']) && $relationship['contact_id_a'] == $contact_id;
        $related_contact_id = $is_contact_a ? $relationship['contact_id_b'] : $relationship['contact_id_a'];
        
        try {
          $related_contact = civicrm_api3('Contact', 'getsingle', [
            'id' => $related_contact_id,
            'return' => ['id', 'contact_sub_type', 'display_name'],
          ]);
          
          if (!empty($related_contact['contact_sub_type'])) {
            $contact_subtypes_arr = is_array($related_contact['contact_sub_type']) 
              ? $related_contact['contact_sub_type'] 
              : [$related_contact['contact_sub_type']];
            
            foreach ($contact_subtypes_arr as $subtype) {
              if (in_array($subtype, $institution_subtypes)) {
                // Record that the user has this type of relationship with a contact of this subtype
                $pattern_key = $relationship['relationship_type_id'] . '_' . $subtype . '_' . ($is_contact_a ? 'a' : 'b');
                
                // Get relationship type details for better display
                $rel_type = civicrm_api3('RelationshipType', 'getsingle', [
                  'id' => $relationship['relationship_type_id'],
                ]);
                
                $patterns[$pattern_key] = [
                  'relationship_type_id' => $relationship['relationship_type_id'],
                  'relationship_name' => $is_contact_a ? $rel_type['label_a_b'] : $rel_type['label_b_a'],
                  'contact_subtype' => $subtype,
                  'is_contact_a' => $is_contact_a,
                  'related_contact' => [
                    'id' => $related_contact_id,
                    'display_name' => $related_contact['display_name'],
                  ],
                ];
                break;
              }
            }
          }
        }
        catch (\Exception $e) {
          // Skip if we can't get contact details
          continue;
        }
      }
      
      return $patterns;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get user relationship patterns: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }
  
  /**
   * Find contacts with matching relationship patterns.
   *
   * @param int $contact_id
   *   The CiviCRM contact ID.
   * @param array $user_patterns
   *   The user's relationship patterns.
   * @param array $options
   *   The filtering options.
   *
   * @return array
   *   An array of matching contacts.
   */
  protected function findContactsWithMatchingPatterns($contact_id, array $user_patterns, array $options) {
    $matching_contacts = [];
    
    try {
      // Get all contacts in the system that match the specified contact types
      $contact_params = [
        'contact_type' => ['IN' => (array) $options['contact_types']],
        'is_deleted' => 0,
        'options' => ['limit' => 0],
        'return' => ['id', 'display_name', 'sort_name', 'email', 'contact_type', 'contact_sub_type'],
      ];
      
      if ($options['limit'] > 0) {
        $contact_params['options']['limit'] = $options['limit'];
      }
      
      $all_contacts = civicrm_api3('Contact', 'get', $contact_params);
      
      // For each contact, check if they have matching relationship patterns
      foreach ($all_contacts['values'] as $potential_contact) {
        // Skip the current user
        if ($potential_contact['id'] == $contact_id) {
          continue;
        }
        
        // Check for relationships where the contact is either contact_id_a or contact_id_b
        $relationships_a = civicrm_api3('Relationship', 'get', [
          'contact_id_a' => $potential_contact['id'],
          'relationship_type_id' => ['IN' => $options['relationship_type_ids']],
          'is_active' => $options['include_inactive'] ? ['IN' => [0, 1]] : 1,
          'options' => ['limit' => 0],
        ]);
        
        $relationships_b = civicrm_api3('Relationship', 'get', [
          'contact_id_b' => $potential_contact['id'],
          'relationship_type_id' => ['IN' => $options['relationship_type_ids']],
          'is_active' => $options['include_inactive'] ? ['IN' => [0, 1]] : 1,
          'options' => ['limit' => 0],
        ]);
        
        // Combine relationships
        $contact_relationships = array_merge(
          $relationships_a['values'],
          $relationships_b['values']
        );
        
        // Initialize array to track which patterns the contact matches
        $matched_patterns = [];
        
        // For each relationship, check if it matches any of the user's patterns
        foreach ($contact_relationships as $relationship) {
          $is_contact_a = $relationship['contact_id_a'] == $potential_contact['id'];
          $related_id = $is_contact_a ? $relationship['contact_id_b'] : $relationship['contact_id_a'];
          
          // If we're matching roles and the contact's role doesn't match the user's role for this relationship type,
          // skip to the next relationship
          if ($options['match_roles']) {
            $found_matching_pattern = FALSE;
            foreach ($user_patterns as $pattern) {
              if ($pattern['relationship_type_id'] == $relationship['relationship_type_id'] && 
                  $pattern['is_contact_a'] == $is_contact_a) {
                $found_matching_pattern = TRUE;
                break;
              }
            }
            
            if (!$found_matching_pattern) {
              continue;
            }
          }
          
          try {
            $related_contact = civicrm_api3('Contact', 'getsingle', [
              'id' => $related_id,
              'return' => ['id', 'contact_sub_type', 'display_name'],
            ]);
            
            if (!empty($related_contact['contact_sub_type'])) {
              $subtypes_arr = is_array($related_contact['contact_sub_type']) 
                ? $related_contact['contact_sub_type'] 
                : [$related_contact['contact_sub_type']];
              
              foreach ($subtypes_arr as $subtype) {
                // Check if this relationship matches any of the user's patterns
                foreach ($user_patterns as $pattern_key => $pattern) {
                  if ($pattern['contact_subtype'] == $subtype && 
                      $pattern['relationship_type_id'] == $relationship['relationship_type_id'] &&
                      (!$options['match_roles'] || $pattern['is_contact_a'] == $is_contact_a)) {
                    
                    // Add this pattern to the list of matches
                    $matched_patterns[$pattern_key] = [
                      'relationship_id' => $relationship['id'],
                      'relationship_type_id' => $relationship['relationship_type_id'],
                      'contact_subtype' => $subtype,
                      'related_contact' => [
                        'id' => $related_id,
                        'display_name' => $related_contact['display_name'],
                      ],
                    ];
                    break;
                  }
                }
              }
            }
          }
          catch (\Exception $e) {
            // Skip if we can't get contact details
            continue;
          }
        }
        
        // Determine if this contact has enough matching patterns to be included
        $include_contact = FALSE;
        
        if ($options['require_all_patterns']) {
          // Contact must match all of the user's patterns
          $include_contact = count($matched_patterns) == count($user_patterns);
        }
        else {
          // Contact must match at least one of the user's patterns
          $include_contact = !empty($matched_patterns);
        }
        
        if ($include_contact) {
          // Add this contact to our results
          $matching_contacts[$potential_contact['id']] = [
            'id' => $potential_contact['id'],
            'display_name' => $potential_contact['display_name'],
            'sort_name' => $potential_contact['sort_name'],
            'email' => $potential_contact['email'] ?? '',
            'contact_type' => $potential_contact['contact_type'],
            'contact_sub_type' => $potential_contact['contact_sub_type'] ?? [],
            'matched_patterns' => $matched_patterns,
          ];
        }
      }
      
      return $matching_contacts;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to find contacts with matching patterns: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }
}
