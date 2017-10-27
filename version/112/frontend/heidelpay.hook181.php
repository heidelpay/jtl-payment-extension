<?php
/**
 * Created by PhpStorm.
 * User: ronja.wann
 * Date: 02.10.2017
 * Time: 10:09
 */

require_once PFAD_ROOT . PFAD_PLUGIN . 'heidelpay_standard/vendor/autoload.php';

use Zend\Http\Request;
use Zend\Http\Client;

// if Versand oder Teilversand - Status s. defines_inc.php
if($args_arr['status'] == 4 OR $args_arr['status'] == 5){



preg_match($args_arr['oBestellung']['cKommentar'], '/[0-9]{3}\.[0-9]{3}\.[0-9]{3}/', $result);



    $_query_live_url = 'https://heidelpay.hpcgw.net/TransactionCore/xml';
    $_query_sandbox_url = 'https://test-heidelpay.hpcgw.net/TransactionCore/xml';


    $xml_params = array(
        "type" => "STANDARD",
        "methods" => array("IV"),
        "types" => array("PA"),
        "identification" => array(
            "ShortID" => $result),
        "procRes" => "ACK",
        "transType" => "PAYMENT"

    );


    $xml_params_fin = array(
        "type" => "LINKED_TRANSACTIONS",
        "methods" => array("IV"),
        "types" => array("FI"),
        "identification" => array(
            "ShortID" => $result),
        "procRes" => "ACK",
        "transType" => "PAYMENT"

    );


    $url = ($oPlugin->oPluginEinstellungAssoc_arr ['HPIVPG_transmode'] == 'LIVE') ? $_query_sandbox_url : $_query_live_url ;

    function array_filter_recursive($input)
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = array_filter_recursive($value);
            }
        }
        return array_filter($input);
    }

    /*
         * Method to use array_filter recursively
         * @param array $input
         * @return filtered array
         * source: http://php.net/manual/de/function.array-filter.php
         */

    function getXMLRequest($config, $xml_params)
    {
        // filter empty key/value pairs to prevent problems while creating the XML
        $xml_params = array_filter_recursive($xml_params);
        $xml = '<?xml version="1.0"?>' .
            '<Request version="1.0">
    <Header>
        <Security/>
    </Header>
    <Query>
        <User/>
    </Query>
</Request>';
        $xmlObject = new SimpleXMLElement($xml);
        $xmlObject->Header->Security->addAttribute('sender', trim(strtoupper($config['security_sender'])));
        $xmlObject->Query->addAttribute('mode', trim(($config['sandbox'] == 0) ? 'LIVE' : 'CONNECTOR_TEST'));

        $xmlObject->Query->addAttribute('level', 'MERCHANT');
        $xmlObject->Query->addAttribute('entity', trim($config['security_sender']));

        if (isset($xml_params['type']) && !empty($xml_params['type'])) {
            $xmlObject->Query->addAttribute('type', trim(strtoupper($xml_params['type'])));
        }
        $xmlObject->Query->User->addAttribute('login', trim($config['user_login']));
        $xmlObject->Query->User->addAttribute('pwd', trim($config['user_password']));

        $period = $xmlObject->Query->addChild('Period');
        $period->addAttribute('from', date('Y-m-d', strtotime('-30 day', time())));
        $period->addAttribute('to', date('Y-m-d'));


        if (isset($xml_params['methods']) && !empty($xml_params['methods'])) {
            $methods = $xmlObject->Query->addChild('Methods');
            foreach ($xml_params['methods'] as $key => $method) {
                if ($method != '') {
                    $newMeth = $methods->addChild('Method');
                    $newMeth->addAttribute('code', strtoupper($method));
                }
            }
        }
        if (isset($xml_params['types']) && !empty($xml_params['types'])) {
            $types = $xmlObject->Query->addChild('Types');
            foreach ($xml_params['types'] as $key => $type) {
                if ($type != '') {
                    $newType = $types->addChild('Type');
                    $newType->addAttribute('code', strtoupper($type));
                }
            }
        }
        if (isset($xml_params['identification']) && !empty($xml_params['identification'])) {
            $identification = $xmlObject->Query->addChild('Identification');
            foreach ($xml_params['identification'] as $key => $ident) {
                if (is_array($ident)) {
                    $subIdent = $identification->addChild(trim($key));
                    foreach ($ident as $key => $id) {
                        $subIdent->addChild('ID', $id);
                    }
                } else {
                    $identification->addChild($key, $ident);
                }
            }
        }

        if (isset($xml_params['procRes']) && !empty($xml_params['procRes'])) {
            $xmlObject->Query->addChild('ProcessingResult', trim(strtoupper($xml_params['procRes'])));
        }
        if (isset($xml_params['transType']) && !empty($xml_params['transType'])) {
            $xmlObject->Query->addChild('TransactionType', trim(strtoupper($xml_params['transType'])));
        }

        return $xmlObject->asXML();
    };



    function doRequest($params = array())
    {
        global $url;
        try {
            $client = new Client('',
                array(
                    'sslverifypeer' => false,
                    'ssltransport' => 'tls'
                )
            );
            $client->setUri($url);
            $client->setMethod('POST');
            $client->setParameterPost($params);
            $client->setAdapter('Zend\Http\Client\Adapter\Curl');

            $response = $client->send();
            if ($response->getStatusCode() == '200') {
                return $response->getBody();
            }
        } catch (Exception $e) {
            $data = array(
                'type' => 0,
                'category' => 0,
                'errorMessage' => htmlspecialchars('Curl Error: ' . $e->getMessage())
            );
            #$this->logdata($data);
            exit();
        }
    };

    $sandboxMode = $oPlugin->oPluginEinstellungAssoc_arr ['HPIVPG_transmode'] == 'LIVE' ? 0 : 1;

    $config = array(
        "sandbox" => $sandboxMode,
        "security_sender" => $oPlugin->oPluginEinstellungAssoc_arr ['sender'],
        "user_login" => $oPlugin->oPluginEinstellungAssoc_arr ['user'],
        "user_password" => $oPlugin->oPluginEinstellungAssoc_arr ['pass']
    );



    $resLinkedTxn = doRequest(array('load' => urlencode(getXMLRequest($config, $xml_params_fin))));

    $resLinkedTxnXMLObject = new SimpleXMLElement($resLinkedTxn);

    $resLinkedTxnCount = (string)$resLinkedTxnXMLObject->Result['count'];


    if($resLinkedTxnCount == 1){



        $res = doRequest(array('load' => urlencode(getXMLRequest($config, $xml_params))));

        $resXMLObject = new SimpleXMLElement($res);

        $resUniquieId = (string)$resXMLObject->Result->Transaction->Identification->UniqueID;

        $paymentObject = new Heidelpay\PhpApi\PaymentMethods\InvoiceB2CSecuredPaymentMethod();

        $paymentObject->getRequest()->authentification(
            $oPlugin->oPluginEinstellungAssoc_arr ['sender'],
            $oPlugin->oPluginEinstellungAssoc_arr ['user'],
            $oPlugin->oPluginEinstellungAssoc_arr ['pass'],
            (string)$resXMLObject->Transaction['channel'],
            $sandboxMode);

        $paymentObject->getRequest()->basketData(
            $args_arr['oBestellung']['kBestellung'],
            $args_arr['oBestellung']['fGesamtsumme'],
            (string)$resXMLObject->Result->Transaction->Payment->Presentation->Currency,
            $args_arr['oBestellung']['cSession']);
        $paymentObject->finalize($resUniquieId);


    }


}