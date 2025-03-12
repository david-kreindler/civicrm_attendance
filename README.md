# CiviCRM Attendance

## Overview

The CiviCRM Attendance module provides a powerful integration between Drupal Webforms and CiviCRM for tracking event participation of contacts based on their relationship patterns with specific contact subtypes.

The module specifically targets contacts who:
1. Have one or more specified relationship types to contacts of a specified subtype
2. Where the current user also has at least one of those same relationship types to contacts of the specified subtype
3. Can be filtered further by event date ranges and participation status

This allows you to efficiently manage event participation for contacts who share similar institutional connections as you, without requiring direct relationships between you and those contacts.

## Key Features

- Advanced contact filtering based on complex relationship patterns:
  - Include contacts who share the same relationship type(s) with contacts of specified subtype(s) as the current user
  - Find "peers" who have similar institutional connections to yours
  - Sophisticated filtering options:
    - Match based on relationship roles (e.g., if the user is an employee of an organization, only show other employees)
    - Require all relationship patterns to match, not just one
    - Optionally include inactive relationships
- Display filtered contacts in a user-friendly interface with relationship details
- Allow recording or updating event participation status for these contacts
- Search/filter functionality to quickly find specific contacts
- Batch operations to set multiple participation statuses at once
- Full integration with Drupal Webforms and CiviCRM

## Use Cases

This module is particularly useful in scenarios such as:

- **Educational institutions**: Faculty members can track attendance for students who are enrolled in the same departments/programs they teach in
- **Healthcare organizations**: Department heads can manage event attendance for staff who have relationships with the same clinics they oversee
- **Non-profits**: Regional coordinators can track participation of volunteers who are connected to the same chapters/programs they coordinate
- **Membership organizations**: Group leaders can record attendance for members who belong to the same interest groups they lead
- **Government agencies**: Program managers can monitor event participation for constituents who are connected to the same districts they administer

## Requirements

- Drupal 9.x or 10.x
- CiviCRM 5.x or newer
- Webform 6.x
- Webform CiviCRM Integration module

## Installation

### Using Composer (Recommended)

1. In your Drupal site's root directory, run:
   ```
   composer require drupal/civicrm_attendance
   ```

2. Navigate to the "Extend" page in your Drupal admin interface (`/admin/modules`)
3. Find "CiviCRM Attendance" in the list and check the box
4. Click "Install" at the bottom of the page

### Manual Installation

1. Place the module directory in your Drupal installation's modules directory (typically `web/modules/custom/` or `sites/all/modules/custom/`)
2. Navigate to the "Extend" page in your Drupal admin interface (`/admin/modules`)
3. Find "CiviCRM Attendance" in the list and check the box
4. Click "Install" at the bottom of the page

## Configuration

1. Navigate to the CiviCRM Attendance settings page at `/admin/config/civicrm/civicrm-attendance`
2. Configure default relationship types and participant status options
3. Create a new webform or edit an existing one
4. Add a "CiviCRM Attendance" element to your form
5. Configure basic settings:
   - Select which relationship types, contact subtypes and events to use
   - Configure display options (bulk operations, relationship info, search)
6. Configure advanced relationship filtering (optional):
   - Require all relationship patterns to match
   - Match relationship roles
   - Include inactive relationships

## Usage

After adding the element to a webform, authenticated users with CiviCRM contacts will see:

1. A list of contacts matching the relationship criteria
2. Relationship information for each contact
3. A table of events with status selection options
4. Options to quickly set or clear statuses for all events

When the form is submitted, the module will create or update participant records in CiviCRM based on the selected statuses.

## API Integration

The module provides a service for interacting with the CiviCRM API:

```php
// Get the service.
$civicrm_api_service = \Drupal::service('civicrm_attendance.civicrm_api');

// Get related contacts (basic method).
$related_contacts = $civicrm_api_service->getRelatedContacts(
  $contact_id,
  $relationship_type_ids,
  $contact_subtypes
);

// Get participant contacts with relationship filtering.
$filtering_options = [
  'relationship_type_ids' => $relationship_type_ids,
  'contact_subtypes' => $contact_subtypes,
  'include_inactive' => FALSE,
  'contact_types' => ['Individual'],
  'limit' => 0, // 0 means no limit
];
$participant_contacts = $civicrm_api_service->getPeerContacts($contact_id, $filtering_options);

// Get events within a specific date range.
$events = $civicrm_api_service->getEvents(
  TRUE, // Active events only
  '2025-01-01', // Start date
  '2025-12-31' // End date
);

// Create or update a participant record.
$participant = $civicrm_api_service->createParticipant(
  $contact_id,
  $event_id,
  $status_id
);
```

## Customization

The module's appearance can be customized by:

1. Overriding the `civicrm-attendance-element.html.twig` template
2. Adding custom CSS to override the default styles
3. Adding custom JavaScript behaviors to extend the default functionality

## Permissions

The module provides two permissions:

- **Administer CiviCRM Attendance**: Access to configure global settings
- **Use CiviCRM Attendance**: Ability to add and configure the element on webforms

## For Developers

### Module Structure

The module follows standard Drupal 9/10 structure:

- `civicrm_attendance.info.yml`: Module metadata and dependencies
- `civicrm_attendance.module`: Primary hooks and functionality
- `civicrm_attendance.install`: Installation, update, and schema hooks
- `civicrm_attendance.routing.yml`: Routing definitions
- `civicrm_attendance.services.yml`: Service definitions
- `civicrm_attendance.permissions.yml`: Custom permissions
- `civicrm_attendance.libraries.yml`: Asset libraries
- `src/`: PHP classes organized by namespace
- `css/`: Stylesheets
- `js/`: JavaScript files
- `templates/`: Twig templates

The `.install` file is particularly important as it handles:
- Initial configuration setup during installation
- Cleanup during uninstallation
- Version-to-version update hooks
- Dependency verification
- Database schema definitions (if needed in future)

### Contributing to the module

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

### Local Development

1. Clone the repository
2. Install Composer dependencies:
   ```
   cd civicrm_attendance
   composer install
   ```

3. Set up linting and coding standards (recommended):
   ```
   composer require --dev drupal/coder
   composer require --dev squizlabs/php_codesniffer
   ```

4. Run tests (once you've created them):
   ```
   composer test
   ```

## Credits

This module was developed to enhance the integration between Drupal Webforms and CiviCRM for advanced relationship-based event participation tracking.
