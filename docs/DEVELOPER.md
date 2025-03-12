# CiviCRM Attendance: Developer Documentation

This document provides detailed information for developers working with or extending the CiviCRM Attendance module.

## Architecture Overview

The CiviCRM Attendance module is structured around several key components:

### Key Components

1. **CiviCrmApiService**: Core service handling all interactions with the CiviCRM API
2. **CivicrmAttendanceElement**: Webform element for rendering and processing attendance data
3. **SettingsForm**: Configuration form for module-wide settings
4. **ReportsController**: Basic controller for reporting features
5. **Exception Handling**: A comprehensive system of custom exceptions for robust error management

### Service Layer

The service layer abstracts API interactions through the `CiviCrmApiService` class, providing:

- Simplified methods for common operations
- Error handling and logging
- Performance optimization for complex queries

### Form Elements

The module extends Drupal's form API with:

- Custom webform element for attendance tracking
- Settings forms for configuration
- Form validation and submission handling

### Data Flow

1. User accesses a webform with a CiviCRM Attendance element
2. `CiviCrmApiService` retrieves:
   - The current user's CiviCRM contact ID
   - Related contacts through relationship filtering
   - Event and participant status data
3. Data is rendered in the webform element
4. User updates participant statuses and submits the form
5. Submission handler updates participant records in CiviCRM

## Code Organization

```
civicrm_attendance/
├── civicrm_attendance.info.yml        # Module metadata
├── civicrm_attendance.install          # Installation/update hooks
├── civicrm_attendance.libraries.yml    # JS/CSS libraries
├── civicrm_attendance.links.task.yml   # Menu task links
├── civicrm_attendance.module           # Module hooks
├── civicrm_attendance.permissions.yml  # Permission definitions
├── civicrm_attendance.routing.yml      # Routes
├── civicrm_attendance.services.yml     # Service definitions
├── composer.json                       # Composer dependencies
├── css/                                # CSS files
│   └── civicrm_attendance.css
├── js/                                 # JavaScript files
│   └── civicrm_attendance.js
├── src/                                # PHP classes
│   ├── Controller/                     # Controllers
│   │   └── ReportsController.php
│   ├── Exception/                      # Custom exceptions
│   │   ├── CiviCrmApiException.php       # API-specific exceptions
│   │   ├── CivicrmAttendanceException.php # Base exception class
│   │   ├── ParticipantException.php       # Participant-related errors
│   │   ├── ServiceException.php           # Service-level errors
│   │   └── ValidationException.php        # Data validation errors
│   ├── Form/                           # Forms
│   │   └── SettingsForm.php
│   ├── Plugin/                         # Plugins
│   │   └── WebformElement/             # Webform elements
│   │       └── CivicrmAttendanceElement.php
│   └── Service/                        # Services
│       └── CiviCrmApiService.php
├── templates/                          # Twig templates
│   └── civicrm-attendance-element.html.twig
└── tests/                              # Tests
    └── src/
        └── Unit/                       # Unit tests
            ├── Controller/
            │   └── ReportsControllerTest.php
            ├── Exception/
            │   ├── CiviCrmApiExceptionTest.php
            │   ├── CivicrmAttendanceExceptionTest.php
            │   ├── ParticipantExceptionTest.php
            │   ├── ServiceExceptionTest.php
            │   └── ValidationExceptionTest.php
            ├── Form/
            │   └── SettingsFormTest.php
            ├── Plugin/
            │   └── WebformElement/
            │       └── CivicrmAttendanceElementTest.php
            └── Service/
                └── CiviCrmApiServiceTest.php
```

## Exception Handling

The module implements a custom exception hierarchy:

```
CivicrmAttendanceException (base class)
├── CiviCrmApiException
├── ParticipantException
├── ServiceException
└── ValidationException
```

This allows for granular error handling and proper logging. When extending the module, you should:

