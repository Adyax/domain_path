<?php

namespace Drupal\domain_path\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the domain_path entity edit forms.
 *
 * @ingroup domain_path
 */
class DomainPathForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\domain_path\Entity\DomainPath */
    $form = parent::buildForm($form, $form_state);
    $options = [];
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $language_manager = \Drupal::service('language_manager');

    foreach ($language_manager->getLanguages() as $language) {
      $options[$language->getId()] = $language->getName();
    }

    $default_target_entity_type = NULL;
    $ref_entity = NULL;

    if ($entity = $this->entity) {
      $default_target_entity_type = $entity->get('entity_type')->value;
      if ($default_target_entity_type) {
        $entity_type_storage = $this->entityTypeManager->getStorage($default_target_entity_type);
        $ref_entity_id = $entity->get('entity_id')->target_id;
        $ref_entity = $ref_entity_id ? $entity_type_storage->load($ref_entity_id) : NULL;
      }
      $langcode = $entity->get('language')->value;
    }

    $form['language'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#options' => $options,
      '#default_value' => $langcode,
      '#languages' => Language::STATE_ALL,
    ];

    $entity_type_options = [];
    $domain_path_helper = \Drupal::service('domain_path.helper');
    $enabled_entity_types = $domain_path_helper->getConfiguredEntityTypes();
    if ($enabled_entity_types) {
      $default_target_entity_type = !empty($default_target_entity_type) ? $default_target_entity_type : reset($enabled_entity_types);
      $entity_types_info = $this->entityTypeManager->getDefinitions();
      foreach ($enabled_entity_types as $enabled_entity_type) {
        $entity_type_options[$enabled_entity_type] = $entity_types_info[$enabled_entity_type]->getLabel();
      }
    }

    $form['entity_type'] = [
      '#title' => $this->t('Entity type'),
      '#type' => 'select',
      '#options' => $entity_type_options,
      '#default_value' => $default_target_entity_type,
      '#required' => TRUE,
      '#limit_validation_errors' => [['entity_type']],
      '#submit' => ['::submitSelectEntityType'],
      '#executes_submit_callback' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxReplaceTargetType',
        'wrapper' => 'domain-path-entity-id-wrapper',
        'method' => 'replace',
      ],
    ];

    if ($entity_type_options) {
      $entity_type_value = $form_state->getValue('entity_type');
      if ($entity_type_value) {
        $ref_entity = !empty($ref_entity) && $default_target_entity_type == $entity_type_value ? $ref_entity : NULL;
      }

      $form['entity_id'] = [
        '#prefix' => '<div id="domain-path-entity-id-wrapper">',
        '#suffix' => '</div>',
        '#type' => 'entity_autocomplete',
        '#target_type' => $entity_type_value ? $entity_type_value : $default_target_entity_type,
        '#title' => $this->t('Entity Id'),
        '#default_value' => $ref_entity,
        '#required' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * Handles switching the entity type selector.
   */
  public function ajaxReplaceTargetType(&$form, FormStateInterface $form_state) {
    $form['entity_id']['#value'] = NULL;
    return $form['entity_id'];
  }

  /**
   * Handles submit call when entity type is selected.
   */
  public function submitSelectEntityType($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
    parent::validateForm($form, $form_state);
    $domains = \Drupal::service('domain.loader')->loadMultipleSorted();
    $domain_id_value = $form_state->getValue('domain_id');
    $domain_id = isset($domain_id_value[0]['target_id']) ? $domain_id_value[0]['target_id'] : NULL;
    $entity_id_value = $form_state->getValue('entity_id');
    $entity_id = isset($entity_id_value[0]['target_id']) ? $entity_id_value[0]['target_id'] : NULL;
    $alias_value = $form_state->getValue('alias');
    $alias = isset($alias_value[0]['value']) ? $alias_value[0]['value'] : NULL;
    $domain_path_loader = \Drupal::service('domain_path.loader');

    $alias_check = rtrim(trim($alias), " \\/");
    if ($alias_check && $alias_check[0] !== '/') {
      $form_state->setErrorByName('alias', $this->t('Domain path "%alias" needs to start with a slash.', ['%alias' => $alias_check]));
    }

    if ($domain_path_entity_data = $domain_path_loader->loadByProperties(['alias' => $alias])) {
      foreach ($domain_path_entity_data as $domain_path_entity) {
        $check_entity_id = $domain_path_entity->get('entity_id')->target_id;
        $check_domain_id = $domain_path_entity->get('domain_id')->target_id;
        if ($check_entity_id != $entity_id
          && $check_domain_id == $domain_id) {
          $domain_path = $domains[$domain_id]->getPath();
          $form_state->setErrorByName('alias', $this->t('Domain path %path matches an existing domain path alias for %domain_path.', ['%path' => $alias, '%domain_path' => $domain_path]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->entity;
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The domain path %feed has been updated.', ['%feed' => $entity->toLink()->toString()]));
    } else {
      drupal_set_message($this->t('The domain path %feed has been added.', ['%feed' => $entity->toLink()->toString()]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $status;
  }

}
