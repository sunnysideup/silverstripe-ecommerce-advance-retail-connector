<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;

/**
 * Class \Sunnysideup\EcommerceAdvanceRetailConnector\Extensions\MemberExtension
 *
 * @property \SilverStripe\Security\Member|\Sunnysideup\EcommerceAdvanceRetailConnector\Extensions\MemberExtension $owner
 * @property int $AdvanceRetailCustomerID
 */
class MemberExtension extends Extension
{
    private static $db = [
        'AdvanceRetailCustomerID' => 'Int',
    ];

    private static $indexes = [
        'AdvanceRetailCustomerID' => true,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        $fields->addFieldsToTab(
            'Root.AdvanceRetail',
            [
                ReadonlyField::create(
                    'AdvanceRetailCustomerID',
                    'AR Customer ID'
                )
            ]
        );

        return $fields;
    }
}
