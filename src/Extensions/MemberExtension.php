<?php


use SilverStripe\Dev\Debug;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Permission;

class MemberExtension extends DataExtension
{

    private static $db = [
        'TotalAPIAllowed' => 'Int',
        'APICalled' => 'Int',   //used for daily api count
        'LastAPICalled' => 'Datetime'
    ];

    private static $defaults = [
        'TotalAPIAllowed' => 100
    ];

    private static $hidden_fields = [
        'LastAPICalled'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('TotalAPIAllowed');
        if(Permission::checkMember($this->owner->ID,'API_ACCESS')){
            $fields->addFieldToTab('Root.Main',NumericField::create('TotalAPIAllowed', 'How many API calls is allowed per day?'));
            $fields->addFieldToTab('Root.Main',ReadonlyField::create('APICalled','API Called'));
        }
    }

    //Record api call
    public function recordApiCall()
    {
        // record api call
        ++$this->owner->APICalled;
        $this->owner->write();
    }

    // check if limit exceeded, true if permit, false if exceeded
    public function checkApiLimit(){

        // check date if counter needs to be reset
        $lastCallDateTS = strtotime($this->owner->dbObject('LastAPICalled')->Date());
        $today = DBDatetime::now();
        $todayTS = strtotime($today->Date());

        if ($lastCallDateTS < $todayTS) {
            $this->owner->APICalled = 0;
        }

        // record datetime api called
        $this->owner->LastAPICalled = $today->getTimestamp();
        $this->owner->write();

        if (($this->owner->APICalled + 1) <= $this->owner->TotalAPIAllowed) {
            return true;
        } else {
            return false;
        }

    }

}