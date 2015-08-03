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

function local_moodec_extends_navigation(global_navigation $nav) {
	global $PAGE, $DB;

	// Add store container to menu
	$storenode = $PAGE->navigation->add(
		get_string('catalogue_title', 'local_moodec'),
		new moodle_url('/local/moodec/pages/catalogue.php'),
		navigation_node::TYPE_CONTAINER
	);

	// Check if there are any products in the database
	$productsExist = $DB->get_records_sql('SELECT * FROM {local_moodec_course}');

	if(!!$productsExist) {

		// Actually get the products
		$products = local_moodec_get_products();

		if (!!get_config('local_moodec', 'page_product_enable')) {
			// Add products to the store menu
			foreach ($products as $product) {
				$storenode->add(
					$product->fullname,
					new moodle_url('/local/moodec/pages/product.php', array('id' => $product->courseid))
				);
			}
		}
	}

	// Add cart page to menu
	$PAGE->navigation->add(
		get_string('cart_title', 'local_moodec'),
		new moodle_url('/local/moodec/pages/cart.php')
	);
}

/**
 * Display the Moodec settings in the course settings block
 * For 2.3 and onwards
 *
 * @param  settings_navigation $nav     The settings navigatin object
 * @param  stdclass            $context Course context
 */
function local_moodec_extends_settings_navigation(settings_navigation $nav, $context) {
	if ($context->contextlevel >= CONTEXT_COURSE and ($branch = $nav->get('courseadmin'))
		and has_capability('moodle/course:update', $context)) {
		$ltiurl = new moodle_url('/local/moodec/settings/course.php', array('id' => $context->instanceid));
		$branch->add(get_string('moodec_course_settings', 'local_moodec'), $ltiurl, $nav::TYPE_CONTAINER, null, 'moodec' . $context->instanceid);
	}
}

/**
 * Formats the course summary description
 *
 * @param  int 		$id 	the course id
 * @return string     		the formatted text
 */
function local_moodec_format_course_summary($id) {
	global $CFG;
	require_once $CFG->libdir . '/filelib.php';
	require_once $CFG->libdir . '/weblib.php';

	$course = get_course($id);

	if (strlen($course->summary) < 1) {
		return '';
	}

	$context = context_course::instance($course->id);

	$options = array(
		'para' => false,
		'newlines' => true,
		'overflowdiv' => false,
	);

	$summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
	return format_text($summary, $course->summaryformat, $options, $course->id);
}

/**
 * Formats the enrolment duration to
 * @param  [type] $duration [description]
 * @return [type]           [description]
 */
function local_moodec_format_enrolment_duration($duration) {
	$output = '';

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
}

/**
 * Returns the url of the first image contained in the course summary file area
 * @param  int $id the course id
 * @return string     the url to the image
 */
