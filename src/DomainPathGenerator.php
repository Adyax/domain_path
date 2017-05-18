<?php

namespace Drupal\domain_path;

use Drupal\pathauto\PathautoGenerator;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Token;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\pathauto\PathautoState;
use Drupal\Component\Utility\Unicode;

class DomainPathGenerator extends PathautoGenerator {

  protected $domain_id;
  /**
   * Resets internal caches.
   */
  public function resetCaches() {
    parent::resetCaches();
  }


  public function setDomainId($domain_id) {
    $this->domain_id = $domain_id;
  }

  /**
   * Loads pathauto patterns for a given entity type ID
   *
   * @param string $entity_type_id
   *   An entity type ID.
   *
   * @return \Drupal\pathauto\PathautoPatternInterface[]
   *   A list of patterns, sorted by weight.
   */
  protected function getPatternByEntityType($entity_type_id) {
    if (!isset($this->patternsByEntityType[$this->domain_id][$entity_type_id])) {
      $ids = \Drupal::entityQuery('pathauto_pattern')
        ->condition('type', array_keys(\Drupal::service('plugin.manager.alias_type')
          ->getPluginDefinitionByType($this->tokenEntityMapper->getTokenTypeForEntityType($entity_type_id))))
        ->condition('status', 1)
        ->condition('third_party_settings.domain_path.domains.' . $this->domain_id, $this->domain_id)
        ->sort('weight')
        ->execute();

      $this->patternsByEntityType[$this->domain_id][$entity_type_id] = \Drupal::entityTypeManager()
        ->getStorage('pathauto_pattern')
        ->loadMultiple($ids);
    }

    return $this->patternsByEntityType[$this->domain_id][$entity_type_id];
  }

  /**
   * Load an alias pattern entity by domain, entity, bundle, and language.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @return \Drupal\pathauto\PathautoPatternInterface|null
   */
  public function getPatternByEntity(EntityInterface $entity) {
    $langcode = $entity->language()->getId();
    if (!isset($this->patterns[$this->domain_id][$entity->getEntityTypeId()][$entity->id()][$langcode])) {
    $pp = $this->getPatternByEntityType($entity->getEntityTypeId());
      foreach ($this->getPatternByEntityType($entity->getEntityTypeId()) as $pattern) {
        if ($pattern->applies($entity)) {
          $this->patterns[$this->domain_id][$entity->getEntityTypeId()][$entity->id()][$langcode] = $pattern;
          break;
        }
      }
      // If still not set.
      if (!isset($this->patterns[$this->domain_id][$entity->getEntityTypeId()][$entity->id()][$langcode])) {
        $this->patterns[$this->domain_id][$domain_id][$entity->getEntityTypeId()][$entity->id()][$langcode] = NULL;
      }
    }
    return $this->patterns[$this->domain_id][$entity->getEntityTypeId()][$entity->id()][$langcode];
  }

  /**
   * Apply patterns to create an alias.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $op
   *   Operation being performed on the content being aliased
   *   ('insert', 'update', 'return', or 'bulkupdate').
   *
   * @return array|string
   *   The alias that was created.
   *
   * @see _pathauto_set_alias()
   */
  public function createEntityAlias(EntityInterface $entity, $op) {
    // Retrieve and apply the pattern for this content type.
    $pattern = $this->getPatternByEntity($entity);
    if (empty($pattern)) {
      // No pattern? Do nothing (otherwise we may blow away existing aliases...)
      return NULL;
    }

    $source = '/' . $entity->toUrl()->getInternalPath();
    $config = $this->configFactory->get('pathauto.settings');
    $langcode = $entity->language()->getId();

    // Core does not handle aliases with language Not Applicable.
    if ($langcode == LanguageInterface::LANGCODE_NOT_APPLICABLE) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }

    // Build token data.
    $data = [
      $this->tokenEntityMapper->getTokenTypeForEntityType($entity->getEntityTypeId()) => $entity,
    ];

    // Allow other modules to alter the pattern.
    $context = array(
      'module' => $entity->getEntityType()->getProvider(),
      'op' => $op,
      'source' => $source,
      'data' => $data,
      'bundle' => $entity->bundle(),
      'language' => &$langcode,
    );
    // @todo Is still hook still useful?
    $this->moduleHandler->alter('pathauto_pattern', $pattern, $context);

