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
        'cmrf_display' => CMRFReferenceUtils::NONE,
        'cmrf_return' => CMRFReferenceUtils::NONE,
        'cmrf_default_filter' => CMRFReferenceUtils::NONE,
        'cmrf_extra_filter' => CMRFReferenceUtils::NONE,
        'cmrf_extra_filter_value' => "",
        'cmrf_multiple' => FALSE,
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
    $element['#attached']['drupalSettings']['cmrf_reference']['webform'] = $webform_submission->getWebform()
      ->id();
    $element['#attached']['drupalSettings']['cmrf_reference']['size'][$element["#webform_key"]] = $element['#size'] ?? '20em';
    $element['#attached']['drupalSettings']['cmrf_reference']['placeholder'][$element["#webform_key"]] = $element['#placeholder'] ?? '';
    $element['#attached']['drupalSettings']['cmrf_reference']['required'][$element["#webform_key"]] = $element['#required'] ?? TRUE;
    if (isset($element['#cmrf_multiple']) && $element['#cmrf_multiple']) {
      $element['#attributes']['multiple'] = 'multiple';
      $element['#attributes']['name'] = $element["#webform_key"] . '[]';
      if (isset($element['#required']) && $element['#required']) {
        unset($element['#required']);
        $element['#attributes']['required'] = 'required';
      }
    }
    $element['#attributes']['webform_key'] = $element["#webform_key"];
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $properties = $form_state->get('element_properties');
    $connection = $form_state->getValue('cmrf_connection') ?? $properties['cmrf_connection'];
    $dataprocessor = $form_state->getValue('dataprocessor') ?? $properties['dataprocessor'];
    $formCmrf = $this->cmrfForm($connection, $dataprocessor);
    $form['cmrf'] = $formCmrf;
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function hasMultipleValues(array $element) {
    if ($this->hasProperty('cmrf_multiple')) {
      if (isset($element['#cmrf_multiple'])) {
        return $element['#cmrf_multiple'];
      }
      else {
        $default_property = $this->getDefaultProperties();
        return $default_property['cmrf_multiple'];
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $raw_value = $this->getValue($element, $webform_submission, $options);
    $format = $this->getItemFormat($element);

    if ($format === 'raw') {
      return $raw_value;
    }

    // Retrieve display value using default value filter.
    $value = CMRFReferenceUtils::lookup($element['#cmrf_connection'],
      $element['#dataprocessor'],
      $element['#cmrf_display'],
      $raw_value,
      $element['#cmrf_default_filter']
    );

    // When no display value could be retrieved, display a warning.
    if (NULL === $value) {
      $this->messenger()->addWarning($this->t(
        'The display value for the element %element_name could not be retrieved via CiviMRF, raw values are being displayed instead.',
        ['%element_name' => $element['#title']]
      ));
      $value = $raw_value;
    }

    $options += ['prefixing' => TRUE];
    if ($options['prefixing']) {
      if (isset($element['#field_prefix'])) {
        $value = strip_tags($element['#field_prefix']) . $value;
      }
      if (isset($element['#field_suffix'])) {
        $value .= strip_tags($element['#field_suffix']);
      }
    }

    return $value;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function connectionCallback(array $form, FormStateInterface $form_state) {
    return $form['properties']['cmrf'];
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
      '#title' => $this->t("CiviMFR settings"),
      '#attributes' => ['id' => 'cmrf-configuration-properties'],
    ];

    $formCmrf['cmrf_connection'] = [
      '#type' => 'select',
      '#title' => $this->t('Connection'),
      '#description' => $this->t('Select the connection'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::connections(),
      '#ajax' => [
        'callback' => [$this, 'connectionCallback'],
        'wrapper' => 'cmrf-configuration-properties',
        'event' => 'change',
      ],
    ];

    $formCmrf['dataprocessor'] = [
      '#type' => 'select',
      '#title' => $this->t('Dataprocessor'),
      '#description' => $this->t('Select the dataprocessor hi'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::dataprocessors($connection),
      '#access' => $connection != CMRFReferenceUtils::NONE,
      '#ajax' => [
        'callback' => [$this, 'connectionCallback'],
        'wrapper' => 'cmrf-configuration-properties',
        'event' => 'change',
      ],
    ];

    $formCmrf['api_parameters'] = $this->getFormInlineContainer();
    $formCmrf['api_parameters']['#access'] = ($connection != CMRFReferenceUtils::NONE && $dataprocessor != CMRFReferenceUtils::NONE);
    $formCmrf['api_parameters']['cmrf_search'] = [
      '#type' => 'select',
      '#title' => $this->t('Search Filter'),
      '#description' => $this->t('Dataprocessor Filter that is use to select the options of the reference'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::fields($connection, $dataprocessor, 'filter'),
    ];
    $formCmrf['api_parameters']['cmrf_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display field'),
      '#description' => $this->t('Dataprocessor Field that shows the result of the search so that the user can recognize it an knows what to select. The text template can be used to combine fields'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::fields($connection, $dataprocessor, 'return'),
    ];
    $formCmrf['api_parameters']['cmrf_return'] = [
      '#type' => 'select',
      '#title' => $this->t('Return Field'),
      '#description' => $this->t('Value that is returned from the search and will be the result of the select box. Often an unique identifier'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::fields($connection, $dataprocessor, 'return'),
    ];
    $formCmrf['api_parameters']['cmrf_default_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Filter'),
      '#description' => $this->t('Filter used to find the display value of an already filled field. Probably a filter on the return field'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::fields($connection, $dataprocessor, 'filter'),
    ];

    $formCmrf['api_parameters']['cmrf_extra_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Extra Filter'),
      '#description' => $this->t('Adds an extra parameter to the dataprocessor to narrow down the selection options'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::fields($connection, $dataprocessor, 'filter'),
    ];

    $formCmrf['api_parameters']['cmrf_extra_filter_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra Filter Value'),
      '#description' => $this->t('Value to add to the extra filter parameter'),
      '#states' => [
        'invisible' => [
          [
            [':input[name="properties[cmrf_extra_filter]"]' => ['value' => CMRFReferenceUtils::NONE]],
          ]
        ]]
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
    $formCmrf['cmrf_multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple values'),
    ];
    return $formCmrf;
  }

}
