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

class Rubic_Wallmob_Model_Import
{

    /**
     * Import config nodes.
     */
    const XML_PATH_IMPORT_ENTITIES     = 'wallmob/import_settings/entities';
    const XML_PATH_IMPORT_LAST_UPDATED = 'wallmob/import_settings/last_updated';

    /**
     * Cached API instance.
     *
     * @var null|Rubic_Wallmob_Model_Api
     */
    protected $_api = null;

    /**
     * Gets the API instance.
     *
     * @return Rubic_Wallmob_Model_Api
     */
    protected function _getApi()
    {
        if ($this->_api === null) {
            $this->_api = Mage::getSingleton('wallmob/api');
        }
        return $this->_api;
    }

    /**
     * Gets the import entities as an associative array.
     *
     * @return array
     */
    public function getEntities()
    {
        return (array)Mage::getStoreConfig(self::XML_PATH_IMPORT_ENTITIES);
    }

    /**
     * Imports all entities.
     *
     * @return void
     */
    public function importAll()
    {
        $helper = Mage::helper('wallmob');

        // Grab changes for our entities.
        $entities = $this->getEntities();
        try {
            $lastUpdated = Mage::getStoreConfig(self::XML_PATH_IMPORT_LAST_UPDATED);
            $helper->logMessage(sprintf('Last updated: %s', date('c', $lastUpdated)));
            $changes = $this->_getApi()->getChanges(
                $lastUpdated,
                array(),
                array_keys($entities)
            );
        } catch (Exception $ex) {
            $helper->logMessage(sprintf('Failed to get changes: %s', $ex->getMessage()));
            return;
        }

        // Grab a timestamp before we start the import.
        $time = time();

        // Import data for all entities.
        if ($changes['changes_found'] !== false) {
            foreach ($entities as $type => $model) {
                try {
                    $data = $changes[$type];
                    if (count($data)) {
                        $helper->logMessage(sprintf('Processing %d changes for %s', count($data), $type));
                        $processor = Mage::getModel($model);
                        $processor->importData($data);
                    }
                } catch (Exception $ex) {
                    $helper->logMessage(sprintf('An error occurred during the import: %s', $ex->getMessage()));
                    return;
                }

                // But only update the timestamp when we had no errors.
                $config = Mage::getConfig();
                $config->saveConfig(self::XML_PATH_IMPORT_LAST_UPDATED, $time);
                $config->removeCache();
            }
        } else {
            $helper->logMessage('No changes found since last update.');
        }
    }

}
