<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products;

// use SilverStripe\Core\Config\Config;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;

class ProductPrices extends ARConnector
{
    protected static $price_cache = [];

    /**
    * @param string $since
    * @param int    $pageNumber
    * @param int    $pageSize
    */
    public function getProducPricesChanged(
        ?string $since = '',
        ?int $pageNumber = 0,
        ?int $pageSize = 0,
        ?string $sortDir = 'ASC'
    ): ?array {
        if (! $since) {
            $since = ARConnector::convert_silverstripe_to_ar_date('1 jan 1980');
        }
        $key = preg_replace('/[^A-Za-z0-9 ]/', '', (string) $since);
        if (! isset(self::$price_cache[$key])) {
            $url = $this->makeUrlFromSegments('products/price/changed');

            $data = [
                'changedSince' => $since,
                'pageNumber' => $pageNumber,
                'pageSize' => $pageSize,
            ];
            self::$price_cache[$key] = $this->runRequest($url, 'POST', $data, false, 10);
        }
        if (!is_array(self::$price_cache[$key])) {
            $this->logError('Invalid JSON response: ' .print_r(self::$price_cache[$key], 1));
            return [];
        }
        return self::$price_cache[$key];
    }

    /**
     * @param int $productCode
     */
    public function getPricesChangedForOneProduct($productCode): ?array
    {
        $products = $this->getProducPricesChanged();
        if ($products === null) {
            return null;
        }
        if (! empty($products)) {
            //make sure to do a false comparison because we do not know type of data.
            $key = array_search($productCode, array_column($products, 'id'), false);

            return $products[$key];
        }

        return [];
    }

    /**
     *  Gets or sets the date range in which the promotion can be active.
     *  The start date of the promotion must be between $toDate and $fromDate.
     */
    public function getActivePromos(
        ?string $fromDate = '2020-01-01T00:00:00.000Z',
        ?string $toDate = '2022-01-18T00:00:00.000Z',
        ?bool $getAllRecords = false,
        ?int $pageNumber = 1,
        ?int $pageSize = 1000,
        ?string $sortOrder = 'itemId',
        ?string $sortDir = 'ASC'
    ): ?array {
        // $url = $this->makeUrlFromSegments('promotions/active'); // old url!
        $url = $this->makeUrlFromSegments('promotions/pricePromotions/active');
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

        return $this->runRequest($url, 'POST', $data, false, 10);
    }


    /**
     *  Gets or sets the date range in which the promotion can be active.
     *  The start date of the promotion must be between $toDate and $fromDate.
     * @deprec 2024-09-01
     */
    public function getActivePromosOld(
        ?string $fromDate = '2020-01-01T00:00:00.000Z',
        ?string $toDate = '2022-01-18T00:00:00.000Z',
        ?bool $getAllRecords = false,
        ?int $pageNumber = 1,
        ?int $pageSize = 1000,
        ?string $sortOrder = 'itemId',
        ?string $sortDir = 'ASC'
    ): ?array {
        $url = $this->makeUrlFromSegments('promotions/active'); // old url!
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

        return $this->runRequest($url, 'POST', $data, false, 10);
    }

}
