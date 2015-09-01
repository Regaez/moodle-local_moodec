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
    		'local_moodec_variation', 
    		array(
    			'product_id' => $id
			)
		);

    	if(!!$productVariations) {
	    	foreach ($productVariations as $pv) {
	    		$variationid = (int) $pv->id;
	    		$_variations[$variationid] = new MoodecProductVariation($variationid, true);
	    	}
	    } else {
        	throw new Exception('Unable to load product variation information using identifier: ' . $id);
   		}
    }

    /**
     * Retrieves the product variations
     * @param  int 			$id 				variation_id
     * @return MoodecProductVariation|array     Either the specific variation, or array of all
     */
    public function get_variations($id = null){

    	if( !is_null($id) ) {
    		// Check if there is a variation matching that ID
    		if( array_key_exists($id, $this->_variations) ) {
    			return $this->_variations[$id];
    		} 

    		return false;
    	}

    	return $this->_variations;
    }
}