<?php
/**
 * Alphatech, <http://www.alphatech.com.ua>
 *
 * Copyright (C) 2015-present Sergii Gamaiunov <hello@webkadabra.com>
 * All rights reserved.
 */

namespace common\components\meestexpress;
use GuzzleHttp\Exception\RequestException;
use \Yii;
use SimpleXMLElement;
use yii\base\Exception;

function array_to_xml( $data, &$xml_data ) {
    foreach( $data as $key => $value ) {
        if( is_array($value) ) {
            if( is_numeric($key) ){
                $key = 'item'.$key; //dealing with <0/>..<n/> issues
            }
            $subnode = $xml_data->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml_data->addChild("$key",htmlspecialchars("$value"));
        }
    }
}

/**
 * Class Api
 * @author sergii gamaiunov <hello@webkadabra.com>
 * @package common\components\meestexpress
 */
class Api extends \yii\base\Component
{
    public $api_key;
    public $api_login;
    public $api_pass;

    const QUERY_API_URL = 'http://api1c.meest-group.com/services/1C_Query.php';
    const DOCUMENT_API_URL = 'http://api1c.meest-group.com/services/1C_Document.php';

    const SERVICE_WAREHOUSE = 0;
    const SERVICE_DOORS = 1;
    /**
     * @param $data
     * @return mixed
     */
    protected function  normalizeQueryRequest($data) {
        foreach(['function', 'where', 'order'] as $prop)
            if (!isset($data[$prop]))
                $data[$prop] = '';
        return $data;
    }
    /**
     * @param $data
     * @return mixed
     */
    protected function  normalizeDocumentRequest($data) {
        return $data;
    }

    /**
     * @param $data
     * @return string
     */
    protected function getSign($data) {
        return md5 ( $this->api_login . $this->api_pass . $data['function'] .$data['where'] . $data['order']);
    }

    /**
     * @param $data
     * @return \SimpleXMLElement $data
     */
    protected function getDocumentSign($data) {
        $xmlRequest = new \SimpleXMLElement('<?xml version="1.0"?><request></request>');
        array_to_xml($data['request'], $xmlRequest);
        $request = $xmlRequest->asXML();

        $request = str_replace('<?xml version="1.0"?>', '', $request);
        $request = str_replace('<request>', '', $request);
        $request = str_replace('</request>', '', $request);

        return md5 ( $this->api_login . $this->api_pass . $data['function'] . trim($request) . $data['request_id'] . $data['wait']);
    }

    /**
     * @param null $likeName
     * @param bool|true $exact
     * @param DescriptionUA|DescriptionRU
     * @return array|bool
     */
    public function findCity($likeName=null, $exact = true, $field='DescriptionRU') {

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><param></param>');

        $request = $this->normalizeQueryRequest([
            'login' => $this->api_login,
            'function' => 'City',
            'where' => $exact == true ? $field. ' = \''.$likeName.'\'' : $field . ' like \''.$likeName.'%\''
        ]);
        if ($this->queryCountry) {
            $request['where'] .= ' AND Countryuuid = "'.$this->queryCountry.'"';
        }
        if ($this->queryRegionUid) {
            $request['where'] .= ' AND Regionuuid = "'.$this->queryRegionUid.'"';
        }

        $sign = $this->getSign($request);
        $request['sign'] = $sign;

        foreach ($request as $key => $value) {
            $xml->addChild($key, $value);
        }
        $xml = $xml->asXML();

        try {
            $response = Yii::$app->httpclient->post(
                self::QUERY_API_URL, // URL
                $xml, // Body
                [], // Options
                true // Detect Mime Type?
            );

            if ($response->result_table && $response->result_table->items) {
                $res = (object) $response->result_table->items;
                return (array)  $res[0];

//                if (!is_array($response->result_table->items)) {
//                    return [$response->result_table->items];
//                } else {
//                    return $response->result_table->items;
//                }
            } else
                return false;
        } catch (RequestException $e) {
            // TODO: disable this handler temporarily
            return false;
        }
    }

