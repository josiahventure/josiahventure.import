<?php
/**
 * Class CRM_Import_Contact
 */
class CRM_Import_Contact
{

    /**
     * If the processing of a record fails, mark it as Error and 'E' and add the error message.
     * @param $const_key
     * @param $error
     */
    public function setError($const_key, $error){
       CRM_Core_DAO::executeQuery('update const set processed=%1,message=%2 where const_key=%3',[
            1 => ['E','String'],
            2 => [$error,'String'],
            3 => [$const_key,'String']
       ]);
   }

    /**
     * Mark a record as succesfully processed.
     * @param $const_key
     */
    public function setSucces($const_key){
        CRM_Core_DAO::executeQuery('update const set processed=%1 where const_key=%2',[
            1 => ['S','String'],
            2 => [$const_key,'String']
        ]);
    }

    /**
     * @throws CiviCRM_API3_Exception
     */
    public function process(){
       // lets the PHP script run for a infinite period
       set_time_limit(0);
       // below statement selects all the organizations that are not processed.
       $sql = <<<SQL
          select const_key, ext_id, contact_type, contact_subtp, rcg_name, first_name, last_name, org_name, gender_id, birth_dt, email, phone, addr1, city, zip_cd, addr_cntry, src_sys_id, trg_id
          from const 
          where contact_type='Organization' and processed='N'
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

               /* if the trg_id is filled (and not 0) it is added to the create
                  the effect is that the api call becomes an update instead of a
                  create
               */
               if($dao->trg_id){
                   $apiParams['id'] = $dao->trg_id;
               }
               $result = civicrm_api3('Contact', 'create',$apiParams);
           } catch (CiviCRM_API3_Exception $e){
               /* if something goes wrong, mark the record as having an error */
               $this->setError($dao->const_key, $e->getMessage());
               /* leave this cycle of the loop - continue with the next record */
               continue;
           }

           if($result['is_error']){
               $this->setError($dao->const_key, $result['error_message']);
               continue;
           }

           /* the result of an api call contains the id of the just created record */
           $contactId = $result['id'];

           /* write the new id back to the load table for two reasons
              - identify in the second run that this is an update
              - identify the contact in the target system for the other load tables
           */
           CRM_Core_DAO::executeQuery('update const set trg_id = %1 where const_key=%2',[
               1 => [$contactId,'Integer'],
               2 => [$dao->const_key,'String']
           ]);

           /* check in the load table an email exists */
           if(isset($dao->email)){

               $apiParams = [
                   'contact_id' => $contactId,
                   'email'      => $dao->email,
               ];
               /* check if the contact already has an email */
               $email_id = CRM_Core_DAO::singleValueQuery('select id from civicrm_email where contact_id=%1',
                   [
                       1=> [$contactId,'Integer'],
                   ]
               );
               /* if it has we do an update - by setting the already known id */
               if($email_id){
                   $apiParams['id'] = $email_id;
               }
               civicrm_api3('Email','create',$apiParams);
           }
           /* TODO processing of addresses etc .. */

           // if the have come so far the processing must have been succesfull.
           $this->setSucces($dao->const_key);

       };
   }
}