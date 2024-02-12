<?php
/**
 * @author Klaas Eikelboom  <klaas.eikelboom@civicoop.org>
 * @date 16-May-2020
 * @license  AGPL-3.0
 */

namespace Drupal\cmrf_reference;
/**
 * Utility class mostly for CiviCRM Communication
 *
 * Class CMRFReferenceUtils
 *
 * @package Drupal\cmrf_reference
 */
class CMRFReferenceUtils {

  public const NONE ='<none>';

  /**
   * Utility function provides a list of connections
   *
   * @return mixed
   */
  static function connections(){
    $core=\Drupal::service('cmrf_core.core');
    return $core->getConnectors();
  }

  /**
   * @param $connection
   *
   * @return array
   */
  static function dataprocessors($connection){
    if($connection == CMRFReferenceUtils::NONE){
      return [];
    }
    $core=\Drupal::service('cmrf_core.core');
    $call = $core->createCall($connection,'DataProcessorOutput','get',[],['limit'=>0]);
    $result = $core->executeCall($call);
    $dataprocessors = [];
    foreach($result['values'] as $value){
      if($value['type']='api') {
        $dataprocessors[$value['api_entity']] = $value['api_entity'];
      }
    }
    return $dataprocessors;
  }

  /**
   * @param $connection
   * @param $dataprocessor
   *
   * @return array
   */
  static function fields($connection,$dataprocessor, $type='filter'){
    if($connection == CMRFReferenceUtils::NONE || $dataprocessor == CMRFReferenceUtils::NONE ){
      return [];
    }
    $core=\Drupal::service('cmrf_core.core');
    $call = $core->createCall($connection,$dataprocessor,'getfields',['api_action' => 'get'],['limit'=>0]);
    $result = $core->executeCall($call);
    $fields = [];
    foreach($result['values'] as $value){
      if($type=='filter' && $value['api.filter']) {
        $fields[$value['name']] = $value['title'];
      } elseif ($type=='return' && $value['api.return']){
        $fields[$value['name']] = $value['title'];
      }
    }
    return $fields;
  }

  /**
   * @param $connection
   * @param $dataprocessor
   * @param $searchField
   * @param $searchValue
   * @param $returnField
   * @param $limit
   *
   * @return array
   */
  static function matches($connection,$dataprocessor,$searchField,$searchValue, $displayField,$returnField,$limit,$extraFilter,$extraFilterValue,$lookupField,$currentSection){
    // check if the configuration is complete - if not the function will just return NULL
    if (empty($connection) || $connection == CMRFReferenceUtils::NONE
       || empty($dataprocessor) || $dataprocessor == CMRFReferenceUtils::NONE
       || empty($searchField) || $searchField == CMRFReferenceUtils::NONE
       || empty($returnField) || $returnField == CMRFReferenceUtils::NONE) {
      \Drupal::logger('cmfr_reference')->warning(t('Incomplete configured CMRF Component will not return any search result'));
      return [];
    }
    $core=\Drupal::service('cmrf_core.core');
    $params = [
      'return' => [$returnField, $displayField],
    ];
    if (strlen($searchValue)) {
      $params[$searchField] = ['LIKE' => '%' . $searchValue . '%'];
    } elseif (!empty($lookupField) && $lookupField!=CMRFReferenceUtils::NONE) {
      if (is_array($currentSection) && count($currentSection)) {
        $params[$lookupField] = ['IN' => $currentSection];
      }
      elseif (!is_array($currentSection) && strlen($currentSection)) {
        $params[$lookupField] = $currentSection;
      }
    }
    if(!empty($extraFilter) && $extraFilter!=CMRFReferenceUtils::NONE){
      $params[$extraFilter] = $extraFilterValue;
    }
    $options = [];
    if ($limit > 0) {
      $options['limit'] = $limit;
    }

    $matches = [
      'results' => [],
      'pagination' => ['more' => false]
    ];
    $foundIds = [];
    if (!empty($lookupField) && $lookupField!=CMRFReferenceUtils::NONE && isset($params[$lookupField])) {
      $call = $core->createCall($connection,$dataprocessor,'get', $params,$options);
      $result = $core->executeCall($call);
      foreach($result['values'] as $value){
        $matches['results'][]=[
          'id' => $value[$returnField],
          'text' => $value[$displayField],
        ];
        $foundIds[] = $value[$returnField];
      }
      if (count($foundIds)) {
        $params[$lookupField] = ['NOT IN' => $foundIds];
      } else {
        unset($params[$lookupField]);
      }
    }
    if ($limit > 0 && count($foundIds) && ($limit - count($foundIds) > 0)) {
      $options['limit'] = $limit - count($foundIds);
    }

    $call = $core->createCall($connection,$dataprocessor,'get', $params,$options);
    $result = $core->executeCall($call);
    foreach($result['values'] as $value){
     $matches['results'][]=[
        'id' => $value[$returnField],
        'text' => $value[$displayField],
     ];
    }
    return $matches;
  }

  /**
   * @param $connection
   * @param $dataprocessor
   * @param $searchField
   * @param $searchValue
   * @param $returnField
   *
   * @return array
   */
  static function lookup($connection,$dataprocessor,$searchField,$searchValue,$returnField, $extraFilter, $extraFilterValue){
    if (empty($connection) || $connection == CMRFReferenceUtils::NONE
      || empty($dataprocessor) || $dataprocessor == CMRFReferenceUtils::NONE
      || empty($searchField) || $searchField==CMRFReferenceUtils::NONE
      || empty($returnField) || $returnField==CMRFReferenceUtils::NONE) {
      \Drupal::logger('cmfr_reference')->warning(t('Incomplete configured CMRF Component will not return any search result'));
      return [];
    }

    $params[$returnField]= $searchValue;
    if(!empty($extraFilter) && $extraFilter!=CMRFReferenceUtils::NONE){
      $params[$extraFilter] = $extraFilterValue;
    }

    $core=\Drupal::service('cmrf_core.core');
    $call = $core->createCall($connection,$dataprocessor,'getsingle',$params,[]);
    $result=$core->executeCall($call);
    return $result[$searchField] ?? NULL;
  }

  /**
   * @param $connection
   * @param $dataprocessor
   * @param $valueField
   * @param $labelField
   * @param $filterField
   * @param $filterValue
   *
   * @return array
   */
  static function options($connection, $dataprocessor, $valueField, $labelField, $filterField, $filterValue){
    $options = [];
    if (empty($connection) || $connection == CMRFReferenceUtils::NONE
      || empty($dataprocessor) || $dataprocessor == CMRFReferenceUtils::NONE
      || empty($valueField) || $valueField==CMRFReferenceUtils::NONE
      || empty($labelField) || $labelField==CMRFReferenceUtils::NONE
      ) {
      \Drupal::logger('cmfr_reference')->warning(t('Incomplete configured CMRF Component will not return any search result'));
      return $options;
    }
    $core=\Drupal::service('cmrf_core.core');
    $params = ['return' => [$valueField, $labelField]];
    if(!empty($filterField) && $filterField!=CMRFReferenceUtils::NONE){
      $params[$filterField] = $filterValue;
    }
    $call = $core->createCall($connection,$dataprocessor,'get',$params,['options' => ['limit' => 0 ]]);
    $core->executeCall($call);
    $values = $call->getReply()['values'];
    foreach ($values as $value){
      $options[$value[$valueField]]=$value[$labelField];
    }
    return $options;
  }
}


