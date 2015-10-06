<?php
/**
 * Moodec Library file
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Declare product type constants
define('PRODUCT_TYPE_SIMPLE', 'PRODUCT_TYPE_SIMPLE');
define('PRODUCT_TYPE_VARIABLE', 'PRODUCT_TYPE_VARIABLE');

// Declare gateway type constants
define('MOODEC_GATEWAY_PAYPAL', 'MOODEC_GATEWAY_PAYPAL');
define('MOODEC_GATEWAY_DPS', 'MOODEC_GATEWAY_DPS');


// Load the required Moodec classes
require_once $CFG->dirroot . '/local/moodec/classes/product.php';
require_once $CFG->dirroot . '/local/moodec/classes/product_simple.php';
require_once $CFG->dirroot . '/local/moodec/classes/product_variable.php';
require_once $CFG->dirroot . '/local/moodec/classes/product_variation.php';
require_once $CFG->dirroot . '/local/moodec/classes/cart.php';
require_once $CFG->dirroot . '/local/moodec/classes/transaction.php';
require_once $CFG->dirroot . '/local/moodec/classes/transaction_item.php';
require_once $CFG->dirroot . '/local/moodec/classes/gateway.php';
require_once $CFG->dirroot . '/local/moodec/classes/gateway_paypal.php';
require_once $CFG->dirroot . '/local/moodec/classes/gateway_dps.php';

/**
 * Extend the default Moodle navigation
 * @param  global_navigation $nav
 * @return void                 
 */
