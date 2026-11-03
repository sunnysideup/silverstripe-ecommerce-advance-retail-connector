<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api\CustomersAndOrders;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use Throwable;
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
     *
     * @return array|int|null
     */
    public function createCustomer(
        ?Member $member,
        ?int $branchId = 1
    ): array|int|null {
        if ($member->AdvanceRetailCustomerID) {
            return (int) $member->AdvanceRetailCustomerID;
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
            // the API may reply with a bare integer id (runRequest returns null for that)
            $raw = $this->getLastDecodedResponse();
            $customerId = $this->turnIntoPositiveId($raw);
            if (null === $customerId && is_array($raw)) {
                $customerId = $this->turnIntoPositiveId($raw['data'] ?? ($raw['customerId'] ?? null));
            }
            if (null === $customerId && is_array($result)) {
                $customerId = $this->turnIntoPositiveId($result['customerId'] ?? null);
            }
            if (null !== $customerId) {
                $member->AdvanceRetailCustomerID = $customerId;
                $member->write();

                return $customerId;
            }
            if (null === $result) {
                return null;
            }
        } catch (Throwable $exception) {
            $this->logError($exception->getMessage());

            return null;
        }

        // unexpected reply: do not store anything against the member.
        $this->logError('Unexpected reply when creating customer: ' . substr($this->getLastRawResponse(), 0, 500));

        return $result;
    }

    /** ORDERS */

    /**
     * @param Order $order
     * @param int   $customerId
     * @param int   $branchId
     * @param int   $workstationId
     *
     * @return array|int|string|null int = the AR order id (success), string = message, array = error data from AR, null = no usable response
     */
    public function createOrder(
        ?Order $order,
        ?int $customerId = 0,
        ?int $branchId = 1,
        ?int $workstationId = 0
    ): array|string|int|null {
        if (! $order || ! $order->exists()) {
            return 'There is no order to send.';
        }
        // only one process at a time can send a particular order.
        $lockName = 'ar_create_order_' . (int) $order->ID;
        $hasLock = false;
        $lockSupported = true;
        try {
            $lockSupported = (bool) DB::get_conn()->supportsLocks();
            $hasLock = $lockSupported && DB::get_conn()->getLock($lockName, 15);
        } catch (Throwable $throwable) {
            $hasLock = false;
        }
        if ($lockSupported && ! $hasLock) {
            return 'Could not send the order to AR: another process is already working on this order.';
        }
        try {
            // fresh read from the database (the in-memory object may be out of date)
            $existingID = $this->getStoredAdvanceRetailOrderID($order);
            if ($existingID) {
                $order->AdvanceRetailOrderID = $existingID;

                return 'This order already exists in AR with the ID: ' . $existingID;
            }
            $data = [
                'lines' => OrderHelpers::get_line_items($order, $branchId, $workstationId),
                'payments' => OrderHelpers::get_payments($order),
                'branchId' => $branchId,
                'workstationId' => $workstationId,
                'customerOrderId' => 0, // this is the AR id of the order (assigned by AR), so it can not hold the website order reference.
                'orderType' => 'All', // what should we put here?
                'orderDate' => $order->LastEdited, //created or last edited or let AR auto populate this?
                'dueDate' => '', // what is the due date?
                // the website order reference goes in the notes so that staff can find out if AR already holds the order
                'notes' => trim('Website order #' . (int) $order->ID . ' ' . $order->CustomerOrderNote),
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

            $result = $this->runRequest($url, 'POST', $data, false, 10);
            // the API may reply with a bare integer id (runRequest returns null for that)
            $raw = $this->getLastDecodedResponse();
            $arOrderID = $this->turnIntoPositiveId($raw);
            if (null === $arOrderID && is_array($raw)) {
                $arOrderID = $this->turnIntoPositiveId($raw['data'] ?? null);
            }
            if (null !== $arOrderID) {
                // store straight away in the database (while we hold the lock)
                $this->storeAdvanceRetailOrderID($order, $arOrderID);
                $order->AdvanceRetailOrderID = $arOrderID;

                return $arOrderID;
            }
            if (null === $result) {
                $this->logError(
                    'No usable order id from AR. Raw response: ' . ($this->getLastRawResponse() !== '' ? substr($this->getLastRawResponse(), 0, 500) : '(none)')
                );
            }
        } catch (Throwable $e) {
            //what should we do if the order is unable to be succesfully created?
            $this->logError($e->getMessage());
            $result = null;
        } finally {
            try {
                if ($hasLock) {
                    DB::get_conn()->releaseLock($lockName);
                }
            } catch (Throwable $throwable) {
                // nothing we can do
            }
        }

        return $result;
    }

    protected function getStoredAdvanceRetailOrderID(Order $order): int
    {
        $table = DataObject::getSchema()->tableName(Order::class);

        return (int) DB::prepared_query(
            'SELECT "AdvanceRetailOrderID" FROM "' . $table . '" WHERE "ID" = ?',
            [(int) $order->ID]
        )->value();
    }

    protected function storeAdvanceRetailOrderID(Order $order, int $arOrderID): void
    {
        $table = DataObject::getSchema()->tableName(Order::class);
        DB::prepared_query(
            'UPDATE "' . $table . '" SET "AdvanceRetailOrderID" = ? WHERE "ID" = ? AND "AdvanceRetailOrderID" = 0',
            [$arOrderID, (int) $order->ID]
        );
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
