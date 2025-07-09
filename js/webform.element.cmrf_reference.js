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
   */
  Drupal.behaviors.cmrfReference = {
    attach: function (context) {
      if (!$.fn.select2) {
        return;
      }
      $(once(
        'webform-cmrfReference',
        'select.js-webform-cmrf-reference, .js-webform-cmrf-reference select',
        context,
      ))
        .each(function () {
          let $select = $(this);
          let $webform_key = $select.attr('webform_key');
          let $webform = $select.attr('webform');
          $select.select2({
            ajax: {
              url: "/cmrfreference/" + $webform + "/autocomplete/" + $webform_key,
              dataType: "json",
              data: function (params) {
                let current_selection = [];
                $select.select2('data').forEach(function (currentValue) {
                  current_selection.push(currentValue.id);
                });
                if (drupalSettings.cmrf_reference[$webform].extra_filter_value && drupalSettings.cmrf_reference[$webform].extra_filter_value[$webform_key]) {
                  return {
                    q: params.term || '', // search term
                    current_selection: current_selection.join(','),
                    extra_filter_value: drupalSettings.cmrf_reference[$webform].extra_filter_value[$webform_key], //Get your value from other elements using Query, for example.
                  };
                } else {
                  return {
                    q: params.term,
                    current_selection: current_selection.join(',')
                  };
                }
              }
            },
            width: drupalSettings.cmrf_reference[$webform].size[$webform_key],
            placeholder: drupalSettings.cmrf_reference[$webform].placeholder[$webform_key],
            allowClear: !drupalSettings.cmrf_reference[$webform].required[$webform_key]
          });
        });
    }
  };

  $('select.js-webform-cmrf-reference').on('state:readonly', function (event) {
    let value = event.value;
    let target = $(event.target);
    let name = target.attr('name');
    if (value) {
      /* A peculiarity of HTML is that the values of disabled elements are not posted.
        A workaround is to create an extra hidden element with the same value and name.
       */
      if (target.parent().find("input[type='hidden'][name='" + name + "']").length === 0) {
        /* first check if the hidden elements are already created, the event can be fired
           multiple times.
         */
        let results = target.val() || [];
        /* if there is just a single make it an array of one, that will be handeld by a loop of
           one iteration.
         */
        if (!(results instanceof Array)) {
          results = [results];
        }
        /* create multiple fields for multiple values (works in HTML)
           if empty the value will [] with the effect that hidden value is created, that will be handled as empty
           by the post handler
         */
        results.forEach(function (result) {
          const surrogate = document.createElement("input");
          surrogate.type = "hidden";
          surrogate.name = target.attr('name');
          surrogate.value = result;
          target.append(surrogate);
        })
      }
    } else {
      /* if the element is enabled the hidden fields are not needed */
      target.parent().find("input[type='hidden'][name='" + name + "']").remove();
    }
    target.prop("disabled", value);
  });
})(jQuery, Drupal, drupalSettings);
