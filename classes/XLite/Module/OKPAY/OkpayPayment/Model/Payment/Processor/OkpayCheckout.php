<?php
/**
 * The MIT License (MIT)
 * 
 * Copyright (c) 2007-2016 OKPAY
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace XLite\Module\OKPAY\OkpayPayment\Model\Payment\Processor;

use Includes\Utils\URLManager;
use XLite\Core\Database;
use XLite\Core\Request;
use XLite\Core\Translation;
use XLite\Model\Payment\Method;
use XLite\Model\Payment\Transaction;
use XLite\Model\Order;

/**
 * OKPAY payment processor
 */
class OkpayCheckout extends \XLite\Model\Payment\Base\WebBased
{
	/**
	 * Form URL
	 */
	const FORM_URL_LIVE = 'https://checkout.okpay.com/';
	
	/**
	 * Form URL for test mode
	 */
	const FORM_URL_TEST = 'https://testcheckout.okpay.com/';

	/**
	 * IPN URL
	 */
	const IPN_URL_LIVE = 'https://checkout.okpay.com/ipn-verify';
	
	/**
	 * IPN URL for test mode
	 */
	const IPN_URL_TEST = 'https://checkout.okpay.com/test-ipn-verify';

	/**
	 * Public invoice id length
	 */
	const PUBLIC_TOKEN_LENGTH = 3;

	/**
	 * Allowed currencies codes
	 */
	protected $allowedCurrencies = array('EUR','USD','GBP','HKD','CHF','AUD','PLN','JPY','SEK','DKK','CAD','RUB','CZK','HRK','HUF','NOK','NZD','RON','TRY','ZAR');

	/**
	 * stateCode - operation current state code. Possible values:
	 * completed - The operation has been completed and the funds have been added successfully to your account balance.
	 * pending   - The operation is pending. See ok_txn_pending_reason for more information.
	 * reversed  - The operation has been reversed together with all the related commission payments (if any).
	 * error     - An error occurred during the operation processing.
	 * canceled  - The operation has been canceled; transaction fees in this case are not returned.
	 * hold      - The operation is held.
	 */
	protected $stateCodes = array(
		'completed' => Transaction::STATUS_SUCCESS,
		'pending'   => Transaction::STATUS_PENDING,
		'reversed'  => Transaction::STATUS_CANCELED,
		'error'     => Transaction::STATUS_FAILED,
		'canceled'  => Transaction::STATUS_CANCELED,
		'hold'      => Transaction::STATUS_INPROGRESS,
	);

	/**
	 * Get settings widget or template
	 *
	 * @return string Widget class name or template path
	 */
	public function getSettingsWidget()
	{
		return 'modules/OKPAY/OkpayPayment/config.twig';
	}

	/**
	 * Check - payment method is configured or not
	 *
	 * @param \XLite\Model\Payment\Method $method Payment method
	 *
	 * @return boolean
	 */
	public function isConfigured(Method $method)
	{
		return parent::isConfigured($method)
			&& $method->getSetting('walletid')
			&& $method->getSetting('language');
	}

	/**
	 * Payment method has settings into Module settings section
	 *
	 * @return boolean
	 */
	public function hasModuleSettings()
	{
		return false;
	}

	/**
	 * Get payment method admin zone icon URL
	 *
	 * @param \XLite\Model\Payment\Method $method Payment method
	 *
	 * @return string
	 */
	public function getAdminIconURL(Method $method)
	{
		return true;
	}
	
	/**
	 * Get callback URL for OKPAY payment method
	 *
	 * @return string
	 */
	public function getOkpayCallbackURL()
	{
		return URLManager::getShopURL(
			\Includes\Utils\Converter::buildURL('callback', 'callback', array(), \XLite::getCustomerScript())
		);
	}

	/**
	 * Get Success URL for OKPAY payment method
	 *
	 * @return string
	 */
	public function getOkpaySuccessURL()
	{
		return URLManager::getShopURL(
			\Includes\Utils\Converter::buildURL('payment_return', '', array('status' => 'success'), \XLite::getCustomerScript())
		);
	}

