<?php

namespace Drupal\Tests\domain_path\Functional;

/**
 * Tests the domain path creation API.
 *
 * @group domain_path
 */
class DomainPathCreateTest extends DomainPathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests initial domain path creation.
   */
  public function testDomainPathCreate() {
    // No domain paths should exist.
    $this->domainPathTableIsEmpty();
    $node = $this->drupalCreateNode();
    $this->assertTrue($node, 'Node found in database.');
    $domain_path_storage = \Drupal::service('entity_type.manager')->getStorage('domain_path');
    $default_domain_id = NULL;
    foreach ($this->domains as $domain) {
      $domain_id = $domain->id();
      if ($domain->isDefault()) {
        $default_domain_id = $domain_id;
      }
      $domain_path_entity = $domain_path_storage->create(['type' => 'domain_path']);
      $domain_specific_alias_value = $this->randomMachineName(8);
      $domain_specific_alias_path = "/$domain_specific_alias_value";
      $properties_map = [
        'alias' => $domain_specific_alias_path,
        'domain_id' => $domain_id,
        'language' => $node->language()->getId(),
        'source' => '/node/' . $node->id(),
      ];
      foreach ($properties_map as $field => $value) {
        $domain_path_entity->set($field, $value);
      }
      $domain_path_entity->save();
    }

    $loaded_domain_paths = $domain_path_storage->loadMultiple();
    foreach ($loaded_domain_paths as $loaded_domain_path) {
      // Check that links are printed.
      $edit_href = "admin/config/domain_path/{$loaded_domain_path->id()}/edit";
      $this->drupalGet($edit_href);
      $this->assertSession()->statusCodeEquals(200);
      $domain_alias = ltrim($loaded_domain_path->getAlias(), '/');
      $this->drupalGet($domain_alias);
      $loaded_domain_path_domain_id = $loaded_domain_path->getDomainId();
      if ($loaded_domain_path_domain_id === $default_domain_id) {
        $this->assertSession()->statusCodeEquals(200);
      }
      else {
        $this->assertSession()->statusCodeNotEquals(200);
      }
    }
  }

}
