<?php
/**
 * @author Klaas Eikelboom  <klaas.eikelboom@civicoop.org>
 * @date 16-May-2020
 * @license  AGPL-3.0
 */
namespace Drupal\cmrf_reference\Element;

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
    $info = parent::getInfo();
    return $info;
   }

}

