<?php
use CRM_Import_ExtensionUtil as E;

/**
 * Event.Import API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
/*
function _civicrm_api3_event_Import_spec(&$spec) {
  $spec['magicword']['api.required'] = 1;
}
*/

/**
 * Event.Import API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function findCustomGroupId($customGroupName)
{
    return civicrm_api3('CustomGroup', 'getvalue', [
        'return' => 'id',
        'name' => $customGroupName
    ]);
}

function findCustomFieldId($customFieldName, $customGroupId)
{
    return civicrm_api3('CustomField', 'getvalue', [
        'return' => 'id',
        'name' => $customFieldName,
        'custom_group_id' => $customGroupId
    ]);
}

function civicrm_api3_event_Import($params) {
    $sql = <<<SQL
          select event_key, src_id, event_name, start_dt, end_dt, reg_end_dt, tp_id, nt_org_val, area_val, mnry_val,
            chlg_val, hway_val, spons_val, src_cd_val, src_sys_val, cr_gold_const_key as cr_const_key,
            attn_cnt, blv_cnt, unb_cnt, fyg_cnt, pof_cnt, rof_cnt, trg_id
           from int_event 
          /*where contact_subtp='Denomination'*/        
SQL
;

/*
function civicrm_api3_event_Import($params) {
  if (array_key_exists('magicword', $params) && $params['magicword'] == 'sesame') {
    $returnValues = array(
      // OK, return several data rows
      12 => array('id' => 12, 'name' => 'Twelve'),
      34 => array('id' => 34, 'name' => 'Thirty four'),
      56 => array('id' => 56, 'name' => 'Fifty six'),
    );
    // ALTERNATIVE: $returnValues = array(); // OK, success
    // ALTERNATIVE: $returnValues = array("Some value"); // OK, return a single value

    // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
    return civicrm_api3_create_success($returnValues, $params, 'NewEntity', 'NewAction');
  }
  else {
    throw new API_Exception(/'Everyone knows that the magicword is "sesame"', 1234);
  }
}
*/
    // echo "start\n";
    $EventDataId = findCustomGroupId('event_data');
    $EventMetricsId = findCustomGroupId('event_metrics');
    // echo "data: $EventDataId metrics: $EventMetricsId\n";
    $nt_org = findCustomFieldId('nt_org', $EventDataId);
    $area = findCustomFieldId('area', $EventDataId);
    $mnry = findCustomFieldId('mnry', $EventDataId);
    $chlg = findCustomFieldId('chlg', $EventDataId); // findCustomFieldId('challenge');
    $hway = findCustomFieldId('hway', $EventDataId); // findCustomFieldId('highway');
    $spons = findCustomFieldId('spons', $EventDataId); // findCustomFieldId('sponsored');
    $src = findCustomFieldId('src', $EventDataId);
    $src_sys = findCustomFieldId('src_sys', $EventDataId);
    $attn = findCustomFieldId('attn', $EventMetricsId);
    $blv = findCustomFieldId('blv', $EventMetricsId);
    $unb = findCustomFieldId('unb', $EventMetricsId);
    $fyg = findCustomFieldId('fyg', $EventMetricsId);
    $pof = findCustomFieldId('pof', $EventMetricsId);
    $rof = findCustomFieldId('rof', $EventMetricsId);

    // echo $nt_org.' '.$area;

    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()){

        /*$contactId_CR = CRM_CORE_DAO::singleValueQuery('select trg_id from int_const where const_key = %1',[
            1 => [$dao->cr_const_key,'Integer']
        ]); */

        if ($dao->trg_id == 0) {
            try{
                $apiParams =  [
                    'title'                 => $dao->event_name,
                    'start_date'            => $dao->start_dt,
                    'end_date'              => $dao->end_dt,
                    'registration_end_date' => $dao->reg_end_dt,
                    'event_type_id'         => $dao->tp_id,
                    'custom_'.$nt_org       => $dao->nt_org_val,
                    'custom_'.$area         => $dao->area_val,
                    'custom_'.$mnry         => $dao->mnry_val,
                    'custom_'.$chlg         => $dao->chlg_val,
                    'custom_'.$hway         => $dao->hway_val,
                    'custom_'.$spons        => $dao->spons_val,
                    'custom_'.$src          => $dao->src_cd_val,
                    'custom_'.$src_sys      => $dao->src_sys_val,
                    'custom_'.$attn         => $dao->attn_cnt,
                    'custom_'.$blv          => $dao->blv_cnt,
                    'custom_'.$unb          => $dao->unb_cnt,
                    'custom_'.$fyg          => $dao->fyg_cnt,
                    'custom_'.$pof          => $dao->pof_cnt,
                    'custom_'.$rof          => $dao->rof_cnt
                    /*'participant_listing_id' => ["Name and Email"]*/
                ];

                // print_r($apiParams);
                // echo "ins $dao->event_key\n";

                /*if($dao->trg_id){
                    $apiParams['id'] = $dao->trg_id;
                }*/
                $result = civicrm_api3('Event', 'create',$apiParams);
            } catch (CiviCRM_API3_Exception $e){
                echo "ins $dao->event_key {$e->getMessage()}\n";
                CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
                    [
                        1 => ['EVNT','String'],
                        2 => [$dao->event_key,'Integer'],
                        3 => [$e->getMessage(),'String']
                    ]);
                continue;
            }

        } else {
            try{
                $apiParams =  [
                    'id'                    => $dao->trg_id,
                    'title'                 => $dao->event_name,
                    'start_date'            => $dao->start_dt,
                    'end_date'              => $dao->end_dt,
                    'registration_end_date' => $dao->reg_end_dt,
                    'event_type_id'         => $dao->tp_id,
                    'custom_'.$nt_org       => $dao->nt_org_val,
                    'custom_'.$area         => $dao->area_val,
                    'custom_'.$mnry         => $dao->mnry_val,
                    'custom_'.$chlg         => $dao->chlg_val,
                    'custom_'.$hway         => $dao->hway_val,
                    'custom_'.$spons        => $dao->spons_val,
                    'custom_'.$src          => $dao->src_cd_val,
                    'custom_'.$src_sys      => $dao->src_sys_val,
                    'custom_'.$attn         => $dao->attn_cnt,
                    'custom_'.$blv          => $dao->blv_cnt,
                    'custom_'.$unb          => $dao->unb_cnt,
                    'custom_'.$fyg          => $dao->fyg_cnt,
                    'custom_'.$pof          => $dao->pof_cnt,
                    'custom_'.$rof          => $dao->rof_cnt
                    /*'participant_listing_id' => ["Name and Email"]*/
                ];

                // print_r($apiParams);
                // echo "upd $dao->event_key\n";

                /*if($dao->trg_id){
                    $apiParams['id'] = $dao->trg_id;
                }*/
                $result = civicrm_api3('Event', 'create',$apiParams);
            }

            catch (CiviCRM_API3_Exception $e){
                echo "upd $dao->event_key {$e->getMessage()}\n";
                CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
                    [
                        1 => ['EVNT','String'],
                        2 => [$dao->event_key,'Integer'],
                        3 => [$e->getMessage(),'String']
                    ]);
                continue;
            }
        }

        if($result['is_error']){
            echo $result['error_message'] ."\n";
            continue;
        }

        $eventId = $result['id'];
        // echo 'Event id:'.$eventId."\n";

        CRM_Core_DAO::executeQuery('update int_event set trg_id = %1 where event_key=%2',[
            1 => [$eventId,'Integer'],
            2 => [$dao->event_key,'String']
        ]);

    };
    echo 'END'."\n";
    return array('SUCCESS');
}
