/**
 * @file
 * JavaScript behaviors for CMFR Reference integration.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.webform = Drupal.webform || {};
  Drupal.webform.cmrfReference = Drupal.webform.cmrfReference || {};
  Drupal.webform.cmrfReference.options = Drupal.webform.cmrfReference.options || {};
  //Drupal.webform.select2.options.width = Drupal.webform.select2.options.width || '100%';
  //Drupal.webform.select2.options.widthInline = Drupal.webform.select2.options.widthInline || '50%';

  /**
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   * Attaches the drag behavior to a applicable table element.
   */
  Drupal.behaviors.cmrfReference = {
    attach: function (context) {
      if (!$.fn.select2) {
        return;
      }
      /*if($default) {
        var $default = drupalSettings.cmrf_reference.default;
        var newOption = new Option($default.text, $default.id, true, false);
        $(this).append(newOption).trigger('change');
      };*/
      $(once(
        'webform-cmrfReference',
        'select.js-webform-cmrf-reference, .js-webform-cmrf-reference select',
        context,
      ))
        .each(function () {
          var $select = $(this);
          var $name = $select.attr('webform_key');
          var newOption;
          // if the field already has a value, crate an option for it and select it.
          if(drupalSettings.cmrf_reference.defaults && drupalSettings.cmrf_reference.defaults[$name]) {
            for (const [key, value] of  Object.entries(drupalSettings.cmrf_reference.defaults[$name])) {
              newOption = new Option(value, key, true, true);
              $select.append(newOption);
            }
          }
          $select.select2({
            ajax: {
              url: "/cmrfreference/" + drupalSettings.cmrf_reference.webform + "/autocomplete/" + $name,
              dataType: "json",
              data: function (params) {
                if (drupalSettings.cmrf_reference.extra_filter_value && drupalSettings.cmrf_reference.extra_filter_value[$name]) {
                  return {
                    q: params.term || '', // search term
                    extra_filter_value: drupalSettings.cmrf_reference.extra_filter_value[$name], //Get your value from other elements using Query, for example.
                  };
                } else {
                  return {q: params.term};
                }
              }
            },
            width: drupalSettings.cmrf_reference.size[$name],
            placeholder: drupalSettings.cmrf_reference.placeholder[$name],
            allowClear:  !drupalSettings.cmrf_reference.required[$name]
          });
        });
    }
  };
})(jQuery, Drupal, drupalSettings);
