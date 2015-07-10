<?php
/**
 * Moodec Renderer
 *
 * @package    local_moodec
 * @copyright  2015 Thomas Threadgold <tj.threadgold@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Moodec Renderer
 *
 * @copyright  2015 Thomas Threadgold <tj.threadgold@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_moodec_renderer extends plugin_renderer_base {


	/**
	 * Outputs the information for the single product page
	 * @param  product 	$product  	object containing all the product information
	 * @return string          		the html output
	 */
	function single_product($product) {
		global $CFG, $DB;

		// Require Moodec lib
		require_once $CFG->dirroot . '/local/moodec/lib.php';

		$cart = local_moodec_get_cart();

		// Product single wrapper
		$html = '<div class="product-single">';

			// Product title
			$html .= sprintf(
				'<h1 class="product__title">%s</h1>',
				get_string('product_title', 'local_moodec', array('coursename' => $product->fullname))
			);

			// Product/course image
			$imageURL = local_moodec_get_course_image_url($product->courseid);

			if (!!$imageURL && !!get_config('local_moodec', 'page_product_show_image')) {

				$html .= sprintf(
					'<img src="%s" alt="%s" class="product-image">',
					$imageURL,
					$product->fullname
				);

			}

			// Product details wrapper
			$html .= '<div class="product-details">';

				// Product description
				if (!!get_config('local_moodec', 'page_product_show_description')) {
					
					$html .= sprintf(
						'<div class="product-description">%s</div>',
						local_moodec_format_course_summary($product->courseid)
					);

				}

				// Product additional description
				if (!!get_config('local_moodec', 'page_product_show_additional_description')) {

					$html .= sprintf(
						'<div class="additional-info">%s</div>',
						$product->additional_info
					);

				}

				// Additional product details wrapper
				$html .= '<div class="product-details__additional">';

					// Product duration
					$html .= '<div class="product-duration__wrapper">';

						$html .= sprintf(
							'<span class="product-duration__label">%s</span>',
							get_string('enrolment_duration_label', 'local_moodec')
						);

						if( $product->pricing_model === 'simple') {

							$html .= sprintf(
								'<span class="product-duration">%s</span>',
								local_moodec_format_enrolment_duration($product->enrolment_duration)
							);
						} else {
							$attr = '';

							foreach ($product->variations as $v) {
								$attr .= sprintf('data-tier-%d="%s" ', 
									$v->variation_id, 
									local_moodec_format_enrolment_duration($v->enrolment_duration)
								);	
							}

							$firstVariation = reset($product->variations);

							$html .= sprintf(
								'<span class="product-duration" %s>%s</span>',
								$attr,
								local_moodec_format_enrolment_duration($firstVariation->enrolment_duration)
							);
						}

					$html .= '</div>';


					// Product category
					if (!!get_config('local_moodec', 'page_catalogue_show_category')) {

						// Get the category the product belongs to
						$category = $DB->get_record(
							'course_categories',
							array(
								'id' => $product->category
							)
						);
						
						// Get the url to link to the category page with the filter active
						$categoryURL = new moodle_url(
							$CFG->wwwroot . '/local/moodec/pages/catalogue.php',
							array(
								'category' => $product->category
							)
						);

						// Category wrapper
						$html .= '<div class="product-category__wrapper">';

							// Category label
							$html .= sprintf(
								'<span class="product-category__label">%s</span>',
								get_string('course_list_category_label', 'local_moodec')
							);

							// Category link
							$html .= sprintf(
								'<a class="product-category__link" href="%s">%s</a>',
								$categoryURL,
								$category->name
							);

						$html .= '</div>';

					}

					// Product Price
					if($product->pricing_model === 'simple') {

						$html .= '<div class="product-price">';

							// Price label
							$html .= sprintf(
								'<span class="product-price__label">%s</span>',
								get_string('price_label', 'local_moodec')
							);

							// Price
							$html .= sprintf(
								'<span class="product-price__value">%s<span class="amount">%.2f</span></span>',
								local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')),
								$product->price
							);

						$html .= '</div>';

					} else {

						$attr = '';

						foreach ($product->variations as $v) {
							$attr .= sprintf('data-tier-%d="%.2f" ', $v->variation_id, $v->price);	
						}

						$firstVariation = reset($product->variations);

						$html .= sprintf(
							'<div class="product-price" %s><span class="product-price__label">%s</span> <span class="product-price__value">%s<span class="amount">%.2f</span></span></div>',
							$attr,
							get_string('price_label', 'local_moodec'),
							local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')),
							$firstVariation->price
						);
					}
					
					// Add to cart button states
					if (isloggedin() && is_enrolled(context_course::instance($product->courseid, MUST_EXIST))) {

						// Display 'enrolled' button
						$html .= sprintf(
							'<div class="product-single__form">
								<button class="product-form__add button--enrolled" disabled="disabled">%s</button>
							</div>',
							get_string('button_enrolled_label', 'local_moodec')
						);

					} else if (is_array($cart['courses']) && array_key_exists($product->courseid, $cart['courses'])) {

						// Display 'in cart' button
						$html .= sprintf(
							'<div class="product-single__form">
								<button class="product-form__add button--cart" disabled="disabled">%s</button>
							</div>',
							get_string('button_in_cart_label', 'local_moodec')
						);

					} else {

						// Check whether this is a simple or variable product
						if($product->pricing_model === 'simple') {

							// Display simple product 'add to cart' form
							$html .= sprintf(
								'<form action="%s" method="POST" class="product-single__form">
									<input type="hidden" name="action" value="addToCart">
									<input type="hidden" name="id" value="%d">
									<input type="submit" class="product-form__add" value="%s">
								</form>',
								new moodle_url('/local/moodec/pages/cart.php'),
								$product->courseid,
								get_string('button_add_label', 'local_moodec')
							);

						} else {

							// Variable product selection 'add to cart' form
							$html .= sprintf(
								'<form action="%s" method="POST" class="product-single__form">
									<input type="hidden" name="action" value="addVariationToCart">
									<select class="product-tier" name="variation">',
								new moodle_url('/local/moodec/pages/cart.php')
							);

							// output variations
							foreach($product->variations as $variation) {

								$html .= sprintf(
									'<option value="%d">%s</option>',
									$variation->variation_id,
									$variation->name
								);

							}

							// output rest of the form
							$html .= sprintf(
								'	</select>
									<input type="hidden" name="id" value="%d">
									<input type="submit" class="product-form__add" value="%s">
								</form>',
								$product->courseid,
								get_string('button_add_label', 'local_moodec')
							);

						}
					}


				// close additional product details wrapper
				$html .= '</div>';

			// close product details wrapper
			$html .= '</div>';

		// close product single wrapper
		$html .= '</div>';

		return $html;
	}
}