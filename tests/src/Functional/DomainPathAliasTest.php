<?php

namespace Drupal\Tests\domain_path\Functional;


/**
 * Tests the domain path aliases saving from edit form.
 *
 * @group domain_path
 */
class DomainPathAliasTest extends DomainPathTestBase {
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   *
   */
  public function testDomainPathAliasesFill() {

    // Create alias.
    $edit = [];
    foreach ($this->domains as $domain) {
      $edit['path[0][domain_path][' . $domain->id() . ']'] = '/' . $this->randomMachineName(8);
    }

    $node1 = $this->drupalCreateNode();

    $edit['path[0][alias]'] = '/' . $this->randomMachineName(8);
    $this->drupalPostForm('node/' . $node1->id() . '/edit', $edit, t('Save'));

    $this->drupalGet('node/' . $node1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);


    // check the redirects from domains. in all cases must be the current main domain
    foreach ($this->domains as $domain) {
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
