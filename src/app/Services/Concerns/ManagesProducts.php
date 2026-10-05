<?php

namespace Siberfx\LaravelTryoto\app\Services\Concerns;

/**
 * "Products" section of the OTO API (products and boxes).
 */
trait ManagesProducts
{
    /**
     * POST /rest/v2/createProduct
     *
     * @param  array  $product  productName, sku, price, taxAmount, barcode, secondBarcode, description, brandId,
     *                          category, productImage, packagingMaterial, customAttributes
     */
    public function createProduct(array $product)
    {
        return $this->call('POST', '/rest/v2/createProduct', $product);
    }


    /**
     * POST /rest/v2/productList
     */
    public function productList(int $currentPage = 1, int $pageSize = 50)
    {
        return $this->call('POST', '/rest/v2/productList', [
            'pageSize' => $pageSize,
            'currentPage' => $currentPage,
        ]);
    }


    /**
     * POST /rest/v2/addBox — dimensions in cm.
     */
    public function addBox(string $name, $length, $width, $height)
    {
        return $this->call('POST', '/rest/v2/addBox', compact('name', 'length', 'width', 'height'));
    }


    /**
     * POST /rest/v2/updateBox — the box is identified by name.
     */
    public function updateBox(string $name, $length, $width, $height)
    {
        return $this->call('POST', '/rest/v2/updateBox', compact('name', 'length', 'width', 'height'));
    }


    /**
     * GET /rest/v2/getBox — all boxes, or a single one by name.
     */
    public function getBox(?string $name = null)
    {
        return $this->call('GET', '/rest/v2/getBox', query: ['name' => $name]);
    }
}
