<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products;

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
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;


class ProductCategories extends ARConnector
{


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

}
