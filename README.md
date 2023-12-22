# CiviMRF Reference

Make a reference to CiviCRM in a webform. (Only Drupal 9).

## How to install (with Composer)

Install the module with

`composer require drupal/cmrf_reference`

For use in Webforms, make sure to enable the *Select 2* Webform library at
*Administration* » *Structure* » *Webform* » *Configuration* »
*Libraries* (`admin/structure/webform/config/libraries`) and download it, e.g.
using *Drush*: `drush webform:libraries:download`.

See the [
*CiviMRF* module documentation](https://www.drupal.org/docs/contributed-modules/cmrf-core-documentation)
for how to connect your *CiviCRM* instance to Drupal.

## Concept

*CiviMRF Reference* fields provide select fields with a dynamic range of
selectable options. Those are being retrieved from the *CiviCRM* REST API using
the *CiviMRF* framework. More specifically, API endpoints provided by the
[*Data Processor*](https://civicrm.org/extensions/data-processor) *CiviCRM*
extension are being used. *Data Processor* allows configuring searches of
*CiviCRM* data and performing actions on the results. One of those actions can
be to provide an API endpoint for querying data from outside.

## Configuration

1. Install the *Data Processor* *CiviCRM* extension in your *CiviCRM* instance
   you want to use as a data source for the *CiviMRF Reference* field.
2. Configure a *Data Processor* with at least the following options:
   * A field for the selectable values (e.g. the Contact ID for
     contacts)
   * A field for the selectable value labels (e.g. the Display name)
   * A filter for the field to allow searching for in the *CiviMRF Reference*
     field (e.g. the Display name), activate *Filter is exposed to the user*
   * An output of type *API* - remember the *API Entity* defined here
   * You can add as many filters as you like if you want to narrow down the set
     of results the user should be able to see in the *CiviMRF Reference* field
     in the Webform
3. Add an element of type *CMRF Reference* to your webform with the following
   options:
   * *Connection*: Select the *CiviMRF* connector
   * *Dataprocessor*. Select the *Data Processor* API output configured before,
     this will be the name of the *API Entity*
   * *Search Filter*: The *Data Processor* exposed filter used to search for
     records
   * *Display Field*: The *Data Processor* field to use as select option labels
   * *Return Field*: The *Data Processor* field to use as select option values

## Known issues.

Documentations has opportunities for improvement.
