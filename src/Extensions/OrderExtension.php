<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Extensions;

use SilverStripe\ORM\DataExtension;

class OrderExtension extends DataExtension
{
    private static $db = [
        'AdvanceRetailOrderID' => 'Int',
    ];
}
