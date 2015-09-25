<?php 
/**
 * Moodec Gateway DPS
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Load Moodle config
require_once dirname(__FILE__) . '/../../../config.php';
// Load Moodec lib
require_once dirname(__FILE__) . '/../lib.php';

class MoodecGatewayDPS extends MoodecGateway {

	protected $_internalGatewayURL = '';

	function __construct($transaction) {
		global $CFG;

		parent::__construct($transaction);

		$this->_gatewayName = get_string('payment_dps_title', 'local_moodec');

		$this->_internalGatewayURL = new moodle_url($CFG->wwwroot . '/local/moodec/payment/dps/index.php');

		// Checks if sandbox mode is enabled
		if( !!get_config('local_moodec', 'payment_dps_sandbox') ) {
			$this->_gatewayURL = 'https://uat.paymentexpress.com/pxaccess/pxpay.aspx'; // the DPS sandbox URL
		} else {
			$this->_gatewayURL = 'https://sec.paymentexpress.com/pxaccess/pxpay.aspx';
		}
	}

	/**
	 * Turn an XML string into a DOM object.
	 *
	 * @param string $xml An XML string
	 * @return object The SimpleXMLElement object representing the root element.
	 */
	public function get_dom($xml) {
	    $dom = new DomDocument();
	    $dom->preserveWhiteSpace = false;
	    $dom->loadXML($xml);
	    return simplexml_import_dom($dom);
	}

	public function query($data){
		global $CFG;

		require_once $CFG->libdir . '/filelib.php';

		$c = new curl();
		$options = array(
			'returntransfer' => true
		);

		$result = $c->post($this->_gatewayURL, $data, $options);

		return $result;
	}

	public function begin(){
		global $CFG, $USER;

		$txnId = time() . $this->_transaction->get_id();
		$site = get_site();

        // create the "Generate Request" XML message
        $xmlrequest = sprintf(
        	"<GenerateRequest>
            	<PxPayUserId>%s</PxPayUserId>
            	<PxPayKey>%s</PxPayKey>
            	<AmountInput>%.2f</AmountInput>
            	<CurrencyInput>%s</CurrencyInput>
            	<MerchantReference>%s</MerchantReference>
            	<EmailAddress>%s</EmailAddress>
            	<TxnData1>%d</TxnData1>
            	<TxnData2>%s</TxnData2>
            	<TxnData3>%s</TxnData3>
            	<TxnType>Purchase</TxnType>
            	<TxnId>%d</TxnId>
            	<BillingId></BillingId>
            	<EnableAddBillCard>0</EnableAddBillCard>
            	<UrlSuccess>%s</UrlSuccess>
            	<UrlFail>%s</UrlFail>
            	<Opt></Opt>
            </GenerateRequest>",
            clean_param(get_config('local_moodec', 'payment_dps_userid'), PARAM_CLEAN), // PxPay User ID
            clean_param(get_config('local_moodec', 'payment_dps_key'), PARAM_CLEAN),	// PxPay Key
            clean_param($this->_transaction->get_cost(), PARAM_CLEAN), // Amount
            clean_param(get_config('local_moodec', 'currency'), PARAM_CLEAN),	// Currency
            clean_param('Transaction #' . $this->_transaction->get_id(), PARAM_CLEAN), // Merchant reference
            clean_param($USER->email, PARAM_CLEAN), // Email
            clean_param($txnId, PARAM_CLEAN),
            clean_param(substr($site->shortname, 0, 50), PARAM_CLEAN),
            clean_param(substr("{$USER->lastname}, {$USER->firstname}", 0, 50), PARAM_CLEAN),
            clean_param(time().$this->_transaction->get_id(), PARAM_CLEAN), // TxnId
            new moodle_url($CFG->wwwroot . '/local/moodec/payment/dps/success.php'), // URL Success
            new moodle_url($CFG->wwwroot . '/local/moodec/payment/dps/fail.php') 	// URL Fail
        );

		// Query DPS with the xml request
        $result = $this->query($xmlrequest);

        // Set the transaction gateway and status
        $this->_transaction->set_gateway(MOODEC_GATEWAY_DPS);
        $this->_transaction->set_txn_id($txnId);
        $this->_transaction->pending();

        // Return the DOM formatted result of the request
        return $this->get_dom($result);
	}

	public function abort($data){

		// Check transaction response and confirm
		$xmlrequest = sprintf(
			"<ProcessResponse>
		    	<PxPayUserId>%s</PxPayUserId>
		    	<PxPayKey>%s</PxPayKey>
		    	<Response>%s</Response>
		    </ProcessResponse>",
		    clean_param(get_config('local_moodec', 'payment_dps_userid'), PARAM_CLEAN), // PxPay User ID
            clean_param(get_config('local_moodec', 'payment_dps_key'), PARAM_CLEAN),	// PxPay Key
		    $data // DPS result data
		);
		$xmlreply = $this->query($xmlrequest);
		$response = $this->get_dom($xmlreply);

		$this->send_error_to_admin("DPS transaction failed!", $response);

		$this->_transaction->fail();
	}

	// handle the response	
	public function handle($data = null){

		// Abort if the transaction is already complete
		if( $this->_transaction->get_status() === MoodecTransaction::STATUS_COMPLETE ) {
			return true;
		}

		// Check to see if the data is null
		if( is_null($data) ) {
			$this->_transaction->fail();
			return false;
		}

		// Check transaction response and confirm
		$xmlrequest = sprintf(
			"<ProcessResponse>
		    	<PxPayUserId>%s</PxPayUserId>
		    	<PxPayKey>%s</PxPayKey>
		    	<Response>%s</Response>
		    </ProcessResponse>",
		    clean_param(get_config('local_moodec', 'payment_dps_userid'), PARAM_CLEAN), // PxPay User ID
            clean_param(get_config('local_moodec', 'payment_dps_key'), PARAM_CLEAN),	// PxPay Key
		    $data // DPS result data
		);
		$xmlreply = $this->query($xmlrequest);
		$response = $this->get_dom($xmlreply);

		// abort if invalid
		if ($response === false or $response->attributes()->valid != '1') {
		    $this->send_error_to_admin("DPS transaction was not valid!", $response);
		    $this->_transaction->fail();
		    return false;
		}

		// Check that the transaction id matches the response one
		if ($this->_transaction->get_txn_id() != $response->TxnId) {
		    $this->send_error_to_admin("Transaction IDs do not match! This ID: " . $this->_transaction->get_id() . ", Response TxnId: " . $response->TxnId, $response);
		    $this->_transaction->fail();
		    return false;
		}

		// Confirm currency is correctly set and matches the plugin config
		if ($response->CurrencySettlement != get_config('local_moodec', 'currency')) {
			$this->send_error_to_admin("Currency does not match course settings, received: " . $response->CurrencySettlement, $response);
			$this->_transaction->fail();
			return false;
		}

		// Check if the payment was less than the transaction cost
		if( $response->AmountSettlement < $this->_transaction->get_cost() ) {
			$this->send_error_to_admin("Amount paid is not enough (".$response->AmountSettlement." < ".$this->_transaction->get_cost().")", $response);
			$this->_transaction->fail();
			return false;
		}

		// enrol and continue if DPS returns "APPROVED"
		if ($response->Success == 1 and $response->ResponseText == "APPROVED") {
			// Lastly, verify the general transaction items and user
			if( $this->verify_transaction() ) {
				
				$this->complete_enrolment();

				return true;
			}

		}

		$this->send_error_to_admin("Something uncaught prevented the transaction from completing!", $response);
		return false;
	}

	public function render(){

		// output form
		$html = sprintf('<form action="%s" method="POST" class="payment-gateway gateway--dps">', $this->_internalGatewayURL);

			$html .= sprintf(
				'<input type="hidden" name="id" value="%s">',
				$this->_transaction->get_id()
			); 

		 	$html .= sprintf(
		 		'<input type="submit" name="submit"  value="%s">',
		 		get_string('button_dps_label', 'local_moodec')
		 	);

		$html .= sprintf('</form>');

		return $html;
	}
}