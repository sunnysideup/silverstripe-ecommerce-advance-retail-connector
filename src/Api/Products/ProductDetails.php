<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products;

// use SilverStripe\Core\Config\Config;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;

class ProductDetails extends ARConnector
{
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
        $url = $this->makeUrlFromSegments('products/changed');

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
        $url = $this->makeUrlFromSegments('products/details/' . $productId);

        return $this->runRequest($url);
    }

    public function getProductDetailsExtra(string $productId): array
    {
        $url = $this->makeUrlFromSegments('products/' . $productId . '/extraDetails');

        return $this->runRequest($url);
    }

    /**
     * @param string $since
     */
    public function getAllProductDetails(?string $since = '2015-09-27T21:11:12.532Z'): array
    {
        $this->output('<h3>Fetching data since: ' . $since . '</h3>');
        $this->output('<hr />');

        // get the basic data of all products
        // it's okay to not use paging this as it doesn't return much data
        $fullData = $this->getProductsChanged($since);

        // paging data in the products request and total number of items
        $pagingData = $fullData['paging'];
        $totalItemCount = $pagingData['totalRecords'];

        // limits the number of items read from API for testing
        $totalItemCountLimit = 50000;
        $totalItemCountLimit = $totalItemCount <= $totalItemCountLimit ? $totalItemCount : $totalItemCountLimit;
        $this->output('<h3>Total number of items: ' . $totalItemCount . '</h3>');
        $this->output('<h3>Out of this, we are fetching ' . $totalItemCountLimit . ' items </h3>');
        $this->output('<hr />');

        // product data in the request
        $itemData = $fullData['data'];

        $itemDetails = [];  // final product details array
        $totalCount = 0;    // counter for the total number of item details that have been read
        $countLimit = 100;  // used to chop up the requests so server doesn't freeze up

        while ($totalCount < $totalItemCountLimit) {
            for ($count = 0; $count < $countLimit; ++$count) {
                $this->output('<b>Item ' . $totalCount . '</b><br />');

                // if ["action"] => "Remove" then skip (not in system anymore)
                $currentItemAction = $itemData[$totalCount]['action'];
                if ('Remove' !== $currentItemAction) {
                    $currentItemId = $itemData[$totalCount]['itemId'];
                    $itemDetail = $this->getProductDetails($currentItemId);
                    $itemDetails[$totalCount] = $itemDetail;
                    $this->output($itemDetail);
                    $output = '<pre>' . ob_get_clean() . '</pre>';
                    $this->output($output);
                    $this->output('<hr />');
                } else {
                    // need this or else API server will crash
                    $this->output('Removed item: SKIPPED <br />');
                    $this->output('<hr />');
                }

                ++$totalCount;
                if ($totalCount >= $totalItemCountLimit) {
                    break;
                }
            }
        }
        if(!is_array($itemDetails)) {
            $this->logError('Invalid JSON response: ' .print_r($itemDetails, 1));
            return [];
        }
        return $itemDetails;
    }

    /**
     * @param string $since
     */
    public function getAllProductDetailsExtra(?string $since = '2015-09-27T21:11:12.532Z'): array
    {
        //$since = '2015-09-27T21:11:12.532Z';
        $this->output('<h3>Fetching data since: ' . $since . '</h3>');
        $this->output('<hr />');

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
        $totalItemCountLimit = 10000 * 20;
        $totalItemCountLimit = $totalItemCount <= $totalItemCountLimit ? $totalItemCount : $totalItemCountLimit;
        $this->output('<h3>Total number of items: ' . $totalItemCount . '</h3>');
        $this->output('<h3>Out of this, we are fetching ' . $totalItemCountLimit . ' items </h3>');
        $this->output('<hr />');

        // product data in the request
        $itemData = $fullData['data'];

        $itemDetails = [];  // final product details array
        $totalCount = 0;    // counter for the total number of item details that have been read
        $countLimit = 100;  // used to chop up the requests so server doesn't freeze up

        while ($totalCount < $totalItemCountLimit) {
            for ($count = 0; $count < $countLimit; ++$count) {
                $this->output('<b>Item ' . $totalCount . '</b><br />');

                // if ["action"] => "Remove" then skip (not in system anymore)
                $currentItemAction = $itemData[$totalCount]['action'];
                if ('Remove' !== $currentItemAction) {
                    $currentItemId = $itemData[$totalCount]['itemId'];
                    $itemDetail = $this->getProductDetailsExtra($currentItemId);
                    $itemDetails[$totalCount] = $itemDetail;
                    $this->output($itemDetail);
                    $output = '<pre>' . ob_get_clean() . '</pre>';
                    $this->output($output);
                    $this->output('<hr />');
                } else {
                    // need this or else API server will crash
                    $this->output('Removed item: SKIPPED <br />');
                    $this->output('<hr />');
                }

                ++$totalCount;
                if ($totalCount >= $totalItemCountLimit) {
                    break;
                }
            }
        }
        if(!is_array($itemDetails)) {
            $this->logError('Invalid JSON response: ' .print_r($itemDetails, 1));
            return [];
        }
        return $itemDetails;
    }

    public function compareProductWithBarcode(string $itemId): array
    {
        $url = $this->makeUrlFromSegments('products/search/compareWithBarcode?queryContract.itemId=' . $itemId);

        return $this->runRequest($url);
    }
}
