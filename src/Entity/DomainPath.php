<?php
/**
 * @file
 * Contains \Drupal\domain_path\Entity\DomainPath.
 */

namespace Drupal\domain_path\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\domain_path\DomainPathInterface;

/**
 * Defines the DomainPath entity.
 *
 * @ingroup domain_path
 *
 *
 * @ContentEntityType(
 *   id = "domain_path",
 *   label = @Translation("Domain path entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\domain_path\Controller\DomainPathListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\domain_path\Form\DomainPathForm",
 *       "edit" = "Drupal\domain_path\Form\DomainPathForm",
 *       "delete" = "Drupal\domain_path\Form\DomainPathDeleteForm",
 *     },
 *     "access" = "Drupal\domain_path\DomainPathAccessControlHandler",
 *   },
 *   base_table = "domain_path",
 *   admin_permission = "administer domain paths",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "domain_id" = "domain_id",
 *     "language" = "language",
 *     "alias" = "alias",
 *     "source" = "source",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/domain_path/{domain_path}/edit",
 *     "edit-form" = "/admin/config/domain_path/{domain_path}/edit",
 *     "delete-form" = "/admin/config/domain_path/{domain_path}/delete",
 *     "collection" = "/admin/config/domain_path"
 *   }
 * )
 */
class DomainPath extends ContentEntityBase implements DomainPathInterface {

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Term entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Domain path entity.'))
      ->setReadOnly(TRUE);

    // Owner field of the Domain path.
    // Entity reference field, holds the reference to the domain object.
    // The view shows the title field of the domain.
    // The form presents a auto complete field for the domain title.
    $fields['domain_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Domain Id'))
      ->setDescription(t('The Title of the associated domain.'))
      ->setSetting('target_type', 'domain')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'weight' => -7,
      ])
      ->setRequired(TRUE);

    $fields['language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The language of Domain path entity.'))
      ->setRequired(TRUE);

    // Name field for the domain path.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source'))
      ->setDescription(t('The source patch of the Domain path alias.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setRequired(TRUE);

    // Name field for the domain path.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['alias'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alias'))
      ->setDescription(t('The alias of the Domain path entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * Get source for domain_path.
   *
   * @return string
   *   Returns domain path source.
   */
  public function getSource() {
    return $this->get('source')->value;
  }

  /**
   * Get alias for domain_path.
   *
   * @return string
   *   Returns domain path alias.
   */
  public function getAlias() {
    return $this->get('alias')->value;
  }

  /**
   * Get language code for domain_path.
   *
   * @return string
   *   Returns domain path language code.
   */
  public function getLanguageCode() {
    return $this->get('language')->value;
  }

  /**
   * Get domain id for domain_path.
   *
   * @return string
   *   Returns domain path domain id.
   */
  public function getDomainId() {
    return $this->get('domain_id')->target_id;
  }

}
