<?php

namespace Drupal\domain_path\Form;

use Drupal\pathauto\Form\PatternEditForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\pathauto\AliasTypeManager;
use Drupal\domain\DomainLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for pathauto patterns.
 */
class DomainPathPatternEditForm extends PatternEditForm {

  /**
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $domainLoaderManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.alias_type'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('domain.loader')
    );
  }

  /**
   * PatternEditForm constructor.
   *
   * @param \Drupal\pathauto\AliasTypeManager $manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\domain\DomainLoaderInterface $domain_loader_manager
   */
  function __construct(AliasTypeManager $manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, DomainLoaderInterface $domain_loader_manager) {
    parent::__construct($manager, $entity_type_bundle_info, $entity_type_manager, $language_manager);
    $this->domainLoaderManager = $domain_loader_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    // if there is no type yet, stop here.
    if ($this->entity->getType()) {

      $alias_type = $this->entity->getAliasType();

      // Expose domain conditions.
      if ($alias_type->getDerivativeId() && $entity_type = $this->entityTypeManager->getDefinition($alias_type->getDerivativeId())) {
        $default_domains = [];
        foreach ($this->entity->getSelectionConditions() as $condition_id => $condition) {
          if ($condition->getPluginId() == 'domain') {
            $default_domains = $condition->getConfiguration()['domains'];
          }
        }

        if ($domains = $this->domainLoaderManager->loadMultipleSorted()) {
          $domain_options = [];
          foreach ($domains as $domain_id => $domain) {
            $domain_options[$domain_id] = $domain->getPath();
          }
          $form['pattern_container']['domains'] = [
            '#title' => $this->t('Domains'),
            '#type' => 'checkboxes',
            '#options' => $domain_options,
            '#default_value' => $default_domains,
            '#description' => $this->t('Check to which domains this pattern should be applied. Leave empty to allow any.'),
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\pathauto\PathautoPatternInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    // Will only be used for new patterns.
    $default_weight = 0;

    $alias_type = $entity->getAliasType();
    if ($alias_type->getDerivativeId() && $this->entityTypeManager->hasDefinition($alias_type->getDerivativeId())) {
      //$entity_type = $alias_type->getDerivativeId();
      // First, remove domain condition.
      foreach ($entity->getSelectionConditions() as $condition_id => $condition) {
        if (in_array($condition->getPluginId(), ['domain'])) {
          $entity->removeSelectionCondition($condition_id);
        }
      }

      if ($domains = array_filter((array) $form_state->getValue('domains'))) {
        $default_weight -= 5;
        //$domain_mapping = $entity_type . ':' . $this->entityTypeManager->getDefinition($entity_type)->getKey('langcode') . ':language' . ':domain';
        $entity->addSelectionCondition(
          [
            'id' => 'domain',
            'domains' => $domains,
            'negate' => FALSE,
//            'context_mapping' => [
//              'entity:domain' => $domain_mapping,
//            ]
          ]
        );
        //$entity->addRelationship($domain_mapping, t('Domain'));
      }

    }

    if ($entity->isNew()) {
      $entity->setWeight($default_weight);
    }

    return $entity;
  }

}
