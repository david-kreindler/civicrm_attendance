<?php

namespace Drupal\Tests\civicrm_attendance\Unit\Controller;

use Drupal\civicrm_attendance\Controller\ReportsController;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the Reports controller.
 *
 * @group civicrm_attendance
 * @coversDefaultClass \Drupal\civicrm_attendance\Controller\ReportsController
 */
class ReportsControllerTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The controller being tested.
   *
   * @var \Drupal\civicrm_attendance\Controller\ReportsController
   */
  protected $controller;

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $translation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the translation service.
    $this->translation = $this->prophesize(TranslationInterface::class);
    $this->translation->translate(Argument::any())->willReturnArgument(0);
    $this->translation->translateString(Argument::any())->willReturn('Translated string');

    // Create the controller.
    $this->controller = new ReportsController();
    $this->controller->setStringTranslation($this->translation->reveal());
  }

  /**
   * Tests the overview page build.
   *
   * @covers ::overview
   */
  public function testOverview() {
    // Get the overview build array.
    $build = $this->controller->overview();

    // Check the basic structure.
    $this->assertEquals('container', $build['#type']);
    $this->assertArrayHasKey('class', $build['#attributes']);
    $this->assertContains('civicrm-attendance-reports-container', $build['#attributes']['class']);

    // Check the description markup.
    $this->assertArrayHasKey('description', $build);
    $this->assertEquals('markup', $build['description']['#type']);
    $this->assertStringContainsString('This page provides reports', $build['description']['#markup']);

    // Check the reports section.
    $this->assertArrayHasKey('reports', $build);
    $this->assertEquals('details', $build['reports']['#type']);
    $this->assertEquals(TRUE, $build['reports']['#open']);

    // Check the report items.
    $this->assertArrayHasKey('items', $build['reports']);
    $this->assertEquals('item_list', $build['reports']['items']['#theme']);
    $this->assertNotEmpty($build['reports']['items']['#items']);
    $this->assertArrayHasKey('class', $build['reports']['items']['#attributes']);
    $this->assertContains('civicrm-attendance-reports-list', $build['reports']['items']['#attributes']['class']);

    // Check the report items format.
    $items = $build['reports']['items']['#items'];
    foreach ($items as $item) {
      $this->assertEquals('container', $item['#type']);
      $this->assertArrayHasKey('title', $item);
      $this->assertEquals('link', $item['title']['#type']);
      $this->assertArrayHasKey('description', $item);
      $this->assertEquals('markup', $item['description']['#type']);
    }

    // Check the help section.
    $this->assertArrayHasKey('help', $build);
    $this->assertEquals('markup', $build['help']['#type']);
    $this->assertStringContainsString('Note: These reports are placeholders', $build['help']['#markup']);
  }

}
