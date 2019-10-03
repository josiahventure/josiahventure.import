<?php
use CRM_Import_ExtensionUtil as E;

/**
 * Relationship.Import API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_relationship_Import_spec(&$spec) {

}

/**
 * Relationship.Import API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_relationship_Import($params) {
  $sql = <<<SQL
          select const_key,church_const_key,REL_TP
          from relationship;
SQL
  ;

  $dao = CRM_Core_DAO::executeQuery($sql);
  while($dao->fetch()){

     $contactId_A = CRM_CORE_DAO::singleValueQuery('select trg_id from const where const_key = %1',[
       1 => [$dao->const_key,'Integer']
     ]);

    $contactId_B = CRM_CORE_DAO::singleValueQuery('select trg_id from const where const_key = %1',[
      1 => [$dao->church_const_key,'Integer']
    ]);

    $apiParams =  [
      'contact_id_a' =>  $contactId_A,
      'contact_id_b' => $contactId_B,
      'relationship_type_id' => 15,
    ];

    print_r($apiParams);

    try{
      $result = civicrm_api3('Relationship', 'create', [
        'contact_id_a' =>  $contactId_A,
        'contact_id_b' => $contactId_B,
        'relationship_type_id' => 15,
      ]);

    } catch (CiviCRM_API3_Exception $e){
    echo "$dao->const_key {$e->getMessage()}\n";
    continue;
  }

    if($result['is_error']){
      echo $result['error_message'] ."\n";
      continue;
    }

  }
}
