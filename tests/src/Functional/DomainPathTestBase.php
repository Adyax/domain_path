<?php

namespace Drupal\Tests\domain_path\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

abstract class DomainPathTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['path', 'pathauto', 'redirect', 'domain_path', 'node'];

  /**
   * We use the standard profile for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

  }

  /**
   * Reusable test function for checking initial / empty table status.
   */
  public function domainPathTableIsEmpty() {
    //$domain_loader = \Drupal::service('domain.loader');
    $domain_paths = \Drupal::service('domain_path.loader')->loadMultiple(NULL, TRUE);
    $this->assertTrue(empty($domain_paths), 'No domain paths have been created.');
  }

}
