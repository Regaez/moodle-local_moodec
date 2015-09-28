<?php 
/**
 * Moodec Cart
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

class MoodecCart {

	/**
	 * The name of the session/cookie for the cart
	 * @var string
	 */
	const STORAGE_ID = 'MoodecCart';

	/**
	 * The string time version of the Moodec Cart.
	 * If the version here is newer than the version stored by the user, we ditch the old cart
	 * @var string 
	 */
	const CART_VERSION = '2015092300';

	/**
	 * Associative array of products, with the product ID as the key, variation ID as value
	 * @var array	
	 */
	protected $_products;

	/**
	 * The cart total, updated when products are added and removed
	 * @var float
	 */
	protected $_cartTotal;

	/**
	 * If they've visited the checkout page, this will contain an existing transaction ID
	 * @var int
	 */
	protected $_transactionId;

	/**
	 * Stores the last time the 
	 * @var [type]
	 */
	protected $_lastUpdated; 

	/**
	 * Constructor will check if any SESSION or COOKIE exists and will load that data if so.
	 * Otherwise, an empty cart is initialised
	 */
	function __construct(){

		// By default, the cart is empty
		$this->_products = array();
		$this->_cartTotal = 0;
		$this->_transactionId = null;

		$sessionTime = 0;
		$sessionProducts = array();
		$sessionCartTotal = 0;
		$sessionVersion = '';
		$sessionData = array();

		$cookieTime = 0;
		$cookieProducts = array();
		$cookieCartTotal = 0;
		$cookieVersion = '';
		$cookieData = array();

		// SESSION is preferred over the COOKIE
		if( isset($_SESSION[self::STORAGE_ID]) ) {

			list($sessionVersion, $sessionData) = unserialize($_SESSION[self::STORAGE_ID]);

			// we check if the version is current, before setting extracting the data
			// this way if it's old, we won't throw an error for a missing field
			if( $sessionVersion === self::CART_VERSION ) {
				list($sessionProducts, $sessionCartTotal, $sessionTransactionId, $sessionTime) = $sessionData;
			} else {
				// if the session is not the current cart version, we destroy it
				unset($_SESSION[self::STORAGE_ID]);
			}
		}

		// Now we do the same for the cookie
		if( isset($_COOKIE[self::STORAGE_ID]) ) {

			list($cookieVersion, $cookieData) = unserialize($_COOKIE[self::STORAGE_ID]);

			if( $sessionVersion === self::CART_VERSION ) {
				// Get cart from the cookies
				list($cookieProducts, $cookieCartTotal, $cookieTransactionId, $cookieTime) = $cookieData;
			} else {
				// if the cookie is not the current cart version, we destroy it
				unset($_COOKIE[self::STORAGE_ID]);
			}
		}

		// Check which is newer; the session, or the cookie
		if( $cookieTime < $sessionTime ) {

			// If session is newer, then we set the cart properties from the session
			$this->_products = $sessionProducts;
			$this->_cartTotal = $sessionCartTotal;
			$this->_transactionId = $sessionTransactionId;
			$this->_lastUpdated = $sessionTime;

		} else if( !!$cookieTime ) {

			// otherwise, we set the cart to the cookie vars
			$this->_products = $cookieProducts;
			$this->_cartTotal = $cookieCartTotal;
			$this->_transactionId = $cookieTransactionId;
			$this->_lastUpdated = $cookieTime;

		}

		// We run through all the products in the cart and load the info
		foreach( $this->_products as $pID => $vID ){
			$newProduct = local_moodec_get_product($pID);

			// If any product is variable, but the variation ID is stored as 0
			// That means the product WAS simple, but has been changed.
			// Therefore we clear the cart, as this could cause errors.
			if( $newProduct->get_type() === PRODUCT_TYPE_VARIABLE && $vID === 0 ){
				$this->clear();
				break;
			}
		}

		// When we create the cart, check if there is a transaction associated with it,
		// and if there is, check if it is complete.
		// If the transaction is complete, then this cart has been purchased,
		// so we need to clear it. 		
		if( !!$this->get_transaction_id() ) {
			
			try {
				$transaction = new MoodecTransaction($this->get_transaction_id());

				if( $transaction->get_status() === MoodecTransaction::STATUS_COMPLETE ) {
					$this->clear();
				}
			} catch (Exception $e) {
				// If there is an exception, then we want to clear the transaction
				// so we will be given a new one
				$this->_transactionId = null;
				$this->clear();
			}
		}
	}

	/**
	 * Update the SESSION and COOKIE with the current cart
	 * @return void
	 */
	private function update(){

		$this->_lastUpdated = time();

		// We store the cart data in a separate array
		// This way we can check the version before trying to extract the info
		$cartData = array(
			$this->_products,
			$this->_cartTotal,
			$this->_transactionId,
			$this->_lastUpdated
		);

		// serialize the data for storage
		$data = serialize(array(self::CART_VERSION, $cartData));


		// Set the PHP session
		$_SESSION[self::STORAGE_ID] = $data;

		// Set the COOKIE, will last 1 year
		setcookie(self::STORAGE_ID, $data, time() + 31536000, '/');
	}

	/**
	 * Reloads the products from the DB to get up-to-date info
	 * and removes any products that are now invalid
	 * THIS IS ESPECIALLY NECESSARY FOR PEOPLE WITH OLD CARTS
	 * @return array 	list of product ids that have been removed
	 */
	public function refresh(){
		global $USER;

		// We'll use this to store a list of products no longer enabled
		$itemsToRemove = array();

		// We run through all the products in the cart and reload the info
		foreach( $this->_products as $pID => $vID ){
			$newProduct = local_moodec_get_product($pID);

			// Check if the product is still valid
			// If not, flag it to be removed
			if( $newProduct->is_enabled() === false ){
				$itemsToRemove[] = $pID;
			}
						
			// If the variation is not 0 for a simple product, make it so (Number One)
			if( $newProduct->get_type() === PRODUCT_TYPE_SIMPLE && $vID !== 0) {
				$this->_products[$pID] = 0;
			}

			// If the product is variable and the variation is disabled, remove
			if( $newProduct->get_type() === PRODUCT_TYPE_VARIABLE) {
				if( !$newProduct->get_variation($vID) ) {
					$itemsToRemove[] = $pID;
				}
			}

			// If the user is logged in, check if they're already enrolled in this course
			if(isloggedin() ) {
				$context = context_course::instance($newProduct->get_course_id());
				$isEnrolled = is_enrolled($context, $USER, '', true);

				if ($isEnrolled) {
					$itemsToRemove[] = $pID;
				}
			}
		}

		// Now we've reloaded all the products, remove the ones which are no longer available
		foreach ($itemsToRemove as $id) {
			$this->remove($id);
		}

		return $itemsToRemove;
	}

	/**
	 * Removes all items from the cart, session and cookie
	 * @return void
	 */
	public function clear(){

		// Reset the cart to it's default state
		$this->_products = array();
		$this->_cartTotal = 0;
		$this->_transactionId = null;
		$this->_lastUpdated = time();

		// update the cart storage
		$this->update();
	}

	/**
	 * Looks in the cart and returns either the product or a bool
	 * @param   int 	$id 			the product id we want to check
	 * @return  bool 					product exists
	 */
	public function check($id){
		$id = (int) $id;
		
		// Reset pointer to beginning of array	
		reset($this->_products);
		
		// Return bool value whether the product id is in the cart
		return array_key_exists($id, $this->_products);
	}

	/**
	 * Adds the product to the cart, updates total
	 * @param int  				$p 		product id
	 * @param int 				$v 		product variation_id
	 */
	public function add($p, $v = 0){
		$p = (int) $p;
		$v = (int) $v;


		// Check if the product is already in the cart
		if( $this->check($p) ) {
			// If so, we don't re-add
			return false;
		}

		$productToAdd = local_moodec_get_product($p);

		// Products are stored in an array under the 'product' key, 
		// alongside the variation ID. Variation id is 0 for simple products. 
		$this->_products[$p] = $v;

		// Update the cart total, using the variation price, or simple price
		// depending on what has been added
		if( $productToAdd->get_type() === PRODUCT_TYPE_VARIABLE) {
			$this->_cartTotal += $productToAdd->get_variation($v)->get_price();
		} else {
			$this->_cartTotal += $productToAdd->get_price();
		}

		// update the cart storage
		$this->update();

		return true;
	}

	/**
	 * Remove a product from the cart, if it exists
	 * @param  int 		$id 	product_id
	 * @return bool     		success or fail
	 */
	public function remove($id) {
		$id = (int) $id;

		// First, we need to check if the product is ACTUALLY in the cart
		if( $this->check($id) ){

			$productToRemove = local_moodec_get_product($id);

			// Get the variation id for this product
			$v = $this->_products[$id];

			// Now we deduct the price from the cart total
			if( $productToRemove->get_type() === PRODUCT_TYPE_VARIABLE ) {
				$this->_cartTotal -= $productToRemove->get_variation($v)->get_price();
			} else {
				$this->_cartTotal -= $productToRemove->get_price();
			}

			// And unset the array value
			unset($this->_products[$id]);

			if( 0 === count($this->_products) ) {
				$this->_transactionId = null;
			}

			// update the cart storage
			$this->update();

			return true;
		}

		return false;
	}
	
	/**
	 * Returns the products stored in the cart
	 * @return array 	Moodec
	 */
	public function get(){
		return $this->_products;
	}

	/**
	 * Returns the cart total, raw or formatted
	 * @param  boolean 			$format 	true to format the total
	 * @return float|string          		float if raw, string if format true
	 */
	public function get_total($format = true){

		if( !!$format ) {
			return number_format($this->_cartTotal, 2, '.', ',');
		}

		return $this->_cartTotal;
	} 

	/**
	 * Returns the number of products stored in the cart
	 * @return int 		size
	 */
	public function get_size(){
		return count($this->_products);
	}

	/**
	 * Returns whether the cart is empty or not
	 * @return boolean
	 */
	public function is_empty(){
		return 0 === count($this->_products);
	}

	/**
	 * Will return the transactionId if set, otherwise false
	 * @return int|bool 
	 */	
	public function get_transaction_id(){
		if( !is_null($this->_transactionId) ) {
			return $this->_transactionId;
		} 

		return false;
	}

	/**
	 * Set the transaction id for the cart
	 * @param int $id 
	 */
	public function set_transaction_id($id) {
		$id = (int) $id;

		$this->_transactionId = $id;

		// update the cart storage
		$this->update();
	}
}