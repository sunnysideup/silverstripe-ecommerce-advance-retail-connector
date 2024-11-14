<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api\CustomersAndOrders;

use Exception;
// use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\Helpers\OrderHelpers;

class CustomerOrder extends ARConnector
{
    /**
     * @param int $customerOrderId
     * @param int $branchId
     * @param int $workstationId
     */
    public function getCustomerOrder(
        ?int $customerOrderId,
        ?int $branchId = 1,
        ?int $workstationId = 0
    ): ?array {
        $url = $this->makeUrlFromSegments('customerOrder/details') ;
        $data = [
            'customerOrderId' => $customerOrderId,
            'branchId' => $branchId,
            'workstationId' => $workstationId,
        ];

        return $this->runRequest($url, 'POST', $data);
    }

    /**
     * @param int $branchId
     */
    public function createCustomer(
        ?Member $member,
        ?int $branchId = 1
    ): ?array {
        if ($member->AdvanceRetailCustomerID) {
            return $member->AdvanceRetailCustomerID;
        }
        $data = [
            'branchId' => $branchId,
            'startDate' => $member->Created,
            'dateOfBirth' => $member->Created,
            'firstName' => $member->FirstName,
            'lastName' => $member->Surname,
            'homeAddress' => [
                'email' => $member->Email,
            ],
        ];

        $url = $this->makeUrlFromSegments('customers');

        try {
            $result = $this->runRequest($url, 'POST', $data, false, 10);
            $member->AdvanceRetailCustomerID = $result;
            $member->write();
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());

            return null;
        }

        return $result;
    }

    /** ORDERS */

    /**
     * @param Order $order
     * @param int   $customerId
     * @param int   $branchId
     * @param int   $workstationId
     *
     * @return array|int|string
     */
    public function createOrder(
        ?Order $order,
        ?int $customerId = 0,
        ?int $branchId = 1,
        ?int $workstationId = 0
    ): array|string|null {
        if ($order->AdvanceRetailOrderID) {
            return 'This order already exists in AR with the ID: ' . $order->AdvanceRetailOrderID;
        }
        $data = [
            'lines' => OrderHelpers::get_line_items($order, $branchId, $workstationId),
            'payments' => OrderHelpers::get_payments($order),
            'branchId' => $branchId,
            'workstationId' => $workstationId,
            'customerOrderId' => 0, // what should we put here?
            'orderType' => 'All', // what should we put here?
            'orderDate' => $order->LastEdited, //created or last edited or let AR auto populate this?
            'dueDate' => '', // what is the due date?
            'notes' => $order->CustomerOrderNote,
            'debtorId' => '', //I don't think we need this?
            'customerId' => $customerId,
            'loyaltyCardNumber' => '', //I don't think we need this?
            'cardNumber' => '', //I don't think we need this?
            'purchaseOrderNumber' => '', //I don't think we need this?
            'quoteNumber' => 0, //I don't think we need this?
            'billingAddress' => OrderHelpers::get_address($order),
            'deliveryAddress' => OrderHelpers::get_address($order, 'ShippingAddress'),
        ];

        $url = $this->makeUrlFromSegments('customerOrder');

        try {
            $result = $this->runRequest($url, 'POST', $data, false, 10);
            $order->AdvanceRetailOrderID = $result;
            $order->write();
        } catch (Exception $e) {
            //what should we do if the order is unable to be succesfully created?
            $this->logError($e->getMessage());
            $result = null;
        }

        return $result;
    }

    /**
     * @param HTTPRequest $request
     */
    public function getOrder($request): ?Order
    {
        $arOrderID = (int) $request->param('ID');
        if ($arOrderID) {
            //$obj = $this->getApi();
            $order = $this->getCustomerOrder($arOrderID);
            $this->output($order);

            return $order;
        }
        $this->output('You need to provide an order id from the AR api, eg: /ar-test/createorder/900000000');

        return null;
    }
}
