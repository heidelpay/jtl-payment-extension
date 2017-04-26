<?php
/**
 * This class provides a user-friendly printing of heidelpay error-codes.
 *
 * The locale refers to the .csv files in the lib/locales path, which contain
 * the error codes and the appropriate messages.
 * The path is important when own implementations have to be used.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/php-customer-messages
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage php-customer-messages
 * @category php-customer-messages
 */

namespace Heidelpay\CustomerMessages;

use Heidelpay\CustomerMessages\Exceptions\MissingLocaleFileException;
use Heidelpay\CustomerMessages\Helpers\FileSystem;

class CustomerMessage
{
    /** @var FileSystem A helper class for file handling. */
    private $_filesystem = null;

    /** @var string The locale (IETF tag is recommended) to be used by the library. */
    private $_locale;

    /** @var string The path of the locale file. */
    private $_path;

    /** @var array Contains all customer messages. */
    private $_messages;


    /**
     * The CustomerMessage constructor, which accepts the locale and
     * an optional different path that may not be the library's
     * path - so that own locale files can be used.
     *
     * @param string $locale (optional) The locale for the language to be used.
     * @param string $path (optional)
     * @throws MissingLocaleFileException
     */
    public function __construct($locale = 'en_US', $path = null)
    {
        $this->_locale = $locale;

        if ( $path ) {
            $this->_path = $path;
        }

        // if the locale file does not exist, we better throw an exception
        // instead of just let PHP error_log something.
        if ( ! file_exists($this->getLocalePath()) ) {
            throw new MissingLocaleFileException(
                "Locale file {$this->getLocalePath()} does not exist."
            );
        }

        // create an array of all message codes and messages
        // to prevent accessing the file each time.
        $this->setContent();
    }

    /**
     * Returns the locale name.
     *
     * @return string Current locale
     */
    public function getLocale()
    {
        return $this->_locale;
    }

    /**
     * Returns the full path for the locale file.
     *
     * @return string Full file path of the locale file
     */
    public function getLocalePath()
    {
        return $this->getPath() . '/' . $this->getLocale() . '.csv';
    }

    /**
     * Returns the path for the locale file.
     *
     * @return string  Path of the locale file
     */
    public function getPath()
    {
        return $this->_path ?: __DIR__ . '/locales';
    }

    /**
     * Returns a message for the given message code.
     *
     * @param string $messagecode The heidelpay message code
     * @return string The customer message that will be displayed
     */
    public function getMessage($messagecode)
    {
        if( preg_match('/^\d{3}\.\d{3}\.\d{3}$/', $messagecode) ) {
            $messagecode = 'HPError-' . $messagecode;
        }

        return isset($this->_messages[$messagecode])
            ? $this->_messages[$messagecode]
            : $this->getDefaultMessage($messagecode);
    }

    /**
     * Returns a default message, if the message code
     * is not specified in the locale file.
     *
     * @param string $messagecode The message code that will be displayed if no default is set.
     * @return string The customer message that will be displayed if the error code is not defined.
     */
    public function getDefaultMessage($messagecode = '000.000.000')
    {
        return isset($this->_messages['Default'])
            ? $this->_messages['Default']
            : "An unspecific error occured. HPErrorcode: {$messagecode}";
    }

    /**
     * Sets the $_messages array, where the customer messages are stored.
     *
     * @return void
     */
    private function setContent()
    {
        // open the fs to retrieve file information.
        $this->_filesystem = new FileSystem($this->getLocalePath());
        $this->_messages = $this->_filesystem->getCsvContent();
    }

}