1. Use the appropriate exception type in your code
2. Catch and handle exceptions at the appropriate level
3. Log exceptions with sufficient context for debugging

Example:

```php
try {
  $result = $this->doSomethingRisky();
  return $result;
}
catch (CiviCrmApiException $e) {
  $this->logger->error('API error: @message', ['@message' => $e->getMessage()]);
  // Handle API-specific error
}
catch (ValidationException $e) {
  $this->logger->warning('Validation error: @message', ['@message' => $e->getMessage()]);
  // Handle validation error
}
catch (CivicrmAttendanceException $e) {
  $this->logger->error('Module error: @message', ['@message' => $e->getMessage()]);
  // Handle general module error
}
catch (\Exception $e) {
  $this->logger->critical('Unexpected error: @message', ['@message' => $e->getMessage()]);
  // Handle unexpected error
}
```

## API Service

The `CiviCrmApiService` is the primary interface to CiviCRM. Key methods include:

### Contact Methods

- `getCurrentContactId()`: Get the CiviCRM contact ID of the current user
- `getContactIdByUserId($user_id)`: Convert Drupal user ID to CiviCRM contact ID
- `getContact($contact_id)`: Get contact details by ID

### Relationship Methods

- `getRelationshipTypes()`: Get all relationship types
- `getRelationshipsByContactId($contact_id, $relationship_type_ids = [], $filters = [])`: Get relationships for a contact
- `getPeerContacts($contact_id, array $options = [])`: Advanced relationship filtering to find peer contacts

### Event Methods

- `getEvents($active_only = FALSE, $start_date = NULL, $end_date = NULL)`: Get events with optional filtering
- `getParticipantStatuses()`: Get available participant statuses
- `getParticipant($contact_id, $event_id)`: Get a specific participant record
- `createParticipant($contact_id, $event_id, $status_id)`: Create or update a participant record

### Configuration Methods

- `getContactSubtypes()`: Get available contact subtypes
- `getSettings($setting_name = NULL)`: Get module settings

## JavaScript Integration

The module's JavaScript provides:

1. Interactive filtering of contacts
2. Bulk operation functionality
3. AJAX updates of participant statuses

Key functions:

```javascript
(function ($, Drupal) {
  Drupal.behaviors.civicrmAttendance = {
    attach: function (context, settings) {
      // Search functionality
      $('.civicrm-attendance-search', context).once('civicrm-attendance').each(function () {
        // ...
      });
      
      // Bulk operations
      $('.civicrm-attendance-bulk-operation', context).once('civicrm-attendance').each(function () {
        // ...
      });
      
      // AJAX status updates
      $('.civicrm-attendance-status-selector', context).once('civicrm-attendance').change(function () {
        // ...
      });
    }
  };
})(jQuery, Drupal);
```

## Extending The Module

### Adding a New API Method

1. Add the method to `CiviCrmApiService`:

```php
/**
 * Gets custom data for a contact.
 *
 * @param int $contact_id
 *   The contact ID.
 * @param string $custom_group
 *   The custom group name.
 *
 * @return array
 *   The custom data.
 *
 * @throws \Drupal\civicrm_attendance\Exception\CiviCrmApiException
 *   If the API call fails.
 */
public function getContactCustomData($contact_id, $custom_group) {
  if (empty($contact_id)) {
    $this->logger->error('Cannot get custom data: Contact ID is required');
    return [];
  }
  
  try {
    $this->civicrm->initialize();
    $result = civicrm_api3('CustomValue', 'get', [
      'entity_id' => $contact_id,
      'entity_type' => 'Contact',
      'return' => $custom_group,
    ]);
    
    return $result['values'] ?? [];
  }
  catch (\Exception $e) {
    $message = 'Failed to get custom data for contact @contact_id: @error';
    $context = [
      '@contact_id' => $contact_id,
      '@error' => $e->getMessage(),
    ];
    $this->logger->error($message, $context);
    throw new CiviCrmApiException($message, 0, $e);
  }
}
```

