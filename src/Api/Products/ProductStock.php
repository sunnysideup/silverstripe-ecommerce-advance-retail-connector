<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products;

// use SilverStripe\Core\Config\Config;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;

class ProductStock extends ARConnector
{
    protected static $storedStockResponses = [];

    private static $ignore_negative_stock = true;

    public function getAvailability(array $productCodes, $branchID = null): ?array
    {
        if ($this->debug) {
            if ($this->debug) {
                $this->startTime = microtime(true);
                $this->output('<h4>' . implode(',', $productCodes) . '</h4>');
            }
        }
        $data = [
            'itemIds' => $productCodes,
            'branchIdsExcluded' => $this->Config()->get('branches_to_be_excluded_from_stock'),
            'availableSince' => null,
            'onlyStoresWithStock' => false,
        ];
        // we filter afterwards
        // if($branchID) {
        //     $data['branchIdsIncluded'] = [$branchID];
        // }
        $url = $this->makeUrlFromSegments('products/inventory/availability');
        if ($this->debug) {
            $this->output('<h3>submitting</h3><pre>' . print_r(json_encode($data), 1) . '</pre>');
            $this->output('<h5>Branch</h5> ' . ($branchID ?: 'ANY'));
            $this->output('<h5>to</h5>' . $url);
        }
        $dataKey = serialize($data);
        if (! isset(self::$storedStockResponses[$dataKey])) {
            self::$storedStockResponses[$dataKey] = $this->runRequest($url, 'POST', $data, false, 1);
            if (self::$storedStockResponses[$dataKey] === null) {
                return null;
            }
        }
        // parse the XML body
        $productsAvailable = [];
        $response = self::$storedStockResponses[$dataKey];
        if (is_array($response)) {
            foreach ($response as $itemData) {
                if (isset($itemData['itemId'])) {
                    $itemID = $itemData['itemId'];
                    $productsAvailable[$itemID][self::ALL_BRANCH_ID] = $productsAvailable[$itemID][self::ALL_BRANCH_ID] ?? 0;
                    foreach ($itemData['branchAvailabilities'] as $branchData) {
                        $availablePerBranch = (int) ($branchData['available'] ?? 0);
                        if ($this->config()->get('ignore_negative_stock')) {
                            if ($availablePerBranch < 0) {
                                $availablePerBranch = 0;
                            }
                        }
                        $productsAvailable[$itemID][self::ALL_BRANCH_ID] += $availablePerBranch;
                        $productsAvailable[$itemID][$branchData['branchId']] = $availablePerBranch;
                    }
                }
            }
        }

        if ($this->debug) {
            if ($this->debug) {
                $timeTaken = round((microtime(true) - $this->startTime) * 1000) . ' microseconds (1000 microseconds in one second)';
                $this->output('<h5>response: ' . print_r($productsAvailable, 1) . '</h5>');
                $this->output('<pre>' . print_r($response, 1) . '</pre>');
                $this->output('<h5>Time Taken: ' . $timeTaken . '</h5>');
            }
        }
        if (!is_array($productsAvailable)) {
            $this->logError('Invalid JSON response: ' . print_r($productsAvailable, 1));
            return [];
        }
        return $productsAvailable;
    }
}
