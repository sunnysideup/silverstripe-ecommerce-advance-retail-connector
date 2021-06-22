<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api;

use Exception;
// use SilverStripe\Core\Config\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Message;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Flush\FlushNow;

class ARConnector
{
    use Extensible;
    use Injectable;
    use Configurable;
    use FlushNow;

    /**
     * @var string | ARESAPI or ARESAPITest
     */
    public $basePath = '';

    /**
     * @var float|mixed
     */
    public $startTime;

    protected $debug = true;

    protected $verbose = true;

    protected $error = '';

    public function __construct($basePath = 'ARESAPI')
    {
        $this->basePath = $basePath;
    }

    /**
     * @var string | ARESAPI or ARESAPITest
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;

        return $this;
    }

    public function setDebug(bool $bool)
    {
        $this->debug = $bool;

        return $this;
    }

    public function setVerbose(bool $bool)
    {
        $this->verbose = $bool;

        return $this;
    }

    /** PRODUCTS */

    /**
     * @param string $since
     * @param bool   $getAllRecords
     * @param int    $pageNumber
     * @param int    $pageSize
     * @param string $sortOrder
     * @param string $sortDir
     */
    public function getProductsChanged(
        ?string $since = '1900-01-01T00:00:00.000Z',
        ?bool $getAllRecords = true,
        ?int $pageNumber = 1,
        ?int $pageSize = 100,
        ?string $sortOrder = 'itemId',
        ?string $sortDir = 'ASC'
    ): array {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/products/changed';

        $data = [
            'since' => $since,
            'pageNumber' => $pageNumber, // if you input 0 will be treated as page 1
            'pageSize' => $getAllRecords ? 0 : $pageSize, //0 will return all records
            'sort' => $sortOrder,
            'dir' => $sortDir,
        ];

        return $this->runRequest($url, 'POST', $data);
    }

    public function getProductDetails(string $productId): array
    {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/products/details/' . $productId;

        return $this->runRequest($url);
    }

    /**
     * @param string $since
     */
    public function getAllProductDetails(?string $since = '2015-09-27T21:11:12.532Z'): array
    {
        self::do_flush('<h3>Fetching data since: ' . $since . '</h3>');
        self::do_flush('<hr />');

        // get the basic data of all products
        // it's okay to not use paging this as it doesn't return much data
        $fullData = $this->getProductsChanged($since);

        // paging data in the products request and total number of items
        $pagingData = $fullData['paging'];
        $totalItemCount = $pagingData['totalRecords'];

        // limits the number of items read from API for testing
        $totalItemCountLimit = 50000;
        $totalItemCountLimit = $totalItemCount <= $totalItemCountLimit ? $totalItemCount : $totalItemCountLimit;
        self::do_flush('<h3>Total number of items: ' . $totalItemCount . '</h3>');
        self::do_flush('<h3>Out of this, we are fetching ' . $totalItemCountLimit . ' items </h3>');
        self::do_flush('<hr />');

        // product data in the request
        $itemData = $fullData['data'];

        $itemDetails = [];  // final product details array
        $totalCount = 0;    // counter for the total number of item details that have been read
        $countLimit = 100;  // used to chop up the requests so server doesn't freeze up

        while ($totalCount < $totalItemCountLimit) {
            for ($count = 0; $count < $countLimit; ++$count) {
                self::do_flush('<b>Item ' . $totalCount . '</b><br />');

                // if ["action"] => "Remove" then skip (not in system anymore)
                $currentItemAction = $itemData[$totalCount]['action'];
                if ('Remove' !== $currentItemAction) {
                    $currentItemId = $itemData[$totalCount]['itemId'];
                    $itemDetail = $this->getProductDetails($currentItemId);
                    $itemDetails[$totalCount] = $itemDetail;
                    ob_start();
                    var_dump($itemDetail);
                    $output = '<pre>' . ob_get_clean() . '</pre>';
                    self::do_flush($output);
                    self::do_flush('<hr />');
                } else {
                    // need this or else API server will crash
                    self::do_flush('Removed item: SKIPPED <br />');
                    self::do_flush('<hr />');
                }

                ++$totalCount;
                if ($totalCount >= $totalItemCountLimit) {
                    break;
                }
            }
        }

        return $itemDetails;
    }

    public function getProductDetailsExtra(string $productId): array
    {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/products/' . $productId . '/extraDetails';

        return $this->runRequest($url);
    }

