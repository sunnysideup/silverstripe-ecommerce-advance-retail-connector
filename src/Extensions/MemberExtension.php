<?php

declare(strict_types=1);

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Extensions;

use SilverStripe\Security\Member;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;

/**
 * Class \Sunnysideup\EcommerceAdvanceRetailConnector\Extensions\MemberExtension
 *
 * @property Member|MemberExtension $owner
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
