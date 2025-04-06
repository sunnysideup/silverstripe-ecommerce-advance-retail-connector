<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use Sunnysideup\Ecommerce\Model\Order;
use Sunnysideup\Ecommerce\Pages\Product;
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
    private static $url_segment = 'admin-test/advanceretailtest';

    protected CustomerDetails $arConnectionCustomerDetails;
    protected CustomerOrder $arConnectionCustomerOrder;
    private ProductCategories $arConnectionProductCategories;
    private ProductDetails $arConnectionProductDetails;
    private ProductPrices $arConnectionProductPrices;
    private ProductStock $arConnectionProductStock;

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

        $arOrderID = ((int) $request->param('ID')) ?:
            Order::get()
            ->sort(
                DB::get_conn()->random()
            )->filter(['AdvanceRetailOrderID:GreaterThan' => 0])
            ->first()
            ->AdvanceRetailOrderID;

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

        $custid = ((int) $request->param('ID')) ?:
            Member::get()
            ->sort(
                DB::get_conn()->random()
            )->filter(['AdvanceRetailCustomerID:GreaterThan' => 0])
            ->first()
            ->AdvanceRetailCustomerID;

        $this->showHeader('Getting customer with ID = ' . $custid);

        $this->showResults($this->arConnectionCustomerDetails->getCustomerDetails($custid));

        $this->showExplanation('You need to add an Customer ID to the end of this link', 'getcustomer/123123');

        $this->showIndex();
    }

    public function getcustomerbyemail($request)
    {
        $this->setApis();

        $email = $request->getVar('email') ?:
            Member::get()
            ->sort(
                DB::get_conn()->random()
            )
            ->first()
            ->Email;

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
            $customerData = $this->arConnectionCustomerDetails->getCustomersChanged($since, false, $pageNumber, $pageSize);
            if (empty($customerData)) {
                break;
            }

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

        $memberID = ((int) $request->Param('ID'));
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

        $itemid = ((int) $request->Param('ID')) ?:
            Product::get()
            ->sort(
                DB::get_conn()->random()
            )->filter(['InternalItemID:NOT' => ['', null]])
            ->first()
            ->InternalItemID;
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
        $since = $this->arConnectionProductDetails->convertTsToArDate(strtotime((string) $datePhrase));
        $this->showHeader('Fetching data since: ' . $datePhrase . '(' . $since . ')');

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
        $itemData = $this->arConnectionProductDetails->getProductsChanged($since, true);

        // paging data in the products request and total number of items
        $totalItemCount = $this->getLastTotalRecords();

        // limits the number of items read from API for testing
        $totalItemCountLimit = 50000;
        $totalItemCountLimit = $totalItemCount <= $totalItemCountLimit ? $totalItemCount : $totalItemCountLimit;
        self::do_flush('<h3>Total number of items: ' . $totalItemCount . '</h3>');
        self::do_flush('<h3>Out of this, we are fetching ' . $totalItemCountLimit . ' items </h3>');
        self::do_flush('<hr />');


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
        $this->showResults($response);
    }

    protected function initArConnection(string $class): mixed
    {
        return Injector::inst()->get($class)->setDebug(true);
    }

    protected function setApis(): void
    {
        $this->arConnectionCustomerDetails = $this->initArConnection(CustomerDetails::class);
        $this->arConnectionCustomerOrder = $this->initArConnection(CustomerOrder::class);
        $this->arConnectionProductCategories = $this->initArConnection(ProductCategories::class);
        $this->arConnectionProductDetails = $this->initArConnection(ProductDetails::class);
        $this->arConnectionProductPrices = $this->initArConnection(ProductPrices::class);
        $this->arConnectionProductStock = $this->initArConnection(ProductStock::class);
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