    /**
     * @param string $since
     */
    public function getAllProductDetailsExtra(?string $since = '2015-09-27T21:11:12.532Z'): array
    {
        //$since = '2015-09-27T21:11:12.532Z';
        self::do_flush('<h3>Fetching data since: ' . $since . '</h3>');
        self::do_flush('<hr />');

        // $arConnector = Injector::inst()->get(ARConnector::class);
        //$obj = $this->getApi();
        //$obj->setDebug(true);

        // get the basic data of all products
        // it's okay to not use paging this as it doesn't return much data
        $fullData = $this->getProductsChanged($since);

        // paging data in the products request and total number of items
        $pagingData = $fullData['paging'];
        $totalItemCount = $pagingData['totalRecords'];

        // limits the number of items read from API for testing
        $totalItemCountLimit = 50000;
        $totalItemCountLimit = $totalItemCount <= $totalItemCountLimit ? $totalItemCount : $totalItemCountLimit;
        self::do_flush('<h3>Total number of items: ' . $totalItemCount . '</h3>');
        self::do_flush('<h3>Out of this, we are fetching ' . $totalItemCountLimit . ' items </h3>');
        self::do_flush('<hr />');

        // product data in the request
        $itemData = $fullData['data'];

        $itemDetails = [];  // final product details array
        $totalCount = 0;    // counter for the total number of item details that have been read
        $countLimit = 100;  // used to chop up the requests so server doesn't freeze up

        while ($totalCount < $totalItemCountLimit) {
            for ($count = 0; $count < $countLimit; ++$count) {
                self::do_flush('<b>Item ' . $totalCount . '</b><br />');

                // if ["action"] => "Remove" then skip (not in system anymore)
                $currentItemAction = $itemData[$totalCount]['action'];
                if ('Remove' !== $currentItemAction) {
                    $currentItemId = $itemData[$totalCount]['itemId'];
                    $itemDetail = $this->getProductDetailsExtra($currentItemId);
                    $itemDetails[$totalCount] = $itemDetail;
                    ob_start();
                    var_dump($itemDetail);
                    $output = '<pre>' . ob_get_clean() . '</pre>';
                    self::do_flush($output);
                    self::do_flush('<hr />');
                } else {
                    // need this or else API server will crash
                    self::do_flush('Removed item: SKIPPED <br />');
                    self::do_flush('<hr />');
                }

                ++$totalCount;
                if ($totalCount >= $totalItemCountLimit) {
                    break;
                }
            }
        }

        return $itemDetails;
    }

    /**
     * @param string $since
     * @param int    $pageNumber
     * @param int    $pageSize
     */
    public function getProducPricesChanged(
        ?string $since = '1970-01-01T00:00:00.000Z',
        ?int $pageNumber = 0,
        ?int $pageSize = 0,
        ?string $sortDir = 'ASC'
    ): array {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/products/price/changed';

        $data = [
            'changedSince' => $since,
            'pageNumber' => $pageNumber,
            'pageSize' => $pageSize,
        ];

        return $this->runRequest($url, 'POST', $data);
    }

    public function getAvailability(array $productCodes, $branchID = null): array
    {
        $this->debug = false;
        if ($this->debug) {
            $this->startTime = microtime(true);
            echo '<hr /><hr /><h1>' . implode(',', $productCodes) . '</h1>';
        }
        $data = [
            'itemIds' => $productCodes,
            'branchId' => $branchID,
            'branchIdsExcluded' => [],
            'availableSince' => null,
            'onlyStoresWithStock' => false,
        ];
        if ($this->debug) {
            echo '<h2>submitting</h2>';
            echo '<pre>';
            print_r(json_encode($data));
            echo '</pre>';
        }

        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/products/inventory/availability';
        if ($this->debug) {
            echo '<h2>to</h2>' . $url;
        }

        $response = $this->runRequest($url, 'POST', $data);
        // parse the XML body
        $productsAvailable = [];

        if (is_array($response) && isset($response['data'])) {
            foreach ($response['data'] as $itemData) {
                if (isset($itemData['itemId'])) {
                    $itemID = $itemData['itemId'];
                    foreach ($itemData['branchAvailabilities'] as $branchData) {
                        if (! isset($productsAvailable[$itemID])) {
                            $productsAvailable[$itemID] = 0;
                        }
                        if (isset($branchData['available'])) {
                            $productsAvailable[$itemID] += (int) $branchData['available'];
                        }
                    }
                }
            }
        }

        if ($this->debug) {
            echo '<h2>response: ' . implode(',', $productsAvailable) . '</h2><pre>';
            print_r($response);
            echo '</pre>';
            $timeTaken = round((microtime(true) - $this->startTime) * 1000) . ' microseconds (1000 microseconds in one second)';
            echo '<h2>Time Taken: ' . $timeTaken . '</h2>';
        }

        return $productsAvailable;
    }

    /*
     *  Gets or sets the date range in which the promotion can be active.
     *  The start date of the promotion must be between $toDate and $fromDate
     *
     */

    public function getActivePromos(
        ?string $toDate = '2022-01-18T00:00:00.000Z',
        ?string $fromDate = '2020-01-01T00:00:00.000Z',
        ?bool $getAllRecords = false,
        ?int $pageNumber = 1,
        ?int $pageSize = 100,
        ?string $sortOrder = 'itemId',
        ?string $sortDir = 'ASC'
    ): array {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/promotions/active';

        $activeBetween = [
            'from' => $fromDate,
            'to' => $toDate,
        ];

        $data = [
            'activeBetween' => $activeBetween,
            'pageNumber' => $pageNumber, // if you input 0 will be treated as page 1
            'pageSize' => $getAllRecords ? 0 : $pageSize, //0 will return all records
            'sort' => $sortOrder,
            'dir' => $sortDir,
        ];

        return $this->runRequest($url, 'POST', $data);
    }

