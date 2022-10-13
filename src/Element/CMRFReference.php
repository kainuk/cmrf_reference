<?php
/**
 * @author Klaas Eikelboom  <klaas.eikelboom@civicoop.org>
 * @date 16-May-2020
 * @license  AGPL-3.0
 */
namespace Drupal\cmrf_reference\Element;

use Drupal\cmrf_reference\CMRFReferenceUtils;
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
    $info['#pre_render'][] = [$class, 'preRenderCMRFReference'];
    $info['#theme'] = 'select';
    //$info['#theme_wrappers'] = 'form_element';
    $info['#process'] []= [$class, 'removeOptions'];
    return $info;
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
    $defaultvalue = isset($element['#default_value']) ? $element['#default_value'] : FALSE;
    if ($defaultvalue) {
      $values = explode(',', $defaultvalue);
      $defaults = [];
      foreach ($values as $value) {
        $defaults[$value] = CMRFReferenceUtils::lookup($element['#cmrf_connection'],
          $element['#dataprocessor'],
          $element['#cmrf_display'],
          $value,
          $element['#cmrf_default_filter']
        );
      }
      $element['#attached']['drupalSettings']['cmrf_reference']['defaults'][$element["#webform_key"]] = $defaults;
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

