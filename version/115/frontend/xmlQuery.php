<?php

namespace Heidelpay;

require_once PFAD_ROOT . PFAD_PLUGIN . $oPlugin->cVerzeichnis .'/vendor/autoload.php';
require_once __DIR__.'/arrayFilter.php';
  
  use \SimpleXMLElement;
  use Zend\Http\Request;
  use Zend\Http\Client;
  
class XmlQuery{

  
	
    public function getXMLRequest($config, $xml_params)
    {
		
		$arrFilClass = new ArrayFilter();
        // filter empty key/value pairs to prevent problems while creating the XML
        $xml_params = $arrFilClass->array_filter_recursive($xml_params);
        $xml = '<?xml version="1.0"?>' .
            '<Request version="1.0">
    <Header>
        <Security/>
    </Header>
    <Query>
        <User/>
    </Query>
</Request>';
        $xmlObject = new \SimpleXMLElement($xml);
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
                    $identification->addChild('ShortID', $ident);
                
            }
        }

        if (isset($xml_params['procRes']) && !empty($xml_params['procRes'])) {
            $xmlObject->Query->addChild('ProcessingResult', trim(strtoupper($xml_params['procRes'])));
        }
        if (isset($xml_params['transType']) && !empty($xml_params['transType'])) {
            $xmlObject->Query->addChild('TransactionType', trim(strtoupper($xml_params['transType'])));
        }

        return $xmlObject->asXML();
    }
	
	public function doRequest($params = array(), $url)
	{
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
            
            exit();
        }
    }
}