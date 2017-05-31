<?php

namespace Drupal\Tests\domain_path\Functional;


/**
 * Tests the domain path aliases proper redirect
 *
 * @group domain_path
 */
class DomainPathAliasRedirectTest extends DomainPathTestBase {
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create domains.
    $this->domainCreateTestDomains();
    $this->domains = $this->getDomains();
  }

  public function testDomainPathCheckRedirectDefault() {
    $this->domainPathBasicSetup();

    $this->domainPathAliasesFill();

    // check the redirects from domains. in all cases must be the current main domain
    foreach ($this->domains as $domain) {
      $this->drupalGet('node/' . $this->node1->id());
      if ($domain->isDefault()) {
        $this->assertSession()
          ->addressEquals($this->edit['path[0][domain_path][' . $domain->id() . ']']);
      }
      else {
        $this->assertSession()
          ->addressNotEquals($this->edit['path[0][domain_path][' . $domain->id() . ']']);
      }
    }
  }
}