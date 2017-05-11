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
use Drupal\Core\Url;

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
 *     "list_builder" = "Drupal\domain_path\Entity\Controller\DomainPathListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\domain_path\Form\DomainPathForm",
 *       "edit" = "Drupal\domain_path\Form\DomainPathForm",
 *       "delete" = "Drupal\domain_path\Form\DomainPathDeleteForm",
 *     },
 *     "access" = "Drupal\domain_path\DomainPathAccessControlHandler",
 *   },
 *   base_table = "domain_path",
 *   admin_permission = "administer domain path entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "domain_id" = "domain_id",
 *     "alias" = "alias",
 *     "language" = "language",
 *     "entity_type" = "entity_type",
 *     "entity_id" = "entity_id"
 *   },
 *   links = {
 *     "canonical" = "/domain_path/{domain_path}",
 *     "edit-form" = "/domain_path/{domain_path}/edit",
 *     "delete-form" = "/domain_path/{domain_path}/delete",
 *     "collection" = "/domain_path/list"
 *   },
 *   field_ui_base_route = "entity.domain_path_settings",
 * )
 */
class DomainPath extends ContentEntityBase  implements DomainPathInterface {

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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Name field for the domain path.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['alias'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alias'))
      ->setDescription(t('The alias of the Domain path entity.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The language of Domain path entity.'));

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type of the Domain path entity.'));

    // Owner field of the Domain path.
    // Entity reference field, holds the reference to the node object.
    // The view shows the title field of the node.
    // The form presents a auto complete field for the node title.
    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity Id'))
      ->setDescription(t('The Title of the associated node.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Gets the source base URL.
   *
   * @return string
   */
  public function getUrl() {
    $domain_id = $this->get('domain_id')->get(0)->getValue()['target_id'];
    $entity_type = 'node';
    $nid = $this->get('entity_id')->get(0)->getValue()['target_id'];
    return Url::fromRoute('domain_path.view', [
      'domain' => $domain_id,
      'entity_type' => $entity_type,
      'node' => $nid
    ]);
  }
}
