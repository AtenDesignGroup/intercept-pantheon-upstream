<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Kernel\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * Sends a request through the HTTP kernel in a kernel test.
 */
trait RequestTrait {

  /**
   * Passes a request to the HTTP kernel and returns a response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param bool $catch
   *   (optional) Whether the kernel should catch exceptions and render them as
   *   responses. Defaults to FALSE, so unexpected exceptions surface with a
   *   full stack trace. Pass TRUE when a test asserts on an error response
   *   (e.g. a 400) that the resource raises as an exception.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  protected function request(Request $request, bool $catch = FALSE): Response {
    // \Drupal\KernelTests\KernelTestBase::bootKernel() pushes a bogus request
    // onto the request stack so the kernel can boot and URL generation works
    // in tests. Pop it (and any earlier test requests) so the kernel handles
    // our request as a clean main request.
    $request_stack = $this->container->get('request_stack');
    while ($request_stack->getCurrentRequest() !== NULL) {
      $request_stack->pop();
    }

    $http_kernel = $this->container->get('http_kernel');
    self::assertInstanceOf(HttpKernelInterface::class, $http_kernel);
    $response = $http_kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, $catch);

    self::assertInstanceOf(TerminableInterface::class, $http_kernel);
    $http_kernel->terminate($request, $response);

    return $response;
  }

  /**
   * Decodes a JSON response body into an array.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   *
   * @return array
   *   The decoded JSON document.
   */
  protected static function decodeResponse(Response $response): array {
    $content = $response->getContent();
    self::assertIsString($content);
    self::assertJson($content);
    return \json_decode($content, TRUE);
  }

}
