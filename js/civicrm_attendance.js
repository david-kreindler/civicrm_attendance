/**
 * @file
 * JavaScript behaviors for the CiviCRM Attendance module.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Behavior for the CiviCRM Attendance element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.civicrmAttendanceElement = {
    attach: function (context, settings) {
      // Initialize status selects to apply the proper styling based on current value
      $('.civicrm-attendance-element-status-select', context).once('civicrm-attendance-element-status-init').each(function() {
        // Apply the 'value' attribute explicitly to make CSS selector work
        if ($(this).val()) {
          $(this).attr('value', $(this).val());
        }
      });
      
      // Update the value attribute when status changes
      $('.civicrm-attendance-element-status-select', context).once('civicrm-attendance-element-status-change').on('change', function() {
        $(this).attr('value', $(this).val());
      });
      
      // Handle search functionality.
      $('.civicrm-attendance-element-search-input', context).once('civicrm-attendance-element-search').on('keyup', function () {
        var searchText = $(this).val().toLowerCase();
        var $contactRows = $('.civicrm-attendance-element-contact-row', context);
        
        $contactRows.each(function () {
          var contactName = $(this).find('.civicrm-attendance-element-contact-name').text().toLowerCase();
          var contactEmail = $(this).find('.civicrm-attendance-element-contact-email').text().toLowerCase();
          var relationshipInfo = $(this).find('.civicrm-attendance-element-relationship').text().toLowerCase();
          
          if (contactName.indexOf(searchText) > -1 || 
              contactEmail.indexOf(searchText) > -1 || 
              relationshipInfo.indexOf(searchText) > -1) {
            $(this).show();
          }
          else {
            $(this).hide();
          }
        });
      });

      // Handle bulk operations.
      $('.civicrm-attendance-element-bulk-event-select', context).once('civicrm-attendance-element-bulk-event').on('change', function () {
        var $statusSelect = $(this).closest('.civicrm-attendance-element-bulk-operations').find('.civicrm-attendance-element-bulk-status-select');
        var $applyButton = $(this).closest('.civicrm-attendance-element-bulk-operations').find('.civicrm-attendance-element-bulk-apply-button');
        
        if ($(this).val()) {
          $statusSelect.prop('disabled', false);
        }
        else {
          $statusSelect.prop('disabled', true);
          $applyButton.prop('disabled', true);
        }
      });

      $('.civicrm-attendance-element-bulk-status-select', context).once('civicrm-attendance-element-bulk-status').on('change', function () {
        var $applyButton = $(this).closest('.civicrm-attendance-element-bulk-operations').find('.civicrm-attendance-element-bulk-apply-button');
        
        if ($(this).val()) {
          $applyButton.prop('disabled', false);
        }
        else {
          $applyButton.prop('disabled', true);
        }
      });

      $('.civicrm-attendance-element-bulk-apply-button', context).once('civicrm-attendance-element-bulk-apply').on('click', function () {
        var $bulkOperations = $(this).closest('.civicrm-attendance-element-bulk-operations');
        var eventId = $bulkOperations.find('.civicrm-attendance-element-bulk-event-select').val();
        var statusId = $bulkOperations.find('.civicrm-attendance-element-bulk-status-select').val();
        
        if (eventId && statusId) {
          $('.civicrm-attendance-element-status-select', context).each(function () {
            if ($(this).data('event-id') == eventId) {
              $(this).val(statusId)
                .attr('value', statusId)
                .trigger('change');
            }
          });
          
          // Show a visual feedback that the operation was completed
          var $button = $(this);
          var originalText = $button.text();
          $button.text(Drupal.t('Applied!'));
          setTimeout(function() {
            $button.text(originalText);
          }, 1500);
        }
      });
    }
  };

})(jQuery, Drupal);
