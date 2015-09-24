<?php
/**
 * Moodec Product Settings
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(__FILE__) . '/../../../config.php';
require_once $CFG->dirroot . '/local/moodec/lib.php';
require_once $CFG->dirroot . '/local/moodec/forms/edit_product.php';

$courseid = required_param('id', PARAM_INT);

$PAGE->set_pagelayout('admin');
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/moodec/js/settings_product.js'));

// Validate course id
if(!!$courseid) {
	$PAGE->set_url('/local/moodec/settings/product.php', array('id' => $courseid));
	
	$course = get_course($courseid);
	require_login($course);

	// error if there is no course matching the id
	if (!($course = $DB->get_record('course', array('id' => $courseid)))) {
		print_error('invalidcourseid', 'error');
	}

	// ensure we have proper context
	if (!$context = context_course::instance($course->id)) {
		print_error('nocontext');
	}

	require_capability('moodle/course:update', $context);
} else {
	require_login();
}

// instantiate edit_product form
$mform = new moodec_edit_product_form();

if ($mform->is_cancelled()) {

	// redirect back to the course page
	redirect(new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $courseid)));

} else if ($data = $mform->get_data()) {
	// Now we process validated data
	global $DB;

	// Check if a record already exists for this course in the DB
	// course_id and id (the product id) are both unique identifiers in the local_moodec_product table
	$recordExists = $DB->get_record('local_moodec_product', array('course_id' => $data->id), '*', IGNORE_MISSING);

	// BUILD UP DB RECORD OBJECTS

	// Form an object for the product to be inserted into DB
	$recordProduct = new stdClass();
	$recordProduct->course_id 		= $data->id;
	$recordProduct->is_enabled 		= $data->product_enabled;
	$recordProduct->type 			= $data->product_type;
	$recordProduct->tags 			= $data->product_tags;
	$recordProduct->description 	= $data->product_description['text'];

	if($recordProduct->type === PRODUCT_TYPE_SIMPLE) {
		// if it is a simple product, we ONLY update the 1 variation
		// any others are ignored
		$recordProduct->variation_count = 1;
	} else {
		$recordProduct->variation_count = $data->format;
	}

	// Form an array of all the variations to be added to the DB
	$recordVariations = array();
	for ($i=1; $i <= $recordProduct->variation_count; $i++) { 
		$newRecord = new stdClass();

		// In the mform, we named variations incrementally,
		// so now we need to use variables to match the field names
		$enabled 	= 'product_variation_enabled_' . $i;
		$name 		= 'product_variation_name_' . $i;
		$price 		= 'product_variation_price_' . $i;
		$duration 	= 'product_variation_duration_' . $i;
		$group 		= 'product_variation_group_' . $i;

		// We force the first variation to be enabled
		// All products need at least 1 variation
		if( $i === 1) {
			$newRecord->is_enabled 	= 1;
		} else {
			$newRecord->is_enabled	= $data->$enabled;
		}

		$newRecord->name 		= $data->$name;
		$newRecord->price 		= $data->$price;
		$newRecord->duration 	= $data->$duration;
		$newRecord->group_id 	= $data->$group;

		$recordVariations[] = $newRecord;
	}

	if(!!$recordExists) {
		// A record DOES already exist!
		$recordProduct->id = $recordExists->id;

		// Update local_moodec_product fields	
		$result = $DB->update_record('local_moodec_product', $recordProduct);

		// Update product variations
		$existingVariations = $DB->get_records('local_moodec_variation', array('product_id' => $recordProduct->id));

		// First, we disable ALL variations (these will be selectively re-enabled by the user)
		// ----
		foreach ($existingVariations as $v) {
			$tempVariation 				= new stdClass();
			$tempVariation->id 			= $v->id;
			$tempVariation->is_enabled 	= 0;
			
			$DB->update_record('local_moodec_variation', $tempVariation);
		}
		// ----

		// Then we store our new variation information
		for ($i=0; $i < count($recordVariations); $i++) { 
			
			// Add product_id field to variation record
			$recordVariations[$i]->product_id = $recordProduct->id;
			
			// Check if there's an existing variation to overwrite
			if(0 < count($existingVariations)) {
				// Get the first existing product from the DB
				$existingRow = array_shift($existingVariations);

				// Set the ID accordingly 
				$recordVariations[$i]->id = $existingRow->id;

				// Update the DB
				$variationResult = $DB->update_record('local_moodec_variation', $recordVariations[$i]);
			} else {
				// No more variations exist in the DB, so we need to insert them!
				$variationResult = $DB->insert_record('local_moodec_variation', $recordVariations[$i]);
			}
		}

	} else {
		// No record exists YET
		// Create a new local_moodec_product row
		$result = $DB->insert_record('local_moodec_product', $recordProduct, true);
		
		if(!!$result) {
			// As well as appropriate product_variation rows
			foreach ($recordVariations as $variation) {
				$variation->product_id = $result;
				$variationResult = $DB->insert_record('local_moodec_variation', $variation);
			}
		}
	}

	if (!!$result) {
		// redirect back to the course page
		redirect(new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $courseid)));
	} else {
		// TODO: throw exception
		echo 'something went wrong...';
	}

} else {

	// Retrieve the existing product data
	$existingProductData = $DB->get_record('local_moodec_product', array('course_id' => $courseid), '*', IGNORE_MISSING);
	
	$toForm = new stdClass();

	// If product data DOES exist
	if(!!$existingProductData) {
		
		// Get the existing variation data
		$existingVariationData = $DB->get_records('local_moodec_variation', array('product_id' => $existingProductData->id));

		if(!!$existingVariationData) {
			
			// Build up a class to be passed as default data to the form			

			$toForm->id 							= $existingProductData->course_id;
			$toForm->product_enabled 				= $existingProductData->is_enabled;
			$toForm->product_type 					= $existingProductData->type;
			$toForm->product_tags					= $existingProductData->tags;
			$toForm->format							= $existingProductData->variation_count;
			$toForm->product_description['text']	= $existingProductData->description;
			$toForm->product_description['format']	= 1;

			$counter = 1;
			foreach ($existingVariationData as $variation) {
				// Map the properties to the form fields				
				$enabled 	= 'product_variation_enabled_'.$counter;
				$name 		= 'product_variation_name_'.$counter;
				$price 		= 'product_variation_price_'.$counter;
				$duration 	= 'product_variation_duration_'.$counter;
				$group 		= 'product_variation_group_'.$counter;

				$toForm->$enabled 	= $variation->is_enabled;
				$toForm->$name 		= $variation->name;
				$toForm->$price 	= $variation->price;
				$toForm->$duration 	= $variation->duration;
				$toForm->$group 	= $variation->group_id;

				// increment counter for each variation
				$counter++;
			}

		}
	} else {
		// we need to at least send the course id
		$toForm->id = $courseid;
	}

	$mform->set_data($toForm);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(
	get_string(
		'edit_product_form_title',
		'local_moodec',
		array(
			'name' => $course->fullname
		)
	)
);

$mform->display();

echo $OUTPUT->footer();