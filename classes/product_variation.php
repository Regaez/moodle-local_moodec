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
	protected $_enrolmentDuration;

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
				name,
				enrolment_duration,
				groupid
			FROM {local_moodec_product_variation}
			WHERE %s = %d',
			!!$variation ? 'id' : 'product_id', // IF NOT VARIATION, MATCH BASED ON PRODUCT ID
			$id
		);

		// run the query
		$product = $DB->get_record_sql($query);

		// return the products
		if (!!$product) {
			$this->_id = (int) $product->id;
			$this->_name = $product->name;
			$this->_price = (float) $product->price;
			$this->_enrolmentDuration = (int) $product->enrolment_duration;
			$this->_group = (int) $product->groupid;
		} else {
        	throw new Exception('Unable to load product variation information using identifier: ' . $id);
   		}
	}

	/**
	 * Return the enrolment duration
	 * @param  boolean $format 		If true, then format the duration as a string
	 * @return string/int       	Duration as a string, or int
	 */
	public function get_enrolment_duration($format = true){
		if($format) {
			$output = '';
			$duration = $this->_enrolmentDuration;

			if ($duration < 1) {
				return get_string('enrolment_duration_unlimited', 'local_moodec');
			}

			if (364 < $duration) {
				$years = floor($duration / 365);
				$duration = $duration % 365;
				$output .= $years == 1 ? sprintf(' %d %s ', $years, get_string('enrolment_duration_year', 'local_moodec')) : sprintf(' %d %s ', $years, get_string('enrolment_duration_year_plural', 'local_moodec'));
			}

			if (29 < $duration) {
				$months = floor($duration / 30);
				$duration = $duration % 30;
				$output .= $months == 1 ? sprintf(' %d %s ', $months, get_string('enrolment_duration_month', 'local_moodec')) : sprintf(' %d %s ', $months, get_string('enrolment_duration_month_plural', 'local_moodec'));
			}

			if (6 < $duration) {
				$weeks = floor($duration / 7);
				$duration = $duration % 7;
				$output .= $weeks == 1 ? sprintf(' %d %s ', $weeks, get_string('enrolment_duration_week', 'local_moodec')) : sprintf(' %d %s ', $weeks, get_string('enrolment_duration_week_plural', 'local_moodec'));
			}

			if (0 < $duration) {
				$output .= $duration == 1 ? sprintf(' %d %s ', $duration, get_string('enrolment_duration_day', 'local_moodec')) : sprintf(' %d %s ', $duration, get_string('enrolment_duration_day_plural', 'local_moodec'));
			}

			return $output;
		} else {
			return $this->_enrolmentDuration;
		}
	}

	/**
	 * Retrieves the variation id
	 * @return int  product variation id
	 */
	public function get_variation_id(){
		return $this->_id;
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