<?php

namespace Drupal\Tests\domain_path\Functional;

/**
 * Tests the domain path node aliases saving from edit form.
 *
 * @group domain_path
 */
class DomainPathNodeAliasTest extends DomainPathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test domain path node aliases.
   */
  public function testDomainPathNodeAliasesFill() {
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    foreach ($this->domains as $domain) {
      $domain_specific_alias_value = $this->randomMachineName(8);
      $domain_specific_alias_path = "/$domain_specific_alias_value";
      $edit['domain_path[' . $domain->id() . ']'] = $domain_specific_alias_path;
      if ($domain->isDefault()) {
        $domain_paths_check['default'] = $domain_specific_alias_value;
      }
      else {
        $domain_paths_check['specific'] = $domain_specific_alias_value;
      }
    }
    $edit['path[0][alias]'] = '/' . $this->randomMachineName(8);
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, 'Node found in database.');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    if (!empty($domain_paths_check['default'])) {
      $this->drupalGet($domain_paths_check['default']);
      $this->assertSession()->statusCodeEquals(200);
    }
    if (!empty($domain_paths_check['specific'])) {
      $this->drupalGet($domain_paths_check['specific']);
      $this->assertSession()->statusCodeNotEquals(200);
    }

  }

}
