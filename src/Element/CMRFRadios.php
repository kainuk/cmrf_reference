<?php
/**
 * @author Klaas Eikelboom  <klaas.eikelboom@civicoop.org>
 * @date 16-May-2020
 * @license  AGPL-3.0
 */
namespace Drupal\cmrf_reference\Element;

use Drupal\cmrf_reference\CMRFReferenceUtils;
use Drupal\Core\Render\Annotation\FormElement;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a lookup using CiviMRF
 *
 * @FormElement("cmrf_radios")
 */
class CMRFRadios extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = parent::getInfo();
    $info['#pre_render'][] = [$class, 'preRenderCMRFSelect'];
    //$info['#theme'] = 'select';
    return $info;
   }


  /**
   * Generete the css class for the jquery element to act on
   *
   * @param $element
   *
   * @return mixed
   */
  public static function preRenderCMRFSelect($element) {
    return $element;
  }
}

