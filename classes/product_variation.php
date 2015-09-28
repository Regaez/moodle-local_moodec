<?php 
/**
 * Moodec Product Variation
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

class MoodecProductVariation {

	/**
	 * The variation id
	 * @var int
	 */
	protected $_id; 

	/**
	 * Determines whether this variation is enabled
	 * @var bool
	 */
	protected $_enabled;

	/**
	 * The variation name
	 * @var string
	 */
	protected $_name;

	/**
	 * The product price
	 * @var float
	 */
	protected $_price;

	/**
	 * The number of days to be enrolled for
	 * @var int
	 */
	protected $_duration;

	/**
	 * The course group id, 0 = no group
	 * @var int
	 */
	protected $_group;

	/**
	 * Builds the object
	 * @param int  		$id        		EITHER the product id OR the variation id
	 * @param boolean 	$variation 		FALSE if the product id was passed to constructor
	 */
	function __construct($id, $variation = false) {

		if(!is_null($id) && is_int($id)) {
			$this->load($id, $variation);
		}
	}

	/**
	 * Loads the product variation from the DB
	 * @param  int 		$id				MoodecProduct id
	 * @param  bool 	$variation		FALSE if product id was passed as param
	 */
	public function load($id, $variation) {
		global $DB;

		$query = sprintf(
			'SELECT 
				id,
				price,
				is_enabled,
				name,
				duration,
				group_id
			FROM {local_moodec_variation}
			WHERE %s = %d
			LIMIT 1',
			!!$variation ? 'id' : 'product_id', // IF NOT VARIATION, MATCH BASED ON PRODUCT ID
			$id
		);

		// run the query
		$product = $DB->get_record_sql($query);

		// return the products
		if (!!$product) {
			$this->_id = (int) $product->id;
			$this->_name = $product->name;
			$this->_enabled = (bool) $product->is_enabled;
			$this->_price = (float) $product->price;
			$this->_duration = (int) $product->duration;
			$this->_group = (int) $product->group_id;
		} else {
        	throw new Exception('Unable to load product variation information using identifier: ' . $id);
   		}
	}

	/**
	 * Return the enrolment duration
	 * @param  boolean $format 		If true, then format the duration as a string
	 * @return string/int       	Duration as a string, or int
	 */
	public function get_duration($format = true){
		if($format) {
			$output = '';
			$duration = $this->_duration;

			if ($duration < 1) {
				return get_string('enrolment_duration_unlimited', 'local_moodec');
			}

			if (364 < $duration && $duration % 365 === 0 ) {
				$years = floor($duration / 365);
				return $years == 1 ? sprintf(' %d %s ', $years, get_string('enrolment_duration_year', 'local_moodec')) : sprintf(' %d %s ', $years, get_string('enrolment_duration_year_plural', 'local_moodec'));
			}

			if (29 < $duration && $duration % 30 === 0) {
				$months = floor($duration / 30);
				return $months == 1 ? sprintf(' %d %s ', $months, get_string('enrolment_duration_month', 'local_moodec')) : sprintf(' %d %s ', $months, get_string('enrolment_duration_month_plural', 'local_moodec'));
			}

			if (6 < $duration && $duration % 7 === 0) {
				$weeks = floor($duration / 7);
				return $weeks == 1 ? sprintf(' %d %s ', $weeks, get_string('enrolment_duration_week', 'local_moodec')) : sprintf(' %d %s ', $weeks, get_string('enrolment_duration_week_plural', 'local_moodec'));
			}

			return $duration == 1 ? sprintf(' %d %s ', $duration, get_string('enrolment_duration_day', 'local_moodec')) : sprintf(' %d %s ', $duration, get_string('enrolment_duration_day_plural', 'local_moodec'));
		} else {
			return $this->_duration;
		}
	}

	/**
	 * Retrieves the variation id
	 * @return int  product variation id
	 */
	public function get_id(){
		return $this->_id;
	}

	/**
	 * Returns whether the variation is enabled or not
	 * @return boolean enabled
	 */	
	public function is_enabled(){
		return !!$this->_enabled;
	}

	/**
	 * Retrieves the product price
	 * @return float price
	 */
	public function get_price(){
		return $this->_price;
	}

	/**
	 * Retrieves the variation name
	 * @return string name
	 */
	public function get_name(){
		return $this->_name;
	}

	/**
	 * Retrieves the course group id 
	 * @return int 		groupid
	 */
	public function get_group(){
		return $this->_group;
	}
}