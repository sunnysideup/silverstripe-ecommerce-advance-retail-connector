<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

/**
 * Class \Sunnysideup\EcommerceAdvanceRetailConnector\Extensions\OrderExtension
 *
 * @property \Sunnysideup\Ecommerce\Model\Order|\Sunnysideup\EcommerceAdvanceRetailConnector\Extensions\OrderExtension $owner
 * @property int $AdvanceRetailOrderID
 */
class OrderExtension extends Extension
{
    private static $db = [
        'AdvanceRetailOrderID' => 'Int',
    ];

    private static $indexes = [
        'AdvanceRetailOrderID' => true,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('AdvanceRetailOrderID');
    }
}
