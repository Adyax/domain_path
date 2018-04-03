<?php

namespace Drupal\Tests\domain_path\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

abstract class DomainPathTestBase extends DomainTestBase {

  /**
   * The test domains list.
   *
   * @var array
   */
  protected $domains;

  /**
   * Sets a base hostname for running tests.
   *
   * When creating test domains, try to use $this->base_hostname or the
   * domainCreateTestDomains() method.
   *
   * @var string
   */
  public $base_hostname;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain_path', 'field', 'node', 'user', 'path', 'system'];

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
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    }
    // Create domains.
    $this->domainCreateTestDomains(2);
    $this->domains = $this->getDomains();
    $this->domainPathBasicSetup();
  }

  /**
   * Basic setup.
   */
  public function domainPathBasicSetup() {
    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer users',
      'administer node fields',
      'administer node display',
      'administer domains',
      'administer url aliases',
      'administer domain paths',
      'edit domain path entity',
      'add domain paths',
      'edit domain paths',
      'delete domain paths',
    ]);
    $this->drupalLogin($admin);
    $this->config('domain_path.settings')
      ->set('entity_types', ['node' => TRUE])->save();
    $this->drupalGet('admin/config/domain_path/domain_path_settings');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Reusable test function for checking initial / empty table status.
   */
  public function domainPathTableIsEmpty() {
    $domain_path_storage = \Drupal::service('entity_type.manager')->getStorage('domain_path');
    $domain_paths = $domain_path_storage->loadMultiple();
    $this->assertTrue(empty($domain_paths), 'No domain paths have been created.');
  }

}