	/**
	 * Get Fail URL for OKPAY payment method
	 *
	 * @return string
	 */
	public function getOkpayFailURL()
	{
		return URLManager::getShopURL(
			\Includes\Utils\Converter::buildURL('payment_return', '', array('status' => 'fail'), \XLite::getCustomerScript())
		);
	}

	/**
	 * Detect transaction
	 *
	 * @return \XLite\Model\Payment\Transaction
	 */
	public function getReturnOwnerTransaction()
	{
		return Request::getInstance()->ok_invoice
			? Database::getRepo('XLite\Model\Payment\Transaction')->findOneBy(
				array('public_id' => Request::getInstance()->ok_invoice)
			)
			: null;
	}

	/**
	 * Process return
	 *
	 * @param \XLite\Model\Payment\Transaction $transaction Return-owner transaction
	 *
	 * @return void
	 */
	public function processReturn(Transaction $transaction)
	{
		parent::processReturn($transaction);
		
		$request = Request::getInstance();
		
		if ($this->transaction->getStatus() === $transaction::STATUS_INPROGRESS) {
			if ('fail' === $request->status) {
				$this->transaction->setStatus($transaction::STATUS_FAILED);
			} elseif ('success' === $request->status) {
				$this->transaction->setStatus($transaction::STATUS_SUCCESS);
			}
			
		}
	}

	/**
	 * Get callback owner transaction or null
	 *
	 * @return \XLite\Model\Payment\Transaction
	 */
	public function getCallbackOwnerTransaction()
	{
		return Request::getInstance()->ok_invoice
			? Database::getRepo('XLite\Model\Payment\Transaction')->findOneBy(
				array('public_id' => Request::getInstance()->ok_invoice)
			)
			: null;
	}

	/**
	 * Process callback
	 *
	 * @param \XLite\Model\Payment\Transaction $transaction Callback-owner transaction
	 *
	 * @return void
	 */
	public function processCallback(Transaction $transaction)
	{
		parent::processCallback($transaction);
		
		$request = Request::getInstance();
		$postback = Request::getPostData();
		$postURL = $this->getIpnURL();
		
		
		$postback['ok_verify'] = 'true';
		
		$xmlRequest = new \XLite\Core\HTTP\Request($postURL);
		$xmlRequest->body = $postback;
		$xmlRequest->verb = 'POST';
		
		$response = $xmlRequest->sendRequest();
		
		if ($response->body)
		{
			$transactionNote = $this->transaction->getNote(); // Transaction notes
			$transactionStatus = $transaction::STATUS_FAILED; // Failed by default
			$result = "";
			
			if ($response->body == 'VERIFIED')
			{
				$transactionNote .= 'Regular transaction;'.PHP_EOL;
				$transactionNote .= static::t('Status') . ': ' . $request->ok_txn_status . '; ';
				$transactionNote .= $this->convertTransactionInfoToString(Request::getPostData());
				
				if( $request->ok_txn_gross == $this->transaction->getValue() &&
					$request->ok_txn_currency == $this->transaction->getCurrency()->getCode() &&
					$request->ok_receiver == $this->getSetting('walletid'))
				{
					if(array_key_exists($stateCode, $this->ok_txn_status))
					{
						$transactionNote .= static::t('IPN Handled');
						$transactionStatus = $this->stateCodes[$stateCode];
						$result = 'OK';
					}
					else
					{
						$transactionNote .= static::t('Transaction failed. Reason: Unknown status received');
						$result = 'Unknown status received';
					}
				}
				else
				{
					$transactionNote .= static::t('Transaction failed. Reason: Transaction parameters mismatch');
					$result = 'Transaction parameters mismatch';
				}
			}
			elseif ($response->body == 'TEST')
			{
				$result = "Test transaction";
				$transactionNote .= 'Test transaction;'.PHP_EOL;
				$transactionNote .= static::t('Status') . ': ' . $request->ok_txn_status . '; ';
				$transactionNote .= $this->convertTransactionInfoToString(Request::getPostData());
				
				if(!$this->isTestModeEnabled())
				{
					$transactionNote .= static::t('Transaction failed. Reason: Test transaction, but test mode has been disabled');
				}
				else
				{
					if( $request->ok_txn_gross == $this->transaction->getValue() &&
						$request->ok_txn_currency == $this->transaction->getCurrency()->getCode() &&
						$request->ok_receiver == $this->getSetting('walletid'))
					{
						if(array_key_exists($stateCode, $this->ok_txn_status))
						{
							$transactionNote .= static::t('IPN Handled');
							$transactionStatus = $this->stateCodes[$stateCode];
							$result = 'OK';
						}
						else
						{
							$transactionNote .= static::t('Transaction failed. Reason: Unknown status received');
							$result = 'Unknown status received';
						}
					}
					else
					{
						$transactionNote .= static::t('Transaction failed. Reason: Transaction parameters mismatch');
						$result = 'Transaction parameters mismatch';
					}
				}
			}
			elseif ($response->body == 'INVALID')
			{
				$transactionNote .= static::t('Transaction failed. Reason: Invalid IPN');
				$result = "Invalid IPN";
			}
			else
			{
				$transactionNote .= static::t('Transaction failed. Reason: Postback validation error');
				$result = "Postback validation error";
			}
			
			$this->transaction->setNote($transactionNote);
			$this->transaction->setStatus($transactionStatus);
			
			header("HTTP/1.1 200 OK");
			header('Content-Type: text/html');
			header('Content-Length: '.strlen($result));
			echo ($result);
		}
		else
		{
			$result = "Error while processing request";
			header("HTTP/1.1 404 Not Found");
			header('Content-Type: text/html');
			header('Content-Length: '.strlen($result));
			echo ($result);
		}
	}

