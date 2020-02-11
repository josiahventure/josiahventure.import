<?php
use CRM_Import_ExtensionUtil as E;

/**
 * Participant.Import API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */

function civicrm_api3_participant_Import($params) {
    $sql = <<<SQL
          select event_part_key, event_key, ifnull(gold_const_key, const_key) as const_key, role_desc, trg_id
           from int_part
           /*select 1 as  event_id, 100 as contact_id, 1 as event_part_key */
SQL
;

    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()){

        $eventId = CRM_CORE_DAO::singleValueQuery('select trg_id from int_event where event_key = %1',[
            1 => [$dao->event_key,'Integer']
        ]);
        $contactId = CRM_CORE_DAO::singleValueQuery('select trg_id from int_const where const_key = %1',[
            1 => [$dao->const_key,'Integer']
        ]);


        try{

            $apiParams =  [
                    'event_id'          => $eventId,
                    'contact_id'        => $contactId,
                    'role_id'           => 1,
                    'status_id'         => 2
            ];

            /*$result = civicrm_api3('Participant', 'create', [
                'event_id' => "",
                'contact_id' => "user_contact_id",
                'role_id' => "",
                'register_date' => "",
                'status_id' => "",
                'registered_by_id' => "",
                'source' => "",
                'id' => "",
                'transferred_to_contact_id' => "user_contact_id",
            ]);*/

            // print_r($apiParams);

            if($dao->trg_id){
                $apiParams['id'] = $dao->trg_id;
            }
            $result = civicrm_api3('Participant', 'create', $apiParams);

        }

        catch (CiviCRM_API3_Exception $e) {
            echo "$dao->event_part_key {$e->getMessage()}\n";
            CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
                [
                    1 => ['PART','String'],
                    2 => [$dao->event_part_key,'Integer'],
                    3 => [$e->getMessage(),'String']
                ]);
            continue;

        }

        if($result['is_error']){
            echo $result['error_message'] ."\n";
            continue;
        }

        $participantId = $result['id'];

        CRM_Core_DAO::executeQuery('update int_part set trg_id = %1 where event_part_key=%2',[
            1 => [$participantId,'Integer'],
            2 => [$dao->event_part_key,'String']
        ]);

    };
    echo 'END'."\n";
    return array('SUCCESS');
}
