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

function civicrm_api3_activity_Import($params) {
    $sql = <<<SQL
          select event_key, src_id, const_key, event_name, start_dt, end_dt, reg_end_dt, tp_id, nt_org_val, area_val, mnry_val,
            chlg_val, hway_val, spons_val, src_cd_val, src_sys_val, ifnull(cr_gold_const_key, cr_const_key) as cr_const_key,
            attn_cnt, blv_cnt, unb_cnt, fyg_cnt, pof_cnt, rof_cnt, conv_cnt, bapt_cnt, trg_id
           from int_activity 
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
    $ActDataId = findCustomGroupId('activity_data');
    $EvalDataId = findCustomGroupId('act_eval_data');
    // echo "data: $ActDataId metrics: $EvalDataId\n";
    $nt_org = findCustomFieldId('nt_org', $ActDataId);
    // $area = findCustomFieldId('area', $ActDataId);
    // $mnry = findCustomFieldId('mnry', $ActDataId);
    // $chlg = findCustomFieldId('chlg', $ActDataId); // findCustomFieldId('challenge');
    // $hway = findCustomFieldId('hway', $ActDataId); // findCustomFieldId('highway');
    // $spons = findCustomFieldId('spons', $ActDataId); // findCustomFieldId('sponsored');
    $src = findCustomFieldId('src', $ActDataId);
    $src_sys = findCustomFieldId('src_sys', $ActDataId);
    $is_private = findCustomFieldId('is_private', $ActDataId);
    // echo "nt_org: $nt_org src: $src src sys: $src_sys priv: $is_private\n";
    // $attn = findCustomFieldId('attn', $ActEvalDataId);
    $blv = findCustomFieldId('blv', $EvalDataId);
    $unb = findCustomFieldId('unb', $EvalDataId);
    $conv = findCustomFieldId('conv', $EvalDataId);
    $bapt = findCustomFieldId('bapt', $EvalDataId);
    // echo "blv: $blv unb: $unb conv: $conv bapt: $bapt\n";
    $dao = CRM_Core_DAO::executeQuery($sql);
    // echo "START \n";
    while($dao->fetch()){

        // echo "ins 0 $dao->const_key\n";

        $contactId = CRM_CORE_DAO::singleValueQuery('select trg_id from int_const where const_key = %1',[
            1 => [$dao->const_key,'Integer']
        ]);

        $contactId_CR = CRM_CORE_DAO::singleValueQuery('select trg_id from int_const where const_key = %1',[
            1 => [$dao->cr_const_key,'Integer']
        ]);

        if (isset($contactId_CR) == false) {
            $contactId_CR = 1;
        }
        // echo "ins 1 $dao->event_key\n";

        try{

            $apiParams =  [
                'source_contact_id'      => $contactId_CR, // 1, // "user_contact_id", // created by
                'activity_type_id'       => 55, // "evaluation", // 55, //
                'activity_date_time'     => $dao->start_dt,
                // 'duration' => "", // $dao->end_dt - $dao->start_dt,
                // 'original_id'            => "",
                // 'parent_id'              => "",
                // 'relationship_id'        => "",
                // 'is_auto'                => 1,
                'target_id'              => $contactId, // ch_grp
                'subject'                => $dao->event_name,
                // 'details'                => $dao->event_name,
                'custom_'.$nt_org        => $dao->nt_org_val,
                'custom_'.$src          => $dao->src_cd_val,
                'custom_'.$src_sys      => $dao->src_sys_val,
                'custom_'.$blv           => $dao->blv_cnt,
                'custom_'.$unb           => $dao->unb_cnt,
                'custom_'.$conv          => $dao->conv_cnt,
                'custom_'.$bapt          => $dao->bapt_cnt,
                'custom_'.$is_private    => 0,
                'status_id'              => "Completed"
            ];

            /*if ($dao->trg_id != 0) {
                $apiParams['id'] = $dao->trg_id;
                echo "trg 1 $dao->trg_id\n";
            } */

            if($dao->trg_id){
                $apiParams['id'] = $dao->trg_id;
                echo "trg 2 $dao->trg_id\n";
            }

            // print_r($apiParams);

            // echo "ins $dao->event_key\n";

            $result = civicrm_api3('Activity', 'create',$apiParams);

            if($result['is_error']){
                echo $result['error_message'] ."\n";
                continue;
            }

        }

        catch (CiviCRM_API3_Exception $e){
            echo "ins $dao->event_key {$e->getMessage()}\n";
            CRM_Core_DAO::executeQuery('INSERT INTO int_log (TBL, REC_KEY, MSG, INS_DT) VALUES (%1, %2, %3, now())',
                [
                    1 => ['ACT','String'],
                    2 => [$dao->event_key,'Integer'],
                    3 => [$e->getMessage(),'String']
                ]);
            continue;
        }

        $activityId = $result['id'];
        // echo 'Activity id: '.$activityId."\n";

        CRM_Core_DAO::executeQuery('update int_activity set trg_id = %1 where event_key=%2',[
            1 => [$activityId,'Integer'],
            2 => [$dao->event_key,'String']
        ]);
    }
    echo 'END'."\n";
    return array('SUCCESS');
}