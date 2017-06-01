<?php

namespace Drupal\Tests\domain_path\Functional;

use Drupal\Tests\domain_path\DomainPathTestHelperTrait;

/**
 * Tests the domain path with pathauto patterns for Node entity.
 *
 * @group domain_path
 */
class DomainPathPathautoNodeTest extends DomainPathTestBase {

  use DomainPathTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test for pathauto pattern generation for each domain
   */
  public function testDomainPathPathautoNode() {
    // create default pattern
    $pattern = $this->createPattern('node', '/pathauto/[node:nid]', -1);
    $this->addBundleCondition($pattern, 'node', 'page');
    $pattern->save();

    //create patterns for each domain
    foreach ($this->domains as $domain) {
      $pattern = $this->createPattern('node', '/node-' . $domain->id() .'/[node:nid]', -1);
      $this->addBundleCondition($pattern, 'node', 'page');

      // add domains settings
      $pattern->setThirdPartySetting(
        'domain_path',
        'domains',
        [$domain->id() => $domain->id()]
      );

      $pattern->save();

      // check each pattern for proper settings
      $this->drupalGet('admin/config/search/path/patterns/' . $pattern->id());
      $this->assertSession()->statusCodeEquals(200);
    }

    // check all patterns
    $this->drupalGet('admin/config/search/path/patterns');
    $this->assertSession()->statusCodeEquals(200);

    //create node
    $node1 = $this->drupalCreateNode();

    $edit = [];
    // check for automatic alias
    $edit['path[0][pathauto]'] = 1;
    $this->drupalPostForm('node/' . $node1->id() . '/edit', $edit, t('Save'));

    // check aliases for domains was generated
    $this->drupalGet('node/' . $node1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
  }
}