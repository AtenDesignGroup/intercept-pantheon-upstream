<?php

namespace Drupal\jsonapi;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;

/**
 * Extends ResourceResponse with cacheability.
 *
 * We want to have the same functionality for both responses that are cacheable
 * and those that are not.  This response class should be used in all instances
 * where the response is expected to be cacheable.  This approach is similar
 * to the REST module's ModifiedResourceResponse.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\rest\ModifiedResourceResponse
 */
class CacheableResourceResponse extends ResourceResponse implements CacheableResponseInterface {

  use CacheableResponseTrait;

}