    /** CATEGORIES */

    /**
     * Gets the basic categories (categoryType=1,2,3).
     */
    public function getCategories(int $categoryType): array
    {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/categories/code/search/info?categoryType=' . $categoryType . '&searchKey=*&pagingInfo.sort=*';

        return $this->runRequest($url);
    }

    /**
     * Gets the sub categories ($categoryId is the id from getCategories e.g. "Bags & Cases").
     */
    public function getSubCategories(string $categoryId): array
    {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/subcategories/code/search/info?categoryId=' . urlencode($categoryId) . '&searchKey=*&pagingInfo.sort=*';
        return $this->runRequest($url);
    }

    /**
     * Gets the sub sub categories (not all items have these).
     */
    public function getSubSubCategories(string $categoryId, string $subCategoryId): array
    {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/subcategories/code/search/info?categoryId=' . $categoryId . '&subCategoryId=' . $subCategoryId . '&searchKey=*&pagingInfo.sort=*';

        return $this->runRequest($url);
    }

    public function compareProductWithBarcode(string $itemId): array
    {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/products/search/compareWithBarcode?queryContract.itemId=' . $itemId;

        return $this->runRequest($url);
    }

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
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/customers/changed';
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
        echo '<h3>Fetching data since: ' . $since . '</h3>';
        echo '<hr />';

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

        echo '<pre>';
        var_dump($customers);
        echo '</pre>';

        return $customers;
    }

    public function getCustomerDetails(string $customerId): array
    {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/customers//' . $customerId;

        return $this->runRequest($url);
    }

    public function getCustomerByEmail(string $email): array
    {
        $data = [
            'email' => $email,
        ];
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/customers/search/detailed';
        $result = $this->runRequest($url, 'POST', $data);

        return $result['data'];
    }

    /**
     * @param int $customerOrderId
     * @param int $branchId
     * @param int $workstationId
     */
    public function getCustomerOrder(
        ?int $customerOrderId,
        ?int $branchId = 1,
        ?int $workstationId = 0
    ): array {
        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/customerOrder/details';
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
    ): array {
        if ($member->AdvanceRetailCustomerID) {
            return $member->AdvanceRetailCustomerID;
        }
        $data = [
            'branchId' => $branchId,
            'startDate' => $member->Created, 'dateOfBirth' => $member->Created, 'firstName' => $member->FirstName,
            'lastName' => $member->Surname,
            'homeAddress' => [
                'email' => $member->Email,
            ],
        ];

        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/customers';

        try {
            $result = $this->runRequest($url, 'POST', $data);
            $member->AdvanceRetailCustomerID = $result;
            $member->write();
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());

            return [];
        }

        return $result;
    }

    /** ORDERS */

    /**
     * @param Order $order
     * @param int   $customerId
     * @param int   $branchId
     * @param int   $workstationId
     * @return array | int
     */
    public function createOrder(
        ?Order $order,
        ?int $customerId = 0,
        ?int $branchId = 1,
        ?int $workstationId = 0
    ) {
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

        $url = $this->Config()->get('base_url') . '/' . $this->basePath . '/customerOrder';

        try {
            $result = $this->runRequest($url, 'POST', $data);
            $order->AdvanceRetailOrderID = $result;
            $order->write();
        } catch (Exception $e) {
            //what should we do if the order is unable to be succesfully created?
            $this->logError($e->getMessage());
            $result = [];
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
            echo '<pre>';
            var_dump($order);
            echo '</pre>';

            return $order;
        }
        echo 'You need to provide an order id from the AR api, eg: /ar-test/createorder/900000000';

        return null;
    }

    /**
     * Makes an HTTP request and sends back the response as JSON.
     *
     * @param string $method
     * @param array | string | int  $data
     */
    protected function runRequest(string $uri, ?string $method = 'GET', ?array $data = [])
    {
        $client = new Client();

        try {
            $response = $client->request(
                $method,
                $uri,
                [
                    'json' => $data,
                ]
            );
        } catch (RequestException $requestException) {
            $this->logError(Message::toString($requestException->getRequest()));
            if ($requestException->hasResponse()) {
                $this->logError(Message::toString($requestException->getResponse()));
            }
        } catch (ClientException $clientException) {
            $this->logError(Message::toString($clientException->getRequest()));
            $this->logError(Message::toString($clientException->getResponse()));
        }

        if (empty($response)) {
            $this->logError('Empty Response');

            return [];
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function logError(string $error)
    {
        $this->error .= '<pre>' . $error . '</pre>';
    }

    //#################################################
    // helpers OUTPUT
    //#################################################
    protected function output($v)
    {
        if ($this->verbose) {
            echo '<hr />';
            if (is_string($v)) {
                echo $v . '<br />';
            } else {
                echo '<pre>
                ' . print_r($v, 1) . '
                </pre>';
            }
        }
    }
}
