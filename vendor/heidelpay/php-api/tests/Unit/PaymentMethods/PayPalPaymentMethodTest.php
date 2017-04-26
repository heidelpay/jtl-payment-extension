<?php
namespace Heidelpay\Tests\PhpApi\Unit\PaymentMethods;

use PHPUnit\Framework\TestCase;
use Heidelpay\PhpApi\PaymentMethods\PayPalPaymentMethod as  PayPal;

/**
 *
 *  PayPal Test
 *
 *  Connection tests can fail due to network issues and scheduled downtimes.
 *  This does not have to mean that your integration is broken. Please verify the given debug information
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/PhpApi
 *
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage PhpApi
 * @category UnitTest
 */
class PayPalPaymentMerhodTest extends TestCase
{
    /**
     * SecuritySender
     *
     * @var string SecuritySender
     */
    protected $SecuritySender = '31HA07BC8142C5A171745D00AD63D182';
    /**
     * UserLogin
     *
     * @var string UserLogin
     */
    protected $UserLogin      = '31ha07bc8142c5a171744e5aef11ffd3';
    /**
     * UserPassword
     *
     * @var string UserPassword
     */
    protected $UserPassword   = '93167DE7';
    /**
     * TransactionChannel
     *
     * @var string TransactionChannel
     */
    protected $TransactionChannel = '31HA07BC8142C5A171749A60D979B6E4';
    /**
     * SandboxRequest
     *
     * Request will be send to Heidelpay sandbox payment system.
     *
     * @var string
     */
    protected $SandboxRequest = true;
    
    /**
     * Customer given name
     *
     * @var string nameGiven
     */
    protected $nameGiven = 'Heidel';
    /**
     * Customer family name
     *
     * @var string nameFamily
     */
    protected $nameFamily ='Berger-Payment';
    /**
     * Customer company name
     *
     * @var string nameCompany
     */
    protected $nameCompany = 'DevHeidelpay';
    /**
     * Customer id
     *
     * @var string shopperId
     */
    protected $shopperId = '12344';
    /**
     * customer billing address street
     *
     * @var string addressStreet
     */
    protected $addressStreet = 'Vagerowstr. 18';
    /**
     * customer billing address state
     *
     * @var string addressState
     */
    protected $addressState  = 'DE-BW';
    /**
     * customer billing address zip
     *
     * @var string addressZip
     */
    protected $addressZip    = '69115';
    /**
     * customer billing address city
     *
     * @var string addressCity
     */
    protected $addressCity    = 'Heidelberg';
    /**
     * customer billing address city
     *
     * @var string addressCity
     */
    protected $addressCountry = 'DE';
    /**
     * customer mail address
     *
     * @var string contactMail
     */
    protected $contactMail = "development@heidelpay.de";
    
    /**
     * Transaction currency
     *
     * @var string currency
     */
    protected $currency = 'EUR';
    /**
     * Secret
     *
     * The secret will be used to generate a hash using
     * transaction id + secret. This hash can be used to
     * verify the the payment response. Can be used for
     * brute force protection.
     *
     * @var string secret
     */
    protected $secret = 'Heidelpay-PhpApi';
    
    /**
     * PaymentObject
     *
     * @var \Heidelpay\PhpApi\PaymentMethods\SofortPaymentMethod
     */
    protected $paymentObject = null;

    /**
     * Constructor used to set timezone to utc
     */
  public function __construct()
  {
      date_default_timezone_set('UTC');
  }

  /**
   * Set up function will create a PayPal object for each testcase
   *
   * @see PHPUnit_Framework_TestCase::setUp()
   */
  public function setUp()
  {
      $PayPal = new PayPal();
    
      $PayPal->getRequest()->authentification($this->SecuritySender, $this->UserLogin, $this->UserPassword, $this->TransactionChannel, 'TRUE');
    
      $PayPal->getRequest()->customerAddress($this->nameGiven, $this->nameFamily, null, $this->shopperId, $this->addressStreet, $this->addressState, $this->addressZip, $this->addressCity, $this->addressCountry, $this->contactMail);
    
    
      $PayPal->_dryRun=true;
    
      $this->paymentObject = $PayPal;
  }
  
  /**
   * Get current called method, without namespace
   *
   * @param string $method
   *
   * @return string class and method
   */
  public function getMethod($method)
  {
      return substr(strrchr($method, '\\'), 1);
  }
    
  /**
   * Test case for a single PayPal authorisation
   *
   * @return string payment reference id for the PayPal authorize transaction
   * @group connectionTest
   */
  public function testAuthorize()
  {
      $timestamp = $this->getMethod(__METHOD__)." ".date("Y-m-d H:i:s");
      $this->paymentObject->getRequest()->basketData($timestamp, 23.12, $this->currency, $this->secret);
      $this->paymentObject->getRequest()->async('DE', 'https://dev.heidelpay.de');
      
      $this->paymentObject->authorize();
      
      /* prepare request and send it to payment api */
      $request =  $this->paymentObject->getRequest()->convertToArray();
      $response =  $this->paymentObject->getRequest()->send($this->paymentObject->getPaymentUrl(), $request);
      
      $this->assertTrue($response[1]->isSuccess(), 'Transaction failed : '.print_r($response[1], 1));
      $this->assertFalse($response[1]->isError(), 'authorize failed : '.print_r($response[1]->getError(), 1));
      
      return (string)$response[1]->getPaymentReferenceId();
  }
  
  /**
   * Test case for a single PayPal debit
   *
   * @return string payment reference id for the PayPal authorize transaction
   * @group connectionTest
   */
  public function testDebit()
  {
      $timestamp = $this->getMethod(__METHOD__)." ".date("Y-m-d H:i:s");
      $this->paymentObject->getRequest()->basketData($timestamp, 23.12, $this->currency, $this->secret);
      $this->paymentObject->getRequest()->async('DE', 'https://dev.heidelpay.de');
      
  
      $this->paymentObject->debit();
          
      /* prepare request and send it to payment api */
      $request =  $this->paymentObject->getRequest()->convertToArray();
      $response =  $this->paymentObject->getRequest()->send($this->paymentObject->getPaymentUrl(), $request);
      
      $this->assertTrue($response[1]->isSuccess(), 'Transaction failed : '.print_r($response[1], 1));
      $this->assertFalse($response[1]->isError(), 'debit failed : '.print_r($response[1]->getError(), 1));
  
      return (string)$response[1]->getPaymentReferenceId();
  }
}
