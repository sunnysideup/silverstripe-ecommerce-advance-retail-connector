<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;

use Sunnysideup\EcommerceAdvanceRetailConnector\Api\CustomersAndOrders\CustomerOrder;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\CustomersAndOrders\CustomerDetails;

use Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products\ProductStock;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products\ProductPrices;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products\ProductDetails;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products\ProductCategories;
use Sunnysideup\Flush\FlushNow;

class ARTestController extends Controller
{
    use FlushNow;

    protected $arConnectionCustomerDetails = null;
    protected $arConnectionCustomerOrder = null;

    private $arConnectionProductCategories;
    private $arConnectionProductDetails;
    private $arConnectionProductPrices;
    private $arConnectionProductStock;

    private static $allowed_actions = [
        'getorder' => 'ADMIN',
        'createorder' => 'ADMIN',
        'getcustomer' => 'ADMIN',
        'getcustomerbyemail' => 'ADMIN',
        'getallcustomerdetails' => 'ADMIN',
        'createcustomer' => 'ADMIN',
        'getproduct' => 'ADMIN',
        'getproductschanged' => 'ADMIN',
        'getextradetails' => 'ADMIN',
        'getallextradetails' => 'ADMIN',
        'getallproductdetails' => 'ADMIN',
        'getallpromos' => 'ADMIN',
        'mytest' => 'ADMIN',
    ];

    public function index()
    {
        echo 'Hello from AR Test Controller';
    }

    public function mytest()
    {
        $this->setApis();


        $test = $this->arConnectionCustomerDetails->getCustomersChanged();

        echo '<pre>';
        var_dump($test);
        echo '</pre>';
    }

    public function getorder($request)
    {
        $arOrderID = (int) $request->param('ID');
        if ($arOrderID) {
            $this->setApis();
            $order = $this->arConnectionCustomerOrder->getCustomerOrder($arOrderID);
            echo '<pre>';
            var_dump($order);
            echo '</pre>';
        } else {
            echo 'You need to provide an order id from the AR api, eg: /ar-test/createorder/900000000';
        }
    }

    public function createorder($request)
    {
        $orderID = (int) $request->param('ID');
        if ($orderID) {
            $this->setApis();
            $order = Order::get_order_cached((int) $orderID);

            if ($order && $order->exists()) {
                $orderResult = $this->arConnectionCustomerOrder->createOrder($order);
                echo 'If the following is an integer, an order has successfully been created';
                echo '<pre>';
                var_dump($orderResult);
                echo '</pre>';
            } else {
                echo 'There is no matching order in the database';
            }
        } else {
            echo 'You need to add an orderID to the end of this link, eg: /ar-test/createorder/123123';
        }
    }

    public function getcustomer($request)
    {
        $this->setApis();


        $custid = $request->Param('ID');

        if (isset($custid)) {
            $this->arConnectionCustomerDetails->getCustomerDetails($custid);
        } else {
            echo 'Customer ID is missing in request!';
        }
    }

    public function getcustomerbyemail($request)
    {
        $email = $request->getVar('email');
        if ($email) {
            $this->setApis();
            $customer = $this->arConnectionCustomerDetails->getCustomerByEmail($email);
            $customer = reset($customer);
            echo '<pre>';
            var_dump($customer);
            echo '</pre>';
        } else {
            echo 'You need to provide an email address, eg: /ar-test/getcustomerbyemail/?email=test@test.com';
        }
    }