function local_moodec_get_course_image_url($id) {
	global $CFG;
	require_once $CFG->libdir . "/filelib.php";
	$course = get_course($id);

	if ($course instanceof stdClass) {
		require_once $CFG->libdir . '/coursecatlib.php';
		$course = new course_in_list($course);
	}

	foreach ($course->get_course_overviewfiles() as $file) {
		$isimage = $file->is_valid_image();

		if ($isimage) {
			return file_encode_url("$CFG->wwwroot/pluginfile.php",
				'/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
				$file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
		}
	}

	return false;
}

/**
 * Returns the cart from cookie
 * @return array cart
 */
function local_moodec_get_cart() {
	global $DB;

	if (isset($_COOKIE['moodec_cart'])) {

		$storedCart = local_moodec_object_to_array(json_decode($_COOKIE['moodec_cart']));
		$validCart = $storedCart;

		// Check all the products which exist
		foreach ($storedCart['courses'] as $product => $quantity) {

			$productExists = $DB->get_record('local_moodec_course', array('courseid' => $product));

			if (!!$productExists) {
				if (!$productExists->show_in_store) {
					$updateCart = true;
					// If it shouldn't be shown in store, remove
					unset($validCart['courses'][$product]);
				}
			} else {
				$updateCart = true;
				// If there is no longer an entry in the DB, remove
				unset($validCart['courses'][$product]);
			}
		}

		// Returns only valid products
		return $validCart;
	}

	return false;
}

/**
 * Adds an item to the cart
 * @param  int 	$id		course id
 * @return array    	the cart
 */
function local_moodec_cart_add($id, $variation = 0) {
	$newCart = array();
	$id = (int) $id;

	$cart = local_moodec_get_cart();

	if (!!$cart) {
		$newCart = $cart;
	}

	// Stores the item in the cart, the key being the course ID, the value the variation
	// 0 = simple, 1-5 is variation tier
	$newCart['courses'][$id] = (int) $variation;

	setcookie('moodec_cart', json_encode($newCart), time() + 31536000, '/');

	return $newCart;
}

/**
 * Removes the product of specified id from the cart and returns the new cart
 * @param  int $id product id
 * @return array     the updated cart
 */
function local_moodec_cart_remove($id) {
	$newCart = array();
	$id = (int) $id;

	$cart = local_moodec_get_cart();

	if (!!$cart) {
		$newCart = $cart;
	}

	unset($newCart['courses'][$id]);

	setcookie('moodec_cart', json_encode($newCart), time() + 31536000, '/');

	return $newCart;
}

/**
 * Converts an object (and any nested objects) to an array
 * @param  stdClass $obj 	an object or class
 * @return array      		the converted object as an array
 */
function local_moodec_object_to_array($obj) {
	if (is_object($obj)) {
		$obj = (array) $obj;
	}

	if (is_array($obj)) {
		$new = array();
		foreach ($obj as $key => $val) {
			$new[$key] = local_moodec_object_to_array($val);
		}
	} else {
		$new = $obj;
	}

	return $new;
}

/**
 * Returns the total of the cart
 * @return float total
 */
function local_moodec_cart_get_total() {
	global $DB;
	$sum = 0;
	$cart = local_moodec_get_cart();

	if (!!$cart) {
		foreach ($cart['courses'] as $product => $variation) {
			$moodecCourse = $DB->get_record('local_moodec_course', array('courseid' => $product));

			// checks if product is variable, or simple
			if($moodecCourse->pricing_model == 'simple') {
				$sum += (float) $moodecCourse->simple_price;
			} else {
				// if variable, we should have a variation ID
				// double check that this is actually set
				// if 0, then something has gone funny along the way, so return simple price
				// (when we create the cart, we default set it to 0 for simple products)
				if( (int) $variation === 0) {
					$sum += (float) $moodecCourse->simple_price;
				} else {
					// otherwise we *should* be gravy to add the variation's price
					// TODO: forseeable issue being variation_id GREATER than 5?
					$sum += (float) $moodecCourse->{"variable_price_$variation"};
				}
			}
		}
	}

	return number_format($sum, 2, '.', ',');
}

function local_moodec_get_currencies() {
	// See https://www.paypal.com/cgi-bin/webscr?cmd=p/sell/mc/mc_intro-outside,
	// 3-character ISO-4217: https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_currency_codes
	$codes = array(
		'AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'JPY',
		'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RUB', 'SEK', 'SGD', 'THB', 'TRY', 'TWD', 'USD');
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
		'BRL' => 'R$',
		'CAD' => '$',
		'CHF' => 'CHF',
		'CZK' => 'Kč',
		'DKK' => 'kr',
		'EUR' => '€',
		'GBP' => '£',
		'HKD' => '$',
		'HUF' => 'Ft',
		'ILS' => '₪',
		'JPY' => '¥',
		'MXN' => '$',
		'MYR' => 'RM',
		'NOK' => 'kr',
		'NZD' => '$',
		'PHP' => '₱',
		'PLN' => 'zł',
		'RUB' => 'руб',
		'SEK' => 'kr',
		'SGD' => '$',
		'THB' => '฿',
		'TRY' => '₺',
		'TWD' => 'NT$',
		'USD' => '$',
	);

	if (array_key_exists($currency, $codes)) {
		return $codes[$currency];
	}

	return '$';
}

/**
 * Returns an product object
 * @param  int $id the course id
 * @return object     Product, false if no product found
 */
