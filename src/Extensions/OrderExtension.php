<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class OrderExtension extends DataExtension
{
    private static $db = [
        'AdvanceRetailOrderID' => 'Int',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('AdvanceRetailOrderID');
    }
}