function local_moodec_extends_navigation(global_navigation $nav) {
	global $CFG, $PAGE, $DB;

	// Add store container to menu
	$storenode = $PAGE->navigation->add(
		get_string('catalogue_title', 'local_moodec'),
		new moodle_url($CFG->wwwroot . '/local/moodec/pages/catalogue.php'),
		navigation_node::TYPE_CONTAINER
	);

	if (!!get_config('local_moodec', 'page_product_enable')) {

		// We store the courses by category
		// but only get categories with active products
		$query = sprintf(
			'SELECT DISTINCT 
					cc.id, 
					cc.visible,
					cc.name
			FROM	{course_categories} cc, 
					{course} c, 
					{local_moodec_product} lmp
			WHERE 	cc.id = c.category
			AND 	c.id = lmp.course_id
			AND 	lmp.is_enabled = 1');

		$categories = $DB->get_records_sql($query);

		if (!!$categories) {
			foreach ($categories as $category) {
				if($category->visible) {

					$catnode = $storenode->add(
						$category->name,
						new moodle_url($CFG->wwwroot . '/local/moodec/pages/catalogue.php', array('category' => $category->id)),
						navigation_node::TYPE_CONTAINER
					);

					// Actually get the products
					$products = local_moodec_get_products(-1, $category->id, 'fullname');

					// Add products to the store menu
					foreach ($products as $product) {
						$catnode->add(
							$product->get_fullname(),
							new moodle_url($CFG->wwwroot . '/local/moodec/pages/product.php', array('id' => $product->get_id()))
						);
					}
				}
			}
		}
	}

	// Add cart page to menu
	$PAGE->navigation->add(
		get_string('cart_title', 'local_moodec'),
		new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php')
	);

	// Add transactions page to menu
	$PAGE->navigation->add(
		get_string('transactions_title', 'local_moodec'),
		new moodle_url($CFG->wwwroot . '/local/moodec/pages/transaction/index.php')
	);
}

/**
 * Display the Moodec settings in the course settings block
 * For 2.3 and onwards
 *
 * @param  settings_navigation $nav     The settings navigation object
 * @param  stdclass            $context Course context
 */
function local_moodec_extends_settings_navigation(settings_navigation $nav, $context) {
	global $CFG;

	if ($context->contextlevel >= CONTEXT_COURSE and ($branch = $nav->get('courseadmin'))
		and has_capability('moodle/course:update', $context)) {
		$url = new moodle_url($CFG->wwwroot . '/local/moodec/settings/product.php', array('id' => $context->instanceid));
		$branch->add(get_string('moodec_product_settings', 'local_moodec'), $url, $nav::TYPE_CONTAINER, null, 'moodec' . $context->instanceid, new pix_icon('i/settings', ''));
	}
}

function local_moodec_get_currencies() {
	// See https://www.paypal.com/cgi-bin/webscr?cmd=p/sell/mc/mc_intro-outside,
	// 3-character ISO-4217: https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_currency_codes
	// See https://www.paymentexpress.com/technical_resources/ecommerce_hosted/pxpay.html

	$codes = array('AUD','CAD','CHF','DKK','EUR','GBP','HKD','JPY','MYR','NZD','SGD','THB','USD');
	$currencies = array();
	foreach ($codes as $c) {
		$currencies[$c] = new lang_string($c, 'core_currencies');
	}

	return $currencies;
}

/**
 * Returns the symbol for the supplied currency
 * @param  string $currency the currency code
 * @return string           the symbol
 */
function local_moodec_get_currency_symbol($currency) {

	$codes = array(
		'AUD' => '$',
		'CAD' => '$',
		'CHF' => 'CHF',
		'DKK' => 'kr',
		'EUR' => '€',
		'GBP' => '£',
		'HKD' => '$',
		'JPY' => '¥',
		'MYR' => 'RM',
		'NZD' => '$',
		'SGD' => '$',
		'THB' => '฿',
		'USD' => '$',
	);

	if (array_key_exists($currency, $codes)) {
		return $codes[$currency];
	}

	return '$';
}

/**
 * Returns an product object
 * @param  int 				$id 	the course id
 * @return MoodecProduct     		Product, exception thrown if no product found
 */
function local_moodec_get_product($id) {
	global $DB;

	// build the query
	$query = sprintf(
		'SELECT type
		FROM {local_moodec_product}
		WHERE id = %d',
		(int) $id
	);

	// run the query
	$product = $DB->get_record_sql($query);

	// Return the product
	if (!!$product) {
		if( $product->type === PRODUCT_TYPE_SIMPLE) {
			return new MoodecProductSimple((int) $id);
		} else if( $product->type === PRODUCT_TYPE_VARIABLE) {
			return new MoodecProductVariable((int) $id);
		}
	}

	// Otherwise
	throw new Exception('Unable to find product using identifier: ' . $id);
}


/**
 * Returns an array of the products
 * @param  int 		$page 		The pagination 'page' to return. -1 will return all products
 * @param  int 		$category  	The category id to filter
 * @param  string 	$sortfield 	The field to sort the data by
 * @param  string 	$sortorder 	Sort by ASC or DESC
 * @return array            	The products
 */
function local_moodec_get_products($page = 1, $category = null, $sortfield = 'sortorder', $sortorder = 'ASC') {
	global $DB;

	// An array to store the products (this will be returned)
	$products = array();

	// Get the number of products to be shown per page from the plugin config
	$productsPerPage = get_config('local_moodec', 'pagination');

	// VALIDATE PARAMETERS
	if (!in_array($sortfield, array('sortorder', 'price', 'fullname', 'duration', 'timecreated'))) {
		$sortfield = 'sortorder';
	}

	// Sorting can only be done by 2 ways
	if (!in_array($sortorder, array('ASC', 'DESC'))) {
		$sortorder = 'ASC';
	}

	// If default, we won't filter by category
	if ($category == 'default') {
		$category = null;
	}

	// Ensure page is an int
	if( !is_int($page) ) {
		$page = (int) $page;
	}

	// Check if we should be returning all products or just a page of products
	$returnAll = false;
	if( $page === -1 ) {
		$returnAll = true;
	}

	// Reduce page by 1 so we can get the first 10 products
	// Because 0-based array stuff
	$page = $page < 1 ? 0 : $page - 1;

	// BUILD THE QUERY
	$query = sprintf(
		'SELECT DISTINCT lmp.id as productid
		FROM 	{local_moodec_product} lmp, 
				{local_moodec_variation} lmv, 
				{course} c
		WHERE 	lmp.id = lmv.product_id
		AND 	lmp.course_id = c.id
		AND		lmp.is_enabled = 1
		%s
	 	ORDER BY %s %s',
	 	$category !== null ? 'AND c.category = ' . $category : '',
	 	$sortfield,
	 	$sortorder
	);
	
	// RUN THE QUERY	
	if( $returnAll ) {
		$records = $DB->get_records_sql($query);
	} else {
		$records = $DB->get_records_sql($query, null, $productsPerPage * $page, $productsPerPage);
	}

	if( !!$records ) {

		foreach ($records as $record) {
	
			// Add the product matching this id to the array
			$products[] = local_moodec_get_product($record->productid);

		}
	}

	return $products;
}

/**
 * Returns an array of the products
 * @param  int 		$limit 		The number of random products to return
 * @param  int 		$category  	The category id to filter by
 * @return array            	The products
 */
function local_moodec_get_random_products($limit = 1, $category = null, $exclude = 0) {
	global $DB;

	// An array to store the products (this will be returned)
	$products = array();

	// VALIDATE PARAMETERS
	// If default, we won't filter by category
	if ($category == 'default') {
		$category = null;
	}

	// Ensure page is an int
	if( !is_int($limit) ) {
		$limit = (int) $limit;
	}

	// BUILD THE QUERY
	$query = sprintf(
		'SELECT DISTINCT lmp.id as productid
		FROM 	{local_moodec_product} lmp, 
				{local_moodec_variation} lmv, 
				{course} c
		WHERE 	lmp.id = lmv.product_id
		AND 	lmp.course_id = c.id
		AND		lmp.is_enabled = 1
		AND 	lmp.id != %d
		%s
	 	ORDER BY rand()',
	 	$exclude,
	 	$category !== null ? 'AND c.category = ' . $category : ''
	);
	
	// RUN THE QUERY	
	$records = $DB->get_records_sql($query, null, 0, $limit);

	if( !!$records ) {
		foreach ($records as $record) {
	
			// Add the product matching this id to the array
			$products[] = local_moodec_get_product($record->productid);
	
		}
	}

	return $products;
}

/**
 * Returns a list of <option> tags of each category
 * @param  int $id the active category
 * @return string     the HTML <option> list
 */
function local_moodec_get_category_list($id) {
	global $DB;

	$list = sprintf(
		'<option value="default" %s>All</option>',
		$id == null ? 'selected="selected"' : ''
	);

	$categories = $DB->get_records('course_categories');

	if (!!$categories) {
		foreach ($categories as $category) {
			if($category->visible) {
				$list .= sprintf(
					'<option value="%d" %s>%s</option>',
					$category->id,
					(int) $category->id === $id ? 'selected="selected"' : '',
					$category->name
				);
			}
		}
	}

	return $list;
}

function local_moodec_get_groups($id) {
	global $CFG;
	require_once $CFG->libdir . '/grouplib.php';
	$arr = array(
		0 => get_string('product_variation_group_none', 'local_moodec')
	);
	$groups = groups_get_all_groups($id);

	foreach ($groups as $g) {
		$arr[$g->id] = $g->name;
	}

	return $arr;
}

function local_moodec_extract_sort_vars($sort) {
	$sortfield = 'sortorder';
	$sortorder = 'ASC';

	if ($sort !== null && 0 < strlen($sort) && strpos('-', $sort) !== -1) {
		$sortArray = explode('-', $sort);

		$sortfield = $sortArray[0];
		$sortorder = strtoupper($sortArray[1]);
	}

	return array($sortfield, $sortorder);
}