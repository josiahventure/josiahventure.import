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
          select r.rel_key, r.frst_const_key, r.scnd_const_key, civi_tp, trg_id /* c1.trg_id, c2.trg_id, */
          from int_rel r
           /*inner join int_const c1 on c1.const_key = r.frst_const_key
           inner join int_const c2 on c2.const_key = r.scnd_const_key*/
           ;
SQL
  ;

  $dao = CRM_Core_DAO::executeQuery($sql);
  while($dao->fetch()){

    $contactId_A = CRM_CORE_DAO::singleValueQuery('select trg_id from int_const where const_key = %1',[
      1 => [$dao->frst_const_key,'Integer']
    ]);

    $contactId_B = CRM_CORE_DAO::singleValueQuery('select trg_id from int_const where const_key = %1',[
      1 => [$dao->scnd_const_key,'Integer']
    ]);

    $apiParams =  [
      'contact_id_a' => $contactId_A,
      'contact_id_b' => $contactId_B,
      'relationship_type_id' => $dao->civi_tp
    ];

    // print_r($apiParams);

    try{
      $result = civicrm_api3('Relationship', 'create', [
        'contact_id_a' => $contactId_A,
        'contact_id_b' => $contactId_B,
        'relationship_type_id' => $dao->civi_tp /*%3*/
      ]);

      /*if($dao->trg_id){
            $apiParams['id'] = $dao->trg_id;
      }*/

    }
    catch (CiviCRM_API3_Exception $e) {
      echo "$dao->frst_const_key.$dao->scnd_const_key {$e->getMessage()}\n";
      CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
           [1 => ['REL','String'],
            2 => [$dao->rel_key,'Integer'],
            3 => [$e->getMessage(),'String']
           ]);
      continue;
    }

    if($result['is_error']){
      echo $result['error_message'] ."\n";
      continue;
    }

    $relationId = $result['id'];

    CRM_Core_DAO::executeQuery('update int_rel set trg_id = %1 where rel_key=%2',[
      1 => [$relationId,'Integer'],
      2 => [$dao->rel_key,'Integer']
    ]);
  }
}