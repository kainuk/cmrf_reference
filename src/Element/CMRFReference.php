<?php
/**
 * @author Klaas Eikelboom  <klaas.eikelboom@civicoop.org>
 * @date 16-May-2020
 * @license  AGPL-3.0
 */
namespace Drupal\cmrf_reference\Element;

use Drupal\cmrf_reference\CMRFReferenceUtils;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Annotation\FormElement;
use Drupal\Core\Render\Element\Textfield;

/**
 * Provides a lookup using CiviMRF
 *
 * @FormElement("cmrf_reference")
 */
class CMRFReference extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = parent::getInfo();
    // maxlength leads to validation errors with multi-value fields.
    unset($info['#maxlength']);
    $info['#pre_render'][] = [$class, 'preRenderCMRFReference'];
    $info['#theme'] = 'select';
    //$info['#theme_wrappers'] = 'form_element';
    $info['#process'] []= [$class, 'removeOptions'];
    return $info;
   }

  /**
   * {@inheritDoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // This part is copied/adapted from
    // \Drupal\Core\Render\Element\Select::valueCallback().
    if (isset($element['#cmrf_multiple']) && $element['#cmrf_multiple']) {
      if ($input !== FALSE) {
        // If an enabled multi-select submits NULL, it means all items are
        // unselected. A disabled multi-select always submits NULL, and the
        // default value should be used.
        if (empty($element['#disabled'])) {
          $return = [];
          if (is_array($input)) {
            foreach ($input as $key => $value) {
              $return[$key] = parent::valueCallback($element, $value, $form_state);
            }
          }
        }
        else {
          $return = (isset($element['#default_value']) && is_array($element['#default_value'])) ? $element['#default_value'] : [];
        }
      }
      else {
        $return = NULL;
      }
    }

    // For single-value fields, use
    // \Drupal\Core\Render\Element\Textfield::valueCallback().
    else {
      $return = parent::valueCallback($element, $input, $form_state);
    }

    return $return;
  }

  /**
   * Generete the css class for the jquery element to act on
   *
   * @param $element
   *
   * @return mixed
   */
  public static function preRenderCMRFReference($element) {
    static::setAttributes($element, ['js-webform-cmrf-reference']);
    $extraFilterValue = isset($element['#cmrf_extra_filter']) && $element['#cmrf_extra_filter'] != CMRFReferenceUtils::NONE
      ? \Drupal::token()->replacePlain($element['#cmrf_extra_filter_value'])
      : NULL;
    if (isset($element['#default_value'])) {
      $values = !is_array($element['#default_value']) ? [$element['#default_value']] : $element['#default_value'];
      $defaults = [];
      foreach ($values as $value) {
        // Ignore empty values added by
        // \Drupal\Core\Form\FormBuilder::handleInputElement() when saving
        // drafts.
        if ('' !== $value) {
          $defaults[$value] = CMRFReferenceUtils::lookup($element['#cmrf_connection'],
            $element['#dataprocessor'],
            $element['#cmrf_display'],
            $value,
            $element['#cmrf_default_filter'],
            $element['#cmrf_extra_filter'] ?? NULL,
            $extraFilterValue
          );
        }
      }
      $element['#attached']['drupalSettings']['cmrf_reference']['defaults'][$element["#webform_key"]] = $defaults;

      if (count($defaults)) {
        $element['#default_value'] = array_keys($defaults);
        $element['#options'] = $defaults;
      }

    }

    if (isset($extraFilterValue)) {
      $element['#attached']['drupalSettings']['cmrf_reference']['extra_filter_value'][$element["#webform_key"]] = $extraFilterValue;
    }

    return $element;
  }

  /**
   * This function is activated by adding the [#process] field toe the getinfo
   *
   * @param $element
   *
   * @return mixed
   */
  public static function removeOptions($element) {
    // deep inside the drupal forms validator is a check that when a fields
    // has options - the resulting value must be one of this options
    // by removing this element - this check is circumvented.
    unset($element['#options']);
    return $element;
  }

}

