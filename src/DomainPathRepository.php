<?php

namespace Drupal\domain_path;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\domain_path\Entity\DomainPath;
use Drupal\domain_path\Exception\DomainPathRedirectLoopException;

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
   * @param string $source_path
   *   The redirect source path.
   * @param array $query
   *   The redirect source path query.
   * @param $language
   *   The language for which is the redirect.
   *
   * @return \Drupal\domain_path\Entity\DomainPath
   *   The matched redirect entity.
   *
   * @throws \Drupal\domain_path\Exception\DomainPathRedirectLoopException
   */
  public function findMatchingRedirect($domain_id, $entity_id, $language = Language::LANGCODE_NOT_SPECIFIED) {
    // Load redirects by hash. A direct query is used to improve performance.
    $id = $this->connection->query('SELECT id FROM {domain_path} WHERE domain_id = :domain_id AND entity_id = :entity_id AND language = :language',
      [
        ':domain_id' => $domain_id,
        ':entity_id' => $entity_id,
        ':language' => $language,
      ]
    )->fetchField();

    if (!empty($id)) {

      // Check if this is a loop.
      if (in_array($id, $this->foundRedirects)) {
        throw new DomainPathRedirectLoopException($source_path, $id);
      }
      $this->foundRedirects[] = $id;
      $domain_path = $this->load($id);
      // Find chained redirects.
      /*if ($recursive = $this->findByRedirect($redirect, $language)) {
        // Reset found redirects.
        $this->foundRedirects = [];
        return $recursive;
      }*/

      return $domain_path;
    }

    return NULL;
  }

  /**
   * Helper function to find recursive redirects.
   *
   * @param \Drupal\redirect\Entity\Redirect
   *   The redirect object.
   * @param string $language
   *   The language to use.
   */
  protected function findByRedirect(Redirect $redirect, $language) {
    $uri = $redirect->getRedirectUrl();
    $baseUrl = \Drupal::request()->getBaseUrl();
    $path = ltrim(substr($uri->toString(), strlen($baseUrl)), '/');
    $query = $uri->getOption('query') ?: [];
    return $this->findMatchingRedirect($path, $query, $language);
  }

  /**
   * Finds redirects based on the source path.
   *
   * @param string $source_path
   *   The redirect source path (without the query).
   *
   * @return \Drupal\redirect\Entity\Redirect[]
   *   Array of redirect entities.
   */
  public function findBySourcePath($source_path) {
    $ids = $this->manager->getStorage('domain_path')->getQuery()
      ->condition('redirect_source.path', $source_path, 'LIKE')
      ->execute();
    return $this->manager->getStorage('domain_path')->loadMultiple($ids);
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
