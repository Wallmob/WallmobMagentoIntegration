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

// Add the wallmob ID attribute.
$installer->addAttribute(
    'catalog_product',
    Wallmob_Wallmob_Model_Processor_Product::ID_ATTRIBUTE_CODE,
    array(
        'group'        => 'General',
        'label'        => 'Wallmob ID',
        'global'       => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'required'     => false,
        'input'        => 'text',
        'user_defined' => true
    )
);

$installer->endSetup();