    public function findRegion($likeName=null, $exact = true, $field='DescriptionRU') {

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><param></param>');

        $request = $this->normalizeQueryRequest([
            'login' => $this->api_login,
            'function' => 'Region',
            ///'where' => $exact == true ? 'DescriptionRU = \''.$likeName.'\'' : 'DescriptionRU like \''.$likeName.'%\''
            'where' => $exact == true ? $field. ' = \''.$likeName.'\'' : $field . ' like \''.$likeName.'%\''
        ]);
        if ($this->queryCountry) {
            $request['where'] .= ' AND Countryuuid = "'.$this->queryCountry.'"';
        }

        $sign = $this->getSign($request);
        $request['sign'] = $sign;

        foreach ($request as $key => $value) {
            $xml->addChild($key, $value);
        }
        $xml = $xml->asXML();

        try {
            $response = Yii::$app->httpclient->post(
                self::QUERY_API_URL, // URL
                $xml, // Body
                [], // Options
                true // Detect Mime Type?
            );

            if ($response->result_table && $response->result_table->items) {
                $res = (object) $response->result_table->items;
                return (array)  $res[0];
            } else
                return false;
        } catch (RequestException $e) {
            // TODO: disable this handler temporarily
            return false;
        }
    }

    public function findCountry($likeName=null, $exact = true, $field='DescriptionRU') {

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><param></param>');

        $request = $this->normalizeQueryRequest([
            'login' => $this->api_login,
            'function' => 'Country',
            'where' => $exact == true ? $field.' = \''.$likeName.'\'' : $field.' like \''.$likeName.'%\''
        ]);

        $sign = $this->getSign($request);
        $request['sign'] = $sign;

        foreach ($request as $key => $value) {
            $xml->addChild($key, $value);
        }
        $xml = $xml->asXML();

        try {
            $response = Yii::$app->httpclient->post(
                self::QUERY_API_URL, // URL
                $xml, // Body
                [], // Options
                true // Detect Mime Type?
            );

            if ($response->result_table && $response->result_table->items) {

                $res = (object) $response->result_table->items;
                return (array)  $res[0];
            } else
                return false;
        } catch (RequestException $e) {
            // TODO: disable this handler temporarily
            return false;
        }
    }

    public $queryCountry;
    public function setCountry($country) {
        $this->queryCountry = $country;
        return $this;
    }
    public $queryRegionUid;
    public function setRegion($value) {
        $this->queryRegionUid = $value;
        return $this;
    }

    /**
     * @link https://wiki.meest-group.com/uk/2-funktsii-formuvannia-vidpravlen/2-5-funktsiia-kalkuliator-calculateshipments
     * @param $senderService
     * @param $senderUID
     * @param $receiverService
     * @param $receiverUID
     * @param $items
     * @return array|bool
     */
    public function calculateShipments($senderService, $senderUID,$receiverService, $receiverUID, $items) {

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><param></param>');

        $request = $this->normalizeDocumentRequest([
            'login' => $this->api_login,
            'function' => 'CalculateShipments',
            'request' => [
                'CalculateShipment' => [
                    'ClientUID' => '8458f0b0-930f-11e2-a91e-003048d2b473', // taken from documentation
                    'SenderService' => self::SERVICE_WAREHOUSE,
                    #'SenderBranch_UID' =>
                    'SenderСity_UID' => $senderService == self::SERVICE_DOORS ? $senderUID : null,
                    'SenderBranch_UID' => $senderService == self::SERVICE_WAREHOUSE ? $senderUID : null,

                    'ReceiverService' => $receiverService,

                    'ReceiverCity_UID' => $receiverService == self::SERVICE_DOORS ? $receiverUID : null,
                    'ReceiverBranch_UID' => $receiverService == self::SERVICE_WAREHOUSE ? $receiverUID : null,

                    'Places_items' => $items
                ]
            ],
            'request_id'=>null,
            'wait' => 1
        ]);

        $sign = $this->getDocumentSign($request);
        $request['sign'] = $sign;
        array_to_xml($request, $xml);

        $xml = $xml->asXML();
        try {
            $response = Yii::$app->httpclient->post(
                self::DOCUMENT_API_URL, // URL
                $xml, // Body
                [], // Options
                true // Detect Mime Type?
            );

            $response = (object) $response->result_table;
            if ($response->items && $response->items->PriceOfDelivery
            ) {
                return (float) $response->items->PriceOfDelivery;
            } else
                return false;
        } catch (RequestException $e) {
            // TODO: disable this handler temporarily
            return false;
        }
    }
}