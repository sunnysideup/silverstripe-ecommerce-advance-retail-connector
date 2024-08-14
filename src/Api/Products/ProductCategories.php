<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api\Products;

// use SilverStripe\Core\Config\Config;
use Sunnysideup\EcommerceAdvanceRetailConnector\Api\ARConnector;

class ProductCategories extends ARConnector
{
    /** CATEGORIES */

    /**
     * Gets the basic categories (categoryType=1,2,3).
     */
    public function getCategories(int $categoryType): array
    {
        $url = $this->makeUrlFromSegments('categories/code/search/info?categoryType=' . $categoryType . '&searchKey=*&pagingInfo.sort=*');

        return $this->runRequest($url);
    }

    /**
     * Gets the sub categories ($categoryId is the id from getCategories e.g. "Bags & Cases").
     */
    public function getSubCategories(string $categoryId): array
    {
        $url = $this->makeUrlFromSegments('subcategories/code/search/info?categoryId=' . urlencode($categoryId) . '&searchKey=*&pagingInfo.sort=*');

        return $this->runRequest($url);
    }

    /**
     * Gets the sub sub categories (not all items have these).
     */
    public function getSubSubCategories(string $categoryId, string $subCategoryId): array
    {
        $url = $this->makeUrlFromSegments('subcategories/code/search/info?categoryId=' . $categoryId . '&subCategoryId=' . $subCategoryId . '&searchKey=*&pagingInfo.sort=*');

        return $this->runRequest($url);
    }
}
