<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Model\Process;

use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\ReadonlyField;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 */
class OrderStatusLogSendOrderToAdvanceRetail extends OrderStatusLog
{
    private static $table_name = 'OrderStatusLogSendOrderToAdvanceRetail';

    private static $db = [
        'AdvanceRetailCustomerOrderID' => 'Int',
        'AdvanceRetailCustomerID' => 'Int',
    ];

    private static $defaults = [
        'InternalUseOnly' => true,
    ];

    private static $singular_name = 'Advance Retail Order Data';

    private static $plural_name = 'Advance Retail Order Data';

    public function i18n_singular_name()
    {
        return self::$singular_name;
    }

    public function i18n_plural_name()
    {
        return self::$plural_name;
    }

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * CMS Fields.
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField(
            'AdvanceRetailCustomerOrderID',
            ReadonlyField::create('AdvanceRetailCustomerOrderID', 'Advance Retail Customer Order ID')
        );

        return $fields;
    }

    /**
     * adding a sequential order number.
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->InternalUseOnly = true;
        if (! $this->exists()) {
            $order = $this->getOrderCached();
            $api = Injector::inst()->get(ARConnector::class);
            if (! Director::isLive()) {
                //only send orders to test API if we are not in live mode
                $api->setBasePath('ARESAPITest');
            }

            $arCustomerID = 0;
            $member = $order->Member();
            if ($member && $member->exists()) {
                //does customer and advance retail customer ID
                $arCustomerID = $member->AdvanceRetailCustomerID;
                if (! $arCustomerID) {
                    $customerData = $api->getCustomerByEmail($member->Email);
                    if (empty($customerData)) {
                        $api->createCustomer($member);
                    } else {
                        $customerData = reset($customerData);
                        $member->AdvanceRetailCustomerID = $customerData['customerId'];
                        $member->write();
                    }
                    $arCustomerID = $member->AdvanceRetailCustomerID;
                }
            }

            $result = $api->createOrder($order);

            if (is_int($result)) {
                $this->AdvanceRetailCustomerOrderID = $result;
                $this->AdvanceRetailCustomerID = $arCustomerID;
                $order->AdvanceRetailOrderID = $result;
                $order->write();
                $this->Note = 'Order has successfully been created in the Advance Retail API.';
            } else {
                $this->Note = $result;
            }
        }
    }
}
