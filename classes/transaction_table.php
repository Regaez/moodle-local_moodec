<?php 
/**
 * Moodec Transaction
 *
 * @package     local
 * @subpackage  local_moodec
 * @author      Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Load Moodle config
require_once dirname(__FILE__) . '/../../../config.php';
// Load Tablelib lib
require_once $CFG->dirroot .'/lib/tablelib.php';
// Load Moodec lib
require_once $CFG->dirroot .'/local/moodec/lib.php';

class moodec_transaction_table extends table_sql {

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = array(
            'purchase_date',
            'id',
            'user_id',
            'amount',
            'items',
            'gateway',
            'txn_id',
            'status',
            'actions'
        );
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array(
            get_string('transaction_field_date', 'local_moodec')    ,   // 'Date'
            get_string('transaction_field_id', 'local_moodec'),         // 'Transaction ID',
            get_string('transaction_field_user', 'local_moodec'),       // 'User',
            get_string('transaction_field_amount', 'local_moodec'),     // 'Amount',
            get_string('transaction_field_items', 'local_moodec'),      // 'Number of items',
            get_string('transaction_field_gateway', 'local_moodec'),    // 'Gateway',
            get_string('transaction_field_txn', 'local_moodec'),
            get_string('transaction_field_status', 'local_moodec'),     // 'Status',
            get_string('transaction_field_actions', 'local_moodec'),     // 'Action'
        );
        $this->define_headers($headers);

        $this->sortable(true, 'purchase_date', SORT_DESC);
        $this->no_sorting('user_id');
        $this->no_sorting('amount');
        $this->no_sorting('items');
        $this->no_sorting('txn_id');
        $this->no_sorting('gateway');
        $this->no_sorting('actions');
    }

    /**
     * This function is called for each data row to allow processing of the
     * purchase_date value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return purchase_date formatted like 09:23:00 01/12/1991
     */
    function col_purchase_date($values) {
        return date('H:i:s d/m/Y', $values->purchase_date);
    }

    /**
     * This function is called for each data row to allow processing of the
     * username value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return username with link to profile or username only
     *     when downloading.
     */
    function col_user_id($values) {
        global $CFG, $DB;

        $user = $DB->get_record('user', array('id' => $values->user_id ));

        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $user->username;
        } else {
            return sprintf(
                '<a href="%s">%s %s</a>',
                new moodle_url($CFG->wwwroot . '/user/profile.php', array( 'id'=> $user->id )),
                $user->firstname,
                $user->lastname
            );
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * amount value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return amount including the currency symbol
     */
    function col_amount($values) {

        $t = new MoodecTransaction((int) $values->id);

        return local_moodec_get_currency_symbol(get_config('local_moodec', 'currency')) . number_format($t->get_cost(), 2, '.', ',');
    }

    /**
     * This function is called for each data row to allow processing of the
     * items value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return the number of items in the transaction
     */
    function col_items($values) {
        
        $t = new MoodecTransaction((int) $values->id);

        return count($t->get_items());
    }

    /**
     * This function is called for each data row to allow processing of the
     * gateway value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return gateway formatted as per MoodecTransaction class function
     */
    function col_gateway($values) {
        
        $t = new MoodecTransaction((int) $values->id);

        return $t->get_gateway(true);
    }

    /**
     * This function is called for each data row to allow processing of the
     * txn_id value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return txn_id 
     */
    function col_txn_id($values) {
        
        if( empty($values->txn_id) || $values->txn_id == 0 ) {
            return '-';
        } else {
            return $values->txn_id;
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * purchase_date value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return status formatted like as per the transaction class
     */
    function col_status($values) {
        
        $t = new MoodecTransaction((int) $values->id);

        return $t->get_status(true);
    }

    /**
     * This function is called for each data row to allow processing of the
     * actions value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return url to view the individual transaction
     */
    function col_actions($values) {
        global $CFG;

        $url = new moodle_url(
            $CFG->wwwroot .'/local/moodec/pages/transaction/view.php', 
            array( 'id' => $values->id )
        );

        if ($this->is_downloading()) {
            return $url;
        } else {
            return sprintf(
                '<a href="%s">%s</a>',
                $url,
                get_string('transaction_view_label', 'local_moodec')
            );
        }
    }

    /**
     * This function is called for each data row to allow processing of
     * columns which do not have a *_cols function.
     * @return string return processed value. Return NULL if no change has
     *     been made.
     */
    function other_cols($colname, $value) {
        // --- Leaving here for future reference ---

        // For security reasons we don't want to show the password hash.
        // if ($colname == 'password') {
        //     return "****";
        // }
    }

    /**
     * This function is not part of the public api.
     */
    function print_nothing_to_display() {
        global $OUTPUT;
        $this->print_initials_bar();

        printf(
            '<p class="transactions--empty">%s</p>',
            get_string('transaction_table_empty', 'local_moodec')
        );
    }
}