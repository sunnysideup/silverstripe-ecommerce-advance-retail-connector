<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

/**
 * Class \Sunnysideup\EcommerceAdvanceRetailConnector\Extensions\OrderExtension
 *
 * @property \Sunnysideup\Ecommerce\Model\Order|\Sunnysideup\EcommerceAdvanceRetailConnector\Extensions\OrderExtension $owner
 * @property int $AdvanceRetailOrderID
 */
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
