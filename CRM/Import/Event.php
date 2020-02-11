<?php

class CRM_Import_Event {
    var $nt_org;
    var $area;
    var $mnry;
    var $chlg;
    var $hway;
    var $spons;
    var $attn;
    var $blv;
    var $unb;
    var $fyg;
    var $pof;
    var $rof;

    public function findCustomFieldId($customFieldName)
    {
        return civicrm_api3('CustomField', 'getvalue', [
            'return' => 'id',
            'name' => $customFieldName,
        ]);
    }

    public function __construct()
    {
        try {
            $this->nt_org = findCustomFieldId('nt_org');
        } catch (API_Exception $e) {
        }
        $this->area = findCustomFieldId('area');
        $this->mnry = findCustomFieldId('mnry');
        $this->chlg = findCustomFieldId('challenge'); // findCustomFieldId('chlg');
        $this->hway = findCustomFieldId('highway'); // findCustomFieldId('hway');
        $this->spons = findCustomFieldId('sponsored'); // findCustomFieldId('spons');
        $this->attn = findCustomFieldId('attn');
        $this->blv = findCustomFieldId('blv');
        $this->unb = findCustomFieldId('unb');
        $this->fyg = findCustomFieldId('fyg');
        $this->pof = findCustomFieldId('pof');
        $this->rof = findCustomFieldId('rof');
    }
}