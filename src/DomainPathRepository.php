<?php

namespace Drupal\domain_path;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
//use Drupal\domain_path\Exception\DomainPathRedirectLoopException;

class DomainPathRepository {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * An array of found redirect IDs to avoid recursion.
   *
   * @var array
   */
  protected $foundRedirects = [];

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityManagerInterface $manager, Connection $connection) {
    $this->manager = $manager;
    $this->connection = $connection;
  }

  /**
   * Gets a redirect for given path, query and language.
   *
   * @param string $domain_id
   *
   * @param array $entity_type
   *
   * @param array $entity_id
   *
   * @param $language
   *   The language for which is the redirect.
   *
   * @return \Drupal\domain_path\Entity\DomainPath
   *   The matched redirect entity.
   *
   * @throws \Drupal\domain_path\Exception\DomainPathRedirectLoopException
   */
  public function findMatchingRedirect($domain_id, $entity_type, $entity_id, $language = Language::LANGCODE_NOT_SPECIFIED) {
    // Load redirects by hash. A direct query is used to improve performance.
    $id = $this->connection->query('SELECT id FROM {domain_path} WHERE domain_id = :domain_id AND entity_type = :entity_type AND entity_id = :entity_id AND language = :language',
      [
        ':domain_id' => $domain_id,
        ':entity_type' => $entity_type,
        ':entity_id' => $entity_id,
        ':language' => $language,
      ]
    )->fetchField();

    if (!empty($id)) {
      $domain_path = $this->load($id);

      return $domain_path;
    }

    return NULL;
  }

  /**
   * Load redirect entity by id.
   *
   * @param int $redirect_id
   *   The redirect id.
   *
   * @return \Drupal\redirect\Entity\Redirect
   */
  public function load($domain_path_id) {
    return $this->manager->getStorage('domain_path')->load($domain_path_id);
  }

  /**
   * Loads multiple redirect entities.
   *
   * @param array $redirect_ids
   *   Redirect ids to load.
   *
   * @return \Drupal\redirect\Entity\Redirect[]
   *   List of redirect entities.
   */
  public function loadMultiple(array $redirect_ids = NULL) {
    return $this->manager->getStorage('domain_path')->loadMultiple($redirect_ids);
  }
}
