<?php

/**
* @file
* Contains \Drupal\domain_path\EventSubscriber\DomainPathRedirectNode
*/

namespace Drupal\domain_path\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\redirect\RedirectChecker;
use Symfony\Component\Routing\RequestContext;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
//use Drupal\domain_path\Exception\DomainPathRedirectLoopException;
use Drupal\domain_path\DomainPathRepository;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

class DomainPathRedirect implements EventSubscriberInterface {


  protected $domain_negotiator;

  /** @var  \Drupal\redirect\RedirectRepository */
  protected $redirectRepository;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\redirect\RedirectChecker
   */
  protected $checker;

  /**
   * @var \Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * A path processor manager for resolving the system path.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
   *
   * @param \Drupal\redirect\RedirectRepository $redirect_repository
   *   The redirect entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config.
   * @param \Drupal\Core\Path\AliasManager $alias_manager
   *   The alias manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\redirect\RedirectChecker $checker
   *   The redirect checker service.
   * @param \Symfony\Component\Routing\RequestContext
   *   Request context.
   */
  public function __construct(DomainNegotiatorInterface $domain_negotiator, DomainPathRepository $repository, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config, AliasManager $alias_manager, ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager, RedirectChecker $checker, RequestContext $context, InboundPathProcessorInterface $path_processor) {
    $this->domain_negotiator = $domain_negotiator;
    $this->redirectRepository = $repository;
    $this->languageManager = $language_manager;
    $this->config = $config->get('redirect.settings');
    $this->aliasManager = $alias_manager;
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
    $this->checker = $checker;
    $this->context = $context;
    $this->pathProcessor = $path_processor;
  }

  /**
  * {@inheritdoc}
  */
  public static function getSubscribedEvents() {
    return([
      KernelEvents::REQUEST => [
        ['redirectEntity'],
      ]
    ]);
  }

  /**
  * Redirect requests for entities to domains aliases.
  *
  * @param GetResponseEvent $event
  * @return void
  */
  public function redirectEntity(GetResponseEvent $event) {
    $request = clone $event->getRequest();
    $domain = $this->domain_negotiator->getActiveDomain();

    if (!$this->checker->canRedirect($request)) {
      return;
    }

    $domain_path_helper = \Drupal::service('domain_path.helper');
    $entity_canonical = $domain_path_helper->getConfiguredEntityCanonical();
    $route_current = $request->attributes->get('_route');
    // todo: add term/user routes
    // This is necessary because this also gets called on
    // node sub-tabs such as "edit", "revisions", etc.  This
    // prevents those pages from redirected.
    /*if (!in_array($route_current, $entity_canonical)) {
      return;
    }*/
    if (!$parameter = $entity_canonical[$route_current]) {
      return;
    }

    $this->context->fromRequest($request);

    $entity = \Drupal::routeMatch()->getParameter($parameter);
    $c =$entity->getEntityTypeId();
    $domain_entity = $this->redirectRepository->findMatchingRedirect($domain->id(), $entity->getEntityTypeId(), $entity->id(), $this->languageManager->getCurrentLanguage()->getId());

    if (!empty($domain_entity)) {

      // Handle internal path.
      $url = $domain_entity->getUrl();

      $headers = [
        'X-Redirect-ID' => $domain_entity->id(),
      ];
      $response = new TrustedRedirectResponse($url->setAbsolute()->toString(), 301, $headers);
      $response->addCacheableDependency($domain_entity);
      $event->setResponse($response);
    }
  }

}