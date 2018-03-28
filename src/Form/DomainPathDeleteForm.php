<?php

namespace Drupal\domain_path\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a domain_path entity.
 *
 * @ingroup domain_path
 */
class DomainPathDeleteForm extends ContentEntityDeleteForm {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %id?', ['%id' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the domain path list.
   */
  public function getCancelUrl() {
    return new Url('entity.domain_path.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. logger() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    $this->logger('domain_path')->notice('deleted %id.',
      [
        '%id' => $this->entity->id(),
      ]);
    // Redirect to domain path list after delete.
    $form_state->setRedirect('entity.domain_path.collection');
  }

}
