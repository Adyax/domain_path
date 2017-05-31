<?php

namespace Drupal\Tests\domain_path;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\pathauto\PathautoPatternInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Helper test class with some added functions for testing.
 */
trait DomainPathTestHelperTrait {
  /**
   * Creates a pathauto pattern.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $pattern
   *   The path pattern.
   * @param int $weight
   *   (optional) The pattern weight.
   *
   * @return \Drupal\pathauto\PathautoPatternInterface
   *   The created pattern.
   */
  protected function createPattern($entity_type_id, $pattern, $weight = 10) {
    $type = ($entity_type_id == 'forum') ? 'forum' : 'canonical_entities:' . $entity_type_id;

    $pattern = PathautoPattern::create([
      'label' => Unicode::strtolower($this->randomMachineName()),
      'id' => Unicode::strtolower($this->randomMachineName()),
      'type' => $type,
      'pattern' => $pattern,
      'weight' => $weight,
    ]);
    $pattern->save();
    return $pattern;
  }

  /**
   * Add a bundle condition to a pathauto pattern.
   *
   * @param \Drupal\pathauto\PathautoPatternInterface $pattern
   *   The pattern.
   * @param string $entity_type
   *   The entity type ID.
   * @param string $bundle
   *   The bundle
   */
  protected function addBundleCondition(PathautoPatternInterface $pattern, $entity_type, $bundle) {
    $plugin_id = $entity_type == 'node' ? 'node_type' : 'entity_bundle:' . $entity_type;

    $pattern->addSelectionCondition(
      [
        'id' => $plugin_id,
        'bundles' => [
          $bundle => $bundle,
        ],
        'negate' => FALSE,
        'context_mapping' => [
          $entity_type => $entity_type,
        ]
      ]
    );
  }

  protected function addThirdPartySettings(PathautoPatternInterface $pattern, $domains) {
    $pattern->setThirdPartySetting(
      'domain_path',
      'domains',
      $domains
    );
  }
}