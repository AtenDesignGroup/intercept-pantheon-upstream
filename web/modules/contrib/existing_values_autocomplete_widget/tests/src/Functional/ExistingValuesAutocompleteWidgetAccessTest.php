<?php

namespace Drupal\Tests\existing_values_autocomplete_widget\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\existing_values_autocomplete_widget\TestContentTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the access to the autocomplete controller.
 *
 * @group existing_values_autocomplete_widget
 */
class ExistingValuesAutocompleteWidgetAccessTest extends BrowserTestBase {

  use TestContentTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'existing_values_autocomplete_widget',
    'node',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A test article node used for controller responses.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testArticle;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createArticleTypeWithField();
    $this->testArticle = $this->createArticle('abc');

    $this->user = $this->drupalCreateUser();
    $this->drupalLogin($this->user);
  }

  /**
   * Tests the controller access based on the main access permission.
   */
  public function testControllerAccessPermission(): void {
    // Try to access the controller without the permission:
    $this->drupalGet('/existing-values/autocomplete/node/article/field_text', [
      'query' => ['q' => 'a'],
    ]);
    $session = $this->assertSession();
    $session->statusCodeEquals(Response::HTTP_OK);
    $session->responseContains('abc');
    // Try again as a user with permission and receive the response:
    Role::load(RoleInterface::AUTHENTICATED_ID)
      ->revokePermission('access content')
      ->save();
    $this->drupalGet('/existing-values/autocomplete/node/article/field_text', [
      'query' => ['q' => 'a'],
    ]);
    $session->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * Tests the controller access based on whether the content is published.
   */
  public function testControllerAccessPublished(): void {
    // Try to access the controller as usual:
    $this->drupalGet('/existing-values/autocomplete/node/article/field_text', [
      'query' => ['q' => 'a'],
    ]);
    $session = $this->assertSession();
    $session->statusCodeEquals(Response::HTTP_OK);
    $session->responseContains('abc');
    // Unpublish the test article and see that it's field value is now missing
    // from the controller results:
    $this->testArticle->setUnpublished()->save();
    $this->drupalGet('/existing-values/autocomplete/node/article/field_text', [
      'query' => ['q' => 'a'],
    ]);
    $session->statusCodeEquals(Response::HTTP_OK);
    $session->responseNotContains('abc');
  }

  /**
   * Tests the controller access based on whether the widget has the right type.
   */
  public function testControllerAccessFieldWidgetType(): void {
    // Try to access the controller on the autocomplete field:
    $this->drupalGet('/existing-values/autocomplete/node/article/field_text', [
      'query' => ['q' => 'a'],
    ]);
    $session = $this->assertSession();
    $session->statusCodeEquals(Response::HTTP_OK);
    $session->responseContains('abc');
    // Try to access the controller on a field that does not have the widget:
    $this->drupalGet('/existing-values/autocomplete/node/article/title', [
      'query' => ['q' => 'a'],
    ]);
    $session->statusCodeEquals(Response::HTTP_OK);
    $session->responseNotContains('abc');
  }

}
