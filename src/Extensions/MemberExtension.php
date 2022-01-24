<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;

class MemberExtension extends DataExtension
{
    private static $db = [
        'AdvanceRetailCustomerID' => 'Int',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        $fields->addFieldsToTab(
            'Root.AdvanceRetail',
            ReadonlyField::create(
                'AdvanceRetailCustomerID',
                'AR Customer ID'
            )
        );

        return $fields;
    }
}
