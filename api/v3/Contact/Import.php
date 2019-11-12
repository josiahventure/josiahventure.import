<?php
use CRM_Import_ExtensionUtil as E;

/**
 * Contact.Import API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_contact_Import_spec(&$spec) {
}

/**
 * Contact.Import API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_contact_Import($params) {
 $import = new CRM_Import_Contact();
 $resultvalues = $import -> process();
 return civicrm_api3_create_success($resultvalues,$params,'Contact','import');
}

