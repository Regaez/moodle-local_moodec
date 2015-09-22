<?php 
/**
 * Moodec Transaction Item
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

class MoodecTransactionItem {

	/**
	 * The id of this TransactionItem
	 * @var int
	 */
	protected $_id;

	/**
	 * The transaction this item belongs to
	 * @var int
	 */
	protected $_transactionId;

	/**
	 * The product id the item relates to
	 * @var int
	 */
	protected $_productId;

	/**
	 * The variation id of the product bought
	 * @var int
	 */
	protected $_variationId;

	/**
	 * The item's price (at the time it was bought)
	 * @var float
	 */
	protected $_itemCost;


	/**
	 * Constructor. If passed an id, will load from the DB. Otherwise will generate empty instance of class.
	 * @param int $id item_id
	 */
	function __construct($id = null){
		
		if( is_int($id) ) {
			// Load this item from the DB
			$this->load($id);
		}

	}

	/**
	 * Loads the transaction item from the DB
	 * @param  int 		$id 	item_id
	 * @return void     
	 */
	private function load($id) {
		global $DB;

		$id = (int) $id;

		$record = $DB->get_record('local_moodec_trans_item', array('id' => $id));

		if( !!$record ) {

			$this->_id = $id;
			$this->_transactionId 	= (int) $record->transaction_id;
			$this->_productId 		= (int) $record->product_id;
			$this->_itemCost 		= (float) $record->item_cost;
			$this->_variationId 	= (int) $record->variation_id;

		} else {
			throw new Exception('Unable to load transaction item using identifier: ' . $id);
		}
	}

	/**
	 * Creates the item in the database
	 * @param  int  	$transactionId 	transaction id
	 * @param  int  	$product       	product_id
	 * @param  float  	$cost          	the cost for this item
	 * @param  int 		$variation     	variation_id
	 * @return int                 		item id
	 */
	public function create($transactionId, $product, $cost, $variation = 0){
		global $DB;

		// Ensure parameters are type cast
		$this->_transactionId 	= (int) $transactionId;
		$this->_productId 		= (int) $product;
		$this->_itemCost 		= (float) $cost;
		$this->_variationId 	= (int) $variation;

		// Create the data object to map to the DB table
		$newRecord = new stdClass();
		$newRecord->transaction_id 	= $this->_transactionId;
		$newRecord->product_id 		= $this->_productId;
		$newRecord->variation_id 	= $this->_variationId;
		$newRecord->item_cost 		= $this->_itemCost;

		// Insert into DB, store the returnedID in class
		$this->_id = $DB->insert_record('local_moodec_trans_item', $newRecord, true);

		return $this->_id;
	}

	/**
	 * Returns this MoodecTransactionItem is
	 * @return int id
	 */
	public function get_id(){
		return $this->_id;
	}

	/**
	 * Returns the MoodecTransaction id
	 * @return int transactionId
	 */
	public function get_transaction_id(){
		return $this->_transactionId;
	}

	/**
	 * Returns the product id for this item
	 * @return int productId
	 */
	public function get_product_id(){
		return $this->_productId;
	}

	/**
	 * Returns the variation id for this item
	 * @return int variationId
	 */
	public function get_variation_id(){
		return $this->_variationId;
	}

	/**
	 * Returns the cost of this item
	 * @return float 	itemCost
	 */
	public function get_cost() {
		return (float) $this->_itemCost;
	}
}