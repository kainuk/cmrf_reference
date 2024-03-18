<?php
/**
 * @author Klaas Eikelboom  <klaas.eikelboom@civicoop.org>
 * @date 16-May-2020
 * @license  AGPL-3.0
 */

namespace Drupal\cmrf_reference\Controller;

use Drupal\cmrf_reference\CMRFReferenceUtils;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Encapsulates the Civi Call to lookup de reference values for the autocomplete
 */
class CMRFReferenceController extends ControllerBase {

  /**
   * Returns response for the element autocomplete route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $key
   *   Webform element key.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request, WebformInterface $webform, $key) {
    // Get autocomplete query.
    $q = $request->query->get('q') ?: '';
    $current_selection = $request->query->get('current_selection') ?: '';
    if (strlen($current_selection)) {
      $current_selection = explode(",", $current_selection);
    }

    // Get the initialized webform element.
    $element = $webform->getElement($key);
    if (!$element) {
      return new JsonResponse(['results' => []]);
    }

    // Set default autocomplete properties.
    $element += [
      '#autocomplete_existing' => FALSE,
      '#autocomplete_items' => [],
      '#autocomplete_match' => 3,
      '#autocomplete_limit' => 10,
    ];

    // Check minimum number of characters.
    if (mb_strlen($q) < (int) $element['#autocomplete_match']) {
      return new JsonResponse(['results' => []]);
    }
    $matches = CMRFReferenceUtils::matches(
      $element['#cmrf_connection'],
      $element['#dataprocessor'],
      $element['#cmrf_search'],
      $q,     // the search element
      $element['#cmrf_display'],
      $element['#cmrf_return'],
      $element['#autocomplete_limit'],
      $element['#cmrf_extra_filter'] ?? NULL,
      $request->query->get('extra_filter_value'),
      $element['#cmrf_default_filter'],
      $current_selection
    );
    return new JsonResponse($matches);
  }

}
