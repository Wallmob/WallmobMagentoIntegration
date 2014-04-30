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

class Wallmob_Wallmob_Model_Processor_Product
    extends Wallmob_Wallmob_Model_Processor_Abstract
        implements Wallmob_Wallmob_Model_Processor_Interface
{

    /**
     * Product processor constants.
     */
    const ID_ATTRIBUTE_CODE = 'wallmob_id';

    /**
     * Product processor config nodes.
     */
    const XML_PATH_DEFAULT_TAX_CLASS  = 'wallmob/import_settings/default_tax_class';
    const XML_PATH_ASSIGN_TO_WEBSITES = 'wallmob/import_settings/assign_to_websites';

    /**
     * Cached taxclass configuration value.
     *
     * @var null|int
     */
    protected $_defaultTaxClass = null;

    /**
     * Cached assign to websites configuration value.
     *
     * @var null|array
     */
    protected $_assignToWebsites = null;

    /**
     * Cached default attribute set id value.
     *
     * @var null|int
     */
    protected $_defaultAttributeSetId = null;

    /**
     * Gets the default attribute set ID.
     *
     * @return int
     */
    protected function _getDefaultAttributeSetId()
    {
        if ($this->_defaultAttributeSetId === null) {
            $this->_defaultAttributeSetId = Mage::getModel('catalog/product')->getDefaultAttributeSetId();
        }
        return $this->_defaultAttributeSetId;
    }

    /**
     * Gets the default tax class from config.
     *
     * @return int
     */
    protected function _getDefaultTaxClass()
    {
        if ($this->_defaultTaxClass === null) {
            $this->_defaultTaxClass = (int)Mage::getStoreConfig(self::XML_PATH_DEFAULT_TAX_CLASS);
        }
        return $this->_defaultTaxClass;
    }

    /**
     * Returns the sites we assign to the product from config.
     *
     * @return array
     */
    protected function _getAssignToWebsites()
    {
        if ($this->_assignToWebsites === null) {
            $allWebsites = array_keys(Mage::app()->getWebsites());
            $selectedWebsites = Mage::getStoreConfig(self::XML_PATH_ASSIGN_TO_WEBSITES);
            if (!empty($selectedWebsites)) {
                $this->_assignToWebsites = array_intersect(explode(',', $selectedWebsites), $allWebsites);
            } else {
                $this->_assignToWebsites = $allWebsites;
            }
        }
        return $this->_assignToWebsites;
    }

    /**
     * Loads and optionally creates a product from sku.
     *
     * @param string $sku
     * @param int $productType
     * @return Mage_Catalog_Model_Product
     */
    protected function _getProductFromSku($sku, $productType)
    {
        $helper    = $this->_getHelper();
        $product   = Mage::getModel('catalog/product');
        $productId = $product->getIdBySku($sku);
        if (!$productId) {
            $helper->logMessage(sprintf('Product does not exist yet, creating...'));
        } else {
            $product->load($productId);
            if ($product->getTypeId() != $productType) {
                $helper->logMessage('Product already exists but of wrong type, deleting original...');
                $product->delete();
            } else {
                $helper->logMessage('Updating product.');
            }
        }
        return $product;
    }

    /**
     * Get a product with base fields filled.
     *
     * @param array $data
     * @param string $name
     * @param string $productType
     * @param int $visibility
     * @param float $price
     * @return Mage_Catalog_Model_Product
     */
    protected function _getBaseProduct($data, $name, $productType, $visibility, $price)
    {
        $product = $this->_getProductFromSku($data['sku'], $productType);
        $product->addData(array(
            'name'             => $name,
            'type_id'          => $productType,
            'visibility'       => $visibility,
            'sku'              => $data['sku'],
            'wallmob_id'       => $data['id'],
            'is_active'        => isset($data['active']) ? $data['active'] : 1,
            'attribute_set_id' => $this->_getDefaultAttributeSetId(),
            'website_ids'      => $this->_getAssignToWebsites(),
            'status'           => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'price'            => $price,
            'tax_class_id'     => $this->_getDefaultTaxClass()
        ));
        return $product;
    }

    /**
     * Gets the variant URL key.
     *
     * @param string $parentName
     * @param string $variantName
     * @return string
     */
    protected function _getVariantUrlKey($parentName, $variantName)
    {
        $urlKey = strtolower($parentName . ' ' . $variantName);
        $urlKey = preg_replace('/[^[:print:]]/', '', $urlKey);
        $urlKey = str_replace('  ', ' ', $urlKey);
        $urlKey = str_replace(' ', '-', $urlKey);
        return $urlKey;
    }

    /**
     * Imports variants, returns their product IDs.
     *
     * @param array $variants
     * @param float $basePrice
     * @return array
     */
    protected function _importVariants($variants, $basePrice, $parentName)
    {
        $productIds = array();
        foreach ($variants as $variant) {
            $this->_getHelper()->logMessage(sprintf('Processing variant: %s', $variant['product_variant_name']));

            // If we can't find a price for this child, assume it's the same as parent.
            $childPrice = $variant['product_data'][0]['retail_price'];
            if (!is_numeric($childPrice) || $childPrice == 0) {
                $childPrice = $basePrice;
            } else {
                $childPrice /= 100;
            }

            // Get child base product.
            $childProduct = $this->_getBaseProduct(
                $variant,
                $variant['product_variant_name'],
                'simple',
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
                $childPrice
            );

            // Set a specific URL path to avoid duplicates.
            $childProduct->setUrlKey($this->_getVariantUrlKey($parentName, $variant['product_variant_name']));

            // Save the child product.
            $childProduct->save();
            $productIds[] = $childProduct->getId();
        }
        return $productIds;
    }

    /**
     * Assign categories to the product.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $categories
     * @return void
     */
    protected function _assignCategories($product, $categories)
    {
        $categoryCollection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter(
                Wallmob_Wallmob_Model_Processor_Category::ID_ATTRIBUTE_CODE,
                array('in' => $categories)
            );
        if ($categoryCollection->count()) {
            $categoryIds = $categoryCollection->getColumnValues('entity_id');
            $this->_getHelper()->logMessage(sprintf('Assigning product to %d categories.', count($categoryIds)));
            $product->setCategoryIds($categoryIds);
        }
    }

    /**
     * Assign image to the product.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $image
     * @return void
     */
    protected function _assignImage($product, $image)
    {
        $helper = $this->_getHelper();
        $helper->logMessage(sprintf('Downloading asset: %s', $image));
        $filePath = $this->_getImportDirectory() . DS . end(explode('/', $image));
        file_put_contents($filePath, file_get_contents($image));
        if (!file_exists($filePath)) {
            $helper->logMessage('Was unable to download asset.');
        } else {
            $helper->logMessage('Assigning asset to product.');
            $product->addImageToMediaGallery($filePath, array('thumbnail', 'small_image', 'image'));
        }
    }

    /**
     * Get import directory and create it when it doesn't exist.
     *
     * @return string
     */
    protected function _getImportDirectory()
    {
        $directory = Mage::getBaseDir('media') . DS . 'import';
        if (!is_dir($directory)) {
            @mkdir($directory);
        }
        return $directory;
    }

    /**
     * Insert variant relations.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $variantProductIds
     */
    protected function _insertVariantRelations($product, $variantProductIds)
    {
        // If we're updating, delete existing options.
        if ($product->getId()) {
            Mage::getModel('bundle/option')->getCollection()
                ->addFieldToFilter('parent_id', array('eq' => $product->getId()))
                ->walk('delete');
        }

        // Create variant option.
        $product->setBundleOptionsData(array(array(
            'title'    => 'Select option',
            'type'     => 'select',
            'required' => 1,
            'position' => 0,
            'delete'   => false
        )));

        // Create variant selections.
        $position = 0;
        $selections = array();
        foreach ($variantProductIds as $variantProductId) {
            $selections[] = array(
                'product_id'    => $variantProductId,
                'is_default'    => false,
                'delete'        => false,
                'selection_qty' => 1,
                'position'      => $position++
            );
        }

        // Make sure our variants get saved.
        $product->setPriceType(Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC);
        $product->setBundleSelectionsData(array($selections));
        $product->setCanSaveBundleSelections(true);
    }

    /**
     * Deletes a product and optionally its variants.
     *
     * @param array $data
     * @return void
     */
    protected function _deleteProduct($data)
    {
        $helper = $this->_getHelper();
        $product = Mage::getModel('catalog/product')->loadByAttribute(self::ID_ATTRIBUTE_CODE, $data['id']);
        if (!$product) {
            $helper->logMessage(sprintf('Attempted to delete product that does not exist: %s', $data['id']));
            return;
        }

        // If we're a bundle, delete our variants.
        if ($product->getTypeId() == 'bundle') {
            $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection(
                $product->getTypeInstance(true)->getOptionsIds($product), $product
            );
            foreach ($selectionCollection as $option) {
                $variant = Mage::getModel('catalog/product')->load($option->getProductId());
                if ($variant->getId()) {
                    $helper->logMessage(sprintf('Deleting variant: %s', $variant->getName()));
                    $variant->delete();
                }
            }
        }
        $helper->logMessage(sprintf('Deleting product: %s', $product->getName()));
        $product->delete();
    }

    /**
     * Imports a single product.
     *
     * @param array $data
     * @return void
     */
    protected function _importProduct($data)
    {
        $this->_getHelper()->logMessage(sprintf('Processing product: %s', $data['id']));

        // If a product has variants, it's a bundle.
        $productType = count($data['product_variants']) ? 'bundle' : 'simple';

        // For now it's safe to just grab the first product data node.
        $productPrice = $data['product_data'][0]['retail_price'] / 100;

        if (isset($data['deleted'])) {
            $this->_deleteProduct($data);
            return;
        }

        // Get parent base product.
        $parentProduct = $this->_getBaseProduct(
            $data,
            $data['name'],
            $productType,
            Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            $productPrice
        );

        // We need to register the product for bundle saves.
        Mage::register('product', $parentProduct);

        // Assign to categories.
        if (count($data['product_categories'])) {
            $this->_assignCategories($parentProduct, $data['product_categories']);
        }

        // Import images.
        if (isset($data['image'])) {
            $this->_assignImage($parentProduct, $data['image']);
        }

        // Create and assign variants.
        if ($productType == 'bundle') {
            $variantProductIds = $this->_importVariants($data['product_variants'], $productPrice, $data['name']);
            $this->_insertVariantRelations($parentProduct, $variantProductIds);
        }
        $parentProduct->save();

        // Unregister it for next iteration.
        Mage::unregister('product');
    }

    /**
     * Imports products.
     *
     * @param array $data
     * @return void
     */
    public function importData($data)
    {
        foreach ($data as $product) {
            $this->_importProduct($product);
        }

        // Forcibly clear the block cache.
        Mage::app()->getCacheInstance()->cleanType(Mage_Core_Block_Abstract::CACHE_GROUP);
    }

}