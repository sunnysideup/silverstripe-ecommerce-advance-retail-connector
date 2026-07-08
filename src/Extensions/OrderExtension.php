<?php

declare(strict_types=1);

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Extensions;

use Sunnysideup\Ecommerce\Model\Order;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;

/**
 * Class \Sunnysideup\EcommerceAdvanceRetailConnector\Extensions\OrderExtension
 *
 * @property Order|OrderExtension $owner
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
