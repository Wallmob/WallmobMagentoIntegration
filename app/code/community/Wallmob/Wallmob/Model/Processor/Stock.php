<?php
/**
 * Copyright 2014 Daniel Sloof <daniel@rubic.nl>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/

class Wallmob_Wallmob_Model_Processor_Stock
    extends Wallmob_Wallmob_Model_Processor_Abstract
        implements Wallmob_Wallmob_Model_Processor_Interface
{

    /**
     * Determines if any of the bundle's variants is in stock.
     *
     * @param Mage_Catalog_Model_Product $productIds
     * @return boolean
     */
    protected function _anyProductInStock($product)
    {
        // Get all variants from this bundle.
        $productIds = $product->getTypeInstance()->getSelectionsCollection(
            $product->getTypeInstance()->getOptionsCollection($product)->getAllIds(), $product
        )->getColumnValues('product_id');

        // Then if any of them have stock, the bundle is in stock too.
        $stockItemCollection = Mage::getModel('cataloginventory/stock_item')->getCollection()
            ->addProductsFilter($productIds);
        foreach ($stockItemCollection as $stockItem) {
            if ((int)$stockItem->getQty() > 0) return true;
        }
        return false;
    }

    /**
     * Updates stock for the parents of specified variants.
     *
     * @param array $stockData
     * @return Wallmob_Wallmob_Model_Processor_Stock
     */
    protected function _updateBundleStock($stockData)
    {
        // Grab all variant product IDs.
        $variantIds = $this->_getProductCollectionFromWallmobIds(array_keys($stockData))
            ->getColumnValues('entity_id');

        // Then use them to get our parent products.
        $bundleCollection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', array('in' =>
                array_unique(Mage::getResourceSingleton('bundle/selection')->getParentIdsByChild($variantIds))
            ));

        // If we're salable, we're in stock.
        $this->_getHelper()->logMessage(sprintf('Updating %d bundle stock status.', $bundleCollection->count()));
        foreach ($bundleCollection as $bundle) {
            $this->_updateStock($bundle->getId(), (int)$this->_anyProductInStock($bundle));
        }
        return $this;
    }

    /**
     * Updates normal product stock.
     *
     * @param array $stockData
     * @return Wallmob_Wallmob_Model_Processor_Stock
     */
    protected function _updateProductStock($stockData)
    {
        $productCollection = $this->_getProductCollectionFromWallmobIds(array_keys($stockData));
        $this->_getHelper()->logMessage(sprintf('Found %d products that can be updated.', $productCollection->count()));
        foreach ($productCollection as $_product) {
            $productId = $_product->getData(Wallmob_Wallmob_Model_Processor_Product::ID_ATTRIBUTE_CODE);
            if (isset($stockData[$productId])) {
                $this->_updateStock($_product->getId(), $stockData[$productId]);
            }
        }
        return $this;
    }

    /**
     * Gets a Magento product collection from wallmob product IDs.
     *
     * @param array $wallmobIds
     * @return Mage_Catalog_Model_Resource_Collection
     */
    protected function _getProductCollectionFromWallmobIds($wallmobIds)
    {
        return Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter(Wallmob_Wallmob_Model_Processor_Product::ID_ATTRIBUTE_CODE, array(
                'in' => $wallmobIds
            ));
    }

    /**
     * Updates stock for a specified product ID.
     *
     * @param int $productId
     * @param int $qty
     * @return Wallmob_Wallmob_Model_Processor_Stock
     */
    protected function _updateStock($productId, $qty)
    {
        $product = Mage::getModel('catalog/product')->load($productId);
        if ($product->getId()) {
            $this->_getHelper()->logMessage(sprintf('Updating inventory: %s -> %s', $product->getName(), $qty));
            $product->setStockData(array(
                'is_in_stock'  => $qty > 0,
                'manage_stock' => 1,
                'qty'          => $qty
            ));
            $product->save();
        }
        return $this;
    }

    /**
     * Import stock data.
     *
     * @param array $data
     * @return void
     */
    public function importData($data)
    {
        $this->_getHelper()->logMessage(sprintf('Updating stock for %d products.', count($data)));

        // Make stock data digestible.
        $stockUpdates   = array();
        $variantUpdates = array();
        foreach ($data as $stock) {
            if (isset($stock['product_variant_id'])) {
                $stockUpdates[$stock['product_variant_id']] = $stock['quantity'];
                $variantUpdates[$stock['product_variant_id']] = $stock['quantity'];
            }
            if (isset($stock['product_id'])) {
                $stockUpdates[$stock['product_id']] = $stock['quantity'];
            }
        }

        // Update normal product stock.
        $this->_updateProductStock($stockUpdates);

        // Update bundle stock.
        $this->_updateBundleStock($variantUpdates);
    }

}
