<?php
/**
 * Moodec Language file
 *
 * @package     local
 * @subpackage  local_moodec
 * @author   	Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Moodec';

// SETTINGS
$string['moodec_pages'] = 'Page settings';
$string['moodec_settings'] = 'General settings';
$string['moodec_course_settings'] = 'Edit product settings';
$string['moodec_product_settings'] = 'Edit product settings';
$string['page_setting_heading_catalogue'] = 'Catalogue page';
$string['page_setting_heading_product'] = 'Product page';

$string['page_catalogue_show_description'] = 'Show course description';
$string['page_catalogue_show_description_desc'] = 'This will show the course description excerpt on the catalogue list page';
$string['page_catalogue_show_additional_description'] = 'Show additional description';
$string['page_catalogue_show_additional_description_desc'] = 'This will show the additional description excerpt on the catalogue list page';
$string['page_catalogue_show_duration'] = 'Show product enrolment duration';
$string['page_catalogue_show_duration_desc'] = 'This will show the product enrolment duration excerpt on the catalogue list page';
$string['page_catalogue_show_image'] = 'Show product image';
$string['page_catalogue_show_image_desc'] = 'This will show the product image on the catalogue list page';
$string['page_catalogue_show_category'] = 'Show product category';
$string['page_catalogue_show_category_desc'] = 'This will show the product category on the catalogue list page';
$string['page_catalogue_show_price'] = 'Show product price';
$string['page_catalogue_show_price_desc'] = 'This will show the product price on the catalogue list page';
$string['page_catalogue_show_button'] = 'Show add to cart button';
$string['page_catalogue_show_button_desc'] = 'This will show the add to cart button on the catalogue list page';

$string['page_product_enable'] = 'Enable this page';
$string['page_product_enable_desc'] = 'This will allow users to view individual products and add links to the navigation block';
$string['page_product_show_image'] = 'Show product image';
$string['page_product_show_image_desc'] = 'This will show the product image on the product page';
$string['page_product_show_description'] = 'Show course description';
$string['page_product_show_description_desc'] = 'This will show the course description excerpt on the product page';
$string['page_product_show_additional_description'] = 'Show product\'s additional description';
$string['page_product_show_additional_description_desc'] = 'This will show the product\'s additional description excerpt on the product page';
$string['page_product_show_category'] = 'Show course category';
$string['page_product_show_category_desc'] = 'This will show the course category on the product page';
$string['page_product_show_related_products'] = 'Show related products';
$string['page_product_show_related_products_desc'] = 'This will show the related products on the product page';

$string['businessemail'] = 'PayPal business email';
$string['businessemail_desc'] = 'The email address of your business PayPal account';
$string['currency'] = 'Currency';
$string['pagination'] = 'Courses per page';
$string['pagination_desc'] = 'The number of courses to be displayed per page in the catalogue';

$string['payment_title'] = 'Payment Gateways';
$string['payment_enable'] = 'Enable';
$string['payment_enable_desc'] = 'Enable this payment gateway';
$string['payment_dps_title'] = 'DPS';
$string['payment_dps_userid'] = 'PxPay User ID';
$string['payment_dps_userid_desc'] = 'Enter your DPS PxPay merchant user id';
$string['payment_dps_key'] = 'PxPay Key';
$string['payment_dps_key_desc'] = 'Enter your DPS PxPay merchant key';
$string['payment_dps_sandbox'] = 'Use sandbox mode';
$string['payment_dps_sandbox_desc'] = 'This will enable DPS sandbox mode for the gateway. You will need to enter a development user and key to test.';
$string['payment_paypal_title'] = 'Paypal';
$string['payment_paypal_email'] = 'Email';
$string['payment_paypal_email_desc'] = 'Enter your Paypal business email address';
$string['payment_paypal_sandbox'] = 'Use sandbox mode';
$string['payment_paypal_sandbox_desc'] = 'This will enable Paypal sandbox for the gateway';

// Errors
$string['error_dpsinitiate'] = 'Could not initiate a transaction with the DPS payment server - please try again later.';
$string['error_invalid_name'] = 'Required field. Please enter a name.';
$string['error_invalid_price'] = 'Invalid format: please enter a single number only, such as 20 or 19.99 - no currency symbols or letters are allowed.';
$string['error_invalid_duration'] = 'Invalid format: please enter a single number, indicating the days for the duration of the course. The number 0 represents an unlimited duration.';

$string['moodec:viewalltransactions'] = 'View all Moodec transactions';
$string['messageprovider:payment_gateway'] = 'Notifications from the Moodec Payment Gateway';

// Pages

// CATALOGUE
$string['catalogue_title'] = 'Store';
$string['catalogue_empty'] = 'No products available.';
$string['catalogue_enrolment_duration_label'] = 'Course duration:';
$string['filter_category_label'] = 'Category:';
$string['filter_sort_label'] = 'Sort by:';
$string['filter_sort_default_asc'] = 'Default';
$string['filter_sort_fullname_asc'] = 'Course Title: A - Z';
$string['filter_sort_fullname_desc'] = 'Course Title: Z - A';
$string['filter_sort_price_asc'] = 'Price: Low to High';
$string['filter_sort_price_desc'] = 'Price: High to Low';
$string['filter_sort_duration_asc'] = 'Duration: Low to High';
$string['filter_sort_duration_desc'] = 'Duration: High to Low';
$string['course_list_category_label'] = 'Category:';

// PRODUCT
$string['product_title'] = '{$a->coursename}';
$string['enrolment_duration_label'] = 'Course duration:';
$string['price_label'] = 'Price:';
$string['product_related_label'] = 'Related Products';
$string['product_related_button_label'] = 'View details';

// CHECKOUT
$string['checkout_title'] = 'Checkout';
$string['checkout_message'] = 'Please review your cart once more before purchasing.';
$string['checkout_removed_courses_label'] = 'The following courses have been removed from your cart as they are either invalid, or you are already enrolled in them:';
$string['checkout_total'] = 'Total:';
$string['checkout_guest_message'] = 'You cannot be logged in as guest to purchase courses! Please log out and create your own account to continue.';

// CART
$string['cart_title'] = 'Cart';
$string['cart_total'] = 'Total:';
$string['cart_empty_message'] = 'Your cart is empty!';

// TRANSACTIONS
$string['transactions_title'] = 'Transactions';
$string['transaction_view_label'] = 'View';
$string['transaction_status_complete'] = 'Complete';
$string['transaction_status_failed'] = 'Failed';
$string['transaction_status_pending'] = 'Pending';
$string['transaction_status_not_submitted'] = 'Not submitted';

$string['transaction_table_empty'] = 'No transactions to display.';

$string['transaction_field_date'] = 'Date';
$string['transaction_field_id'] = 'Transaction ID';
$string['transaction_field_user'] = 'User';
$string['transaction_field_amount'] = 'Amount';
$string['transaction_field_items'] = '# Items';
$string['transaction_field_gateway'] = 'Gateway';
$string['transaction_field_txn'] = 'Txn ID';
$string['transaction_field_status'] = 'Status';
$string['transaction_field_actions'] = '';
$string['transaction_field_actions_course'] = 'Go to course';

$string['transaction_filter_date'] = 'Date range';
$string['transaction_filter_date_from'] = 'From';
$string['transaction_filter_date_to'] = 'To';
$string['transaction_filter_gateway'] = 'Gateway';
$string['transaction_filter_status'] = 'Status';
$string['transaction_filter_button_filter'] = 'Filter';
$string['transaction_filter_button_reset'] = 'Reset';

$string['transaction_view_title'] = 'Transaction #{$a->id}';
$string['transaction_section_details'] = 'Details';
$string['transaction_section_items'] = 'Items';
$string['transaction_section_error'] = 'Error details';

// Buttons
$string['button_add_label'] = 'Add to cart';
$string['button_remove_label'] = 'Remove';
$string['button_checkout_label'] = 'Proceed to checkout';
$string['button_cart_empty_label'] = 'Empty cart';
$string['button_paypal_label'] = 'Pay with PayPal';
$string['button_dps_label'] = 'Pay via DPS';
$string['button_return_store_label'] = 'Return to store';
$string['button_logout_label'] = 'Logout';
$string['button_enrolled_label'] = 'Already enrolled';
$string['button_in_cart_label'] = 'In cart';

// Lib
$string['enrolment_duration_unlimited'] = 'Unlimited';
$string['enrolment_duration_year'] = 'year';
$string['enrolment_duration_year_plural'] = 'years';
$string['enrolment_duration_month'] = 'month';
$string['enrolment_duration_month_plural'] = 'months';
$string['enrolment_duration_week'] = 'week';
$string['enrolment_duration_week_plural'] = 'weeks';
$string['enrolment_duration_day'] = 'day';
$string['enrolment_duration_day_plural'] = 'days';

// Edit Product form
$string['edit_product_form_title'] = 'Product settings for {$a->name}';
$string['product_enabled'] = 'Enable';
$string['product_enabled_label'] = 'Toggle whether this product is shown in the store and able to be bought';
$string['product_description'] = 'Description';
$string['product_description_help'] = 'The Moodec product description. This can be combined with the default Course summary and used as additional information, or as the only means to display information on the product page.';
$string['product_tags'] = 'Product tags';
$string['product_tags_help'] = 'List the keyword tags, comma separated, which will be used when performing a search.';
$string['product_type'] = 'Product type';
$string['product_type_help'] = 'Select the type of product this is. Simple products have a single price, duration and group that can be assigned to it. Variable products can have up to 10 variations specified - each with its own name, price and duration.';
$string['product_type_simple_label'] = 'Simple';
$string['product_type_variable_label'] = 'Variable';
$string['product_variation_header'] = 'Variation {$a->count}';
$string['product_variation_enabled'] = 'Enable';
$string['product_variation_enabled_label'] = 'Toggle whether this variation is active or not';
$string['product_variation_count'] = 'Number of variations';
$string['product_variation_count_help'] = 'Select the number of variations for variable product types, up to 10. Simple product types only have 1 variation.';
$string['product_variation_name'] = 'Name';
$string['product_variation_name_help'] = 'The name to be displayed in the store for this variation. This is not shown for Simple product types.';
$string['product_variation_price'] = 'Price';
$string['product_variation_price_help'] = 'The price, set to 2 decimal places. Do not include currency symbol.';
$string['product_variation_duration'] = 'Duration';
$string['product_variation_duration_help'] = 'Enter the number of days for the course enrolment duration. A value of 0 will set an unlimited enrolment duration.';
$string['product_variation_group'] = 'Group';
$string['product_variation_group_help'] = 'Select a course group for the user to be assigned when they purchase this variation.';
$string['product_variations_update'] = 'Update variations form';
$string['product_variation_group_none'] = 'No group';