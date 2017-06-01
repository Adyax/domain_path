<?php

namespace Drupal\Tests\domain_path\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Component\Render\FormattableMarkup;

abstract class DomainPathTestBase extends BrowserTestBase {
  /**
   * @var
   */
  protected $domains;

  /**
   * @var
   */
  protected $node1;

  /**
   * @var
   */
  protected $edit;

  /**
   * Sets a base hostname for running tests.
   *
   * When creating test domains, try to use $this->base_hostname or the
   * domainCreateTestDomains() method.
   */
  public $base_hostname;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['domain_path', 'field', 'node', 'user', 'path', 'system', 'domain_access', 'pathauto'];

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

    $this->base_hostname = \Drupal::service('domain.creator')->createHostname();

    // Create domains.
    $this->domainCreateTestDomains();
    $this->domains = $this->getDomains();
    $this->domainPathBasicSetup();
  }

  /**
   * Reusable test function for checking initial / empty table status.
   */
  public function domainPathTableIsEmpty() {
    $domain_paths = \Drupal::service('domain_path.loader')->loadMultiple(NULL, TRUE);
    $this->assertTrue(empty($domain_paths), 'No domain paths have been created.');
  }

  /**
   * Basic setup.
   */
  public function domainPathBasicSetup() {
    $admin = $this->drupalCreateUser(array(
      'bypass node access',
      'administer content types',
      'administer users',
      'administer node fields',
      'administer node display',
      'administer domains',
      'administer pathauto',
      'administer domain path entity',
      'administer url aliases',
      'edit domain path entity',
    ));
    $this->drupalLogin($admin);

    $entity_alias = [
      'node' => '1',
      'taxonomy_term' => '1',
      'user' => '1'
    ];

    $this->config('pathauto.settings')
      ->set('enabled_entity_types', array_keys($entity_alias))->save();

    $this->drupalGet('admin/config/search/path/settings');
    $this->assertSession()->statusCodeEquals(200);

    // check Node entity type
    $this->config('domain_path.settings')
      ->set('entity_types', $entity_alias)->save();

    $this->drupalGet('admin/config/domain_path/domain_path_settings');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Generates a list of domains for testing.
   *
   * In my environment, I use the example.com hostname as a base. Then I name
   * hostnames one.* two.* up to ten. Note that we always use *_example_com
   * for the machine_name (entity id) value, though the hostname can vary
   * based on the system. This naming allows us to load test schema files.
   *
   * The script may also add test1, test2, test3 up to any number to test a
   * large number of domains.
   *
   * @param int $count
   *   The number of domains to create.
   * @param string|NULL $base_hostname
   *   The root domain to use for domain creation (e.g. example.com).
   * @param array $list
   *   An optional list of subdomains to apply instead of the default set.
   */
  public function domainCreateTestDomains($base_hostname = NULL, $list = array()) {
    $original_domains = \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    if (empty($base_hostname)) {
      $base_hostname = $this->base_hostname;
    }
    // Note: these domains are rigged to work on my test server.
    // For proper testing, yours should be set up similarly, but you can pass a
    // $list array to change the default.
    if (empty($list)) {
      $list = array('', 'first', 'second', 'third');
      $count = count($list);
    }
    for ($i = 0; $i < $count; $i++) {
      if ($i === 0) {
        $hostname = $base_hostname;
        $machine_name = 'example.com';
        $name = 'Example';
      }
      elseif (!empty($list[$i])) {
        $hostname = $list[$i] . '.' . $base_hostname;
        $machine_name = $list[$i] . '.example.com';
        $name = 'Test ' . ucfirst($list[$i]);
      }
      // These domains are not setup and are just for UX testing.
      else {
        $hostname = 'test' . $i . '.' . $base_hostname;
        $machine_name = 'test' . $i . '.example.com';
        $name = 'Test ' . $i;
      }
      // Create a new domain programmatically.
      $values = array(
        'hostname' => $hostname,
        'name' => $name,
        'id' => \Drupal::service('domain.creator')->createMachineName($machine_name),
      );
      $domain = \Drupal::entityTypeManager()->getStorage('domain')->create($values);
      $domain->save();
    }
    $domains = \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    $this->assertTrue((count($domains) - count($original_domains)) == $count, new FormattableMarkup('Created %count new domains.', array('%count' => $count)));
  }

  /**
   * Returns an uncached list of all domains.
   *
   * @return array
   *   An array of domain entities.
   */
  public function getDomains() {
    return \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
  }

}
