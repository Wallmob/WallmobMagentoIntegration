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

class Wallmob_Wallmob_Model_Observer
{

    /**
     * Transaction constants.
     */
    const WALLMOB_TRANSACTION_TYPE  = 'WM_TRANSACTION_TYPE_CARD_EXTERNAL';
    const WALLMOB_TRANSACTION_STATE = 'WM_TRANSACTION_STATE_CAPTURED';
    const WALLMOB_USER_NAME         = 'MAGENTO';

    /**
     * Configuration constants.
     */
    const XML_PATH_SEND_EMAIL = 'wallmob/order_settings/send_email';

    /**
     * Gets the base data for an order.
     *
     * @param Mage_Sales_Model_Order $order
     * @param array|bool $shop
     * @param string $id
     * @return array
     */
    protected function _getBaseOrder($order, $shop, $id)
    {
        $data = array(
            'id'          => $id,
            'user_name'   => self::WALLMOB_USER_NAME,
            'customer_id' => $order->getCustomerName(),
            'shop_name'   => Mage::app()->getStore()->getName(),
            'timestamp'   => time()
        );
        if (Mage::getStoreConfig(self::XML_PATH_SEND_EMAIL)) {
            $data['email'] = $order->getCustomerEmail();
        }
        if ($shop) {
            $data['shop_id'] = $shop['id'];
        }

        if ($order->getDiscountAmount() > 0) {
            $data['discounts'] = array(array(
                'amount'      => -($order->getDiscountAmount() * 100),
                'description' => $order->getDiscountDescription()
            ));
        }
        return $data;
    }

    /**
     * Gets the line items for an order.
     *
     * @param Mage_Sales_Model_Order $order
     * @param array|bool $shop
     * @return array
     */
    protected function _getLineItems($order, $shop)
    {
        $lineItems = array();
        foreach ($order->getAllVisibleItems() as $item) {
            // Set line item defaults.
            $lineItem = array(
                'product_id'        => $item->getProduct()->getWallmobId(),
                'product_name'      => $item->getName(),
                'sku'               => $item->getSku(),
                'retail_price'      => $item->getPriceInclTax()    * 100,
                'total_line_amount' => $item->getRowTotalInclTax() * 100,
                'quantity'          => (int)$item->getQtyOrdered()
            );

            // See if we need to override any settings from variants.
            $childrenItems = $item->getChildrenItems();
            if (count($childrenItems)) {
                $variant = current($childrenItems);
                $lineItem['product_variant_name'] = $variant->getName();
                $lineItem['sku'] = $variant->getSku();
                $lineItem['product_variant_id'] = $variant->getProduct()->getWallmobId();
            }

            // Copy the stock location from the current shop.
            if ($shop) {
                $lineItem['stock_location_id'] = $shop['stock_location_id'];
            }

            $lineItems[] = $lineItem;
        }
        return $lineItems;
    }

    /**
     * Gets a unique guid.
     *
     * @return string
     */
    protected function _getGuid()
    {
        $str = md5(uniqid());
        return sprintf('%s-%s-%s-%s-%s',
            substr($str, 0,  8),
            substr($str, 8,  4),
            substr($str, 12, 4),
            substr($str, 16, 4),
            substr($str, 20, 12)
        );
    }

    /**
     * Gets the shipping item for an order.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getShippingItem($order)
    {
        return array(
            'product_name' => $order->getShippingDescription(),
            'retail_price' => $order->getShippingInclTax() * 100,
            'quantity'     => 1
        );
    }

    /**
     * Gets the transaction for an order.
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $orderId
     * @return array
     */
    protected function _getTransaction($order, $orderId)
    {
        return array(
            'order_id'             => $orderId,
            'type'                 => self::WALLMOB_TRANSACTION_TYPE,
            'state'                => self::WALLMOB_TRANSACTION_STATE,
            'base_currency'        => $order->getBaseCurrencyCode(),
            'currency'             => $order->getOrderCurrencyCode(),
            'base_currency_amount' => $order->getBaseGrandTotal() * 100,
            'currency_amount'      => $order->getGrandTotal()     * 100
        );
    }

    /**
     * Creates a wallmob order through the API.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function createWallmobOrder($observer)
    {
        $helper = Mage::helper('wallmob');
        try {
            $order = Mage::getModel('sales/order')->load(current($observer->getOrderIds()));
            $api   = Mage::getSingleton('wallmob/api');
            $shop  = $api->getCurrentShop();

            // Construct the order.
            $orderId = $this->_getGuid();
            $data = $this->_getBaseOrder($order, $shop, $orderId);
            $data['order_line_items'] = $this->_getLineItems($order, $shop);
            $data['order_line_items'][] = $this->_getShippingItem($order);
            $data['transactions'][] = $this->_getTransaction($order, $orderId);

            // Attempt to post it.
            // $helper->logMessage(sprintf('Sending order: %s', print_r($data, true)));
            $return = $api->postOrder($data);
            $helper->logMessage(sprintf('Succesfully posted order: %s', $return['id']));
        } catch (Exception $e) {
            $helper->logMessage(sprintf('Failed to post order: %s', $e->getMessage()));
        }
    }

    /**
     * Adds the wallmob ID attribute to the item collection.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function addWallmobAttribute($observer)
    {
        $observer->getEvent()
            ->getAttributes()
            ->setData(Wallmob_Wallmob_Model_Processor_Product::ID_ATTRIBUTE_CODE, true);
    }

}
