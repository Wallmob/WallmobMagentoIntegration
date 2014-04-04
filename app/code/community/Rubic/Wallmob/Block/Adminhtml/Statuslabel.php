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

class Rubic_Wallmob_Block_Adminhtml_Statuslabel
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Gets a formatted message.
     *
     * @param string $message
     * @param string $color
     * @return string
     */
    protected function _getMessage($message, $color)
    {
        return sprintf('<span style="color: #%s">%s</span>', $color, $message);
    }

    /**
     * Retrieve Element HTML
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        try {
            $reachability = Mage::getModel('wallmob/api')->getReachability();
            if ($reachability) {
                return $this->_getMessage('Reachable', '00CD00');
            } else {
                return $this->_getMessage('Unreachable', 'CD0000');
            }
        } catch (Exception $e) {
            return $this->_getMessage(sprintf('Error: %s', $e->getMessage()), 'CD0000');
        }
    }

}