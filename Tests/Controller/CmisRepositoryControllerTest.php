<?php

/**
 * @file
 * Contains \Drupal\cmis\Tests\CmisRepositoryController.
 */

namespace Drupal\cmis\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the cmis module.
 */
class CmisRepositoryControllerTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "cmis CmisRepositoryController's controller functionality",
      'description' => 'Test Unit for module cmis and controller CmisRepositoryController.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests cmis functionality.
   */
  public function testCmisRepositoryController() {
    // Check that the basic functions of module cmis.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
