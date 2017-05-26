<?php

namespace Drupal\Tests\domain_path\Functional;

//use Drupal\Tests\domain_path\Functional\DomainPathTestBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests the domain path creation API.
 *
 * @group domain_path
 */
class DomainPathAliasTest extends DomainPathTestBase {
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create domains.
    $this->domainCreateTestDomains();
  }

  /**
   * Basic test setup.
   */
  public function testDomainPathAlias() {
    $admin = $this->drupalCreateUser(array(
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
      'administer pathauto',
      'administer domain path entity',
      'administer url aliases',
      'edit domain path entity'
    ));
    $this->drupalLogin($admin);


    $this->config('pathauto.settings')
      ->set('enabled_entity_types', ['node' => '1'])->save();

    $this->drupalGet('admin/config/search/path/settings');
    $this->assertSession()->statusCodeEquals(200);

    // check Node entity type
    $this->config('domain_path.settings')
      ->set('entity_types', ['node' => '1', 'taxonomy_term' => '1'])->save();

    $this->drupalGet('admin/config/domain_path/domain_path_settings');
    $this->assertSession()->statusCodeEquals(200);

    $domains = $this->getDomains();

    $this->assertSession()->statusCodeEquals(200);

    $node1 = $this->drupalCreateNode();
    // Create alias.
    $edit = [];
    foreach ($domains as $domain) {
      $edit['path[0][domain_path][' . $domain->id() . ']'] = '/' . $this->randomMachineName(8);
    }

    $edit['path[0][alias]'] = '/' . $this->randomMachineName(8);
    $this->drupalPostForm('node/' . $node1->id() . '/edit', $edit, t('Save'));

    $this->drupalGet('node/' . $node1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // check the redirects from domains. in all cases must be the current main domain
    foreach ($domains as $domain) {
      $this->drupalGet('node/' . $node1->id());
      if ($domain->isDefault()) {
        $this->assertSession()
          ->addressEquals($edit['path[0][domain_path][' . $domain->id() . ']']);
      }
      else {
        $this->assertSession()
          ->addressNotEquals($edit['path[0][domain_path][' . $domain->id() . ']']);
      }
    }
  }
}
