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

	function catalogue($products) {

		$html = '';

		if (is_array($products) && 0 < count($products)) {

			// OPEN PRODUCT LIST
			$html .= sprintf('<div class="product-list">');

			foreach ($products as $product) {
				
				$html .= $this->catalogue_item($product);

			}

			// CLOSE PRODUCT LIST
			$html .= sprintf('</div>');

		} else {

			$html .= sprintf(
				'<div class="catalogue-empty">%s</div>',
				get_string(
					'catalogue_empty',
					'local_moodec'
				)
			);
		}

		return $html;
	}

	function catalogue_item($product) {
		global $CFG;

		// Require Moodec lib
		require_once $CFG->dirroot . '/local/moodec/lib.php';

		// OPEN PRODUCT ITEM
		$html = sprintf('<div class="product-item">');

			// OPEN PRODUCT DETAILS
			$html .= sprintf('<div class="product-details">');

				// output product image
				if (!!get_config('local_moodec', 'page_catalogue_show_image')) {
					$html .= $this->product_image($product);
				}

				// OPEN PRODUCT DETAILS WRAPPER
				$html .= sprintf('<div class="product-details__wrapper">');

					// output page title
					$html .= $this->product_title($product);

					//output course summary
					if (!!get_config('local_moodec', 'page_catalogue_show_description')) {
						$html .= sprintf(
							'<div class="product-summary">%s</div>',
							$product->get_summary()
						);
					}

					// output product description
					if (!!get_config('local_moodec', 'page_catalogue_show_additional_description')) {
						$html .= sprintf(
							'<div class="product-summary additional">%s</div>',
							$product->get_description()
						);
					}

					// output product duration
					if(!!get_config('local_moodec', 'page_catalogue_show_duration')) {
						$html .= $this->product_duration($product);
					}	

					// output product category			
					if (!!get_config('local_moodec', 'page_catalogue_show_category')) {
						$html .= $this->product_category($product);
					}

				// CLOSE PRODUCT DETAILS WRAPPER
				$html .= sprintf('</div>');

			// CLOSE PRODUCT DETAILS
			$html .= sprintf('</div>');
			
			// OPEN PRODUCT ACTIONS
			$html .= sprintf('<div class="product-actions">');

				//output price
				if (!!get_config('local_moodec', 'page_catalogue_show_price')) {

					if($product->get_type() === PRODUCT_TYPE_SIMPLE) { 
						
						$html .= sprintf(
							'<h4 class="product-price">%s</h4>',
							local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . $product->get_price()
						);

					} else {
						$attr = '';

						foreach ($product->get_variations() as $v) {
							$attr .= sprintf('data-tier-%d="%.2f" ', $v->get_id(), $v->get_price());
						}

						list($firstVariation) = array_values($product->get_variations());

						$html .= sprintf('<h4 class="product-price" %s>%s<span class="amount">%.2f</span></h4>',
							$attr,
							local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')),
							$firstVariation->get_price()
						);

					}
				}

				// output button
				if (!!get_config('local_moodec', 'page_catalogue_show_button')) {
					$html .= $this->product_button($product);
				}

			// CLOSE PRODUCT ACTIONS
			$html .= sprintf('</div>');
		
		// CLOSE PRODUCT ITEM
		$html .= sprintf('</div>');

		return $html;
	}


	/**
	 * Outputs the information for the single product page
	 * @param  product 	$product  	object containing all the product information
	 * @return string          		the html output
	 */
	function single_product($product) {
		global $CFG, $DB;

		// Require Moodec lib
		require_once $CFG->dirroot . '/local/moodec/lib.php';

		$cart = new MoodecCart();

		// Product single wrapper
		$html = '<div class="product-single">';

			// Product title
			$html .= sprintf(
				'<h1 class="product__title">%s</h1>',
				get_string('product_title', 'local_moodec', array('coursename' => $product->get_fullname()) )
			);

			// Product/course image
			if (!!get_config('local_moodec', 'page_product_show_image')) {
				$html .= $this->product_image($product);
			}

			// Product details wrapper
			$html .= '<div class="product-details">';

				// Product description
				if (!!get_config('local_moodec', 'page_product_show_description')) {

					$description = $product->get_summary();

					if( 0 < strlen($description) ) {
						$html .= sprintf(
							'<div class="product-description">%s</div>',
							$description
						);
					}
				}

				// Product additional description
				if (!!get_config('local_moodec', 'page_product_show_additional_description')) {

					if( $product->has_description() ) {
						$html .= sprintf(
							'<div class="additional-info">%s</div>',
							$product->get_description()
						);
					}
				}

				// Additional product details wrapper
				$html .= '<div class="product-details__additional">';

					// Product duration
					$html .= '<div class="product-duration__wrapper">';

						$html .= sprintf(
							'<span class="product-duration__label">%s</span>',
							get_string('enrolment_duration_label', 'local_moodec')
						);

						if( $product->get_type() === PRODUCT_TYPE_SIMPLE) {

							$html .= sprintf(
								'<span class="product-duration">%s</span>',
								$product->get_duration()
							);
						} else {
							$attr = '';

							foreach ($product->get_variations() as $v) {
								$attr .= sprintf('data-tier-%d="%s" ',
									$v->get_id(),
									$v->get_duration()
								);
							}

							list($firstVariation) = array_values($product->get_variations());

							$html .= sprintf(
								'<span class="product-duration" %s>%s</span>',
								$attr,
								$firstVariation->get_duration()
							);
						}

					$html .= '</div>';


					// Product category
					if (!!get_config('local_moodec', 'page_product_show_category')) {

						// Get the category the product belongs to
						$category = $DB->get_record(
							'course_categories',
							array(
								'id' => $product->get_category_id()
							)
						);

						// Get the url to link to the category page with the filter active
						$categoryURL = new moodle_url(
							$CFG->wwwroot . '/local/moodec/pages/catalogue.php',
							array(
								'category' => $product->get_category_id()
							)
						);

						// Category wrapper
						$html .= '<div class="product-category__wrapper">';

							// Category label
							$html .= sprintf(
								'<span class="product-category__label">%s</span> ',
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
					if($product->get_type() === PRODUCT_TYPE_SIMPLE) {

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
								$product->get_price()
							);

						$html .= '</div>';

					} else {

						$attr = '';

						foreach ($product->get_variations() as $v) {
							$attr .= sprintf('data-tier-%d="%.2f" ', $v->get_id(), $v->get_price() );
						}

						list($firstVariation) = array_values($product->get_variations());

						$html .= sprintf(
							'<div class="product-price" %s><span class="product-price__label">%s</span> <span class="product-price__value">%s<span class="amount">%.2f</span></span></div>',
							$attr,
							get_string('price_label', 'local_moodec'),
							local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')),
							$firstVariation->get_price()
						);
					}

					// Add to cart button states
					if (isloggedin() && is_enrolled(context_course::instance($product->get_course_id(), MUST_EXIST))) {

						// Display 'enrolled' button
						$html .= sprintf(
							'<div class="product-single__form">
								<button class="product-form__add button--enrolled" disabled="disabled">%s</button>
							</div>',
							get_string('button_enrolled_label', 'local_moodec')
						);

					} else if ( $cart->check($product->get_id()) ) {

						// Display 'in cart' button
						$html .= sprintf(
							'<div class="product-single__form">
								<a href="%s" class="product-form__add btn button--cart">%s</a>
							</div>',
							new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'),
							get_string('button_in_cart_label', 'local_moodec')
						);

					} else {

						// Check whether this is a simple or variable product
						if($product->get_type() === PRODUCT_TYPE_SIMPLE) {

							// Display simple product 'add to cart' form
							$html .= sprintf(
								'<form action="%s" method="POST" class="product-single__form">
									<input type="hidden" name="action" value="addToCart">
									<input type="hidden" name="id" value="%d">
									<input type="submit" class="product-form__add" value="%s">
								</form>',
								new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'),
								$product->get_id(),
								get_string('button_add_label', 'local_moodec')
							);

						} else {

							// Variable product selection 'add to cart' form
							$html .= sprintf(
								'<form action="%s" method="POST" class="product-single__form">
									<input type="hidden" name="action" value="addVariationToCart">
									<select class="product-tier" name="variation">',
								new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php')
							);

							// output variations
							foreach($product->get_variations() as $variation) {

								$html .= sprintf(
									'<option value="%d">%s</option>',
									$variation->get_id(),
									$variation->get_name()
								);

							}

							// output rest of the form
							$html .= sprintf(
								'	</select>
									<input type="hidden" name="id" value="%d">
									<input type="submit" class="product-form__add" value="%s">
								</form>',
								$product->get_id(),
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


	/**
	 * Outputs the HTML to display related products given a product
	 * @param  product 	$product  	the product for which to find the related ones
	 * @return string           	the HTML output
	 */
	function related_products($product) {
		global $CFG;

		// Require Moodec lib
		require_once $CFG->dirroot . '/local/moodec/lib.php';

		$html = '';
		$iterator = 0;

		// Get products related to the product passed to us
		$products = $product->get_related();

		// We only output anything if there ARE related products
		if (is_array($products) && 0 < count($products)) {

			// Output section wrapper
			$html .= '<div class="related-products">';

				// Show the section title
				$html .= sprintf(
					'<h2 class="related-products__title">%s</h2>',
					get_string('product_related_label', 'local_moodec')
				);

				// Output container to hold product items
				$html .= '<ul class="grid-container">';

				foreach ($products as $p) {

					$html .= '<li class="grid-item">';

						// Product image
						$html .= $this->product_image($p);

						// Product title
						$html .= sprintf(
							'<h5>%s</h5>',
							$p->get_fullname()
						);

						// Product link
						$html .= sprintf(
							'<a href="%s" class="product-view btn">%s</a>',
							new moodle_url($CFG->wwwroot . '/local/moodec/pages/product.php', array('id' => $p->get_id()) ),
							get_string('product_related_button_label', 'local_moodec')
						);

					$html .= '</li>';

					// Iterator limits only 3 products to be shown
					$iterator++;
					if ($iterator > 2) {
						break;
					}
				}

				// Close item container
				$html .= '</ul>';

			// Close section wrapper
			$html .= '</div>';
		}

		return $html;
	}


	/**
	 * Returns the HTML for the product image
	 * @param  product 	$product 	the product for the image to be retrieved
	 * @return string          		the HTML output
	 */
	function product_image($product) {
		global $CFG;

		// Require Moodec lib
		require_once $CFG->dirroot . '/local/moodec/lib.php';

		$html = '';
		$imageURL = $product->get_image_url();

		if ( !!$imageURL ) {
			$html = sprintf(
				'<img src="%s" alt="%s" class="product-image">',
				$imageURL,
				$product->get_fullname()
			);
		}

		return $html;
	}

	function product_title($product) {
		global $CFG;

		$html = sprintf('<h3 class="product-title">');

		if (!!get_config('local_moodec', 'page_product_enable')) {
			$html .= sprintf(
				'<a href="%s">%s</a>',
				new moodle_url($CFG->wwwroot . '/local/moodec/pages/product.php', array('id' => $product->get_id())),
				$product->get_fullname()
			);
		} else {
			$html .= $product->get_fullname();
		}
		
		$html .= sprintf('</h3>');

		return $html;
	}

	function product_duration($product) {

		// Product duration
		$html = '<div class="product-duration__wrapper">';

			// Product duration label
			$html .= sprintf(
				'<span class="product-duration__label">%s</span> ',
				get_string('catalogue_enrolment_duration_label', 'local_moodec')
			);

			if( $product->get_type() === PRODUCT_TYPE_SIMPLE) {
				$html .= sprintf(
					'<span class="product-duration">%s</span>',
					$product->get_duration()
				);
			} else {
				$attr = '';

				foreach ($product->get_variations() as $v) {
					$attr .= sprintf(
						'data-tier-%d="%s" ',
						$v->get_id(),
						$v->get_duration()
					);
				}

				list($firstVariation) = array_values($product->get_variations());

				$html .= sprintf(
					'<span class="product-duration" %s>%s</span>',
					$attr,
					$firstVariation->get_duration()
				);
			}
		
		// Product duration wrapper close
		$html .= sprintf('</div>');

		return $html;
	}

	function product_category($product) {
		global $CFG, $DB;

		// Get the category the product belongs to
		$category = $DB->get_record(
			'course_categories',
			array(
				'id' => $product->get_category_id()
			)
		);

		// Get the url to link to the category page with the filter active
		$categoryURL = new moodle_url(
			$CFG->wwwroot . '/local/moodec/pages/catalogue.php',
			array(
				'category' => $product->get_category_id()
			)
		);

		// Category wrapper
		$html = '<div class="product-category__wrapper">';

			// Category label
			$html .= sprintf(
				'<span class="product-category__label">%s</span> ',
				get_string('course_list_category_label', 'local_moodec')
			);

			// Category link
			$html .= sprintf(
				'<a class="product-category__link" href="%s">%s</a>',
				$categoryURL,
				$category->name
			);

		$html .= '</div>';

		return $html;
	}

	function product_button($product) {
		global $CFG;

		$cart = new MoodecCart();

		if (isloggedin() && is_enrolled(context_course::instance($product->get_course_id(), MUST_EXIST))) {
			
			$html = sprintf(
				'<div class="product-form"><button class="product-form__add button--enrolled" disabled="disabled">%s</button></div>',
				get_string('button_enrolled_label', 'local_moodec')
			);

		} else if( $cart->check($product->get_id()) ){

			// Display 'in cart' button
			$html = sprintf(
				'<div class="product-single__form">
					<a href="%s" class="product-form__add btn button--cart">%s</a>
				</div>',
				new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'),
				get_string('button_in_cart_label', 'local_moodec')
			);

		} else {

			// Check whether this is a simple or variable product
			if($product->get_type() === PRODUCT_TYPE_SIMPLE) {

				// Display simple product 'add to cart' form
				$html = sprintf(
					'<form action="%s" method="POST" class="product-form">
						<input type="hidden" name="action" value="addToCart">
						<input type="hidden" name="id" value="%d">
						<input type="submit" class="product-form__add" value="%s">
					</form>',
					new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'),
					$product->get_id(),
					get_string('button_add_label', 'local_moodec')
				);

			} else {

				// Variable product selection 'add to cart' form
				$html = sprintf(
					'<form action="%s" method="POST" class="product-form">
						<input type="hidden" name="action" value="addVariationToCart">
						<select class="product-tier" name="variation">',
					new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php')
				);

				// output variations
				foreach($product->get_variations() as $variation) {

					$html .= sprintf(
						'<option value="%d">%s</option>',
						$variation->get_id(),
						$variation->get_name()
					);

				}

				// output rest of the form
				$html .= sprintf(
					'	</select>
						<input type="hidden" name="id" value="%d">
						<input type="submit" class="product-form__add" value="%s">
					</form>',
					$product->get_id(),
					get_string('button_add_label', 'local_moodec')
				);

			}
		}

		return $html;
	}

	/**
	 * Outputs the HTML for the pagination
	 * @param  array  $products    An array of the products to be paginated
	 * @param  integer $currentPage The index of the current page
	 * @param  int  $category    the category ID
	 * @param  string  $sort        the string sorting parameter
	 * @return string               the HTML output
	 */
	function pagination($products, $currentPage = 0, $category = null, $sort = null) {
		global $CFG;

		$html = '';

		// Calculate total page count
		$pageCount = ceil(count($products) / get_config('local_moodec', 'pagination'));

		// Only output pagination when there is more than one page
		if (1 < $pageCount && $currentPage <= $pageCount ) {

			$html .= sprintf('<div class="pagination-bar"><ul class="pagination">');

			$params = array();

			if ($sort !== null) {
				$params['sort'] = $sort;
			}

			if ($category !== null && !!$category ) {
				$params['category'] = $category;
			}

			for ($paginator = 1; $paginator <= $pageCount; $paginator++) {
				$params['page'] = $paginator;

				$html .= sprintf('<li class="page-item"><a href="%s" %s>%d</a></li>',
					new moodle_url($CFG->wwwroot . '/local/moodec/pages/catalogue.php', $params),
					$paginator === $currentPage ? 'class="active"' : '',
					$paginator
				);

			}

			$html .= sprintf('</ul></div>');
		}

		return $html;
	}

	/**
	* Returns the HTML for the Moodec cart
	* @param 	array 		cart
	* @param 	bool 		is it the checkout page
	* @return 	string 		the HTML output
	*/
	function moodec_cart($cart) {
		global $CFG, $USER;

		// Require Moodec lib
		require_once $CFG->dirroot . '/local/moodec/lib.php';

		// Initialise vars
		$html = '';

		$html .= '<div class="cart-overview">';

		if ( $cart->is_empty() === false ) {

			$html .= '<ul class="products">';

			// Go through each product in the cart
			foreach ($cart->get() as $pID => $vID) {

				$product = local_moodec_get_product($pID);

				$html .= '<li class="product-item">';

					// Product title and variation
					$html .= sprintf(
						'<h4 class="product-title"><a href="%s">%s</a></h4>',
						new moodle_url($CFG->wwwroot . '/local/moodec/pages/product.php', array( 'id' => $product->get_id() )),
						$product->get_type() === PRODUCT_TYPE_SIMPLE ? $product->get_fullname() : $product->get_fullname() . ' - ' . $product->get_variation($vID)->get_name()
					);

					// Product price
					$html .= sprintf(
						'<div class="product-price">%s%.02f</div>',
						local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')),
						$product->get_type() === PRODUCT_TYPE_SIMPLE ? $product->get_price() : $product->get_variation($vID)->get_price()
					);

					// 'Remove' from cart button
					$html .= sprintf(
						'<form class="product__form" action="" method="POST">
							<input type="hidden" name="id" value="%d">
							<input type="hidden" name="action" value="removeFromCart">
							<input class="form__submit" type="submit" value="%s">
						</form>',
						$product->get_id(),
						get_string('button_remove_label', 'local_moodec')
					);

				$html .= '</li>';
			}

			$html .= '</ul>';

			// Output cart summary section
			$html .= '<div class="cart-summary">';

				// Cart total price
				$html .= sprintf(
					'<h3 class="cart-total__label">%s</h3><h3 class="cart-total">%s%01.2f</h3>',
					get_string('cart_total', 'local_moodec'),
					local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')),
					$cart->get_total(false)
				);

			$html .= '</div>';

			// Get the cart action HTML
			$html .= $this->cart_actions();

		} else {

			// Empty cart message
			$html .= sprintf(
				'<p class="cart-mesage--empty">%s</p>',
				get_string('cart_empty_message', 'local_moodec')
			);

			// Return to store button
			$html .= $this->return_to_store_action();

		}

		$html .= '</div>';

		return $html;
	}

	/**
	* Returns the HTML for the Moodec cart review on the checkout page
	* @param 	array 		cart
	* @param 	bool 		is it the checkout page
	* @return 	string 		the HTML output
	*/
	function cart_review($cart, $removedProducts = array()) {
		global $CFG, $USER;

		// Require Moodec lib
		require_once $CFG->dirroot . '/local/moodec/lib.php';

		// OPEN cart-overview
		$html = '<div class="cart-overview">';

		// Output cart review message
		$html .= sprintf(
			'<p class="cart-review__message">%s</p>',
			get_string('checkout_message', 'local_moodec')
		);

		if (!!$removedProducts && is_array($removedProducts)) {
			
			$html .= '<div class="cart-review__wrapper">';

			$html .= sprintf(
				'<p class="cart-review__message--removed">%s</p>', 
				get_string('checkout_removed_courses_label', 'local_moodec')
			);

			$html .= '<ul>';

			foreach ($removedProducts as $p) {
				$thisProduct = local_moodec_get_product($p);
				
				$html .= sprintf(
					'<li class="cart-review__item--removed">%s</li>', 
					$thisProduct->get_fullname()
				);
			}

			$html .= '</ul></div>';

		}
		

		if ( $cart->is_empty() === false ) {

			$html .= '<ul class="products">';

			// Go through each product in the cart
			foreach ($cart->get() as $pID => $vID) {

				$product = local_moodec_get_product($pID);

				$html .= '<li class="product-item">';

					// Product title and variation
					$html .= sprintf(
						'<h4 class="product-title"><a href="%s">%s</a></h4>',
						new moodle_url($CFG->wwwroot . '/local/moodec/pages/product.php', array( 'id' => $product->get_id() )),
						$product->get_type() === PRODUCT_TYPE_SIMPLE ? $product->get_fullname() : $product->get_fullname() . ' - ' . $product->get_variation($vID)->get_name()
					);

					// Product price
					$html .= sprintf(
						'<div class="product-price">%s%.02f</div>',
						local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')),
						$product->get_type() === PRODUCT_TYPE_SIMPLE ? $product->get_price() : $product->get_variation($vID)->get_price()
					);

				$html .= '</li>';
			}

			$html .= '</ul>';

			// Output cart summary section
			$html .= '<div class="cart-summary">';

				// Cart total price
				$html .= sprintf(
					'<h3 class="cart-total__label">%s</h3><h3 class="cart-total">%s%01.2f</h3>',
					get_string('cart_total', 'local_moodec'),
					local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')),
					$cart->get_total(false)
				);

			$html .= '</div>';

			// Return to store button
			$html .= $this->return_to_store_action();

			// Render all active Gateway options
			if( !!get_config('local_moodec', 'payment_dps_enable') ){
				$gatewayDPS = new MoodecGatewayDPS($cart->get_transaction_id());
				$html .= $gatewayDPS->render();
			}

			if( !!get_config('local_moodec', 'payment_paypal_enable') ){
				$gatewayPaypal = new MoodecGatewayPaypal($cart->get_transaction_id());
				$html .= $gatewayPaypal->render();
			}

		} else {

			// Empty cart message
			$html .= sprintf(
				'<p class="cart-mesage--empty">%s</p>',
				get_string('cart_empty_message', 'local_moodec')
			);

			// Return to store button
			$html .= $this->return_to_store_action();

		}

		// CLOSE cart-overview
		$html .= '</div>';

		return $html;
	}	


	/**
	 * Returns the HTML output for the catalogue page filter bar
	 * @param  int 		$c 		The catalogue ID
	 * @param  string 	$s  	The sorting string
	 * @return string   		HTML
	 */
	function filter_bar($c = null, $s= null) {

		$filters = array(
			'default-asc',
			'fullname-asc',
			'fullname-desc',
			'price-asc',
			'price-desc',
			'duration-asc',
			'duration-desc',
		);

		// open form wrapper
		$html = '<form action="" method="GET" class="filter-bar">';

			// Render category filter
			$html .= sprintf(
				'<div class="filter__category">
					<span>%s</span>
					<select name="category" id="category">
						%s
					</select>
				</div>',
				get_string('filter_category_label', 'local_moodec'),
				local_moodec_get_category_list($c)
			);

			// Render sorting filter
			$html .= sprintf(
				'<div class="filter__sort">
					<span>%s</span>
					<select name="sort" id="sort">',
				get_string('filter_sort_label', 'local_moodec')
			);

				// Output all options for filters
				foreach ($filters as $f) {
					
					// Category option
					$html .= sprintf(
						'<option value="%s" %s>%s</option>',
						$f,
						$s === $f ? 'selected="selected"' : '',
						get_string('filter_sort_' . str_replace('-', '_', $f), 'local_moodec')
					);

				}

			// close sort filter
			$html .= '</select></div>';

		// close filter-bar form
		$html .= '</form>';

		return $html;

	}


	/**
	 * Returns the HTML output for the standard cart actions
	 * @return string  	HTML
	 */	
	function cart_actions() {
		global $CFG;

		$html = '<div class="cart-actions">';

			// Return to store button
			$html .= $this->return_to_store_action();

			// Proceed to checkout button
			$html .= sprintf(
				'<a href="%s" class="btn proceed-to-checkout">%s</a>',
				new moodle_url($CFG->wwwroot . '/local/moodec/pages/checkout.php'),
				get_string('button_checkout_label', 'local_moodec')
			);

			// Empty cart button
			$html .= sprintf(
				'<form action="%s" method="POST" class="empty-cart">
					<input type="hidden" name="action" value="emptyCart">
					<input type="submit" value="%s">
				</form>',
				new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php'),
				get_string('button_cart_empty_label', 'local_moodec')
			);

		$html .= '</div>';

		return $html;
	}


	/**
	 * Returns the HTML output for the return to store button
	 * @return string 	HTML
	 */	
	function return_to_store_action() {
		global $CFG;

		// Return to store button
		return sprintf(
			'<a href="%s" class="btn back-to-shop">%s</a>',
			new moodle_url($CFG->wwwroot . '/local/moodec/pages/catalogue.php'),
			get_string('button_return_store_label', 'local_moodec')
		);
	}

	function single_transaction($transaction) {
		global $CFG, $DB;

		$fieldDate = date('H:i:s d/m/Y', $transaction->get_date());

		$user = $DB->get_record('user', array('id' => $transaction->get_user_id() ));
		$fieldUser = sprintf(
			'<a href="%s">%s %s</a>',
			new moodle_url($CFG->wwwroot . '/user/profile.php', array( 'id'=> $user->id )),
			$user->firstname,
			$user->lastname
		);

		$fieldAmount = local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . number_format($transaction->get_cost(), 2, '.', ','); 
		$fieldItemCount = count($transaction->get_items());

		$html = sprintf(
			'<div class="moodec-transaction__details span5 desktop-first-column">
				<h4>%s</h4>
				<div class="row-fluid">
					<div class="column span3"><strong>%s:</strong></div>
					<div class="column span9">%s</div>
				</div>
				<div class="row-fluid">
					<div class="column span3"><strong>%s:</strong></div>
					<div class="column span9">%s</div>
				</div>
				<div class="row-fluid">
					<div class="column span3"><strong>%s:</strong></div>
					<div class="column span9">%s</div>
				</div>
				<div class="row-fluid">
					<div class="column span3"><strong>%s:</strong></div>
					<div class="column span9">%s</div>
				</div>
				<div class="row-fluid">
					<div class="column span3"><strong>%s:</strong></div>
					<div class="column span9">%s</div>
				</div>
				<div class="row-fluid">
					<div class="column span3"><strong>%s:</strong></div>
					<div class="column span9">%s</div>
				</div>
				<div class="row-fluid">
					<div class="column span3"><strong>%s:</strong></div>
					<div class="column span9">%s</div>
				</div>
			</div>',
			get_string('transaction_section_details', 'local_moodec'),
			get_string('transaction_field_id', 'local_moodec'),
			$transaction->get_id(),
			get_string('transaction_field_user', 'local_moodec'),
			$fieldUser,
			get_string('transaction_field_amount', 'local_moodec'),
			$fieldAmount,
			get_string('transaction_field_date', 'local_moodec'),
			$fieldDate,
			get_string('transaction_field_gateway', 'local_moodec'),
			$transaction->get_gateway(true),
			get_string('transaction_field_txn', 'local_moodec'),
			$transaction->get_txn_id(),
			get_string('transaction_field_status', 'local_moodec'),
			$transaction->get_status(true)
		);

		$html .= sprintf(
			'<div class="moodec-transaction__items span7 pull-right">
				<h4>%s (%s)</h4>',
			get_string('transaction_section_items', 'local_moodec'),
			count($transaction->get_items())
		);

		if( 0 < count($transaction->get_items())) {

			foreach ($transaction->get_items() as $item) {
				
				$product = local_moodec_get_product($item->get_product_id());
				$name = $product->get_fullname();

				if( $product->get_type() === PRODUCT_TYPE_VARIABLE ) {
					$name .= ' - ' . $product->get_variation($item->get_variation_id())->get_name();
				}

				$html .= sprintf(
					'<div class="row-fluid">
						<div class="column span8"><a href="%s">%s</a></div>
						<div class="column span2">%s</div>
						<div class="column span2 align-right"><a href="%s">%s</a></div>
					</div>',
					new moodle_url($CFG->wwwroot .'/local/moodec/pages/product.php', array('id' => $item->get_product_id() )),
					$name,
					local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . number_format($item->get_cost(), 2, '.', ','),
					new moodle_url($CFG->wwwroot .'/course/view.php', array('id' => $product->get_course_id())),
					get_string('transaction_field_actions_course', 'local_moodec')
				);

			}
		}

		$html .= '</div>';
		
		return $html;
	}

	function transaction_filter($params = array(), $pageURL = '') {

		$html = '';

		$html .= sprintf('<form action="" class="transaction__filter">');

		$html .= sprintf(
			'<fieldset class="filterset__date">
				<legend>%s</legend>',
			get_string('transaction_filter_date', 'local_moodec')
		);

			$html .= sprintf(
				'<div class="span6 desktop-first-column">
					<label>%s</label><input type="date" name="date-from" max="%s" value="%s">
				</div>',
				get_string('transaction_filter_date_from', 'local_moodec'),
				date('Y-m-d', time()),
				isset($params['date-from']) ? $params['date-from'] : date('Y-m-d', strtotime("1 month ago"))
			);

			$html .= sprintf(
				'<div class="span6">
					<label>%s</label><input type="date" name="date-to" max="%s" value="%s">
				</div>',
				get_string('transaction_filter_date_to', 'local_moodec'),
				date('Y-m-d', strtotime("+1 day")),
				isset($params['date-to']) ? $params['date-to'] : date('Y-m-d', strtotime("+1 day"))
			);

		$html .= sprintf('</fieldset>');

		$html .= sprintf(
			'<fieldset class="filterset__gateway">
				<legend>%s</legend>',
			get_string('transaction_filter_gateway', 'local_moodec')
		);

			$html .= sprintf(
				'<div>
					<input type="checkbox" id="paypal" name="paypal" %s><label for="paypal">%s</label>
				</div>',
				(isset($params['paypal']) && !!$params['paypal'] ) ? 'checked' : '',
				get_string('payment_paypal_title', 'local_moodec')
			);

			$html .= sprintf(
				'<div>
					<input type="checkbox" id="dps" name="dps" %s><label for="dps">%s</label>
				</div>',
				(isset($params['dps']) && !!$params['dps'] ) ? 'checked' : '',
				get_string('payment_dps_title', 'local_moodec')
			);

		$html .= sprintf('</fieldset>');

		$html .= sprintf(
			'<fieldset class="filterset__status">
				<legend>%s</legend>',
			get_string('transaction_filter_status', 'local_moodec')
		);

			$html .= sprintf(
				'<div>
					<input type="checkbox" id="status-complete" name="status-complete" %s><label for="status-complete">%s</label>
					<div class="column--right">
						<input type="checkbox" id="status-failed" name="status-failed" %s><label for="status-failed">%s</label>
					</div>
				<div>',
				(isset($params['status-complete']) && !!$params['status-complete']) ? 'checked' : '',
				get_string('transaction_status_complete', 'local_moodec'),
				(isset($params['status-failed']) && !!$params['status-failed'] ) ? 'checked' : '',
				get_string('transaction_status_failed', 'local_moodec')
			);

			$html .= sprintf(
				'<div>
					<input type="checkbox" id="status-pending" name="status-pending" %s><label for="status-pending">%s</label>
					<div class="column--right">
						<input type="checkbox" id="status-nosubmit" name="status-nosubmit" %s><label for="status-nosubmit">%s</label>
					</div>
				</div>',
				(isset($params['status-pending']) && !!$params['status-pending'] ) ? 'checked' : '',
				get_string('transaction_status_pending', 'local_moodec'),
				(isset($params['status-nosubmit']) && !!$params['status-nosubmit'] ) ? 'checked' : '',
				get_string('transaction_status_not_submitted', 'local_moodec')
			);

		$html .= sprintf('</fieldset>');

		$html .= sprintf(
			'<input class="filter__button" type="submit" value="%s">
			<a href="%s" class="btn filter__button">%s</a>',
			get_string('transaction_filter_button_filter', 'local_moodec'),
			$pageURL,
			get_string('transaction_filter_button_reset', 'local_moodec')
		);

		$html .= sprintf('</form>');

		return $html;
	}
}