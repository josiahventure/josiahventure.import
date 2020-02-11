<?php


class CRM_Import_Lookup {
    function lookupRelationTypeId($name){
        $relationship_type_id = civicrm_api3('RelationshipType', 'getvalue', [
            'return' => "id",
            'name_a_b' => "",
        ]);
        return $relationship_type_id;
    }
}