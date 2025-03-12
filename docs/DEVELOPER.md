# Developer Documentation

This document provides technical information for developers working with the CiviCRM Attendance module.

## Architecture

The CiviCRM Attendance module provides an integration between Drupal Webform and CiviCRM to manage event attendance based on institutional connections rather than direct relationships.

### Key Components

1. **CiviCrmApiService** - Service for interacting with the CiviCRM API
2. **CivicrmAttendanceElement** - Webform element plugin for attendance management
3. **ReportsController** - Controller for attendance reports


## Adding New Features

### Adding a New Route/Page

1. Create a controller method in a suitable controller:

```php
/**
 * Display an event attendance report.
 *
 * @param int $event_id
 *   The CiviCRM event ID.
 *
 * @return array
 *   A render array.
 */
public function eventAttendanceReport($event_id) {
  try {
    // Get event data.
    $event = $this->civiCrmApi->getEvent($event_id);
    if (empty($event)) {
      throw new \Exception($this->t('Event not found.'));
    }

    // Get participant data.
    $participants = $this->civiCrmApi->getEventParticipants($event_id);

    // Build the header.
    $header = [
      'name' => $this->t('Name'),
      'email' => $this->t('Email'),
      'status' => $this->t('Status'),
      'registered' => $this->t('Registration Date'),
    ];

    // Build the rows.
    $rows = [];
    foreach ($participants as $participant) {
      $rows[] = [
        'name' => $participant['display_name'],
        'email' => $participant['email'],
        'status' => $participant['status'],
        'registered' => $this->dateFormatter->format($participant['register_date'], 'medium'),
      ];
    }

    // Build the render array.
    $build = [];
    $build['event_info'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $event['title'] . '</h2><p>' . $this->dateFormatter->format($event['start_date'], 'medium') . '</p>',
    ];

    $build['participants'] = [
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

Unit tests focus on isolated components like services and plugins. See the `tests/src/Unit` directory for examples.

### Kernel Tests

Kernel tests verify the integration between components with a minimal Drupal environment.

### Functional Tests

Functional tests ensure the module works correctly within a full Drupal environment.

## Contribution Guidelines

1. Follow Drupal coding standards
2. Write tests for new features
3. Document your code
4. Submit pull requests with clear descriptions
