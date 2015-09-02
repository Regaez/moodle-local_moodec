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

		$sessionTime = 0;
		$sessionProducts = array();
		$sessionCartTotal = 0;
		$cookieTime = 0;
		$cookieProducts = array();
		$sessionCartTotal = 0;

		// SESSION is preferred over the COOKIE
		if( isset($_SESSION[self::STORAGE_ID]) ) {

			// Get cart from PHP session
			list($sessionProducts, $sessionCartTotal, $sessionTime) = unserialize($_SESSION[self::STORAGE_ID]);

		} 

		if( isset($_COOKIE[self::STORAGE_ID]) ){

			// Get cart from the cookies
			list($cookieProducts, $cookieCartTotal, $cookieTime) = unserialize($_COOKIE[self::STORAGE_ID]);

		}

		// Check which is newer; the session, or the cookie
		if( $cookieTime < $sessionTime ) {

			$this->_products = $sessionProducts;
			$this->_cartTotal = $sessionCartTotal;
			$this->_lastUpdated = $sessionTime;

		} else if( $cookieTime !== 0 ) {

			$this->_products = $cookieProducts;
			$this->_cartTotal = $cookieCartTotal;
			$this->_lastUpdated = $cookieTime;

		}

		// Reload the cart products from the DB.
		// --- 
		// Perhaps only call this at the checkout, when it matters,
		// rather than every time the cart is instantiated...?
		// ---
		//$this->refresh();
	}

	/**
	 * Update the SESSION and COOKIE with the current cart
	 * @return void
	 */
	private function update(){

		$this->_lastUpdated = time();

		// serialize the data for storage
		$data = serialize(array($this->_products, $this->_cartTotal, $this->_lastUpdated));

		// Set the PHP session
		$_SESSION[self::STORAGE_ID] = $data;

		// Set the COOKIE, will last 1 year
		setcookie(self::STORAGE_ID, $data, time() + 31536000, '/');
	}

	/**
	 * Reloads the products from the DB to get up-to-date info
	 * and removes any products that are now invalid
	 * THIS IS ESPECIALLY NECESSARY FOR PEOPLE WITH OLD CARTS
	 * @return void
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
	}

	/**
	 * Removes all items from the cart, session and cookie
	 * @return void
	 */
	public function clear(){

		// Reset the cart to it's default state
		$this->_products = array();
		$this->_cartTotal = 0;

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
}