	/**
	 * Convert transaction params to the string
	 *
	 * @param array $info Transaction params
	 *
	 * @return string
	 */
	protected function convertTransactionInfoToString($info)
	{
		$transactionNote = '';

		foreach ($info as $k => $v) {
			if (is_string($v) && $v) {
				$transactionNote .= $k . ': ' . $v . '; ';
			}
		}

		return rtrim(rtrim($transactionNote), ';');
	}
	
	/**
	 * Return TRUE if the test mode is ON (coming soon)
	 *
	 * @return boolean
	 */
	protected function isTestModeEnabled()
	{
		return false; //$this->getSetting('mode') === 'test';
	}

	/**
	 * Get payment form URL
	 *
	 * @return string
	 */
	protected function getFormURL()
	{
		return $this->isTestModeEnabled()
			? static::FORM_URL_TEST
			: static::FORM_URL_LIVE;
	}

	/**
	 * Get POST form URL (for checking of the transaction status)
	 *
	 * @return string
	 */
	protected function getIpnURL()
	{
		return $this->isTestModeEnabled()
			? static::IPN_URL_TEST
			: static::IPN_URL_LIVE;
	}
	
	/**
	 * Get redirect form fields list
	 *
	 * @return array
	 */
	protected function getFormFields()
	{
		$transactionId = $this->transaction->getTransactionId() . \XLite\Core\Operator::getInstance()->generateToken(
			static::PUBLIC_TOKEN_LENGTH,
			array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9')
		);
		$this->transaction->setPublicId($transactionId);
		
		return array(
			'ok_receiver'        => $this->getSetting('walletid'),
			'ok_item_1_name'     => substr(Translation::lbl('Order X', array('id' => $this->getTransactionId())), 0, 100),
			'ok_item_1_price'    => $this->transaction->getValue(),
			'ok_item_1_quantity' => 1,
			'ok_invoice'         => $transactionId,
			'ok_currency'        => $this->transaction->getCurrency()->getCode(),
			'ok_language'        => $this->getSetting('language'),
			'ok_return_success'  => $this->getOkpaySuccessURL(),
			'ok_return_fail'     => $this->getOkpayFailURL(),
			'ok_ipn'             => $this->getOkpayCallbackURL(),
		);
	}

	/**
	 * Get allowed currencies
	 *
	 * @param Method $method Payment method
	 *
	 * @return array
	 */
	protected function getAllowedCurrencies(Method $method)
	{
		return $this->allowedCurrencies;
	}
}
