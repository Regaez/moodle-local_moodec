<?php 
/**
 * Moodec Gateway
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

abstract class MoodecGateway {

	/**
	 * The MoodecTransaction to be handled by this gateway 
	 * @var MoodecTransaction
	 */
	protected $_transaction;

	/**
	 * The name of this gateway
	 * @var string
	 */
	protected $_gatewayName;

	/**
	 * The URL to send the info to
	 * @var string
	 */
	protected $_gatewayURL;

	/**
	 * The Moodle enrolment plugin
	 * @var moodle_enrol_plugin
	 */
	protected $_enrolPlugin;

	/**
	 * Creates a gateway class
	 * @param MoodecTransaction|int 	$transaction 	A transaction class or transaction ID
	 */
	function __construct($transaction) {
		global $CFG;
		require_once $CFG->libdir . '/enrollib.php';

		// Set the transaction to be handled
		if( $transaction instanceof MoodecTransaction ) {
			// We have been passed an existing instance, so use it
			$this->_transaction = $transaction;
		} else {
			// We have been passed the ID, so make a new instance of transaction
			$this->_transaction = new MoodecTransaction($transaction);
		}

		// Get the enrolment plugin
		$this->_enrolPlugin = enrol_get_plugin('moodec');

		// If the moodec enrolment plugin is not found (although that shouldn't happen, as it is a dependency of this plugin) then fall back to manual
		if( is_null($this->_enrolPlugin) ) {
			$this->_enrolPlugin = enrol_get_plugin('manual');
		}

		// Set gateway properties to default strings;
		$this->_gatewayName = '';
		$this->_gatewayURL = '';
	}

	/**
	 * Checks the user and transaction items' course and ensures they exist
	 * @return bool 
	 */
	protected function verify_transaction(){
		global $DB;

		// Ensure that the user associated to the transaction actually exists
		if (!$user = $DB->get_record('user', array('id' => $this->_transaction->get_user_id())) ) {
			// Check that user exists
			$this->send_error_to_admin("User ". $this->_transaction->get_user_id() ." doesn't exist");
			
			$transaction->fail();

			return false;
		}

		// Check that each course associated with the items in the transaction ACTUALLY exist
		// (it should be VERY unlikely for this to happen, but we check nonetheless!)
		foreach ($this->_transaction->get_items() as $item) {
			
			$product = local_moodec_get_product($item->get_product_id());

			if (!$course = $DB->get_record('course', array('id' => $product->get_course_id()))) {
				// Check that course exists
				$this->send_error_to_admin("Course ". $product->get_course_id() ." doesn't exist");
				
				$this->_transaction->fail();

				return false;
			}
		}

		return true;
	}

	/**
	 * Goes through all the items in the transaction and enrols the user, 
	 * given the product's duration. Also adds them into a group, if necessary.
	 * @return void
	 */
	protected function complete_enrolment(){
		global $CFG, $DB;

		require_once $CFG->libdir . '/enrollib.php';
		require_once $CFG->dirroot . '/group/lib.php';

		// ENROL USER INTO EACH OF THE TRANSACTION ITEMS
		foreach ($this->_transaction->get_items() as $item) {

			$product = local_moodec_get_product($item->get_product_id());

			// We set the start time to be 1 min earlier (this is so the course will immediately show up in the course overview block - otherwise you need to wait til the current minute ticks over)
			$timestart = time() - 60;
			$timeend = 0;
			$instance = $DB->get_record('enrol', array('courseid' => $product->get_course_id(), 'enrol' => 'moodec'));

			if( !$instance ) {
				// Notify admin that the enrolment method is not active on the course
				$this->send_error_to_admin("Moodec enrolment method not active on course ". $product->get_course_id() . ". Transaction #" . $this->_transaction->get_id() . " defaulted to manual enrolment method");

				// set the enrol plugin to use manual
				$this->_enrolPlugin = enrol_get_plugin('manual');
				// and get the manual enrolment method instance for the course instead
				$instance = $DB->get_record('enrol', array('courseid' => $product->get_course_id(), 'enrol' => 'manual'));				
			}

			// Check if the product is simple, or variable
			// And retrieve the enrolment duration for this product
			if( $product->get_type() === PRODUCT_TYPE_SIMPLE ) {
				$enrolmentDuration = $product->get_duration(false);
			} else {
				$enrolmentDuration = $product->get_variation($item->get_variation_id())->get_duration(false);
			}
			
			// If the course is not unlimited, set the duration to be the current time, plus the number of days, converted to seconds. (from product settings)
			if( $enrolmentDuration !== 0 ) {
				$timeend = $timestart + ( $enrolmentDuration * 86400 );	
			}

			// This will enrol the user! yay!
			// We set the user enrolment to be 'active', because any users that were previously 
			// enrolled will be marked as 'suspended' automatically when their enrolment expires
			$this->_enrolPlugin->enrol_user($instance, $this->_transaction->get_user_id(), $instance->roleid, $timestart, $timeend, ENROL_USER_ACTIVE);


			// if there is a group set (ie NOT 0), then add them to it
			if( $product->get_type() === PRODUCT_TYPE_SIMPLE ) {
				if ( !!$product->get_group() ) { 
					groups_add_member($product->get_group(), $this->_transaction->get_user_id() );
				}
			} else {
				if ( !!$product->get_variation($item->get_variation_id())->get_group() ) { 
					groups_add_member($product->get_variation($item->get_variation_id())->get_group(), $this->_transaction->get_user_id() );
				}
			}	

		}

		// Mark the transaction as complete! :D
		$this->_transaction->complete();
	}

	/**
	 * Sends an email notification with the details of the transaction error
	 * @param  string $subject email subject line
	 * @param  array  $data    transaction data
	 * @return void          
	 */
	protected function send_error_to_admin($subject, $data = array()) {
		global $CFG;
		require_once $CFG->libdir . '/eventslib.php';

		$admin = get_admin();
		$site = get_site();

		$message = sprintf(
			"%s: Transaction #%d failed. %s \n\n",
			$site->fullname,
			$this->_transaction->get_id(),
			$subject
		);

		foreach ($data as $key => $value) {
			$message .= "$key => $value\n";
		}

		// Update the transaction with the error message details
		$this->_transaction->set_error($message);

		$eventdata = new stdClass();
		$eventdata->component = 'local_moodec';
		$eventdata->name = 'payment_gateway';
		$eventdata->userfrom = $admin;
		$eventdata->userto = $admin;
		$eventdata->subject = $this->_gatewayName . " ERROR: " . $subject;
		$eventdata->fullmessage = $message;
		$eventdata->fullmessageformat = FORMAT_PLAIN;
		$eventdata->fullmessagehtml = '';
		$eventdata->smallmessage = '';
		message_send($eventdata);
	}

	/**
	 * Returns the gateway URL
	 * @return string 
	 */
	public function get_url(){
		return $this->_gatewayURL;
	}

	/**
	 * The function to handle the transaction 
	 * @return void
	 */
	abstract public function handle($data = null);

	/**
	 * Function to output the gateway 'button' on the checkout page
	 * @return string 	HTML
	 */
	abstract public function render();

}