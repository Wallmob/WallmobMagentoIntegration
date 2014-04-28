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

class Wallmob_Wallmob_Model_Processor_Vat
    extends Wallmob_Wallmob_Model_Processor_Abstract
        implements Wallmob_Wallmob_Model_Processor_Interface
{

    /**
     * Imports vat rates.
     *
     * @param array $data
     * @return void
     */
    public function importData($data)
    {
        $helper = $this->_getHelper();
        $helper->logMessage(sprintf('Processing %d VAT rates.', count($data)));
        $helper->logMessage('VAT import is not implemented yet.');
    }

}
