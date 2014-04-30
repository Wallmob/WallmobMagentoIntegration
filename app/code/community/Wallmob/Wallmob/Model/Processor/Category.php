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

class Wallmob_Wallmob_Model_Processor_Category
    extends Wallmob_Wallmob_Model_Processor_Abstract
        implements Wallmob_Wallmob_Model_Processor_Interface
{

    /**
     * Category processor constants.
     */
    const ROOT_CATEGORY_NAME = 'Wallmob';
    const ID_ATTRIBUTE_CODE = 'wallmob_id';

    /**
     * Cache wallmob category instance.
     *
     * @var null|Mage_Catalog_Model_Category
     */
    protected $_wallmobCategory = null;

    /**
     * Return the wallmob root category.
     *
     * @return Mage_Catalog_Model_Category
     */
    protected function _getWallmobCategory()
    {
        if ($this->_wallmobCategory === null) {
            $categoryCollection = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('name', array('eq' => self::ROOT_CATEGORY_NAME))
                ->addAttributeToFilter('parent_id', array('eq' => Mage_Catalog_Model_Category::TREE_ROOT_ID));
            if (!$categoryCollection->count()) {
                Mage::throwException('Wallmob root category did not exist.');
            }
            $this->_wallmobCategory = $categoryCollection->getFirstItem();
        }
        return $this->_wallmobCategory;
    }

    /**
     * Imports a category.
     *
     * @param array $data
     * @return void
     */
    protected function _importCategory($data)
    {
        // Get our parent category.
        if ($data['parent_id'] !== null) {
            $parentCategory = Mage::getModel('catalog/category')->loadByAttribute(self::ID_ATTRIBUTE_CODE, $data['parent_id']);
            if ($parentCategory === false) {
                Mage::throwException('Attempted to import into a non-existent parent.');
            }
        } else {
            $parentCategory = $this->_getWallmobCategory();
        }

        // If the category already exists, grab it. Otherwise create a new one.
        $helper = $this->_getHelper();
        $category = Mage::getModel('catalog/category')->loadByAttribute(self::ID_ATTRIBUTE_CODE, $data['id']);
        if ($category === false) {
            $helper->logMessage(sprintf('Category does not exist yet, creating...'));
            $category = Mage::getModel('catalog/category');
        } else {
            $helper->logMessage('Updating category...');
        }

        if (isset($data['deleted'])) {
            // Delete the category.
            $helper->logMessage(sprintf('Deleting category: %s', $data['id']));
            $category->delete();
        } else {
            // Save the category.
            $helper->logMessage(sprintf('Saving category: %s (parent: %s)', $data['name'], $parentCategory->getName()));
            $category->addData(array(
                'wallmob_id'       => $data['id'],
                'name'             => $data['name'],
                'store_id'         => Mage_Core_Model_App::ADMIN_STORE_ID,
                'parent_id'        => $parentCategory->getId(),
                'attribute_set_id' => $category->getDefaultAttributeSetId(),
                'is_active'        => true
            ));
            if (!$category->getPath()) {
                $category->setPath($parentCategory->getPath());
            }
            $category->save();

            // We need to specifically move it, Magento doesn't do that when we update parent id.
            $category->move($parentCategory->getId(), null);
        }
    }

    /**
     * Import categories based on their hierarchical tree.
     *
     * @param array $tree
     * @param string $root
     * @return void
     */
    protected function _importCategories(&$tree, $root = null)
    {
        foreach ($tree as $child => $data) {
            if ($data['parent_id'] == $root) {
                $this->_importCategory($data);
                unset($tree[$child]);
                $this->_importCategories($tree, $data['id']);
            }
        }
    }

    /**
     * Imports categories.
     *
     * @param array $data
     * @return void
     */
    public function importData($data)
    {
        // First base it on hierarchy.
        $this->_importCategories($data);

        // Then import what's left on top level (no relationship found).
        // We assume this is an update and the parent hasn't updated.
        foreach ($data as $category) {
            $this->_importCategory($category);
        }
    }

}
