<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\CustomersAndOrders\CustomerDetails;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\CustomersAndOrders\CustomerOrder;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products\ProductCategories;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products\ProductDetails;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products\ProductPrices;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products\ProductStock;
use Sunnysideup\Flush\FlushNow;
use Sunnysideup\Flush\FlushNowImplementor;

/**
 * Class \Sunnysideup\EcommerceAdvanceRetailConnector\Control\ARTestController
 *
 */
class ARTestController extends Controller
{
    use FlushNow;

    protected $arConnectionCustomerDetails;
    protected $arConnectionCustomerOrder;

    private static $url_segment = 'admin-test/advanceretailtest';

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
        $this->setApis();
        $this->showIndex();
    }

    public function mytest()
    {
        $this->setApis();

        $test = $this->arConnectionCustomerDetails->getCustomersChanged();

        $this->showResults($test);

        $this->showIndex();
    }

    public function getorder($request)
    {
        $this->setApis();

        $arOrderID = ((int) $request->param('ID')) ?: rand(1, 999);

        $this->showHeader('Order with order id = ' . $arOrderID);

        $this->showResults($this->arConnectionCustomerOrder->getCustomerOrder($arOrderID));

        $this->showExplanation('You need to provide an order id from the AR api, eg: ', 'getorder/900000000');

        $this->showIndex();
    }

    public function createorder($request)
    {
        $this->setApis();

        $orderID = (int) $request->param('ID');
        if ($orderID) {
            $order = Order::get_order_cached((int) $orderID);
            if ($order && $order->exists()) {
                $this->showHeader('Creating order #' . $orderID);
                $this->showResults(
                    $this->arConnectionCustomerOrder->createOrder($order)
                );
            } else {
                FlushNowImplementor::do_flush('There is no matching order in the database', 'deleted');
            }
        } else {
            $this->showExplanation('You need to add an orderID to the end of this link', 'createorder/123123');
        }

        $this->showIndex();
    }

    public function getcustomer($request)
    {
        $this->setApis();

        $custid = ((int) $request->Param('ID')) ?: rand(0, 9999);

        $this->showHeader('Getting customer with ID = ' . $custid);

        $this->showResults($this->arConnectionCustomerDetails->getCustomerDetails($custid));

        $this->showExplanation('You need to add an Customer ID to the end of this link', 'getcustomer/123123');

        $this->showIndex();
    }

    public function getcustomerbyemail($request)
    {
        $this->setApis();

        $email = $request->getVar('email') ?: 'hello@test.com';

        $this->showHeader('Getting customer with Email = ' . $email);
        $customer = $this->arConnectionCustomerDetails->getCustomerByEmail($email);
        $customer = reset($customer);
        $this->showResults($customer);

        $this->showExplanation('You need to provide an email address', 'getcustomerbyemail/?email=test@test.com');

        $this->showIndex();
    }

    public function getallcustomerdetails($request)
    {
        $this->setApis();
        $datePhrase = '1 year ago';
        $since = $this->arConnectionCustomerDetails->convertTsToArDate(strtotime((string) $datePhrase));
        $this->showHeader('Fetching data since: ' . $datePhrase);

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

        $this->showHeader('Number of customers: ' . count($customers));
        $this->showResults($customers);
        $this->showIndex();
    }

    public function createcustomer($request)
    {
        $this->setApis();

        $memberID = ((int) $request->Param('ID')) ?: rand(100000, 9999999);
        if ($memberID) {
            $member = Member::get_by_id($memberID);

            if ($member && $member->exists()) {
                $result = $this->arConnectionCustomerOrder->createCustomer($member);
                echo 'If the following is an integer, an customer has successfully been created';
                echo '<pre>';
                $this->showResults($result);
                echo '</pre>';
            } else {
                echo 'There is no matching member in the database';
            }
        }

        $this->showExplanation('You need to add an memberID to the end of this link', 'createcustomer/123123');
        $this->showIndex();
    }

    public function getproduct($request)
    {
        $this->setApis();

        $itemid = ((int) $request->Param('ID')) ?: rand(100000, 9999999);
        $this->showHeader('Show details for Product with ID = ' . $itemid);
        $this->showResults($this->arConnectionProductDetails->getProductDetails($itemid));
        $this->showExplanation('Use like this:', 'getproduct/123123');
        $this->showIndex();
    }

    public function getextradetails($request)
    {
        $this->setApis();

        $itemid = ((int) $request->Param('ID')) ?: rand(100000, 9999999);
        $this->showHeader('Show extra details for Product with ID = ' . $itemid);

        $this->showResults($this->arConnectionProductDetails->getProductDetailsExtra($itemid));
        $this->showIndex();
    }

    public function getallextradetails()
    {
        $this->setApis();
        $this->arConnectionProductDetails->getAllProductDetailsExtra();
        $this->showIndex();
    }

    public function getproductschanged($request)
    {
        $this->setApis();
        $datePhrase = '1 year ago';
        $since = $this->arConnectionCustomerDetails->convertTsToArDate(strtotime((string) $datePhrase));
        $this->showHeader('Fetching data since: ' . $datePhrase);

        $this->showResults($this->arConnectionProductDetails->getProductsChanged($since, true));
        $this->showIndex();
    }

    public function getallproductdetails($request)
    {
        $this->setApis();

        $datePhrase = '1 year ago';
        $since = $this->arConnectionCustomerDetails->convertTsToArDate(strtotime((string) $datePhrase));
        $this->showHeader('Fetching data since: ' . $datePhrase);

        // $arConnector = Injector::inst()->get(ARConnector::class);

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
                    $this->showResults($itemDetail);
                } else {
                    // need this or else API server will crash
                    self::do_flush('Removed item: SKIPPED');
                    self::do_flush('---------------------------');
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
        $this->showResults($response['data']);
    }

    protected function setApis()
    {
        $this->arConnectionCustomerDetails = Injector::inst()->get(CustomerDetails::class)->setDebug(true);
        $this->arConnectionCustomerOrder = Injector::inst()->get(CustomerOrder::class)->setDebug(true);
        // products
        $this->arConnectionProductCategories = Injector::inst()->get(ProductCategories::class)->setDebug(true);
        $this->arConnectionProductDetails = Injector::inst()->get(ProductDetails::class)->setDebug(true);
        $this->arConnectionProductPrices = Injector::inst()->get(ProductPrices::class)->setDebug(true);
        $this->arConnectionProductStock = Injector::inst()->get(ProductStock::class)->setDebug(true);
    }

    protected function showHeader(string $header)
    {
        FlushNowImplementor::do_flush_heading($header);
    }

    protected function showIndex()
    {
        echo '<h2>Tests</h2>';
        echo '<ul>';
        foreach (array_keys(self::$allowed_actions) as $action) {
            echo '<li><a href="' . Director::absoluteURL($this->Link($action)) . '">' . $action . '</a></li>';
        }
        echo '</ul>';
    }

    protected function showExplanation(string $explanation, string $action)
    {
        FlushNowImplementor::do_flush($explanation);
        FlushNowImplementor::do_flush($this->Link($action));
    }

    protected function showResults($results)
    {
        echo '<pre>';
        print_r($results);
        echo '</pre>';
    }
}