function local_moodec_get_product($id) {
	global $DB;

	// build the query
	$query = sprintf(
		'SELECT
			lmc.id,
			c.id as courseid,
			fullname,
			shortname,
			category,
			summary,
			sortorder,
			pricing_model,
			simple_price,
			simple_enrolment_duration,
			simple_group,
			variable_tiers,
			variable_name_1,
			variable_price_1,
			variable_enrolment_duration_1,
			variable_group_1,
			variable_name_2,
			variable_price_2,
			variable_enrolment_duration_2,
			variable_group_2,
			variable_name_3,
			variable_price_3,
			variable_enrolment_duration_3,
			variable_group_3,
			variable_name_4,
			variable_price_4,
			variable_enrolment_duration_4,
			variable_group_4,
			variable_name_5,
			variable_price_5,
			variable_enrolment_duration_5,
			variable_group_5,
			additional_info,
			product_tags,
			timecreated
		FROM {local_moodec_course} lmc, {course} c
		WHERE show_in_store = 1
		AND lmc.courseid = c.id
		AND lmc.courseid = %d
		LIMIT 1',
		$id
	);

	// run the query
	$products = $DB->get_records_sql($query);

	// return the products
	if (!!$products) {
		foreach ($products as $product) {
			$newProduct = new stdClass();

			$newProduct->id = (int) $product->id;
			$newProduct->courseid = (int) $product->courseid;
			$newProduct->fullname = $product->fullname;
			$newProduct->pricing_model = $product->pricing_model;
			$newProduct->category = (int) $product->category;
			$newProduct->summary = $product->summary;
			$newProduct->sortorder = (int) $product->sortorder;
			$newProduct->price = (float) $product->simple_price;
			$newProduct->enrolment_duration = (int) $product->simple_enrolment_duration;
			$newProduct->group = (int) $product->simple_group;
			$newProduct->additional_info = $product->additional_info;
			$newProduct->tags = explode(',', $product->product_tags);
			$newProduct->variable_tiers = (int) $product->variable_tiers;
			$newProduct->variations = array();

			// Store variations as an array of objects
			for($i = 1; $i <= (int) $product->variable_tiers; $i++) {
				$newVariation = new stdClass();
				$newVariation->variation_id = $i;
				$newVariation->name = $product->{"variable_name_$i"};
				$newVariation->price = (float) $product->{"variable_price_$i"};
				$newVariation->enrolment_duration = (int) $product->{"variable_enrolment_duration_$i"};
				$newVariation->group = (int) $product->{"variable_group_$i"};

				$newProduct->variations[$i] = $newVariation;
			}

			return $newProduct;
		}
	}

	// return false if no product matches that ID;
	return false;
}


/**
 * Returns an array of the products
 * @param  int 		$category  	The category id to filter
 * @param  string 	$sortfield 	The field to sort the data by
 * @param  string 	$sortorder 	Sort by ASC or DESC
 * @param  int 		$page 		The pagination 'page' to return. -1 will return all products
 * @return array            	The products
 */
