<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products;

// use SilverStripe\Core\Config\Config;

use SilverStripe\ORM\DataList;
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
        if (!is_array(self::$price_cache[$key])) {
            $this->logError('Invalid JSON response: ' .print_r(self::$price_cache[$key], 1));
            return [];
        }
        return self::$price_cache[$key];
    }

    /**
     * @param int $productCode
     */
    public function getPricesChangedForOneProduct($productCode): array
    {
        $response = $this->getProducPricesChanged();
        $products = $response['data'] ?? [];
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
    ): array {
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

        return $this->runRequest($url, 'POST', $data);
    }

    protected function getPromotionDetails(string $json): array
    {
        $promotionData = json_decode($json, true);
        // Extract required data
        $id = $promotionData['id'] ?? '';
        $name = $promotionData['name'] ?? '';
        $discountType = $promotionData['discountDescriptor']['type'] ?? '';
        $discountValue = $promotionData['discountDescriptor']['value'] ?? 0;

        // Item filters
        $items = [];
        foreach ($promotionData['bins'] as $bin) {
            foreach ($bin['items'] as $item) {
                $items[] = [
                    'itemId' => $item['itemId'] ?? '',
                    'itemType' => $item['itemType'] ?? '',
                    'excluded' => $item['excluded'] ?? false
                ];
            }
        }

        // Start and end dates
        $startDate = $promotionData['occurrenceTime']['startDate'] ?? '';
        $endDate = $promotionData['occurrenceTime']['endDate'] ?? '';

        return [
            'id' => $id,
            'name' => $name,
            'discountType' => $discountType,
            'discountValue' => $discountValue,
            'items' => $this->getPromotionItems($items),
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }


    protected function getPromotionItems(array $items): DataList
    {
        $includeIds = [];
        $excludeIds = [];
        foreach ($items as $item) {
            $itemId = $item['itemId'] ?? '';
            $itemType = $item['itemType'] ?? '';
            $excluded = $item['excluded'] ?? false;
            switch ($itemType) {
                case 'product':
                    // Get product by ID
                    $id = (int) Product::get()->filter('InternalItemID', $itemId)->first()?->ID;
                    if ($id) {
                        if ($excluded) {
                            $excludeIds[] = $id;
                        } else {
                            $includeIds[] = $id;
                        }
                    }
                    break;
                case 'Category2':
                case 'Category3':
                    // Get products by product category
                    $group = ProductGroup::get()->filter('InternalItemID', $itemId)->first();
                    if ($group) {
                        $ids = $group->getProducts()->column('ID');
                        if ($excluded) {
                            $excludeIds = array_merge($excludeIds, $ids);
                        } else {
                            $includeIds = array_merge($includeIds, $ids);
                        }
                    }
                    break;
                default:
                    user_error('Invalid item type: ' . $itemType);
                    // Invalid item type
                    break;
            }
        }
        return Product::get()->filter('ID', $includeIds)->exclude('ID', $excludeIds);
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
    ): array {
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

        return $this->runRequest($url, 'POST', $data);
    }
}
