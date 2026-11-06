<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Model\Process;

use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\ReadonlyField;
use Sunnysideup\Ecommerce\Model\Process\OrderStatusLog;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\CustomersAndOrders\CustomerDetails;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\CustomersAndOrders\CustomerOrder;
use Throwable;

/**
 * Class \Sunnysideup\EcommerceAdvanceRetailConnector\Model\Process\OrderStatusLogSendOrderToAdvanceRetail
 *
 * @property int $AdvanceRetailCustomerOrderID
 * @property int $AdvanceRetailCustomerID
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
            if (Director::isLive()) {
                $order = $this->getOrderCached();
                $arConnectionCustomerOrder = null;
                try {
                    $arConnectionCustomerDetails = Injector::inst()->get(CustomerDetails::class);
                    $arConnectionCustomerOrder = Injector::inst()->get(CustomerOrder::class);

                    $arCustomerID = 0;
                    $member = $order->Member();
                    if ($member && $member->exists()) {
                        //does customer and advance retail customer ID
                        $arCustomerID = $member->AdvanceRetailCustomerID;
                        if (! $arCustomerID) {
                            $customerData = $arConnectionCustomerDetails->getCustomerByEmail($member->Email);
                            if (empty($customerData)) {
                                $arConnectionCustomerOrder->createCustomer($member);
                            } else {
                                $customerData = reset($customerData);
                                $member->AdvanceRetailCustomerID = $customerData['customerId'];
                                $member->write();
                            }
                            $arCustomerID = $member->AdvanceRetailCustomerID;
                        }
                    }

                    $result = $arConnectionCustomerOrder->createOrder($order);
                } catch (Throwable $throwable) {
                    // e.g. a TypeError: it must never block the order step
                    $result = null;
                    $this->Note = 'Order has NOT been created in the Advance Retail API. Error: ' . $throwable->getMessage();
                    $this->sendFailureAlert($order, $this->Note);

                    return;
                }

                if (is_int($result) && $result > 0) {
                    $this->AdvanceRetailCustomerOrderID = $result;
                    $this->AdvanceRetailCustomerID = (int) $arCustomerID;
                    $order->AdvanceRetailOrderID = $result;
                    try {
                        $order->write();
                    } catch (Throwable $throwable) {
                        // the id is already stored in the database by createOrder
                    }
                    $this->Note = 'Order has successfully been created in the Advance Retail API.';
                } elseif (is_string($result) && $result) {
                    $this->Note = $result;
                    // the "already exists" message is not a failure
                    if (! $order->AdvanceRetailOrderID && ! str_contains($result, 'another process is already working')) {
                        $this->sendFailureAlert($order, $this->Note);
                    }
                } elseif (is_array($result) && count($result) > 0) {
                    $this->Note = 'Order has NOT been created in the Advance Retail API. Reply: ' . substr(json_encode($result), 0, 1000);
                    $this->sendFailureAlert($order, $this->Note);
                } else {
                    $this->Note = 'Order has NOT been created in the Advance Retail API.';
                    $errors = $arConnectionCustomerOrder ? $arConnectionCustomerOrder->getErrors() : [];
                    if (count($errors) > 0) {
                        $this->Note .= ' ' . substr(implode(' | ', $errors), 0, 1000);
                    }
                    $this->Note .= ' Please check in AR (look for "Website order #' . (int) $order->ID . '" in the notes) before sending it again.';
                    $this->sendFailureAlert($order, $this->Note);
                }
            } else {
                $this->Note = 'No action taken as the site is not in LIVE mode.';
            }
        }
    }

    /**
     * tell the admins that an order was not pushed to AR.
     * This must never throw an error.
     */
    protected function sendFailureAlert($order, string $message): void
    {
        try {
            $subject = 'Order #' . (int) $order->ID . ' could NOT be sent to Advance Retail';
            $body = '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p>Please check Advance Retail before sending this order again: it may already be there (notes start with "Website order #' . (int) $order->ID . '").</p>';
            $to = Email::config()->get('admin_email');
            if ($to) {
                $email = new Email($to, $to, $subject . ' - ' . Director::absoluteBaseURL(), $body);
                $email->send();
            }
        } catch (Throwable $throwable) {
            // nothing we can do
        }
    }
}