function local_moodec_get_products($category = null, $sortfield = 'sortorder', $sortorder = 'ASC', $page = 1) {
	global $DB;

	// VALIDATE PARAMETERS
	if (!in_array($sortfield, array('sortorder', 'simple_price', 'fullname', 'shortname', 'simple_enrolment_duration', 'timecreated'))) {
		$sortfield = 'sortorder';
	}

	if (!in_array($sortorder, array('ASC', 'DESC'))) {
		$sortorder = 'ASC';
	}

	if ($category == 'default') {
		$category = null;
	}

	// Check if we should be returning all products or just a page of products
	$returnAll = false;
	if( $page === -1 ) {
		$returnAll = true;
	}

	$page = $page < 1 ? 0 : $page - 1;

	// build the query
	$query = sprintf(
		'SELECT
			lmc.id,
			c.id as courseid,
			fullname,
			shortname,
			category,
			summary,
			sortorder,
			pricing_model,
			simple_price,
			simple_enrolment_duration,
			simple_group,
			variable_tiers,
			variable_name_1,
			variable_price_1,
			variable_enrolment_duration_1,
			variable_group_1,
			variable_name_2,
			variable_price_2,
			variable_enrolment_duration_2,
			variable_group_2,
			variable_name_3,
			variable_price_3,
			variable_enrolment_duration_3,
			variable_group_3,
			variable_name_4,
			variable_price_4,
			variable_enrolment_duration_4,
			variable_group_4,
			variable_name_5,
			variable_price_5,
			variable_enrolment_duration_5,
			variable_group_5,
			additional_info,
			product_tags,
			timecreated
		FROM {local_moodec_course} lmc, {course} c
		WHERE show_in_store = 1
		AND lmc.courseid = c.id
		%s
		ORDER BY %s %s',
		$category !== null ? 'AND c.category = ' . $category : '',
		$sortfield,
		$sortorder
	);

	// run the query
	$products = $DB->get_records_sql($query);

	// return the products
	if (!!$products) {
		$castProducts = array();

		// Cast the fields to be correct type
		foreach ($products as $product) {
			$newProduct = new stdClass();

			$newProduct->id = (int) $product->id;
			$newProduct->courseid = (int) $product->courseid;
			$newProduct->fullname = $product->fullname;
			$newProduct->pricing_model = $product->pricing_model;
			$newProduct->category = (int) $product->category;
			$newProduct->summary = $product->summary;
			$newProduct->sortorder = (int) $product->sortorder;
			$newProduct->price = (float) $product->simple_price;
			$newProduct->enrolment_duration = (int) $product->simple_enrolment_duration;
			$newProduct->group = (int) $product->simple_group;
			$newProduct->additional_info = $product->additional_info;
			$newProduct->tags = explode(',', $product->product_tags);
			$newProduct->variable_tiers = (int) $product->variable_tiers;
			$newProduct->variations = array();

			// Store variations as an array of objects
			for($i = 1; $i <= (int) $product->variable_tiers; $i++) {
				$newVariation = new stdClass();
				$newVariation->variation_id = $i;
				$newVariation->name = $product->{"variable_name_$i"};
				$newVariation->price = (float) $product->{"variable_price_$i"};
				$newVariation->enrolment_duration = (int) $product->{"variable_enrolment_duration_$i"};
				$newVariation->group = (int) $product->{"variable_group_$i"};

				$newProduct->variations[] = $newVariation;
			}

			array_push($castProducts, $newProduct);
		}

		if( $returnAll ) {
			return $castProducts;
		} else {
			return array_slice($castProducts, $page * get_config('local_moodec', 'pagination'));
		}
	}

	// return an empty array if nothing matches the query
	return array();
}

/**
 * Returns an array of the products
 * @param  int 		$limit 		The number of random products to return
 * @param  int 		$category  	The category id to filter by
 * @return array            	The products
 */
function local_moodec_get_random_products($limit = 1, $category = null) {
	global $DB;

	// VALIDATE PARAMETERS
	if ($category == 'default') {
		$category = null;
	}

	// build the query
	$query = sprintf(
		'SELECT
			lmc.id,
			c.id as courseid,
			fullname,
			shortname,
			category,
			summary,
			sortorder,
			pricing_model,
			simple_price,
			simple_enrolment_duration,
			simple_group,
			variable_tiers,
			variable_name_1,
			variable_price_1,
			variable_enrolment_duration_1,
			variable_group_1,
			variable_name_2,
			variable_price_2,
			variable_enrolment_duration_2,
			variable_group_2,
			variable_name_3,
			variable_price_3,
			variable_enrolment_duration_3,
			variable_group_3,
			variable_name_4,
			variable_price_4,
			variable_enrolment_duration_4,
			variable_group_4,
			variable_name_5,
			variable_price_5,
			variable_enrolment_duration_5,
			variable_group_5,
			additional_info,
			product_tags,
			timecreated
		FROM {local_moodec_course} lmc, {course} c
		WHERE show_in_store = 1
		AND lmc.courseid = c.id
		%s
		ORDER BY rand()
		LIMIT %d',
		$category !== null ? 'AND c.category = ' . $category : '',
		$limit
	);

	// run the query
	$products = $DB->get_records_sql($query);

	// return the products
	if (!!$products) {
		$castProducts = array();

		// Cast the fields to be correct type
		foreach ($products as $product) {
			$newProduct = new stdClass();

			$newProduct->id = (int) $product->id;
			$newProduct->courseid = (int) $product->courseid;
			$newProduct->fullname = $product->fullname;
			$newProduct->pricing_model = $product->pricing_model;
			$newProduct->category = (int) $product->category;
			$newProduct->summary = $product->summary;
			$newProduct->sortorder = (int) $product->sortorder;
			$newProduct->price = (float) $product->simple_price;
			$newProduct->enrolment_duration = (int) $product->simple_enrolment_duration;
			$newProduct->group = (int) $product->simple_group;
			$newProduct->additional_info = $product->additional_info;
			$newProduct->tags = explode(',', $product->product_tags);
			$newProduct->variable_tiers = (int) $product->variable_tiers;
			$newProduct->variations = array();

			// Store variations as an array of objects
			for($i = 1; $i <= (int) $product->variable_tiers; $i++) {
				$newVariation = new stdClass();
				$newVariation->variation_id = $i;
				$newVariation->name = $product->{"variable_name_$i"};
				$newVariation->price = (float) $product->{"variable_price_$i"};
				$newVariation->enrolment_duration = (int) $product->{"variable_enrolment_duration_$i"};
				$newVariation->group = (int) $product->{"variable_group_$i"};

				$newProduct->variations[] = $newVariation;
			}

			array_push($castProducts, $newProduct);
		}

		return $castProducts;
	}

	// return an empty array if nothing matches the query
	return array();
}

