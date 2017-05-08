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
    $entity = $this->entity;

    $form['language'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
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
