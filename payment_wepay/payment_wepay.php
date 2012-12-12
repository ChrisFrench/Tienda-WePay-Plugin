<?php
/**
 * @version	2.5
 * @package	Tienda
 * @author 	Bojan Nisevic
 * @link 	http://www.boyansoftware.com
 * @copyright Copyright (C) 2012 CrowdFunding. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

Tienda::load( 'TiendaPaymentPlugin', 'library.plugins.payment' );
jimport('joomla.log.log');
class plgTiendaPayment_wepay extends TiendaPaymentPlugin
{
    /**
     * @var $_element  string  Should always correspond with the plugin's filename,
     *                         forcing it to be unique
     */
    var $_element    = 'payment_wepay';

    /**
     * Constructor
     *
     * @param object $subject The object to observe
     * @param 	array  $config  An array that holds the plugin configuration
     * @since 1.5
     */
    function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $language = JFactory::getLanguage();
        $language -> load('plg_tienda_'.$this->_element, JPATH_ADMINISTRATOR, 'en-GB', true);
        $language -> load('plg_tienda_'.$this->_element, JPATH_ADMINISTRATOR, null, true);
    }
	
	
	 /************************************
     * Note to 3pd: 
     * 
     * The methods between here
     * and the next comment block are 
     * yours to modify
     * 
     ************************************/
	
    /**
     * Prepares the payment form
     * and returns HTML Form to be displayed to the user
     * generally will have a message saying, 'confirm entries, then click complete order'
     * 
     * Submit button target for onsite payments & return URL for offsite payments should be:
     * index.php?option=com_tienda&view=checkout&task=confirmPayment&orderpayment_type=xxxxxx
     * where xxxxxxx = $_element = the plugin's filename 
     *  
     * @param $data     array       form post data
     * @return string   HTML to display
     */
    function _prePayment( $data )
    {
        // prepare the payment form
        $vars = new JObject();
        $vars->url = JRoute::_( "index.php?option=com_tienda&view=checkout" );
        $vars->order_id = $data['order_id'];
        $vars->orderpayment_id = $data['orderpayment_id'];
        $vars->orderpayment_amount = $data['orderpayment_amount'];
        $vars->orderpayment_type = $this->_element;
            
        $vars->cardnum = !empty($data['cardnum']) ? $data['cardnum'] : JRequest::getVar("cardnum");      
        $vars->cardcvv = !empty($data['cardcvv']) ? $data['cardcvv'] : JRequest::getVar("cardcvv");
        $vars->cardnum_last4 = substr( $vars->cardnum, -4 );

        $exp_month = !empty($data['cardexp_month']) ? $data['cardexp_month'] : JRequest::getVar("cardexp_month");
        if ($exp_month < '10') { $exp_month = '0'.$exp_month; } 
		$vars->cardexp_month = $exp_month;
        $exp_year = !empty($data['cardexp_year']) ? $data['cardexp_year'] : JRequest::getVar("cardexp_year");
        $exp_year = $exp_year - 2000;
		$vars->cardexp_year = $exp_year;
        $cardexp = $exp_month.$exp_year;
        $vars->cardexp = $cardexp;
        
        $html = $this->_getLayout('prepayment', $vars);
		
        return $html;
    }

	/**
     * Payment plugins should override this function
     * to customize the one-line summary that is displayed
     * during the new OPC
     *
     * @param unknown_type $data
     * @return NULL
     */
    protected function _getSummary( $data )
    {
    	$vars->message = 'Checking out via Wepay, Credit Card';
		
		$html = $this->_getLayout('summary', $vars);
		
        return $html;
    }
    
    /**
     * Processes the payment form
     * and returns HTML to be displayed to the user
     * generally with a success/failed message
     *  
     * @param $data     array       form post data
     * @return string   HTML to display
     */
    function _postPayment( $data )
    {
    	// Process the payment        
        $vars = new JObject();
        
        $app = JFactory::getApplication();
        $paction = JRequest::getVar( 'paction' );
        
        switch ($paction)
        {
            case 'process_recurring':
                // TODO Complete this
                // $this->_processRecurringPayment();
                $app->close();                  
              break;
            case 'process':
                $vars->message = $this->_process();
                $html = $this->_getLayout('message', $vars);
              break;
            default:
                $vars->message = JText::_('COM_TIENDA_INVALID_ACTION');
                $html = $this->_getLayout('message', $vars);
              break;
        }
        
        return $html;
		
     
    }
    
    /**
     * Prepares variables and 
     * Renders the form for collecting payment info
     * 
     * @return unknown_type
     */
    function _renderForm( $data=null )
    {
        $html = $this->_getLayout('form');
        

        return $html;
    }
    
    /**
     * Verifies that all the required form fields are completed
     * if any fail verification, set 
     * $object->error = true  
     * $object->message .= '<li>x item failed verification</li>'
     * 
     * @param $submitted_values     array   post data
     * @return unknown_type
     */
    function _verifyForm( $submitted_values )
    {
    	
		// Include the JLog class.
		$object = new JObject();
        $object->error = false;
        $object->message = '';
        $user = JFactory::getUser();
 
        foreach ($submitted_values as $key=>$value) 
        {
            switch ($key) 
            {
                
                case "cardnum":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key])) 
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_('PLG_TIENDA_PAYMENT_WEPAY_CARD_NUMBER_INVALID')."</li>";
                    } 
                  break;
                case "cardexp":
                    if (!isset($submitted_values[$key]) || JString::strlen($submitted_values[$key]) != 4) 
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_('PLG_TIENDA_PAYMENT_WEPAY_CARD_EXPIRATION_DATE_INVALID')."</li>";
                    } 
                  break;
                case "cardcvv":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key])) 
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_('PLG_TIENDA_PAYMENT_WEPAY_CARD_CVV_INVALID')."</li>";
                    } 
                  break;
                default:
                  break;
            }
        }   
            
        return $object;
    }
	
    /************************************
     * Note to 3pd: 
     * 
     * The methods between here
     * and the next comment block are 
     * specific to this payment plugin
     * 
     ************************************/
	
	 /**
     * Processes the payment
     * 
     * This method process only real time (simple and subscription create) payments
     * The scheduled recurring payments are processed by the corresponding method
     * 
     * @return string
     * @access protected
     */
    function _process()
    {
        $data = JRequest::get('post');
		// Add the logger.
        
		// order info
        DSCTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
        $order = DSCTable::getInstance('Orders', 'TiendaTable');
        $order->load( $data['order_id'] );
		$orderitems = $order->getItems();
        $orderpayment = DSCTable::getInstance('OrderPayments', 'TiendaTable');
        $orderpayment->load( $data['orderpayment_id'] );
       // $orderinfo = DSCTable::getInstance('OrderInfo', 'TiendaTable');
       // $orderinfo->load( array( 'order_id'=>$data['order_id']) );
		/*We need the TWO letter short codes of state and country*/
		$db = JFactory::getDBO();
        $query = NEW DSCQuery();
        $query->select('o.*, c.*, z.code as state_code');
        $query->from('#__tienda_orderinfo AS o');
        $query->leftJoin('#__tienda_countries AS c ON o.billing_country_id = c.country_id');
		$query->leftJoin('#__tienda_zones AS z ON o.billing_zone_id = z.zone_id');
        $query->where('o.order_id = '. (int) $data['order_id']);
        $db->setQuery($query);
        $orderinfo =  $db->loadObject();
		
		
		
		// GET the enviroment STAGING OR PRODUCTION
        require 'library/wepay.php';

        if($this->params->get('test_environment'))
        {
            $client_id = $this->params->get('stage_client_id');
            $client_secret = $this->params->get('stage_client_secret');
            $access_token = $this->params->get('stage_access_token');
            $account_id = $this->params->get('stage_account_id');

            Wepay::useStaging($client_id, $client_secret);
        }
        else
        {
            $client_id = $this->params->get('client_id');
            $client_secret = $this->params->get('client_secret');
            $access_token = $this->params->get('access_token');
            $account_id = $this->params->get('account_id');

            Wepay::useProduction($client_id, $client_secret);
        }

		// We Generated the Class now tell wePay who we are.
        $wepay = new WePay($access_token);
		
		// prepare CC data
		$wepay_cc_info = array();
		$wepay_cc_info['client_id'] = $client_id;
		$wepay_cc_info['user_name'] = $orderinfo->billing_first_name . ' ' . $orderinfo->billing_last_name;
		$wepay_cc_info['email'] = $orderinfo->user_email;
		$wepay_cc_info['cc_number'] = str_replace(" ", "", str_replace("-", "", $data['cardnum'] ) );
		$wepay_cc_info['cvv'] = $data['cardcvv'];// should setup textbox to accept only three digits
		$wepay_cc_info['expiration_month'] = $data['cardexp_month'];
		$wepay_cc_info['expiration_year'] = $data['cardexp_year'];		
		$address = array(
			'address1'	=> $orderinfo->billing_address_1. " ".$orderinfo->billing_address_2,
			'city'		=> $orderinfo->billing_city,
			'state'		=> $orderinfo->state_code,
			'country'	=> $orderinfo->country_isocode2,
			'zip'		=> $orderinfo->billing_postal_code
		);		
		$wepay_cc_info['address'] = $address;
		
		// credit card to charge
		$wepay_credit_card = $this->_getWePayCCID($wepay_cc_info, $wepay);
		
	    // prepare checkout data	
	    $wepay_checkout_data = array();
		$wepay_checkout_data['account_id'] = $account_id;
		$wepay_checkout_data['order_id']   = $data['order_id'];
		$wepay_checkout_data['orderpayment_id'] = $data['orderpayment_id'];
		$wepay_checkout_data['orderpayment_amount'] = $orderpayment->orderpayment_amount;
		foreach($items as $item)
        {
            $long_description .= 'Items purchased: SKU: ' . $item->orderitem_sku . ' - ' . $item->orderitem_name . '; ';
        }
		$wepay_checkout_data['long_description'] = $long_description;
		$wepay_checkout_data['credit_card_id'] = $wepay_credit_card->credit_card_id; 
        $long_description = JText::_('PLG_TIENDA_PAYMENT_WEPAY_LONG_DESCRIPTION_HEADER');
        
		$wepay_checkout = $this->_WePayCheckout($wepay_checkout_data, $wepay);
		// if here, all went well
		
		$this->processSale($orderpayment->orderpayment_id, $wepay_checkout);
		
        $error = 'processed';
		return $error;
		
    }

	function makeTiendaStatus($wepay_checkout) {
		switch ($wepay_checkout->state) {
			case 'authorized':
				
				break;
			
			default:
				
				break;
		}
	}
	
	function processSale($orderpayment_id, $wepay_checkout) {
		
        	// =======================
        	// verify & create payment
       		// =======================
            // check that payment amount is correct for order_id
            DSCTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
            $orderpayment = DSCTable::getInstance('OrderPayments', 'TiendaTable');
            $orderpayment->load( $orderpayment_id );
            if (empty($orderpayment->order_id))
            {
                // TODO fail
            }
            $orderpayment->transaction_details  = '';
            $orderpayment->transaction_id       = $wepay_checkout->checkout_id;
            $orderpayment->transaction_status   = $wepay_checkout->state;

            Tienda::load( 'TiendaHelperBase', 'helpers._base' );
            $stored_amount = TiendaHelperBase::number( $orderpayment->get('orderpayment_amount'), array( 'thousands'=>'' ) );
            $respond_amount = TiendaHelperBase::number( $amountResponse, array( 'thousands'=>'' ) );
           
		     /* we don't get a huge tranaction response  basically just yes good job. 
		     if ($stored_amount != $respond_amount ) {
                $errors[] = JText::_('COM_TIENDA_TIENDA_WEPAY_MESSAGE_PAYMENT_AMOUNT_INVALID');
                $errors[] = $stored_amount . " != " . $respond_amount;
            }*/
            
            // set the order's new status and update quantities if necessary
            Tienda::load( 'TiendaHelperOrder', 'helpers.order' );
            Tienda::load( 'TiendaHelperCarts', 'helpers.carts' );
            $order = DSCTable::getInstance('Orders', 'TiendaTable');
            $order->load( $orderpayment->order_id );
            if (count($errors)) 
            {
                // if an error occurred 
                $order->order_state_id = $this->params->get('failed_order_state', '10'); // FAILED
            }
                else 
            {
                $order->order_state_id = $this->params->get('payment_received_order_state', '17');; // PAYMENT RECEIVED
                
                // do post payment actions
                $setOrderPaymentReceived = true;
                
                // send email
                $send_email = true;
            }
    
            // save the order
            if (!$order->save())
            {
                $errors[] = $order->getError();
            }
            
            // save the orderpayment
            if (!$orderpayment->save())
            {
                $errors[] = $orderpayment->getError(); 
            }
            
            if (!empty($setOrderPaymentReceived))
            {
                $this->setOrderPaymentReceived( $orderpayment->order_id );
            }
            
            if ($send_email)
            {
                // send notice of new order
                Tienda::load( "TiendaHelperBase", 'helpers._base' );
                $helper = TiendaHelperBase::getInstance('Email');
                $model = Tienda::getClass("TiendaModelOrders", "models.orders");
                $model->setId( $orderpayment->order_id );
                $order = $model->getItem();
                $helper->sendEmailNotices($order, 'new_order');
            }

            if (empty($errors))
            {
                $return = JText::_('COM_TIENDA_TIENDA_WEPAY_MESSAGE_PAYMENT_SUCCESS');
                return $return;                
            }
            
            if (!empty($errors))
            {
                $string = implode("\n", $errors);
                $return = "<div class='note_pink'>" . $string . "</div>";
                return $return;
            }
	}
	
	
	/**
	 * WePay function to get WePay CC ID
	 * 
	 * @param array $wepay_cc_info array of CC and holder's info
	 * @param object $wepay_object
	 * 
	 * @return $credit_card_id
	 */
	function _getWePayCCID($wepay_cc_info, $wepay_object)
	{
		$error = '';
	 	try
        {	
		// create the credit card
		$response = $wepay_object->request('credit_card/create', array(
			'client_id'			=> $wepay_cc_info['client_id'],
			'user_name'			=> $wepay_cc_info['user_name'],
			'email'				=> $wepay_cc_info['email'],
			'cc_number'			=> $wepay_cc_info['cc_number'],
			'cvv'				=> $wepay_cc_info['cvv'],
			'expiration_month'	=> $wepay_cc_info['expiration_month'],
			'expiration_year'	=> $wepay_cc_info['expiration_year'],
			'address'			=> $wepay_cc_info['address']
		));
		}
		 catch (WePayException $e)
        { // if the API call returns an error, get the error message for display later
            $error = $e->getMessage();
			
        }
		
		if(empty($error))
		{
			return $response;
		}
		else
		{
		
			FB::log($error); // ONLY FOR TESTING CHANGE IT TO MORE CONVINIENT
		}
		
		return $response;
	}
    
	/**
	 * WePay function to create WePay checkout
	 * 
	 * @param array $wepay_checkout_data
	 * @param object $wepay object
	 * 
	 * @return JSON $checkout WePay response	 *
	 */
	 function _WePayCheckout($wepay_checkout_data, $wepay)
	 {
	 	$error = '';
	 	try
        {
            $checkout = $wepay->request('/checkout/create', array(
                    'account_id' => $wepay_checkout_data['account_id'], // ID of the account that you want the money to go to
                    'short_description' => JText::_('PLG_TIENDA_PAYMENT_WEPAY_ORDER_ID') . $wepay_checkout_data['order_id'] . '; ' . JText::_('PLG_TIENDA_PAYMENT_WEPAY_ORDERPAYMENT_ID') . $wepay_checkout_data['orderpayment_id'], // a short description of what the payment is for
                    'type' => $this->params->get('checkout_type'), // the type of the payment - choose from GOODS SERVICE DONATION or PERSONAL
                    'amount' => $wepay_checkout_data['orderpayment_amount'], // dollar amount you want to charge the user
                    'long_description' => $wepay_checkout_data['long_description'],
                    'payer_email_message' => $this->params->get('payer_email_message'),
                    'payee_email_message' => $this->params->get('payee_email_message'),
                    'reference_id' => $wepay_checkout_data['orderpayment_id'],
                    'fee_payer' => $this->params->get('fee_payer'),
                    'require_shipping' => 0,
                    'charge_tax' => 0,
                    'funding_sources' => $this->params->get('funding_sources'),
                    'payment_method_id'		=> $wepay_checkout_data['credit_card_id'], // the user's credit_card_id 
					'payment_method_type'	=> 'credit_card'
                )
            );
			
        }
        catch (WePayException $e)
        { // if the API call returns an error, get the error message for display later
            $error = $e->getMessage();
			
        }
		
		if(empty($error))
		{
			return $checkout;
		}
		else
		{
			
			FB::log($error); // ONLY FOR TESTING CHANGE IT TO MORE CONVINIENT
		}
	 }
    
    /**
     * Displays the article with payment info on the order page & email if the order is yet to pay
     *
     * @param TiendaModelOrders $order
     */
    function onBeforeDisplayOrderView($order)
    {
    	
    }
    
	/**
     * Formats the value of the card expiration date
     * 
     * @param string $format
     * @param $value
     * @return string|boolean date string or false
     * @access protected
     */
    function _getFormattedCardExprDate($format, $value)
    {
        // we assume we received a $value in the format MMYY
        $month = substr($value, 0, 2);
        $year = substr($value, 2);
        
        if (strlen($value) != 4 || empty($month) || empty($year) || strlen($year) != 2) {
            return false;
        }
        
        $date = date($format, mktime(0, 0, 0, $month, 1, $year));
        return $date;
    }

	/**
     * Shows the CVV popup
     * @return unknown_type
     */
    public function showCVV($row)
    {
        if (!$this->_isMe($row))
        {
            return null;
        }
        
        $vars = new JObject();
        echo $this->_getLayout('showcvv', $vars);
        return;
    }
}
