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

class Wallmob_Wallmob_Model_Api
{

    /**
     * API config nodes.
     */
    const XML_PATH_API_URL      = 'wallmob/api_settings/url';
    const XML_PATH_API_USERNAME = 'wallmob/api_settings/username';
    const XML_PATH_API_PASSWORD = 'wallmob/api_settings/password';
    const XML_PATH_API_STORE    = 'wallmob/api_settings/store';

    /**
     * Cached HTTP client instance.
     *
     * @var null|Zend_Http_Client
     */
    protected $_client = null;

    /**
     * Returns the HTTP client.
     *
     * @return Zend_Http_Client
     */
    protected function _getClient()
    {
        if ($this->_client === null) {
            $this->_client = new Zend_Http_Client();
            $this->_client->setAuth(
                Mage::getStoreConfig(self::XML_PATH_API_USERNAME),
                Mage::getStoreConfig(self::XML_PATH_API_PASSWORD)
            );
        }
        return $this->_client;
    }

    /**
     * Gets URI for specific endpoint.
     *
     * @param string $endpoint
     * @return string
     */
    protected function _getUri($endpoint)
    {
        return Mage::getStoreConfig(self::XML_PATH_API_URL) . $endpoint;
    }

    /**
     * Make a GET request to a specific endpoint and return the decoded body.
     *
     * @param string $endpoint
     * @param array $parameters
     * @return array
     */
    protected function _get($endpoint, $parameters = array())
    {
        $client = $this->_getClient()->setUri($this->_getUri($endpoint));
        $client->setParameterGet($parameters);
        try {
            $response = $client->request();
            $body = json_decode($response->getBody(), true);
            if (isset($body['error'])) {
                Mage::throwException($body['error']);
            }
            return $body;
        } catch (Exception $ex) {
            Mage::throwException($ex->getMessage());
        }
    }

    /**
     * Make a POST request to a specific endpoint and return the decoded body.
     *
     * @param string $endpoint
     * @param array $parameters
     * @return array
     */
    protected function _post($endpoint, $parameters = array())
    {
        $client = $this->_getClient()->setUri($this->_getUri($endpoint));
        $client->setParameterPost($parameters);
        $client->setMethod(Zend_Http_Client::POST);
        try {
            $response = $client->request();
            $body = json_decode($response->getBody(), true);
            if (isset($body['error'])) {
                Mage::throwException(sprintf('%s (%s)', $body['error'], json_encode($body['data'])));
            }
            return $body;
        } catch (Exception $ex) {
            Mage::throwException($ex->getMessage());
        }
    }

    /**
     * Gets changes from the API, limited by timestamp and/or excludes/includes.
     *
     * @param int $timestamp
     * @param array $exclude
     * @param array $include
     * @return array
     */
    public function getChanges($timestamp = 0, $exclude = array(), $include = array())
    {
        $shop = Mage::getStoreConfig(self::XML_PATH_API_STORE);
        $endpoint = $shop ? sprintf('/shops/%s/changes', $shop) : '/changes';
        return $this->_get($endpoint, array(
            'from'    => $timestamp,
            'exclude' => json_encode($exclude),
            'include' => json_encode($include)
        ));
    }

    /**
     * Gets the server reachability.
     *
     * @return int
     */
    public function getReachability()
    {
        return $this->_get('/reachability');
    }

    /**
     * Gets shops.
     *
     * @return array
     */
    public function getShops()
    {
        return $this->_get('/shops');
    }

    /**
     * Gets the current shop.
     *
     * @return array|false
     */
    public function getCurrentShop()
    {
        $shop = Mage::getStoreConfig(self::XML_PATH_API_STORE);
        return $shop ? $this->_get(sprintf('/shops/%s', $shop)) : false;
    }

    /**
     * Posts an order.
     *
     * @param array $data
     * @return array
     */
    public function postOrder($data)
    {
        return $this->_post('/orders', $data);
    }

}