2. Create a test case for your new method:

```php
/**
 * Tests getContactCustomData method.
 *
 * @covers ::getContactCustomData
 */
public function testGetContactCustomData() {
  // Test with empty contact ID.
  $this->logger->error('Cannot get custom data: Contact ID is required')
    ->shouldBeCalled();
  $result = $this->apiService->getContactCustomData(NULL, 'test_group');
  $this->assertEquals([], $result, 'The method should return an empty array when contact ID is empty.');
  
  // Test with valid inputs but API failure.
  $this->civicrm->initialize()->shouldBeCalled();
  $this->logger->error(Argument::containingString('Failed to get custom data'), Argument::any())
    ->shouldBeCalled();
  
  try {
    $this->apiService->getContactCustomData(123, 'test_group');
    $this->fail('Expected exception was not thrown.');
  }
  catch (\Exception $e) {
    $this->assertInstanceOf(CiviCrmApiException::class, $e);
  }
}
```

### Creating a New Report Type

1. Create a new controller method in `ReportsController`:

```php
/**
 * Displays an attendance by event report.
 *
 * @param int $event_id
 *   The event ID.
 *
 * @return array
 *   A render array representing the report.
 */
public function eventAttendanceReport($event_id) {
  $build = [
    '#type' => 'container',
    '#attributes' => [
      'class' => ['civicrm-attendance-event-report'],
    ],
  ];
  
  /** @var \Drupal\civicrm_attendance\Service\CiviCrmApiService $civicrm_api */
  $civicrm_api = \Drupal::service('civicrm_attendance.civicrm_api');
  
  // Get event details.
  try {
    $civicrm_api->getCivicrm()->initialize();
    $event = civicrm_api3('Event', 'getsingle', [
      'id' => $event_id,
    ]);
    
    $build['title'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('Attendance Report: @title', ['@title' => $event['title']]) . '</h2>',
    ];
    
    // Get participant counts by status.
    $statuses = $civicrm_api->getParticipantStatuses();
    $counts = [];
    
    foreach ($statuses as $status_id => $status_label) {
      try {
        $count = civicrm_api3('Participant', 'getcount', [
          'event_id' => $event_id,
          'status_id' => $status_id,
        ]);
        $counts[$status_id] = $count;
      }
      catch (\Exception $e) {
        $counts[$status_id] = 0;
      }
    }
    
    // Create a table of status counts.
    $header = [
      'status' => $this->t('Status'),
      'count' => $this->t('Count'),
      'percentage' => $this->t('Percentage'),
    ];
    
    $rows = [];
    $total = array_sum($counts);
    
    foreach ($counts as $status_id => $count) {
      $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
      $rows[] = [
        'status' => $statuses[$status_id],
        'count' => $count,
        'percentage' => $percentage . '%',
      ];
    }
    
    $build['counts'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No participants found for this event.'),
    ];
    
    return $build;
  }
  catch (\Exception $e) {
    return [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Error loading event information: @error', ['@error' => $e->getMessage()]) . '</p>',
    ];
  }
}
```

2. Add a route in `civicrm_attendance.routing.yml`:

```yaml
civicrm_attendance.event_report:
  path: '/admin/reports/civicrm-attendance/event/{event_id}'
  defaults:
    _controller: '\Drupal\civicrm_attendance\Controller\ReportsController::eventAttendanceReport'
    _title: 'Event Attendance Report'
  requirements:
    _permission: 'access civicrm attendance reports'
  options:
    parameters:
      event_id:
        type: integer
```

3. Create a test for your new controller method

### Adding a New Configuration Option

1. Update the schema in `civicrm_attendance.schema.yml`:

```yaml
civicrm_attendance.settings:
  type: config_object
  label: 'CiviCRM Attendance settings'
  mapping:
    # ... existing settings ...
    new_feature_flag:
      type: boolean
      label: 'Enable new feature'
```

