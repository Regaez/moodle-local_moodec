<?php 
/**
 * Moodec Variable Product
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

class MoodecProductVariable extends MoodecProduct {

	/**
	 * Stores an array of MoodecProductVariation instances
	 * @var array
	 */
	protected $_variations;

    /**
     * Loads the product from the DB, incuding product variation info
     * @param  int 		$id		MoodecProduct id
     * @return [type]     		[description]
     */
    public function load($id) {
    	global $DB;

    	parent::load($id);

    	$productVariations = $DB->get_records(
    		'local_moodec_product_variation', 
    		array(
    			'product_id' => $id
			)
		);

    	if(!!$productVariations) {
	    	foreach ($productVariations as $pv) {
	    		$variationid = (int) $pv->id;
	    		$_variations[] = new MoodecProductVariation($variationid, true);
	    	}
	    } else {
        	throw new Exception('Unable to load product variation information using identifier: ' . $id);
   		}
    }

    /**
     * Retrieves the product variations
     * @return array 	variations
     */
    public function get_variations(){
    	return $this->_variations;
    }
}