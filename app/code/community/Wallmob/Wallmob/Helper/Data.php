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

class Wallmob_Wallmob_Helper_Data
    extends Mage_Core_Helper_Abstract
{

    /**
     * XML configuration constants.
     */
    const XML_PATH_DEBUG_MODE = 'wallmob/import_settings/debug_mode';

    /**
     * Cached debug mode configuration.
     *
     * @var null|bool
     */
    protected $_isDebugMode = null;

    /**
     * Whether or not we are running in debug mode.
     *
     * @return bool
     */
    protected function _getIsDebugMode()
    {
        if ($this->_isDebugMode === null) {
            $this->_isDebugMode = (bool)Mage::getStoreConfig(self::XML_PATH_DEBUG_MODE);
        }
        return $this->_isDebugMode;
    }

    /**
     * Logs a message to the wallmob log file.
     *
     * @param string $message
     * @return void
     */
    public function logMessage($message)
    {
        Mage::log($message, null, 'wallmob.log', $this->_getIsDebugMode());
    }

}