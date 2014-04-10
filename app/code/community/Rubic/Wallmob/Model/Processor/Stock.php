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

class Rubic_Wallmob_Model_Processor_Stock
    extends Rubic_Wallmob_Model_Processor_Abstract
        implements Rubic_Wallmob_Model_Processor_Interface
{

    /**
     * Import stock data.
     *
     * @param array $data
     * @return void
     */
    public function importData($data)
    {
        $helper = $this->_getHelper();
        $helper->logMessage(sprintf('Updating stock for %d products.', count($data)));

        // Make stock data digestible.
        $stockUpdates = array();
        foreach ($data as $stock) {
            if (isset($stock['product_variant_id'])) $stockUpdates[$stock['product_variant_id']] = $stock['quantity'];
            if (isset($stock['product_id']))         $stockUpdates[$stock['product_id']]         = $stock['quantity'];
        }

        // Grab all products that match stock data.
        $productCollection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter(Rubic_Wallmob_Model_Processor_Product::ID_ATTRIBUTE_CODE, array(
                'in' => array_keys($stockUpdates)
            ));
        $helper->logMessage(sprintf('Found %d products that can be updated.', $productCollection->count()));

        // Update the products.
        foreach ($productCollection as $product) {
            $productId = $product->getData(Rubic_Wallmob_Model_Processor_Product::ID_ATTRIBUTE_CODE);
            if (isset($stockUpdates[$productId])) {
                $_product = Mage::getModel('catalog/product')->load($product->getId());
                if ($_product) {
                    $qty = $stockUpdates[$productId];
                    $helper->logMessage(sprintf('Updating inventory: %s -> %s', $product->getName(), $qty));
                    $_product->setStockData(array(
                        'is_in_stock'  => $qty > 0,
                        'manage_stock' => 1,
                        'qty'          => $qty
                    ));
                    $_product->save();
                }
            }
        }
    }

}