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

    // Create domains.
    $this->domainCreateTestDomains();
    $this->domains = $this->getDomains();
  }

  public function testDomainPathAliasesFill() {
    $this->domainPathBasicSetup();

    $this->domainPathAliasesFill();
  }
}
