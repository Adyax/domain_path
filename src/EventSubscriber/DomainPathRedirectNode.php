<?php

/**
* @file
* Contains \Drupal\domain_path\EventSubscriber\DomainPathRedirectNode
*/

namespace Drupal\domain_path\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\redirect\RedirectChecker;
use Symfony\Component\Routing\RequestContext;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\domain_path\Exception\DomainPathRedirectLoopException;
use Drupal\domain_path\DomainPathRepository;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

class DomainPathRedirectNode implements EventSubscriberInterface {


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
    // This announces which events you want to subscribe to.
    // We only need the request event for this example.  Pass
    // this an array of method names
    return([
      KernelEvents::REQUEST => [
        ['redirectNode'],
      ]
    ]);
  }

  /**
  * Redirect requests for my_content_type node detail pages to node/123.
  *
  * @param GetResponseEvent $event
  * @return void
  */
  public function redirectNode(GetResponseEvent $event) {
    $request = clone $event->getRequest();
    $domain = $this->domain_negotiator->getActiveDomain();

    if (!$this->checker->canRedirect($request)) {
      return;
    }

    // no need to redirect. default domain is controlled by redirect module.
    /*if ($domain->isDefault()) {
      return;
    }*/

    // todo: add term/user routes
    // This is necessary because this also gets called on
    // node sub-tabs such as "edit", "revisions", etc.  This
    // prevents those pages from redirected.
    if ($request->attributes->get('_route') !== 'entity.node.canonical') {
      return;
    }

    // Get URL info and process it to be used for hash generation.
    //parse_str($request->getQueryString(), $request_query);

    // Do the inbound processing so that for example language prefixes are
    // removed.
    //$path = $this->pathProcessor->processInbound($request->getPathInfo(), $request);
    //$path = ltrim($path, '/');

    $this->context->fromRequest($request);

    try {
      $node = \Drupal::routeMatch()->getParameter('node');


      // TODO: enable for all entities types
      $domain_entity = $this->redirectRepository->findMatchingRedirect($domain->id(), $node->id(), $this->languageManager->getCurrentLanguage()->getId());
    }
    catch (DomainPathRedirectLoopException $e) {
      \Drupal::logger('domain_path')->warning($e->getMessage());
      $response = new Response();
      $response->setStatusCode(503);
      $response->setContent('Service unavailable');
      $event->setResponse($response);
      return;
    }

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