    // Special handling when updating an item which is already aliased.
    $existing_alias = NULL;
    if ($op == 'update' || $op == 'bulkupdate') {
      if ($existing_alias = $this->aliasStorageHelper->loadBySource($source, $langcode)) {
        switch ($config->get('update_action')) {
          case PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW:
            // If an alias already exists,
            // and the update action is set to do nothing,
            // then gosh-darn it, do nothing.
            return NULL;
        }
      }
    }

    // Replace any tokens in the pattern.
    // Uses callback option to clean replacements. No sanitization.
    // Pass empty BubbleableMetadata object to explicitly ignore cacheablity,
    // as the result is never rendered.
    $alias = $this->token->replace($pattern->getPattern(), $data, array(
      'clear' => TRUE,
      'callback' => array($this->aliasCleaner, 'cleanTokenValues'),
      'langcode' => $langcode,
      'pathauto' => TRUE,
    ), new BubbleableMetadata());

    // Check if the token replacement has not actually replaced any values. If
    // that is the case, then stop because we should not generate an alias.
    // @see token_scan()
    $pattern_tokens_removed = preg_replace('/\[[^\s\]:]*:[^\s\]]*\]/', '', $pattern->getPattern());
    if ($alias === $pattern_tokens_removed) {
      return NULL;
    }

    $alias = $this->aliasCleaner->cleanAlias($alias);

    // Allow other modules to alter the alias.
    $context['source'] = &$source;
    $context['pattern'] = $pattern;
    $this->moduleHandler->alter('pathauto_alias', $alias, $context);

    // If we have arrived at an empty string, discontinue.
    if (!Unicode::strlen($alias)) {
      return NULL;
    }

    // If the alias already exists, generate a new, hopefully unique, variant.
    $original_alias = $alias;
    $this->aliasUniquifier->uniquify($alias, $source, $langcode);
    if ($original_alias != $alias) {
      // Alert the user why this happened.
      $this->messenger->addMessage($this->t('The automatically generated alias %original_alias conflicted with an existing alias. Alias changed to %alias.', array(
        '%original_alias' => $original_alias,
        '%alias' => $alias,
      )), $op);
    }

    // Return the generated alias if requested.
    if ($op == 'return') {
      return $alias;
    }

    // Build the new path alias array and send it off to be created.
    $path = array(
      'source' => $source,
      'alias' => $alias,
      'language' => $langcode,
    );

    return $this->aliasStorageHelper->save($path, $existing_alias, $op);
  }

  /**
   * Creates or updates an alias for the given entity.
   *
   * @param EntityInterface $entity
   *   Entity for which to update the alias.
   * @param string $op
   *   The operation performed (insert, update)
   * @param array $options
   *   - force: will force updating the path
   *   - language: the language for which to create the alias
   *
   * @return array|null
   *   - An array with alias data in case the alias has been created or updated.
   *   - NULL if no operation performed.
   */
  public function updateEntityAlias(EntityInterface $entity, $op, array $options = array()) {
    // Skip if the entity does not have the path field.
    if (!($entity instanceof ContentEntityInterface) || !$entity->hasField('path')) {
      return NULL;
    }

    // Skip if pathauto processing is disabled.
    if ($entity->path->pathauto != PathautoState::CREATE && empty($options['force'])) {
      return NULL;
    }

    // Only act if this is the default revision.
    if ($entity instanceof RevisionableInterface && !$entity->isDefaultRevision()) {
      return NULL;
    }

    $options += array('language' => $entity->language()->getId());
    $type = $entity->getEntityTypeId();

    // Skip processing if the entity has no pattern.
    if (!$this->getPatternByEntity($entity)) {
      return NULL;
    }

    // Deal with taxonomy specific logic.
    // @todo Update and test forum related code.
    if ($type == 'taxonomy_term') {

      $config_forum = $this->configFactory->get('forum.settings');
      if ($entity->getVocabularyId() == $config_forum->get('vocabulary')) {
        $type = 'forum';
      }
    }

    try {
      $result = $this->createEntityAlias($entity, $op);
    }
    catch (\InvalidArgumentException $e) {
      drupal_set_message($e->getMessage(), 'error');
      return NULL;
    }

    // @todo Move this to a method on the pattern plugin.
    if ($type == 'taxonomy_term') {
      foreach ($this->loadTermChildren($entity->id()) as $subterm) {
        $this->updateEntityAlias($subterm, $op, $options);
      }
    }

    return $result;
  }
}