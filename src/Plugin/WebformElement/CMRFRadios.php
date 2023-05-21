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
 *   id = "cmrf_radios",
 *   label = @Translation("CMRF Select"),
 *   description = @Translation("Create a radios group using a dataprocessor"),
 *   category = @Translation("CMRF"),
 * )
 */
class CMRFRadios extends TextBase {

  // Its just magic - add this trait and AjaX works
  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
        // Autocomplete settings.
        'cmrf_connection' => CMRFReferenceUtils::NONE,
        'dataprocessor' => CMRFReferenceUtils::NONE,
        'cmrf_value' => CMRFReferenceUtils::NONE,
        'cmrf_label' => CMRFReferenceUtils::NONE,
        'cmrf_extra_filter' => CMRFReferenceUtils::NONE,
        'cmrf_extra_filter_value' => "",
        'cmrf_multiple' => FALSE,
      ] + TextBase::getDefaultProperties();
    // Remove autocomplete property which is not applicable to
    // this autocomplete element.
    return $properties;
  }

  /**
   * @param array $element
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
    $options = CMRFReferenceUtils::options($element['#cmrf_connection'],
      $element['#dataprocessor'],
      $element['#cmrf_value'],
      $element['#cmrf_label'],
      $element['#cmrf_extra_filter'],
      \Drupal::token()->replacePlain($element['#cmrf_extra_filter_value']));
    $element['#options'] = $options;
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
    $connection = $form_state->getValue('cmrf_connection') ?? $properties['cmrf_connection'];
    $dataprocessor = $form_state->getValue('dataprocessor') ?? $properties['dataprocessor'];
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
    $formCmrf['api_parameters']['cmrf_value'] = [
      '#type' => 'select',
      '#title' => $this->t('Value Filter'),
      '#description' => $this->t('Dataprocessor Filter that is use to select the options of the reference'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::fields($connection, $dataprocessor, 'return'),
    ];
    $formCmrf['api_parameters']['cmrf_label'] = [
      '#type' => 'select',
      '#title' => $this->t('Label field'),
      '#description' => $this->t('Dataprocessor Field that shows the result of the search so that the user can recognize it an knows what to select. The text template can be used to combine fields'),
      '#options' => [CMRFReferenceUtils::NONE => t('-Select-')] + CMRFReferenceUtils::fields($connection, $dataprocessor, 'return'),
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
