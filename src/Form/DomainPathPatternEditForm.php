<?php

namespace Drupal\domain_path\Form;

use Drupal\pathauto\Entity\PathautoPattern;
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

  protected $domain_path_property = 'domains';

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
        $default_domains = $this->entity->getThirdPartySetting('domain_path', $this->domain_path_property);

        if ($domains = $this->domainLoaderManager->loadMultipleSorted()) {
          $domain_options = [];
          foreach ($domains as $domain_id => $domain) {
            $domain_options[$domain_id] = $domain->getPath();
          }
          $form['pattern_container']['domains'] = [
            '#title' => $this->t('Domains'),
            '#type' => 'checkboxes',
            '#options' => $domain_options,
            '#default_value' => $default_domains ? $default_domains : [],
            '#description' => $this->t('Check to which domains this pattern should be applied.'),
          ];
        }
      }
    }

    $form['#entity_builders'][] = [$this, 'entityBuilder'];

    return $form;
  }

  public function entityBuilder( $entity_type,
                                 PathautoPattern $pattern,
                                 &$form,
                                 \Drupal\Core\Form\FormStateInterface $form_state) {
    //If our property can be found from form_state values
    if ($form_state->getValue($this->domain_path_property)) {
      //We can update the linked property on thirdPartySetting
      $pattern->setThirdPartySetting(
        'domain_path',
        $this->domain_path_property,
        $form_state->getValue($this->domain_path_property)
      );
    }
    else {
      //User surely wanted to remove the previous value,
      //so remove it from thirdPartySetting property too
      $pattern->unsetThirdPartySetting('domain_path', $this->domain_path_property);
    }
  }

}
