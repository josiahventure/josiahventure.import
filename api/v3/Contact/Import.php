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
          select const_key, ext_id, contact_type, contact_subtp, rcg_name, first_name, last_name, org_name, gender_id, birth_dt,
            email, phone, addr1, city, zip_cd as zip, addr_cntry, cntry, src_sys_id, trg_id
          from int_const 
          /*where org_name like 'AC%'
          limit 7*/
          /*where contact_subtp='Denomination'*/
         
SQL
    ;

    $dao = CRM_Core_DAO::executeQuery($sql);

    while($dao->fetch()) {

        if ($dao->trg_id == 0) {

            try{
                $apiParams =  [
                    'external_identifier' => $dao->ext_id,
                    'contact_type'        => $dao->contact_type,
                    'contact_sub_type'    => $dao->contact_subtp,
                    'source'              => $dao->src_sys_id,
                    'first_name'          => $dao->first_name,
                    'last_name'           => $dao->last_name,
                    'legal_name'          => $dao->org_name,
                    'organization_name'   => $dao->org_name,
                    'sort_name'           => $dao->rcg_name,
                    'gender_id'           => $dao->gender_id,
                    'birth_date'          => $dao->birth_dt
                ];

                // print_r($apiParams);
                // echo "ins const_key: $dao->const_key\n";

                /* // autonomous definition for updates, user story may ask not to update some fields from EMS and control them after initial load only from Civi
                 * // different solution for emails, phones and addresses, some email tp, addr tp, phone tp always updated, some controlled from Civi
                 * if($dao->trg_id){
                    $apiParams['id'] = $dao->trg_id;
                }*/

                $result = civicrm_api3('Contact', 'create',$apiParams);
            }

            catch (CiviCRM_API3_Exception $e) {
                echo "ins $dao->const_key {$e->getMessage()}\n";
                CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
                    [
                        1 => ['CONT','String'],
                        2 => [$dao->const_key,'Integer'],
                        3 => [$e->getMessage(),'String']
                    ]);
                continue;
            }

        } else {

            try{
                $apiParams =  [
                    'id'                  => $dao->trg_id,
                    'external_identifier' => $dao->ext_id,
                    'contact_type'        => $dao->contact_type,
                    'contact_sub_type'    => $dao->contact_subtp,
                    'source'              => $dao->src_sys_id,
                    'first_name'          => $dao->first_name,
                    'last_name'           => $dao->last_name,
                    'legal_name'          => $dao->org_name,
                    'organization_name'   => $dao->org_name,
                    'sort_name'           => $dao->rcg_name,
                    'gender_id'           => $dao->gender_id,
                    'birth_date'          => $dao->birth_dt
                ];

                // print_r($apiParams);
                // echo "upd const_key: $dao->const_key\n";

                $result = civicrm_api3('Contact', 'create',$apiParams);
            }

            catch (CiviCRM_API3_Exception $e) {
                echo "upd $dao->const_key {$e->getMessage()}\n";
                CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
                [
                    1 => ['CONT','String'],
                    2 => [$dao->const_key,'Integer'],
                    3 => [$e->getMessage(),'String']
                ]);
                continue;
            }
        }

        if($result['is_error']){
            echo $result['error_message'] ."\n";
            continue;
        }

        $contactId = $result['id'];
        // echo 'Contact id:'.$contactId."\n";
        CRM_Core_DAO::executeQuery('update int_const set trg_id = %1 where const_key=%2',[
            1 => [$contactId,'Integer'],
            2 => [$dao->const_key,'String']
        ]);

        if(isset($dao->email)){

            try{
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

                $result = civicrm_api3('Email','create',$apiParams);
            }

            catch (CiviCRM_API3_Exception $e) {
                echo "$dao->const_key {$e->getMessage()}\n";
                CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
                    [
                        1 => ['EMAIL','String'],
                        2 => [$dao->const_key,'Integer'],
                        3 => [$e->getMessage(),'String']
                    ]);
                continue;
            }

            if($result['is_error']){
              echo $result['error_message'] ."\n";
              continue;
            }
        }

        if(isset($dao->phone)){

            try{
                $apiParams = [
                    'contact_id' => $contactId,
                    'phone'      => $dao->phone,
                ];

                $phone_id = CRM_Core_DAO::singleValueQuery('select id from civicrm_phone where contact_id=%1',
                    [
                        1=> [$contactId,'Integer'],
                    ]
                );

                if($phone_id){
                    $apiParams['id'] = $phone_id;
                }
                $result = civicrm_api3('Phone', 'create', $apiParams);
            }

            catch (CiviCRM_API3_Exception $e) {
                echo "$dao->const_key {$e->getMessage()}\n";
                CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
                    [
                        1 => ['PHONE','String'],
                        2 => [$dao->const_key,'Integer'],
                        3 => [$e->getMessage(),'String']
                    ]);
                continue;
            }

            if($result['is_error']){
              echo $result['error_message'] ."\n";
              continue;
            }
        }

        if(isset($dao->addr1)){

            try{
                $apiParams = [
                    'contact_id' => $contactId,
                    'location_type_id' => "Home",
                    'is_primary' => 1,
                    'street_address' => $dao->addr1,
                    'city' => $dao->city,
                    // 'supplemental_address_1' => $dao->addr1,
                    // 'supplemental_address_2' => $dao->addr2,
                    'postal_code' => $dao->zip,
                    'street_parsing' => 0,
                    'country_id' => $dao->addr_cntry,
                    'skip_geocode' => 0 // skip 1
                ];

                $addr_id = CRM_Core_DAO::singleValueQuery('select id from civicrm_address where contact_id=%1 and location_type_id=%2',
                    [
                        1=> [$contactId,'Integer'],
                        2=> [1,'Integer']
                    ]
                );

                if($addr_id){
                    $apiParams['id'] = $addr_id;
                }
                $result = civicrm_api3('Address', 'create', $apiParams);
            }

            catch (CiviCRM_API3_Exception $e) {
                echo "$dao->const_key {$e->getMessage()}\n";
                CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
                    [
                        1 => ['ADDR','String'],
                        2 => [$dao->const_key,'Integer'],
                        3 => [$e->getMessage(),'String']
                    ]);
                continue;
            }

            if($result['is_error']){
                echo $result['error_message'] ."\n";
                continue;
            }
        }

        if(isset($dao->cntry)){

            try{
                $apiParams = [
                    'contact_id' => $contactId,
                    'location_type_id' => "Service",
                    'is_primary' => 0,
                    'street_parsing' => 0,
                    'country_id' => $dao->cntry,
                    'skip_geocode' => 1
                ];

                $addr_id = CRM_Core_DAO::singleValueQuery('select id from civicrm_address where contact_id=%1 and location_type_id=%2',
                    [
                        1=> [$contactId,'Integer'],
                        2=> [6,'Integer']
                    ]
                );

                if($addr_id){
                    $apiParams['id'] = $addr_id;
                }
                $result = civicrm_api3('Address', 'create', $apiParams);
            }

            catch (CiviCRM_API3_Exception $e) {
                echo "$dao->const_key {$e->getMessage()}\n";
                CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
                    [
                        1 => ['ADDR_S','String'],
                        2 => [$dao->const_key,'Integer'],
                        3 => [$e->getMessage(),'String']
                    ]);
                continue;
            }

            if($result['is_error']){
                echo $result['error_message'] ."\n";
                continue;
            }
        }
    }
    echo 'END'."\n";
    return array('SUCCESS');
}