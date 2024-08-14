<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products;

// use SilverStripe\Core\Config\Config;
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
    ): array {
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
            self::$price_cache[$key] = $this->runRequest($url, 'POST', $data);
        }

        return self::$price_cache[$key];
    }

    /**
     * @param int $productCode
     */
    public function getPricesChangedForOneProduct($productCode): array
    {
        $response = $this->getProducPricesChanged();
        $products = $response['data'];
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
        ?int $pageSize = 100,
        ?string $sortOrder = 'itemId',
        ?string $sortDir = 'ASC'
    ): array {
        $url = $this->makeUrlFromSegments('promotions/active');

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
}
