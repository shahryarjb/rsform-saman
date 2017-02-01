<?php header("Content-type: text/html; charset=UTF-8"); ?>
<?php
/**
 * @package     Joomla - > Site and Administrator payment info
 * @subpackage  com_Rsfrom
 * @subpackage 	trangell_Saman
 * @copyright   trangell team => https://trangell.com
 * @copyright   Copyright (C) 20016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die('Restricted access');
if (!class_exists ('checkHack')) {
	require_once( JPATH_PLUGINS . '/system/rsfptrangellsaman/trangell_inputcheck.php');
}

class plgSystemRSFPTrangellSaman extends JPlugin {
	var $componentId = 203;
	var $componentValue = 'trangellsaman';
	
	public function __construct( &$subject, $config )
	{
		parent::__construct( $subject, $config );
		$this->newComponents = array(203);
	}
	
	function rsfp_bk_onAfterShowComponents() {
		$lang = JFactory::getLanguage();
		$lang->load('plg_system_rsfptrangellsaman');
		$db = JFactory::getDBO();
		$formId = JRequest::getInt('formId');
		$link = "displayTemplate('" . $this->componentId . "')";
		if ($components = RSFormProHelper::componentExists($formId, $this->componentId))
		   $link = "displayTemplate('" . $this->componentId . "', '" . $components[0] . "')";
?>
        <li class="rsform_navtitle"><?php echo 'درگاه سامان'; ?></li>
		<li><a href="javascript: void(0);" onclick="<?php echo $link; ?>;return false;" id="rsfpc<?php echo $this->componentId; ?>"><span id="TRANGELLSAMAN"><?php echo JText::_('اضافه کردن درگاه سامان'); ?></span></a></li>
		
		
		<?php
		
	}
	
	function rsfp_getPayment(&$items, $formId) {
		if ($components = RSFormProHelper::componentExists($formId, $this->componentId)) {
			$data = RSFormProHelper::getComponentProperties($components[0]);
			$item = new stdClass();
			$item->value = $this->componentValue;
			$item->text = $data['LABEL'];
			// add to array
			$items[] = $item;
		}
	}
	
	function rsfp_doPayment($payValue, $formId, $SubmissionId, $price, $products, $code) {//test
	    $app	= JFactory::getApplication();
		// execute only for our plugin
		if ($payValue != $this->componentValue) return;
		$tax = RSFormProHelper::getConfig('trangellsaman.tax.value');
		if ($tax)
			$nPrice = round($tax,0) + round($price,0) ;
		else 
			$nPrice = round($price,0);
		if ($nPrice > 100) {
			$merchantId = RSFormProHelper::getConfig('trangellsaman.samanmerchantid');
			$reservationNumber = time();
			$totalAmount =  $nPrice;
			$callBackUrl  = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId . '&task=plugin&plugin_task=trangellsaman.notify&code=' . $code;
			$sendUrl = "https://sep.shaparak.ir/Payment.aspx";
			
			echo '
				<form id="paymentForm" method="post" action="'.$sendUrl.'">
					<input type="hidden" name="Amount" value="'.$totalAmount.'" />
					<input type="hidden" name="MID" value="'.$merchantId.'" />
					<input type="hidden" name="ResNum" value="'.$reservationNumber.'" />
					<input type="hidden" name="RedirectURL" value="'.$callBackUrl.'" />
				</form>
				<script type="text/javascript">
				document.getElementById("paymentForm").submit();
				</script>'
			;
			exit;
			die;
		}
		else {
			$msg= $this->getGateMsg('price'); 
			$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
			$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
		}
	}
	
	function rsfp_bk_onAfterCreateComponentPreview($args = array()) {
		if ($args['ComponentTypeName'] == 'trangellsaman') {
			$args['out'] = '<td>&nbsp;</td>';
			$args['out'].= '<td>'.$args['data']['LABEL'].'</td>';
		}
	}
	
	function rsfp_bk_onAfterShowConfigurationTabs($tabs) {
		$lang = JFactory::getLanguage(); 
		$lang->load('plg_system_rsfptrangellsaman'); 
		$tabs->addTitle('تنظیمات درگاه سامان', 'form-TRANGELSAMAN'); 
		$tabs->addContent($this->trangellsamanConfigurationScreen());
	}
  
	function rsfp_f_onSwitchTasks() {
		if (JRequest::getVar('plugin_task') == 'trangellsaman.notify') {
			$app	= JFactory::getApplication();
			$jinput = $app->input;
			$code 	= $jinput->get->get('code', '', 'STRING');
			$formId = $jinput->get->get('formId', '0', 'INT');
			$db 	= JFactory::getDBO();
			$db->setQuery("SELECT SubmissionId FROM #__rsform_submissions s WHERE s.FormId='".$formId."' AND MD5(CONCAT(s.SubmissionId,s.DateSubmitted)) = '".$db->escape($code)."'");
			$SubmissionId = $db->loadResult();
			//$mobile = $this::getPayerMobile ($formId,$SubmissionId);
			//===================================================================================
			$resNum = $jinput->post->get('ResNum', '0', 'INT');
			$trackingCode = $jinput->post->get('TRACENO', '0', 'INT');
			$stateCode = $jinput->post->get('stateCode', '1', 'INT');
			
			$refNum = $jinput->post->get('RefNum', 'empty', 'STRING');
			if (checkHack::strip($refNum) != $refNum )
				$refNum = "illegal";
			$state = $jinput->post->get('State', 'empty', 'STRING');
			if (checkHack::strip($state) != $state )
				$state = "illegal";
			$cardNumber = $jinput->post->get('SecurePan', 'empty', 'STRING'); 
			if (checkHack::strip($cardNumber) != $cardNumber )
				$cardNumber = "illegal";
				
			$price = round($this::getPayerPrice ($formId,$SubmissionId),0);	
			$merchantId = RSFormProHelper::getConfig('trangellsaman.samanmerchantid');
			
			if (
				checkHack::checkNum($resNum) &&
				checkHack::checkNum($trackingCode) &&
				checkHack::checkNum($stateCode) &&
				checkHack::checkString($code)
			){
				if (isset($state) && ($state == 'OK' || $stateCode == 0)) {
					try {
						$out    = new SoapClient('https://sep.shaparak.ir/payments/referencepayment.asmx?WSDL');
						$resultCode    = $out->VerifyTransaction($refNum, $merchantId);
					
						if ($resultCode == $price) { 
							if ($SubmissionId) {
								$db->setQuery("UPDATE #__rsform_submission_values sv SET sv.FieldValue=1 WHERE sv.FieldName='_STATUS' AND sv.FormId='".$formId."' AND sv.SubmissionId = '".$SubmissionId."'");
								$db->execute();
								$db->setQuery("UPDATE #__rsform_submission_values sv SET sv.FieldValue='"  . "کد پیگیری  " . $trackingCode . "     ". $cardNumber . "شماره کارت " . "' WHERE sv.FieldName='transaction' AND sv.FormId='" . $formId . "' AND sv.SubmissionId = '" . $SubmissionId . "'");
								$db->execute();
								$mainframe = JFactory::getApplication();
								$mainframe->triggerEvent('rsfp_afterConfirmPayment', array($SubmissionId));
							}
							$msg= $this->getGateMsg(1); 
							$app->enqueueMessage($msg. '<br />' . ' کد پیگیری شما ' . $trackingCode, 'message');	
						}
						else {
							$msg= $this->getGateMsg($state); 
							$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
							$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 	
						}
					}
					catch(\SoapFault $e)  {
						$msg= $this->getGateMsg('error'); 
						$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
						$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 	
					}
				}
				else {
					$msg= $this->getGateMsg($state);
					$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
					$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 	
				}
			}
			else {
				$msg= $this->getGateMsg('hck2'); 
				$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
				$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 	
			}
		}
	}
	
	function trangellsamanConfigurationScreen() {
		ob_start();
?>
		<div id="page-trangellsaman" class="com-rsform-css-fix">
			<table  class="admintable">
				<tr>
					<td width="200" style="width: 200px;" align="right" class="key"><label for="api"><?php echo 'شماره ترمینال'; ?></label></td>
					<td><input type="text" name="rsformConfig[trangellsaman.samanmerchantid]" value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('trangellsaman.samanmerchantid')); ?>" size="100" maxlength="64"></td>
				</tr>
				<tr>
					<td width="200" style="width: 200px;" align="right" class="key"><label for="tax.value"><?php echo 'مقدار مالیات'; ?></label></td>
					<td><input type="text" name="rsformConfig[trangellsaman.tax.value]" value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('trangellsaman.tax.value')); ?>" size="4" maxlength="5"></td>
				</tr>
			</table>
		</div>
	
		<?php
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
	
	function getGateMsg ($msgId) {
		switch($msgId){
			case '-1': $out=  'خطای داخل شبکه مالی'; break;
			case '-2': $out=  'سپردها برابر نیستند'; break;
			case '-3': $out=  'ورودی های حاوی کاراکترهای غیر مجاز می باشد'; break;
			case '-4': $out=  'کلمه عبور یا کد فروشنده اشتباه است'; break;
			case '-5': $out=  'Database excetion'; break;
			case '-6': $out=  'سند قبلا برگشت کامل یافته است'; break;
			case '-7': $out=  'رسید دیجیتالی تهی است'; break;
			case '-8': $out=  'طول ورودی های بیش از حد مجاز است'; break;
			case '-9': $out=  'وجود کاراکترهای غیر مجاز در مبلغ برگشتی'; break;
			case '-10': $out=  'رسید دیجیتالی حاوی کاراکترهای غیر مجاز است'; break;
			case '-11': $out=  'طول ورودی های کمتر از حد مجاز است'; break;
			case '-12': $out=  'مبلغ برگشت منفی است'; break;
			case '-13': $out=  'مبلغ برگشتی برای برگشت جزیی بیش از مبلغ برگشت نخورده رسید دیجیتالی است'; break;
			case '-14': $out=  'چنین تراکنشی تعریف نشده است'; break;
			case '-15': $out=  'مبلغ برگشتی به صورت اعشاری داده شده است'; break;
			case '-16': $out=  'خطای داخلی سیستم'; break;
			case '-17': $out=  'برگشت زدن جزیی تراکنشی که با کارت بانکی غیر از بانک سامان انجام پذیرفته است'; break;
			case '-18': $out=  'IP Adderess‌ فروشنده نامعتبر'; break;
			case 'Canceled By User': $out=  'تراکنش توسط خریدار کنسل شده است'; break;
			case 'Invalid Amount': $out=  'مبلغ سند برگشتی از مبلغ تراکنش اصلی بیشتر است'; break;
			case 'Invalid Transaction': $out=  'درخواست برگشت یک تراکنش رسیده است . در حالی که تراکنش اصلی پیدا نمی شود.'; break;
			case 'Invalid Card Number': $out=  'شماره کارت اشتباه است'; break;
			case 'No Such Issuer': $out=  'چنین صادر کننده کارتی وجود ندارد'; break;
			case 'Expired Card Pick Up': $out=  'از تاریخ انقضا کارت گذشته است و کارت دیگر معتبر نیست'; break;
			case 'Allowable PIN Tries Exceeded Pick Up': $out=  'رمز (PIN) کارت ۳ بار اشتباه وارد شده است در نتیجه کارت غیر فعال خواهد شد.'; break;
			case 'Incorrect PIN': $out=  'خریدار رمز کارت (PIN) را اشتباه وارده کرده است'; break;
			case 'Exceeds Withdrawal Amount Limit': $out=  'مبلغ بیش از سقف برداشت می باشد'; break;
			case 'Transaction Cannot Be Completed': $out=  'تراکنش تایید شده است ولی امکان سند خوردن وجود ندارد'; break;
			case 'Response Received Too Late': $out=  'تراکنش در شبکه بانکی  timeout خورده است'; break;
			case 'Suspected Fraud Pick Up': $out=  'خریدار فیلد CVV2 یا تاریخ انقضا را اشتباه وارد کرده و یا اصلا وارد نکرده است.'; break;
			case 'No Sufficient Funds': $out=  'موجودی به اندازه کافی در حساب وجود ندارد'; break;
			case 'Issuer Down Slm': $out=  'سیستم کارت بانک صادر کننده در وضعیت عملیاتی نیست'; break;
			case 'TME Error': $out=  'کلیه خطاهای دیگر بانکی که باعث ایجاد چنین خطایی می گردد'; break;
			case '1': $out=  'تراکنش با موفقیت انجام شده است'; break;
			case 'error': $out ='خطا غیر منتظره رخ داده است';break;
			case 'hck2': $out = 'لطفا از کاراکترهای مجاز استفاده کنید';break;
			case	'notff': $out = 'سفارش پیدا نشد';break;
			case	'price': $out = 'مبلغ وارد شده کمتر از ۱۰۰۰ ریال می باشد';break;
			default: $out ='خطا غیر منتظره رخ داده است';break;
		}
		return $out;
	}

	function getPayerMobile ($formId,$SubmissionId) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('FieldValue')
			->from($db->qn('#__rsform_submission_values'));
		$query->where(
			$db->qn('FormId') . ' = ' . $db->q($formId) 
							. ' AND ' . 
			$db->qn('SubmissionId') . ' = ' . $db->q($SubmissionId)
							. ' AND ' . 
			$db->qn('FieldName') . ' = ' . $db->q('mobile')
		);
		$db->setQuery((string)$query); 
		$result = $db->loadResult();
		return $result;
	}

	function getPayerPrice ($formId,$SubmissionId) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('FieldValue')
			->from($db->qn('#__rsform_submission_values'));
		$query->where(
			$db->qn('FormId') . ' = ' . $db->q($formId) 
							. ' AND ' . 
			$db->qn('SubmissionId') . ' = ' . $db->q($SubmissionId)
							. ' AND ' . 
			$db->qn('FieldName') . ' = ' . $db->q('price')
		);
		$db->setQuery((string)$query); 
		$result = $db->loadResult();
		return $result;
	}
}
