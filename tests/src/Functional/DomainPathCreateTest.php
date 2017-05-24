<?php

namespace Drupal\Tests\domain_path\Functional;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests the domain path creation API.
 *
 * @group domain_path
 */
class DomainPathCreateTest extends DomainPathTestBase {
  /**
   * Tests initial domain path creation.
   */
  public function testDomainPathCreate() {
    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domain path entity',
      'edit domain path entity',
    ]);
    $this->drupalLogin($admin);

    $list_href = 'admin/config/domain_path';

    $this->drupalGet($list_href);
    $this->assertSession()->statusCodeEquals(200);

    // No domain paths should exist.
    $this->domainPathTableIsEmpty();

    // Create a new domain programmatically.
    $domain_path_storage = \Drupal::service('domain_path.loader')->getStorage();
    $domain_path_entity = $domain_path_storage->create(['type' => 'domain_path']);
    $properties_map = [
      'alias' => '/test-alias',
      'domain_id' => 'http://test.com/',
      'language' => 'und',
      'entity_type' => 'node',
      'entity_id' => 1,
    ];
    foreach ($properties_map as $field => $value) {
      $domain_path_entity->set($field, $value);
    }

    foreach (array_keys($properties_map) as $key) {
      $property = $domain_path_entity->get($key);
      $this->assertTrue(isset($property), new FormattableMarkup('New $domain_path->@key property is set to default value: %value.', array('@key' => $key, '%value' => $property)));
    }
    $domain_path_entity->save();

    // Did it save correctly?
    $loaded_path_entity_data = \Drupal::service('domain_path.loader')->loadByProperties(['entity_id' => 1]);
    $loaded_path_entity = !empty($loaded_path_entity_data) ? reset($loaded_path_entity_data) : NULL;
    $default_id = !empty($loaded_path_entity) ? $loaded_path_entity->id() : NULL;
    $this->assertTrue(!empty($default_id), 'Domain path has been set.');

    // Does it load correctly?
    $new_domain_path = \Drupal::service('domain_path.loader')->load($default_id);
    $this->assertTrue($new_domain_path->id() == $domain_path_entity->id(), 'Domain path loaded properly.');

    // Has domain path id been set?
    //$this->assertTrue($new_domain_path->getDomainId(), 'Domain path id set properly.');

    // Has a UUID been set?
    $this->assertTrue($new_domain_path->uuid(), 'Entity UUID set properly.');

    $this->drupalGet($list_href);
    $this->assertSession()->statusCodeEquals(200);

    // Check that links are printed.
    $edit_href = "admin/config/domain_path/{$domain_path_entity->id()}/edit";
    $this->assertSession()->linkByHrefExists($edit_href, 0, 'Link found ' . $edit_href);
    $this->assertSession()->assertEscaped($domain_path_entity->id());
    $this->drupalGet($edit_href);
    $this->assertSession()->statusCodeEquals(200);

    // Delete the domain path.
    $domain_path_entity->delete();
    $domain_path_entity = \Drupal::service('domain_path.loader')->load($default_id, TRUE);
    $this->assertTrue(empty($domain_path_entity), 'Domain path record deleted.');

    // No domain path should exist.
    $this->domainPathTableIsEmpty();
    $this->drupalGet($list_href);
    $this->assertSession()->statusCodeEquals(200);
  }
}
