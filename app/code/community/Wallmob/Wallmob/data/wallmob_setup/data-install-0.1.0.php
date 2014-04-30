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

/* @var $installer Mage_Catalog_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// Fake an admin environment.
$currentStoreId = Mage::app()->getStore()->getId();
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

// Create the wallmob root category.
$category = Mage::getModel('catalog/category');
$category
    ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
    ->setName(Wallmob_Wallmob_Model_Processor_Category::ROOT_CATEGORY_NAME)
    ->setParentId(Mage_Catalog_Model_Category::TREE_ROOT_ID)
    ->setPath(Mage_Catalog_Model_Category::TREE_ROOT_ID)
    ->setAttributeSetId($category->getDefaultAttributeSetId())
    ->setIsActive(true)
    ->save();

// Restore the original store.
Mage::app()->setCurrentStore($currentStoreId);

$installer->endSetup();