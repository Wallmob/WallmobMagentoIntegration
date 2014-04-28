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

class Wallmob_Wallmob_Block_Adminhtml_Scheduledlabel
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Retrieve Element HTML
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $schedule = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('job_code', array('eq' => 'wallmob_import_all'))
            ->addFieldToFilter('executed_at', array('null' => true))
            ->addOrder('scheduled_at', 'asc');
        if ($schedule->count()) {
            return $schedule->getFirstItem()->getScheduledAt();
        } else {
            return '<i>Not scheduled</i>';
        }
    }

}