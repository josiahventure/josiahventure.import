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
   $sql = <<<SQL
          select const_key, ext_id, contact_type, contact_subtp, rcg_name, first_name, last_name, org_name, gender_id, birth_dt, email, phone, addr1, city, zip_cd, addr_cntry, src_sys_id, trg_id
          from const 
          where contact_subtp='Denomination'
         
SQL
;

   $dao = CRM_Core_DAO::executeQuery($sql);
   while($dao->fetch()){

     try{

      $apiParams =  [
        'external_identifier' => $dao->ext_id,
        'contact_type'        => $dao->contact_type,
        'contact_sub_type'    => $dao->contact_subtp,
        'first_name'          => $dao->first_name,
        'last_name'           => $dao->last_name,
        'organization_name'   => $dao->org_name,
        'gender_id'          =>  $dao->gender_id,
        'birth_date'         =>  $dao->birth_dt
      ];

      print_r($apiParams);

      if($dao->trg_id){
        $apiParams['id'] = $dao->trg_id;
      }
      $result = civicrm_api3('Contact', 'create',$apiParams);
     } catch (CiviCRM_API3_Exception $e){
       echo "$dao->const_key {$e->getMessage()}\n";
       continue;
     }

     if($result['is_error']){
       echo $result['error_message'] ."\n";
       continue;
     }

     $contactId = $result['id'];

     CRM_Core_DAO::executeQuery('update const set trg_id = %1 where const_key=%2',[
       1 => [$contactId,'Integer'],
       2 => [$dao->const_key,'String']
     ]);

     if(isset($dao->email)){

       $apiParams = [
         'contact_id' => $contactId,
         'email'      => $dao->email,
       ];

       $email_id = CRM_Core_DAO::singleValueQuery('select id from civicrm_email where contact_id=%1',
         [
           1=> [$contactId,'Integer'],
         ]
       );

       if($email_id){
         $apiParams['id'] = $email_id;
       }
       civicrm_api3('Email','create',$apiParams);
     }

   };
}