2. Update the settings form in `SettingsForm.php`:

```php
$form['advanced_settings'] = [
  '#type' => 'details',
  '#title' => $this->t('Advanced Settings'),
  '#open' => FALSE,
];

$form['advanced_settings']['new_feature_flag'] = [
  '#type' => 'checkbox',
  '#title' => $this->t('Enable new feature'),
  '#description' => $this->t('This enables the new experimental feature.'),
  '#default_value' => $config->get('new_feature_flag') ?: FALSE,
];

// In submitForm():
$this->config('civicrm_attendance.settings')
  // ... existing settings ...
  ->set('new_feature_flag', $form_state->getValue('new_feature_flag'))
  ->save();
```

## Testing Strategy

The module uses PHPUnit for testing with several test categories:

### Unit Tests

- Test classes in isolation with mocked dependencies
- Test exception handling
- Test business logic
- Example: `CiviCrmApiServiceTest`

### Kernel Tests (Future Development)

- Test integration with Drupal's container
- Test database operations
- Test service interactions

### Functional Tests (Future Development)

- Test UI interactions
- Test form submissions
- Test Ajax functionality

## Performance Considerations

When extending the module, keep these performance considerations in mind:

1. **Batching**: When working with large data sets, use batching:

```php
// Instead of this:
foreach ($contact_ids as $contact_id) {
  $this->processContact($contact_id);
}

// Do this:
$chunks = array_chunk($contact_ids, 50);
foreach ($chunks as $chunk) {
  $this->processBatch($chunk);
}
```

2. **Caching**: Use Drupal's cache system for expensive operations:

```php
public function getExpensiveData($key) {
  $cid = 'civicrm_attendance:expensive_data:' . $key;
  if ($cache = $this->cache->get($cid)) {
    return $cache->data;
  }
  
  $data = $this->computeExpensiveData($key);
  $this->cache->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, ['civicrm_attendance']);
  return $data;
}
```

3. **Lazy Loading**: Only load data when needed:

```php
protected function loadContactDetails() {
  if ($this->contactDetailsLoaded) {
    return;
  }
  
  $this->contactDetails = $this->apiService->getContact($this->contactId);
  $this->contactDetailsLoaded = TRUE;
}
```

## Common Issues and Solutions

### CiviCRM API Not Accessible

**Issue**: `CRM is not initialized` errors

**Solution**: Always call `$this->civicrm->initialize()` before making API calls:

```php
try {
  $this->civicrm->initialize();
  $result = civicrm_api3('Contact', 'get', [...]);
}
catch (\Exception $e) {
  // Handle error
}
```

### Participant Record Conflicts

**Issue**: Duplicate participant records or status confusion

**Solution**: Always check for existing records before creating new ones:

```php
$existing = $this->getParticipant($contact_id, $event_id);
if (!empty($existing)) {
  // Update existing record
}
else {
  // Create new record
}
```

### Memory Usage with Large Relationship Networks

**Issue**: Memory exhaustion with complex relationship graphs

**Solution**: Use pagination and limit query sizes:

```php
$options = [
  'relationship_type_ids' => $relationship_type_ids,
  'contact_subtypes' => $contact_subtypes,
  'limit' => 100,
  'offset' => $page * 100,
];
$contacts = $this->apiService->getPeerContacts($contact_id, $options);
```

## Future Development Roadmap

Areas for potential expansion:

1. **Advanced Reporting**: Enhanced visualization and export capabilities
2. **Notification System**: Email and CiviCRM activity notifications for status changes
3. **Custom Field Integration**: Support for custom CiviCRM fields in participant records
4. **Event Series**: Support for recurring events and series
5. **Access Control**: Fine-grained permissions by relationship type and event type

## License

This module is licensed under the GNU General Public License v2.0 or later (GPL-2.0+).
