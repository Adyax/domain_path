<?php

namespace Drupal\domain_path;

use Drupal\pathauto\PathautoPatternListBuilder;
use Drupal\Core\Entity\EntityInterface;


class DomainPathPathautoListBuilder extends PathautoPatternListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\pathauto\PathautoPatternInterface $entity */
    $row['label'] = $entity->label();
    $row['patern']['#markup'] = $entity->getPattern();
    $row['type']['#markup'] = $entity->getAliasType()->getLabel();
    $row['conditions']['#theme'] = 'item_list';
    foreach ($entity->getSelectionConditions() as $condition) {
      $row['conditions']['#items'][] = $condition->summary();
    }

    $third_party_settings = $entity->getThirdPartySetting('domain_path', 'domains');
    if ($third_party_settings) {
      $domains_ids = array_filter($entity->getThirdPartySetting('domain_path', 'domains'));
      if (!empty($domains_ids)) {
        $domain_path_loader = \Drupal::service('domain.loader');
        $domains = $domain_path_loader->loadMultiple($domains_ids);
        foreach ($domains as $domain) {
          $row['conditions']['#items'][] = t('The domain is ') . $domain->getHostname();
        }
      }
    }

    return $row + parent::buildRow($entity);
  }

}