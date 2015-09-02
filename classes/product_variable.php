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
    			'product_id' => $id,
			)
		);

    	if(!!$productVariations) {
	    	foreach ($productVariations as $pv) {
	    		$variationid = (int) $pv->id;
	    		$this->_variations[$variationid] = new MoodecProductVariation($variationid, true);
	    	}
	    } else {
        	throw new Exception('Unable to load product variation information using identifier: ' . $id);
   		}
    }

    /**
     * Retrieves the product variation
     * @param  int 			$id 				variation_id
     * @return MoodecProductVariation     		Either the specific variation
     */
    public function get_variation($id = null, $returnDisabled = false){

		// Check if there is a variation matching that ID
		if( array_key_exists($id, $this->_variations) ) {
			if( $this->_variations[$id]->is_enabled() || $returnDisabled ) {
				return $this->_variations[$id];
			}
		} 

		return false;
    }

    /**
     * Returns the product variations
     * @param  boolean 	$returnAll 	true if disabled variations should be returned
     * @return array             	variations
     */	
    public function get_variations($returnAll = false) {

    	if( $returnAll ) {
    		return $this->_variations;
    	}

		$enabledVariations = array();

		// We only want to return variations which are enabled
    	foreach ($this->_variations as $id => $v) {
    		if( $v->is_enabled() ) {
    			$enabledVariations[$id] = $v; 
    		}
    	}

    	return $enabledVariations;
	}

}