<?php

namespace Drupal\civicrm_attendance\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the CiviCRM Attendance reports.
 */
class ReportsController extends ControllerBase {

  /**
   * Displays an overview of available reports.
   *
   * @return array
   *   A render array representing the reports page.
   */
  public function overview() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['civicrm-attendance-reports-container'],
      ],
    ];

    $build['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('This page provides reports on event attendance tracked through the CiviCRM Attendance module.') . '</p>',
    ];

    $build['reports'] = [
      '#type' => 'details',
      '#title' => $this->t('Available Reports'),
      '#open' => TRUE,
    ];

    // Example reports that could be implemented
    $reports = [
      [
        'title' => $this->t('Attendance by Relationship Type'),
        'description' => $this->t('View attendance statistics grouped by relationship types.'),
        'url' => '#', // Would point to a specific report route
      ],
      [
        'title' => $this->t('Attendance by Event'),
        'description' => $this->t('View attendance statistics for specific events.'),
        'url' => '#', // Would point to a specific report route
      ],
      [
        'title' => $this->t('User Activity Log'),
        'description' => $this->t('View a log of attendance status changes made by users.'),
        'url' => '#', // Would point to a specific report route
      ],
    ];

    $items = [];
    foreach ($reports as $report) {
      $items[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['civicrm-attendance-report-item'],
        ],
        'title' => [
          '#type' => 'link',
          '#title' => $report['title'],
          '#url' => \Drupal\Core\Url::fromUri('internal:' . $report['url']),
          '#attributes' => [
            'class' => ['civicrm-attendance-report-title'],
          ],
        ],
        'description' => [
          '#type' => 'markup',
          '#markup' => '<p>' . $report['description'] . '</p>',
        ],
      ];
    }

    $build['reports']['items'] = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#attributes' => [
        'class' => ['civicrm-attendance-reports-list'],
      ],
    ];

    $build['help'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Note: These reports are placeholders. Implementation of specific reports will be available in future updates.') . '</p>',
    ];

    return $build;
  }

}