    public function getallcustomerdetails($request)
    {
        $since = '1990-09-27T21:11:12.532Z';
        echo '<h3>Fetching data since: ' . $since . '</h3>';
        echo '<hr />';

        $this->setApis();


        $pageNumber = 1;    // starting page number
        $pageSize = 100000;

        $customers = [];    // array containing all customers
        $pageNumberLimit = 5;   // limit the number of resutls to 5 pages
        $customerCount = 0;     // number of customers read from API

        while ($pageNumber <= $pageNumberLimit) {
            $fullData = $this->arConnectionCustomerDetails->getCustomersChanged($since, false, $pageNumber, $pageSize);
            $customerData = $fullData['data'];
            // useful
            // $pagingData = $fullData['paging'];
            // $itemsOnPage = sizeof($customerData);

            foreach ($customerData as $customer) {
                $customers[$customerCount] = $customer;
                ++$customerCount;
            }

            ++$pageNumber;
        }

        echo '<pre>';
        print_r(count($customers));
        echo '</pre>';
        echo '<pre>';
        print_r($customers);
        echo '</pre>';
    }

    public function createcustomer($request)
    {
        $memberID = (int) $request->param('ID');
        if ($memberID) {
            $this->setApis();
            $member = Member::get_by_id($memberID);

            if ($member && $member->exists()) {
                $result = $this->arConnectionCustomerOrder->createCustomer($member);
                echo 'If the following is an integer, an customer has successfully been created';
                echo '<pre>';
                var_dump($result);
                echo '</pre>';
            } else {
                echo 'There is no matching member in the database';
            }
        } else {
            echo 'You need to add an memberID to the end of this link, eg: /ar-test/createcustomer/123123';
        }
    }

    public function getproduct($request)
    {
        $this->setApis();


        $itemid = $request->Param('ID');

        if (isset($itemid)) {
            $product = $this->arConnectionProductDetails->getProductDetails($itemid);
            echo '<pre>';
            var_dump($product);
            echo '</pre>';
        } else {
            echo 'Item ID is missing in request!';
        }
    }

    public function getextradetails($request)
    {
        $this->setApis();


        $itemid = $request->Param('ID');

        if (isset($itemid)) {
            $product = $this->arConnectionProductDetails->getProductDetailsExtra($itemid);
            var_dump($product);
        } else {
            echo 'Item ID is missing in request!';
        }
    }

    public function getallextradetails()
    {
        $this->setApis();

        $this->arConnectionProductDetails->getAllProductDetailsExtra();
    }

    public function getproductschanged($request)
    {
        $this->setApis();


        $products = $this->arConnectionProductDetails->getProductsChanged('2015-09-27T21:11:12.532Z', true);
        var_dump($products);
    }

    public function getallproductdetails($request)
    {
        $since = '2015-09-27T21:11:12.532Z';
        self::do_flush('<h3>Fetching data since: ' . $since . '</h3>');
        self::do_flush('<hr />');

        // $arConnector = Injector::inst()->get(ARConnector::class);
        $this->setApis();


        // get the basic data of all products
        // it's okay to not use paging this as it doesn't return much data
        $fullData = $this->arConnectionProductDetails->getProductsChanged($since, true);

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
                    $itemDetail = $this->arConnectionProductDetails->getProductDetails($currentItemId);
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
    }

    public function getallpromos()
    {
        $this->setApis();


        ////'1970-01-01T00:00:00.000Z',
        $response = $this->arConnectionProductPrices->getActivePromos(
            ARConnector::convert_silverstripe_to_ar_date('1 jan 1980'),
            ARConnector::convert_silverstripe_to_ar_date('tomorrow')
        );
        $this->promos = $response['data'];
        echo '<pre>';
        var_dump($this->promos);
        echo '</pre>';
    }

    protected function getApi()
    {
        $this->arConnectionCustomerDetails = Injector::inst()->get(CustomerDetails::class)->setDebug(true);
        $this->arConnectionCustomerOrder = Injector::inst()->get(CustomerOrder::class)->setDebug(true);
        // products
        $this->arConnectionProductCategories = Injector::inst()->get(ProductCategories::class)->setDebug(true);
        $this->arConnectionProductDetails = Injector::inst()->get(ProductDetails::class)->setDebug(true);
        $this->arConnectionProductPrices = Injector::inst()->get(ProductPrices::class)->setDebug(true);
        $this->arConnectionProductStock = Injector::inst()->get(ProductStock::class)->setDebug(true);
    }
}
