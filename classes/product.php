<?php 
/**
 * Moodec Product
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

class MoodecProduct {

	/**
	 * The Moodec product id
	 * @var int
	 */
	protected $_id;

	/**
	 * The Moodle course id
	 * @var int
	 */
	protected $_courseid;

	/**
	 * Whether the product is enabled
	 * @var bool
	 */
	protected $_enabled;

	/**
	 * The product type
	 * @var string
	 */
	protected $_type;

	/**
	 * The course fullname
	 * @var string
	 */
	protected $_fullname;

	/**
	 * The course shortname
	 * @var string
	 */	
	protected $_shortname;

	/**
	 * The Moodle category id the course belongs to
	 * @var int
	 */
	protected $_categoryid;

	/**
	 * The moodle course summary
	 * @var string
	 */
	protected $_summary;

	/**
	 * The moodle course summary format
	 * @var int
	 */
	protected $_summaryFormat;

	/**
	 * Additional product information
	 * @var [type]
	 */
	protected $_description;

	/**
	 * List of tags 
	 * @var array
	 */
	protected $_tags;

	function __construct($id = null){

		if(!is_null($id) && is_int($id)) {
			$this->load($id);
		}

	}

	/**
	 * Loads the product from the DB
	 * @param  int 		$id		MoodecProduct id
	 * @return [type]     		[description]
	 */
	public function load($id){
		global $DB;

		$query = sprintf(
			'SELECT 
				lmp.id, 
				c.id as course_id,
				fullname,
				shortname,
				is_enabled,
				category,
				summary,
				c.summaryformat as summary_format,
				type,
				description,
				tags,
				timecreated
			FROM {local_moodec_product} lmp, {course} c
			WHERE lmp.course_id = c.id
			AND lmp.id = %d',
			$id
		);

		// run the query
		$product = $DB->get_record_sql($query);

		// return the products
		if (!!$product) {
			$this->_id = (int) $product->id;
			$this->_courseid = (int) $product->course_id;
			$this->_enabled = (bool) $product->is_enabled;
			$this->_fullname = $product->fullname;
			$this->_shortname = $product->shortname;
			$this->_type = $product->type;
			$this->_categoryid = (int) $product->category;
			$this->_summary = $product->summary;
			$this->_summaryFormat = (int) $product->summary_format;
			$this->_description = $product->description;
			$this->_tags = explode(',', $product->tags);
		} else {
        	throw new Exception('Unable to load product using identifier: ' . $id);
   		}
	}

	/**
	 * Formats the product summary
	 * @return string  formatted summary
	 */
	public function get_summary(){
		global $CFG;

		require_once $CFG->libdir . '/filelib.php';
		require_once $CFG->libdir . '/weblib.php';

		if (strlen($this->_summary) < 1) {
			return '';
		}

		$context = context_course::instance($this->_courseid);

		$options = array(
			'para' => false,
			'newlines' => true,
			'overflowdiv' => false,
		);

		$summary = file_rewrite_pluginfile_urls($this->_summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
		return format_text($summary, $this->_summaryFormat, $options, $this->_courseid);
	}

	/**
	 * Retrieves the image url for the Moodle course related to the product
	 * @return string  		URL, or FALSE
	 */			
	public function get_image_url(){
		global $CFG;

		require_once $CFG->libdir . "/filelib.php";

		$course = get_course($this->_courseid);

		if ($course instanceof stdClass) {
			require_once $CFG->libdir . '/coursecatlib.php';
			$course = new course_in_list($course);
		}

		foreach ($course->get_course_overviewfiles() as $file) {
			$isImage = $file->is_valid_image();

			if ($isImage) {
				return file_encode_url("$CFG->wwwroot/pluginfile.php",
					'/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
					$file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isImage);
			}
		}

		return false;
	}

	/**
	 * Return array of related products
	 * @param int 		$limit 		the max number of related products to be returned
	 * @return array 				products
	 */
	public function get_related($limit = 3){

		// TODO: 	Figure out where to set limit
		// 			Perhaps plugin settings?
		// 			Or just stay as parameter?
		
		// We get random products that are in the same category as this product
		return local_moodec_get_random_products($limit, $this->_categoryid, $this->_id);
	}

	/**
	 * Returns the visibility state of the product
	 * @return boolean 		true if enabled
	 */
	public function is_enabled(){
		return !!$this->_enabled;
	}

	/**
	 * Retrieves the MoodecProduct id
	 * @return int    id
	 */
	public function get_id(){
		return $this->_id;
	}

	/**
	 * Retrievs the Moodle course id associated with the product
	 * @return int 		courseid
	 */
	public function get_course_id(){
		return $this->_courseid;
	}

	/**
	 * Returns the product type
	 * @return string ('PRODUCT_TYPE_SIMPLE', 'PRODUCT_TYPE_VARIABLE', 'PRODUCT_TYPE_SUBSCRIPTION')
	 */
	public function get_type(){
		return $this->_type;
	}

	/**
	 * Returns the Moodle course fullname
	 * @return string fullname
	 */
	public function get_fullname(){
		return $this->_fullname;
	}

	/**
	 * Returns the Moodle course shortname
	 * @return string shortname
	 */
	public function get_shortname(){
		return $this->_shortname;
	}

	/**
	 * Returns the Moodle category relating to the product
	 * @return int 	categoryid
	 */
	public function get_category_id(){
		return $this->_categoryid;
	}

	/**
	 * Returns the product description
	 * @return string description
	 */
	public function get_description(){
		return $this->_description;
	}

	/**
	 * Returns true if the product has a description
	 * @return boolean 
	 */
	public function has_description(){
		return 0 < strlen($this->_description);
	}
}