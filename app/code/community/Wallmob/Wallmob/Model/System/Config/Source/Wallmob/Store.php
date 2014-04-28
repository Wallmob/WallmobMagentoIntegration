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

class Wallmob_Wallmob_Model_System_Config_Source_Wallmob_Store
{

    /**
     * Returns an array of wallmob shops.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $stops = array();
        try {
            $shops = Mage::getModel('wallmob/api')->getShops();
        } catch (Exception $e) {}
        $options = array();
        $options[] = array('label' => 'All shops', 'value' => 0);
        foreach ($shops as $shop) {
            $options[] = array(
                'label' => $shop['name'],
                'value' => $shop['id']
            );
        }
        return $options;
    }

}