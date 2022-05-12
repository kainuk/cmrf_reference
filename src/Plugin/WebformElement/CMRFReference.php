<?php
/**
 * @author Klaas Eikelboom  <klaas.eikelboom@civicoop.org>
 * @date 16-May-2020
 * @license  AGPL-3.0
 */

namespace Drupal\cmrf_reference\Plugin\WebformElement;

use Drupal\cmrf_reference\CMRFReferenceUtils;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\TextBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 *
 * @WebformElement(
 *   id = "cmrf_reference",
 *   label = @Translation("CMRF Reference"),
 *   description = @Translation("Select with help of CMRF Api"),
 *   category = @Translation("CMRF"),
 * )
 */
class CMRFReference extends TextBase {

  // Its just magic - add this trait and AjaX works
  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
        // Autocomplete settings.
        'autocomplete_existing' => FALSE,
        'autocomplete_items' => [],
        'autocomplete_limit' => 11,
        'autocomplete_match' => 3,
        'cmrf_connection' => CMRFReferenceUtils::NONE,
        'dataprocessor' => CMRFReferenceUtils::NONE,
        'cmrf_search' => CMRFReferenceUtils::NONE,
        'cmrf_return' => CMRFReferenceUtils::NONE
      ] + TextBase::getDefaultProperties();
    // Remove autocomplete property which is not applicable to
    // this autocomplete element.
    unset($properties['autocomplete']);
    return $properties;
  }

  /**
   * @param array $element
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
    $element['#attached']['library'][] = 'cmrf_reference/webform.element.cmrf_reference';
    $element['#attached']['drupalSettings']['cmrf_reference']['webform'] =  $webform_submission->getWebform()->id();
    $element['#attached']['drupalSettings']['cmrf_reference']['size'][$element["#webform_key"]] = $element['#size'] ?? '20em';
    $element['#attached']['drupalSettings']['cmrf_reference']['placeholder'][$element["#webform_key"]] = $element['#placeholder'] ?? '';
    $value = isset($element['#default_value']) ? $element['#default_value'] : false;
    if($value) {
      $result = CMRFReferenceUtils::lookup($element['#cmrf_connection'],
        $element['#dataprocessor'],
        $element['#cmrf_search'],
        $value,
        $element['#cmrf_return']
      );
      $element['#attached']['drupalSettings']['cmrf_reference']['defaultid'][$element["#webform_key"]] = $value;
      $element['#attached']['drupalSettings']['cmrf_reference']['defaulttext'][$element["#webform_key"]] = $result;
    }
  }

  /**
   * @param $key
   * @param $form_state
   *
   * @return mixed
   */
  private function lookupInConfiguration($key,$form_state){
    $properties = $form_state->getValue('properties') ?? $form_state->get('element_properties');
    return $properties[$key] ?? CMRFReferenceUtils::NONE;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $properties = $form_state->get('element_properties');
    $connection = $properties['cmrf_connection'];
    $dataprocessor = $properties['dataprocessor'];
    $formCmrf = $this->cmrfForm($connection, $dataprocessor);
    $form['cmrf'] = $formCmrf;
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function connectionCallback(array $form, FormStateInterface $form_state) {
    $properties = $form_state->getValue('properties');
    $connection = $properties['cmrf_connection'];
    $dataprocessor = $properties['custom']['dataprocessor'];
    $formCmrf = $this->cmrfForm($connection, $dataprocessor);
    return $formCmrf;
  }

  /**
   * @param $connection
   * @param $dataprocessor
   *
   * @return array
   */
  protected function cmrfForm($connection, $dataprocessor) {
    $formCmrf = [
      '#type' => 'fieldset',
      '#title' => $this->t('CiviMFR settingss'),
      '#attributes' => ['id' => 'cmfr-reference-stuff'],
    ];
    $formCmrf['cmrf_connection'] = [
      '#type' => 'select',
      '#title' => $this->t('Connection'),
      '#description' => $this->t('Select the connection'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::connections(),
    ];
    $formCmrf['dataprocessor'] = [
      '#type' => 'select',
      '#title' => $this->t('Dataprocessor'),
      '#description' => $this->t('Select the dataprocessor'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::dataprocessors($connection),
    ];
    $formCmrf['cmrf_search'] = [
      '#type' => 'select',
      '#title' => $this->t('Search field'),
      '#description' => $this->t('Select the field to search with'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::fields($connection, $dataprocessor),
    ];
    $formCmrf['cmrf_return'] = [
      '#type' => 'select',
      '#title' => $this->t('Return'),
      '#description' => $this->t('Return value'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::fields($connection, $dataprocessor),
    ];

    $formCmrf['autocomplete_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete limit'),
      '#description' => $this->t("The maximum number of matches to be displayed."),
      '#min' => 1,
    ];
    $formCmrf['autocomplete_match'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete minimum number of characters'),
      '#description' => $this->t('The minimum number of characters a user must type before a search is performed.'),
      '#min' => 1,
    ];
    return $formCmrf;
  }

}
