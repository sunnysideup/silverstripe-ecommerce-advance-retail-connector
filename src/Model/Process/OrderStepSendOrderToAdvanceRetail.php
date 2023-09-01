<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Model\Process;

use SilverStripe\Control\Director;
use Sunnysideup\Ecommerce\Interfaces\OrderStepInterface;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Model\Process\OrderStep;

/**
 * Class \Sunnysideup\EcommerceAdvanceRetailConnector\Model\Process\OrderStepSendOrderToAdvanceRetail
 *
 */
class OrderStepSendOrderToAdvanceRetail extends OrderStep implements OrderStepInterface
{
    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = OrderStatusLogSendOrderToAdvanceRetail::class;

    private static $table_name = 'OrderStepSendOrderToAdvanceRetail';

    private static $defaults = [
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 0,
        'Name' => 'Send Order to Advance Retail',
        'Code' => 'ADVANCE_RETAIL_ORDER',
        'ShowAsInProcessOrder' => 1,
        'HideStepFromCustomer' => 1,
    ];

    /**
     *initStep:
     * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
     * should be able to run this function many times to check if the step is ready.
     *
     * @see Order::doNextStatus
     *
     * @return bool - true if the current step is ready to be run...
     */
    public function initStep(Order $order): bool
    {
        return true;
    }

    /**
     *doStep:
     * should only be able to run this function once
     * (init stops you from running it twice - in theory....)
     * runs the actual step.
     *
     * @see Order::doNextStatus
     *
     * @return bool - true if run correctly
     */
    public function doStep(Order $order): bool
    {
        if(Director::isDev()) {
            return true;
        }
        $logsExist = $this->RelevantLogEntries($order)->exists();
        if (! $logsExist) {
            $className = $this->getRelevantLogEntryClassName();
            $object = $className::create();
            $object->OrderID = $order->ID;
            $object->Title = $this->Name;
            $object->write();
        }

        return true;
    }

    /**
     * For some ordersteps this returns true...
     *
     * @return bool
     */
    public function hasCustomerMessage()
    {
        return false;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return 'Sends website order data to the Advance Retail API';
    }
}
