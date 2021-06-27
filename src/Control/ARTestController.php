<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;
use Sunnysideup\Flush\FlushNow;

class ARTestController extends Controller
{
    use FlushNow;

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
        $obj = $this->getApi();
        $obj->setDebug(true);

        $test = $obj->getCustomersChanged();

        echo '<pre>';
        var_dump($test);
        echo '</pre>';
    }

    public function getorder($request)
    {
        $arOrderID = (int) $request->param('ID');
        if ($arOrderID) {
            $obj = $this->getApi();
            $order = $obj->getCustomerOrder($arOrderID);
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
            $obj = $this->getApi();
            $order = Order::get()->byID($orderID);

            if ($order && $order->exists()) {
                $orderResult = $obj->createOrder($order);
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
        $obj = $this->getApi();
        $obj->setDebug(true);

        $custid = $request->Param('ID');

        if (isset($custid)) {
            $obj->getCustomerDetails($custid);
        } else {
            echo 'Customer ID is missing in request!';
        }
    }

    public function getcustomerbyemail($request)
    {
        $email = $request->getVar('email');
        if ($email) {
            $obj = $this->getApi();
            $customer = $obj->getCustomerByEmail($email);
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

        $obj = $this->getApi();
        $obj->setDebug(true);

        $pageNumber = 1;    // starting page number
        $pageSize = 100000;

        $customers = [];    // array containing all customers
        $pageNumberLimit = 5;   // limit the number of resutls to 5 pages
        $customerCount = 0;     // number of customers read from API

        while ($pageNumber <= $pageNumberLimit) {
            $fullData = $obj->getCustomersChanged($since, false, $pageNumber, $pageSize);
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
            $obj = $this->getApi();
            $member = Member::get()->byID($memberID);

            if ($member && $member->exists()) {
                $result = $obj->createCustomer($member);
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
        $obj = $this->getApi();
        $obj->setDebug(true);

        $itemid = $request->Param('ID');

        if (isset($itemid)) {
            $product = $obj->getProductDetails($itemid);
            echo '<pre>';
            var_dump($product);
            echo '</pre>';
        } else {
            echo 'Item ID is missing in request!';
        }
    }

    public function getextradetails($request)
    {
        $obj = $this->getApi();
        $obj->setDebug(true);

        $itemid = $request->Param('ID');

        if (isset($itemid)) {
            $product = $obj->getProductDetailsExtra($itemid);
            var_dump($product);
        } else {
            echo 'Item ID is missing in request!';
        }
    }

    public function getallextradetails()
    {
        $obj = $this->getApi();
        $obj->setDebug(true);
        $obj->getAllProductDetailsExtra();
    }

    public function getproductschanged($request)
    {
        $obj = $this->getApi();
        $obj->setDebug(true);

        $products = $obj->getProductsChanged('2015-09-27T21:11:12.532Z', true);
        var_dump($products);
    }

    public function getallproductdetails($request)
    {
        $since = '2015-09-27T21:11:12.532Z';
        self::do_flush('<h3>Fetching data since: ' . $since . '</h3>');
        self::do_flush('<hr />');

        // $arConnector = Injector::inst()->get(ARConnector::class);
        $obj = $this->getApi();
        $obj->setDebug(true);

        // get the basic data of all products
        // it's okay to not use paging this as it doesn't return much data
        $fullData = $obj->getProductsChanged($since, true);

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
                    $itemDetail = $obj->getProductDetails($currentItemId);
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
        $obj = $this->getApi();
        $obj->setDebug(true);
        $response = $obj->getActivePromos('1970-01-01T00:00:00.000Z', date('Y-m-d\\TH:i:s.000\\Z'));
        $this->promos = $response['data'];
        echo '<pre>';
        var_dump($this->promos);
        echo '</pre>';
    }

    protected function getApi(): ARConnector
    {
        $api = new ARConnector('ARESAPITest');
        if (isset($_GET['live'])) {
            $api->setBasePath('ARESAPI');
        }

        return $api;
    }
}