function local_moodec_get_related_products($id, $category = null) {
	global $DB;

	// build the query
	$query = sprintf(
		'SELECT
			lmc.id,
			c.id as courseid,
			fullname,
			shortname,
			category,
			summary,
			sortorder,
			pricing_model,
			simple_price as price,
			simple_enrolment_duration as enrolment_duration,
			additional_info,
			product_tags,
			timecreated
		FROM {local_moodec_course} lmc, {course} c
		WHERE show_in_store = 1
		AND lmc.courseid = c.id
		AND lmc.courseid != %d
		%s
		ORDER BY uuid()',
		$id,
		$category !== null ? 'AND c.category = ' . $category : ''
	);

	$products = $DB->get_records_sql($query);

	// return the products
	if (!!$products) {
		$castProducts = array();

		// Cast the fields to be correct type
		foreach ($products as $product) {
			$newProduct = $product;

			$newProduct->id = (int) $product->id;
			$newProduct->courseid = (int) $product->courseid;
			$newProduct->category = (int) $product->category;
			$newProduct->sortorder = (int) $product->sortorder;
			$newProduct->price = (float) $product->price;
			$newProduct->enrolment_duration = (int) $product->enrolment_duration;
			$newProduct->tags = explode(',', $product->product_tags);

			array_push($castProducts, $newProduct);
		}

		return $castProducts;
	}

	return array();
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

/**
 * Outputs the HTML for the pagination
 * @param  array  $products    An array of the products to be paginated
 * @param  integer $currentPage The index of the current page
 * @param  int  $category    the category ID
 * @param  string  $sort        the string sorting parameter
 * @return string               the HTML output
 */
function local_moodec_output_pagination($products, $currentPage = 0, $category = null, $sort = null) {

	// Calculate total page count
	$pageCount = ($currentPage - 1) + ceil(count($products) / get_config('local_moodec', 'pagination'));

	// Only output pagination when there is more than one page
	if (1 < $pageCount) {

		printf('<div class="pagination-bar"><ul class="pagination">');

		$params = array();

		if ($sort !== null) {
			$params['sort'] = $sort;
		}

		if ($category !== null) {
			$params['category'] = $category;
		}

		for ($paginator = 1; $paginator <= $pageCount; $paginator++) {
			$params['page'] = $paginator;

			printf('<li class="page-item"><a href="%s" %s>%d</a></li>',
				new moodle_url('/local/moodec/pages/catalogue.php', $params),
				$paginator === $currentPage ? 'class="active"' : '',
				$paginator
			);

		}

		printf('</ul></div>');
	}
}

function local_moodec_get_groups($id) {
	global $CFG;
	require_once $CFG->libdir . '/grouplib.php';
	$arr = array(
		0 => get_string('variable_group_none', 'local_moodec')
	);
	$groups = groups_get_all_groups($id);

	foreach ($groups as $g) {
		$arr[$g->id] = $g->name;
	}

	return $arr;
}
