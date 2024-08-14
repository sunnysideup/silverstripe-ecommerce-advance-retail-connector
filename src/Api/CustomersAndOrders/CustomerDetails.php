<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api\CustomersAndOrders;

// use SilverStripe\Core\Config\Config;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;

class CustomerDetails extends ARConnector
{
    /** CUSTOMERS */

    /**
     * @param string $since
     * @param int    $pageNumber
     * @param int    $pageSize
     * @param string $sortOrder
     * @param string $sortDir
     */
    public function getCustomersChanged(
        ?string $since = '2015-09-27T21:11:12.532Z',
        ?bool $getAllRecords = false,
        ?int $pageNumber = 1, // if you input 0 will be treated as page 1
        ?int $pageSize = 1000,
        ?string $sortOrder = 'customerId',
        ?string $sortDir = 'ASC'
    ): array {
        $url = $this->makeUrlFromSegments('customers/changed');
        $data = [
            'since' => $since,
            'pageNumber' => $pageNumber,
            'pageSize' => $getAllRecords ? 0 : $pageSize, //0 will return all records
            'sort' => $sortOrder,
            'dir' => $sortDir,
        ];

        return $this->runRequest($url, 'POST', $data);
    }

    /**
     * @param string $since
     */
    public function getAllCustomerDetails(?string $since = '2015-09-27T21:11:12.532Z'): array
    {
        $this->output('<h3>Fetching data since: ' . $since . '</h3>');

        $pageNumber = 1;    // starting page number
        $pageSize = 100;

        $customers = [];    // array containing all customers
        $pageNumberLimit = 5;   // limit the number of resutls to 5 pages
        $customerCount = 0;     // number of customers read from API

        while ($pageNumber <= $pageNumberLimit) {
            $fullData = $this->getCustomersChanged($since, false, $pageNumber, $pageSize);
            $customerData = $fullData['data'];
            // useful information ...
            // $pagingData = $fullData['paging'];
            //$itemsOnPage = sizeof($customerData);

            foreach ($customerData as $customer) {
                $customers[$customerCount] = $customer;
                ++$customerCount;
            }

            ++$pageNumber;
        }
        $this->output($customers);

        return $customers;
    }

    public function getCustomerDetails(string $customerId): array
    {
        $url = $this->makeUrlFromSegments('customers/' . $customerId);

        return $this->runRequest($url);
    }

    public function getCustomerByEmail(string $email): array
    {
        $data = [
            'email' => $email,
        ];
        $url = $this->makeUrlFromSegments('customers/search/detailed');
        $result = $this->runRequest($url, 'POST', $data);

        return isset($result['data']) ? $result['data'] : [];
    }
}
