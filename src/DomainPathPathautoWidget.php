<?php

namespace Drupal\domain_path;

use Drupal\pathauto\PathautoWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the core path widget.
 */
class DomainPathPathautoWidget extends PathautoWidget implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $domainLoaderManager;

  /**
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $accountManager;

  /**
   * DomainPathPathautoWidget constructor.
   *
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param array $third_party_settings
   * @param \Drupal\domain\DomainLoaderInterface $domain_loader_manager
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   * @param \Drupal\Core\Session\AccountInterface $account_manager
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, DomainLoaderInterface $domain_loader_manager, AliasManagerInterface $alias_manager, AccountInterface $account_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->domainLoaderManager = $domain_loader_manager;
    $this->aliasManager = $alias_manager;
    $this->accountManager = $account_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('domain.loader'),
      $container->get('path.alias_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity = $items->getEntity();

    $pattern = \Drupal::service('pathauto.generator')->getPatternByEntity($entity);
    if (empty($pattern)) {
      return $element;
    }

    if ($domains = $this->domainLoaderManager->loadMultipleSorted()) {
      $current = t('<none>');

      $entity_id = NULL;
      //$node = $form_state->getFormObject()->getEntity();
      $langcode = NULL;
      if ($entity && $entity_id = $entity->id()) {
        $entity_type = $entity->getEntityTypeId();
        $current = $this->aliasManager->getAliasByPath("/$entity_type/$entity_id");
        $langcode = $entity->get('langcode')->value;
        $user = $this->accountManager;

        $show_delete = FALSE;
        $domain_path_loader = \Drupal::service('domain_path.loader');

        foreach ($domains as $domain_id => $domain) {
          $path = FALSE;
          $properties = [
            'entity_id' => $entity_id,
            'language' => $langcode,
            'domain_id' => $domain_id,
            'entity_type' => $entity_type,
          ];
          if ($entity_id && $domain_paths = $domain_path_loader->loadByProperties($properties)) {
            foreach ($domain_paths as $domain_path) {
              $path = $domain_path->get('alias')->value;
            }
          }

          $default = '';
          if ($path) {
            $show_delete = TRUE;
          }

          $element[$domain_id] = [
            '#type' => 'textfield',
            '#title' => Html::escape($domain->getPath()),
            '#default_value' => $path ? $path : $default,
            '#access' => $user->hasPermission('edit domain path entity'),
          ];
        }
      }
    }

    /*$element['pathauto'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Generate automatic URL alias'),
      '#default_value' => $entity->path->pathauto,
      '#description' => $description,
      '#weight' => -1,
    );   */

    return $element;
  }

}
