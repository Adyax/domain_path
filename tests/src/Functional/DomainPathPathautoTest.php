<?php

namespace Drupal\Tests\domain_path\Functional;

//use Drupal\Tests\domain_path\Functional\DomainPathTestBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests the domain path with pathauto patterns.
 *
 * @group domain_path
 */
class DomainPathPathautoTest extends DomainPathTestBase {
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
    $this->domainPathAliasesFill();

  }
}