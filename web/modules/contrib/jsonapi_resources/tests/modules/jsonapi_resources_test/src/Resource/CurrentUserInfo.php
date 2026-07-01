<?php

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeAttribute;
use Drupal\jsonapi\ResourceType\ResourceTypeRelationship;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\Resource\ResourceBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Route;

/**
 * Processes a request for the authenticated user's information.
 *
 * @internal
 */
class CurrentUserInfo extends ResourceBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The JSON:API Entity Resource controller.
   *
   * @var \Drupal\jsonapi\Controller\EntityResource
   */
  protected EntityResource $entityResource;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected CsrfTokenGenerator $tokenGenerator;

  /**
   * Constructs a new EntityResourceBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Tne entity type manager.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON:API resource type repository.
   * @param \Drupal\jsonapi\Controller\EntityResource $entity_resource
   *   The JSON:API Entity Resource controller.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $token_generator
   *   The CSRF token generator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ResourceTypeRepositoryInterface $resource_type_repository, EntityResource $entity_resource, AccountInterface $account, CsrfTokenGenerator $token_generator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->entityResource = $entity_resource;
    $this->currentUser = $account;
    $this->tokenGenerator = $token_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): CurrentUserInfo {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jsonapi.resource_type.repository'),
      $container->get('jsonapi.entity_resource'),
      $container->get('current_user'),
      $container->get('csrf_token')
    );
  }

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The route resource types.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function process(Request $request, array $resource_types): ResourceResponse {
    // Vary responses by user and by session, so that CSRF token changes if a
    // user logs out and then back in and different users get different CSRF
    // tokens.
    $cacheability =
      (new CacheableMetadata())
        ->addCacheContexts(['user', 'session']);

    $current_user = $this->getCurrentUser();
    $resource_type = reset($resource_types);

    $user_fields = $current_user->getFields();
    $user_roles  = $user_fields['roles'];

    $links = new LinkCollection([]);
    $primary_data = new ResourceObject(
      $cacheability,
      $resource_type,
      $current_user->uuid(),
      NULL,
      [
        'displayName' => $current_user->getDisplayName(),
        'roles' => $user_roles,
        'token' => $this->tokenGenerator->get(CsrfRequestHeaderAccessCheck::TOKEN_KEY),
      ],
      $links
    );
    $top_level_data = new ResourceObjectData([$primary_data], 1);
    $response = $this->createJsonapiResponse($top_level_data, $request);

    if ($response instanceof CacheableResponseInterface) {
      $response->addCacheableDependency($cacheability);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteResourceTypes(Route $route, string $route_name): array {
    $fields = [
      'displayName' => new ResourceTypeAttribute('displayName'),
      'roles' => new ResourceTypeRelationship('roles', NULL, TRUE, FALSE),
      'token' => new ResourceTypeAttribute('token'),
    ];

    $user_role_resource_type = $this->resourceTypeRepository->get('user_role', 'user_role');

    $resource_type = new ResourceType('current_user', 'current_user', NULL, FALSE, TRUE, TRUE, FALSE, $fields);
    $resource_type->setRelatableResourceTypes([
      'roles' => [$user_role_resource_type],
    ]);

    return [$resource_type];
  }

  /**
   * Gets the user roles related to the logged-in user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The user roles response.
   *
   * @noinspection PhpUnused
   */
  public function getRelatedRoles(Request $request): ResourceResponse {
    $user_resource_type = $this->resourceTypeRepository->get('user', 'user');

    return $this->entityResource->getRelated(
      $user_resource_type,
      $this->getCurrentUser(),
      'roles',
      $request
    );
  }

  /**
   * Gets the "roles" relationship of the logged-in user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The user roles relationship response.
   *
   * @noinspection PhpUnused
   */
  public function getRolesRelationship(Request $request): ResourceResponse {
    $user_resource_type = $this->resourceTypeRepository->get('user', 'user');

    return $this->entityResource->getRelationship(
      $user_resource_type,
      $this->getCurrentUser(),
      'roles',
      $request
    );
  }

  /**
   * Gets the logged-in user.
   *
   * @return \Drupal\user\UserInterface
   *   The current user.
   */
  protected function getCurrentUser(): UserInterface {
    try {
      $user_storage = $this->entityTypeManager->getStorage('user');
      $current_user = $user_storage->load($this->currentUser->id());
      assert($current_user instanceof UserInterface);
      return $current_user;
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      throw new HttpException(
        Response::HTTP_INTERNAL_SERVER_ERROR,
        'Failed to load user storage: ' . $e->getMessage(),
        $e
      );
    }
  }

}
