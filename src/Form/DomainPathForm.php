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
    $langcode = NULL;
    $language_manager = \Drupal::service('language_manager');

    foreach ($language_manager->getLanguages() as $language) {
      $options[$language->getId()] = $language->getName();
    }

    if ($entity = $this->entity) {
      $langcode = $entity->get('language')->value;
    }

    $form['language'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => isset($options[$langcode]) ? $options[$langcode] : NULL,
      '#languages' => Language::STATE_ALL,
      '#options' => $options,
    ];

    $form['entity_type'] = [
      '#type' => 'hidden',
      '#value' => 'node',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
    parent::validateForm($form, $form_state);
    //$entity = $this->entity;
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
      $form_state->setErrorByName('alias', t('Domain path "%alias" needs to start with a slash.', ['%alias' => $alias_check]));
    }

    if ($domain_path_entity_data = $domain_path_loader->loadByProperties(['alias' => $alias])) {
      foreach ($domain_path_entity_data as $domain_path_entity) {
        $check_entity_id = $domain_path_entity->get('entity_id')->target_id;
        $check_domain_id = $domain_path_entity->get('domain_id')->target_id;
        if ($check_entity_id != $entity_id
          && $check_domain_id == $domain_id) {
          $domain_path = $domains[$domain_id]->getPath();
          $form_state->setErrorByName('alias', t('Domain path %path matches an existing domain path alias for %domain_path.', ['%path' => $alias, '%domain_path' => $domain_path]));
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
