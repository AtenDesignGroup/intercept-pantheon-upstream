<?php

declare(strict_types=1);

namespace Drupal\jsonapi_resources\Unstable\Controller\ArgumentResolver;

use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi_resources\Unstable\DocumentExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

// The ArgumentValueResolverInterface is deprecated since Symfony 6.2 and
// removed from Symfony 7. Hence, below workaround to run PHPUnit tests against
// Drupal 9, 10 and 11.
// @todo Remove when all supported versions require Symfony 7.
if (!interface_exists(ValueResolverInterface::class)) {
  class_alias('\Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface', ValueResolverInterface::class);
}

/**
 * Deserializes POST, PATCH and DELETE request documents.
 *
 * @internal
 */
final class DocumentResolver implements ValueResolverInterface {

  public function __construct(
    protected DocumentExtractor $documentExtractor,
  ) {}

  /**
   * Backwards-compatibility layer for Symfony < 7.
   *
   * @todo Remove when all supported versions of Drupal require Symfony 7.
   *
   * @param Request $request
   *   Request.
   * @param ArgumentMetadata $argument
   *   Argument metadata.
   *
   * @return bool
   *   Flag indicating supported status.
   */
  public function supports(Request $request, ArgumentMetadata $argument): bool {
    return $this->shouldResolve($request, $argument);
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(Request $request, ArgumentMetadata $argument): iterable {
    if (!$this->shouldResolve($request, $argument)) {
      return [];
    }
    yield $this->documentExtractor->getDocument($request);
  }

  /**
   * Gets whether the given request should be resolved.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata $argument
   *   The argument.
   *
   * @return bool
   *   Whether the given request should be resolved.
   */
  private function shouldResolve(Request $request, ArgumentMetadata $argument): bool {
    $supported_method = in_array($request->getMethod(), [
      'POST',
      'PATCH',
    ], TRUE);
    $is_delete = $request->isMethod('DELETE');
    $is_relationship = $request->attributes->has('_jsonapi_relationship_field_name');
    $supported_method = $supported_method || ($is_delete && $is_relationship);
    $supported_format = $request->getRequestFormat() === 'api_json';
    $correct_type = $argument->getType() === JsonApiDocumentTopLevel::class;
    return $supported_method && $supported_format && $correct_type;
  }

}
