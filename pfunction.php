<?php
@session_start();
include( dirname(__FILE__) . '/../../config/config.inc.php' );
include( dirname(__FILE__).'/../../header.php' );
include_once( dirname(__FILE__) . '/payping.php' );
if( isset( $_GET['do'] ) ){
	$payping = new payping;
	if( $_GET['do'] == 'payment' ){
		$payping->do_payment($cart);
	}elseif( $_GET['do'] == 'success_message' ){
		$url = 'https://' . $_SERVER['HTTP_HOST'];
		$order_id = $_GET['id'];
		$au = $_GET['refid'];
		echo pp_show_success_message( $url, $order_id, $au);
	}else{
		if( isset( $_GET['id'] ) && isset( $_GET['amount'] ) && isset( $_GET['clientrefid'] ) && isset( $_GET['refid'] ) ){
			$orderId  = $_GET['clientrefid'];
			$refId    = $_GET['refid'];
			$amount   = $_GET['amount'];
			$hash     = $_GET['hash'];

			if( md5($amount.$orderId.Configuration::get('payping_HASH_KEY') ) == $hash ){
				try{
					$curl = curl_init();
					curl_setopt_array($curl, array(
						CURLOPT_URL => "https://api.payping.ir/v1/pay/verify",
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => "",
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => "POST",
						CURLOPT_POSTFIELDS => json_encode( array( 'refId' => $refId , 'amount' => $amount ) ),
						CURLOPT_HTTPHEADER => array(
							"accept: application/json",
							"authorization: Bearer ".Configuration::get('payping_token'),
							"cache-control: no-cache",
							"content-type: application/json",
						),
					));
					$response = curl_exec($curl);
					$err = curl_error($curl);
					$header = curl_getinfo($curl);
					curl_close($curl);

					if($err){
						$Status = 'failed';
						$Fault = 'Curl Error.';
						echo $Message = 'خطا در ارتباط به پی‌پینگ : شرح خطا '.$err;
					}else{
						if( $header['http_code'] == 200 ){
							$response = json_decode($response, true);
							if( isset( $_GET["refid"] ) and $_GET["refid"] != '' ){
								error_reporting(E_ALL);
								$au = $_GET['refid'];
								$payping->validateOrder( $orderId, _PS_OS_PAYMENT_, ( $amount*Configuration::get( 'payping_currency' ) ), $payping->displayName, "سفارش تایید شده / کد رهگیری {$au}", array(), $cookie->id_currency);
								$url = 'https://' . $_SERVER['HTTP_HOST'] . '/modules/payping/pfunction.php?do=success_message&id='.$orderId.'&refid='. $au;
								Tools::redirect($url);
							}else{
								echo $payping->error($payping->l('cant get transaction code') . $payping->status_message($header['http_code'])  . ' (' .$header['http_code'] . ')<br/>' . $payping->l('Authority code') . ' : ' . $_GET['refid']);
							}
						}elseif($header['http_code'] == 400){
							echo $payping->error($payping->l('There is a problem. reason :') . implode('. ',array_values (json_decode($response,true))));
						}else{
							echo $payping->error($payping->l('There is a problem. reason :') . $payping->status_message($header['http_code'])  . ' (' .$header['http_code'] . ')' . $payping->l('Authority code') . ' : ' . $_GET['refid']);
						}
					}
				}catch( Exception $e ){
					echo $payping->error($payping -> l('Curl Error : ') .$e->getMessage() . ' <br> ' . $payping->l('Authority code') . ' : ' . $_GET['refid']);
				}
			}else{
				echo $payping->error($payping->l('There is a problem.'));
			}
		}else{
			echo $payping->error($payping->l('There is a problem.'));
		}
	}
}else{
	_403();
}
function _403() {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}
function pp_show_success_message( $url, $orderId, $au ){
	return '<div class="payment-success-notification">
								<p class="success-message text-success">خرید شما با موفقیت انجام شد.</p>
								<p class="order-id-data"><span class="show-text">شماره سفارش: </span><span class="show-order-id">'. $orderId .'</span></p>
								<p class="source-id-data"><span class="show-text">مرجع سفارش: </span><span class="show-source">'. $orderId .'</span></p>
								<p class="track-id-data"><span class="show-text">شماره پیگیری: </span><span class="show-trackid">'. $au .'</span></p>
								<a href="'. $url .'/?controller=order-detail&id_order='. $orderId .'" class="btn success">مشاهده جزئیات بیشتر</a>
								</div>';
}
include_once( dirname(__FILE__).'/../../footer.php' );