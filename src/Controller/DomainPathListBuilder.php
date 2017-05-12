<?php

namespace Drupal\domain_path\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for domain_path entity.
 *
 * @ingroup domain_path
 */
class DomainPathListBuilder extends EntityListBuilder {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;


  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('url_generator')
    );
  }

  /**
   * Constructs a new DomainPathListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type domain_path.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $storage);
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['table'] = parent::render();

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Domain Path ID');
    $header['domain_id'] = $this->t('Domain ID');
    $header['alias'] = $this->t('Alias');
    $header['language'] = $this->t('Language');
    $header['entity_type'] = $this->t('Entity Type');
    $header['entity_id'] = $this->t('Entity ID');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\domain_path\Entity\DomainPath */
    $row['id'] = $entity->id();
    $row['domain_id'] = $entity->get('domain_id')->target_id;
    $row['alias'] = $entity->get('alias')->value;
    $row['language'] = $entity->get('language')->value;
    $row['entity_type'] = $entity->get('entity_type')->value;
    $row['entity_id'] = $entity->get('entity_id')->target_id;

    return $row + parent::buildRow($entity);
  }

}
