<?php
/**d
 * @package    PayPing payment module
 * @author     Erfan Ebrahimi
 * @copyright  2018  ErfanEbrahimi.ir
 * @version    1.0
 */
if (!defined('_PS_VERSION_'))
	exit ;
class payping extends PaymentModule {

	private $_html = '';
	private $_postErrors = array();

	public function __construct() {

		$this->name = 'payping';
		$this->tab = 'payments_gateways';
		$this->version = '1.1';
		$this->author = 'Erfan Ebrahimi';
		$this->currencies = true;
		$this->currencies_mode = 'radio';
		parent::__construct();
		$this->displayName = $this->l('PayPing Payment Module');
		$this->description = $this->l('Online Payment With PayPing');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
		if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module');
		$config = Configuration::getMultiple(array('payping_token'));
		if (!isset($config['payping_token']))
			$this->warning = $this->l('You have to enter your payping token code to use payping for your online payments.');

	}

	public function install() {
		if (!parent::install() || !Configuration::updateValue('payping_token', '') || !Configuration::updateValue('payping_currency', '') || !Configuration::updateValue('payping_logo', '') || !Configuration::updateValue('payping_HASH_KEY', $this->hash_key()) || !$this->registerHook('payment') || !$this->registerHook('paymentReturn'))
			return false;
		else
			return true;
	}

	public function uninstall() {
		if (!Configuration::deleteByName('payping_token') || !Configuration::deleteByName('payping_logo') || !Configuration::deleteByName('payping_currency') || !Configuration::deleteByName('payping_HASH_KEY') || !parent::uninstall())
			return false;
		else
			return true;
	}

	public function hash_key() {
		$en = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
		$one = rand(1, 26);
		$two = rand(1, 26);
		$three = rand(1, 26);
		return $hash = $en[$one] . rand(0, 9) . rand(0, 9) . $en[$two] . $en[$tree] . rand(0, 9) . rand(10, 99);
	}

	public function getContent() {

		if (Tools::isSubmit('payping_submit')) {

			Configuration::updateValue('payping_token', $_POST['PToken']);
			Configuration::updateValue('payping_logo', $_POST['PLogo']);
			Configuration::updateValue('payping_currency', $_POST['PCurrency']);
			$this->_html .= '<div class="conf confirm">' . $this->l('Settings updated') . '</div>';
		}

		$this->_generateForm();
		return $this->_html;
	}

	private function _generateForm() {
		if ( Configuration::get('payping_currency') == 1 or Configuration::get('payping_currency') == 10 )
			$currency = Configuration::get('payping_currency') ;
		else
			$currency = 1 ;
		$this->_html .= '<div align="center"><form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
		$this->_html .= $this->l('Enter your pin :') . '<br/><br/>';
		$this->_html .= '<input type="text" name="PToken" value="' . Configuration::get('payping_token') . '" ><br/><br/>';
		$this->_html .= $this->l('Your currency in Rials or Toman? (If the rial is 10, enter the number 1 otherwise.)') . '<br/><br/>';
		$this->_html .= '<input type="text" name="PCurrency" value="' . $currency . '" ><br/><br/>';
		$this->_html .= '<input type="submit" name="payping_submit"';
		$this->_html .= 'value="' . $this->l('Save it!') . '" class="button" />';
		$this->_html .= '</form><br/></div>';
	}

	public function status_message($code) {
		switch ($code){
			case 200 :
				return $this->l('200');
				break ;
			case 400 :
				return $this->l('400');
				break ;
			case 500 :
				return $this->l('500');
				break;
			case 503 :
				return $this->l('503');
				break;
			case 401 :
				return $this->l('401') ;
				break;
			case 403 :
				return $this->l('403');
				break;
			case 404 :
				return  $this->l('404');
				break;
		}
		return null ;
	}

	public function do_payment($cart) {
		$amount = floatval(number_format($cart ->getOrderTotal(true, 3), 2, '.', '')/Configuration::get('payping_currency'));
		$callbackUrl = $this->l('link') . 'modules/payping/pfunction.php?do=call_back&id=' . $cart ->id . '&amount=' . $amount. '&hash=' . md5($amount.$cart ->id.Configuration::get('payping_HASH_KEY'));
		$orderId = $cart ->id;
		$Description = 'پرداخت سفارش شماره: ' . $cart ->id;
		try {
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "https://api.payping.ir/v1/pay",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				//CURLOPT_POSTFIELDS => json_encode(array('payerName'=> null, 'Amount' => $amount,'payerIdentity'=> null , 'returnUrl' => $CallbackUrl, 'Description' => $Description , 'clientRefId' => $order->get_order_number() )),
				CURLOPT_POSTFIELDS => json_encode(array('Amount' => $amount, 'returnUrl' => $callbackUrl, 'Description' => $Description , 'clientRefId' => $orderId )),
				CURLOPT_HTTPHEADER => array(
					"accept: application/json",
					"authorization: Bearer " . Configuration::get('payping_token'),
					"cache-control: no-cache",
					"content-type: application/json"),
				)
			);

			$response = curl_exec($curl);
			$header = curl_getinfo($curl);
			$err = curl_error($curl);
			curl_close($curl);

			if ($err) {
				echo "cURL Error #:" . $err;
			} else {
				if ($header['http_code'] == 200) {
					$response = json_decode($response, true);
					if (isset($response["code"]) and $response["code"] != '') {
						echo $this->success($this->l('Redirecting...'));
						echo '<script>window.location=("https://api.payping.ir/v1/pay/gotoipg/' . $response['code'] . '");</script>';
						exit;
					} else {
						echo $this->error($this->l('There is a problem in get code.').$e->getMessage());
					}
				} elseif ($header['http_code'] == 400) {
					echo $this->error($this->l('There is a problem.'). implode('. ',array_values (json_decode($response,true))));
				} else {
					echo $this->error($this->l('There is a problem.').$this->status_message($header['http_code']) . '(' . $header['http_code'] . ')' );
				}
			}
		} catch (Exception $e){
			echo $this->error($this->l('There curl is a problem.').$e->getMessage());
		}
	}

	public function error($str) {
		return '<div class="alert error" dir="rtl" style="text-align: right">' . $str . '</div>';
	}

	public function success($star) {
		echo '<div class="conf confirm" dir="rtl" style="text-align: right">' . $str . '</div>';
	}

	public function hookPayment($params) {
		global $smarty;
		$smarty ->assign('payping_logo', Configuration::get('payping_logo'));
		if ($this->active)
			return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookPaymentReturn($params) {
		if ($this->active)
			return $this->display(__FILE__, 'confirmation.tpl');
	}

}
?>
