<?php

/**
 * @package PD Category Auto Emailer
 */
/*
Plugin Name: PD Category Auto Emailer
Description: Sends an automated email to an email address set per category upon publishing a post for the first time
Version: 1.0
Author: Rory Molyneux
Author URI: http://pitchdark.co.za
License: Private
Text Domain: pd-category-tracker
*/

add_action( 'save_post', 'pd_category_auto_emailer_init', 10, 3 );

function pd_category_auto_emailer_init($post_ID, $post, $update) {
    
    $Auto_emailer = new PD_Category_Auto_Emailer($post_ID, $post, $update);
       
    $Auto_emailer->pre_email();
    
    exit;
}

class PD_Category_Auto_Emailer {
    
    public function __construct($post_ID, $post, $update) {
        
        $this->post_id = $post_ID;
        $this->post = $post;
        $this->update = $update;
        
        $this->emails = array();
        $this->template = '';
    }
    
    /**
     * Pre Email
     * 
     * Prepares the content, addresses, sent flag and authority to send the auto
     * email
     */
    public function pre_email() {
        
        $this->emails = $this->_get_category_email_addresses();
        $this->template = $this->_prepare_template();
        
    }
    
    /**
     * Prepare Template
     * 
     * Prepares the Email template by substituting the relevant information on
     * the document.
     * @return String
     */
    private function _prepare_template() {
       // print_r(scandir('../wp-content/plugins/pd-category-auto-emailer'));
        //
        
        // Hack while on dev
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );  
        
        $html = file_get_contents(plugin_dir_url(__FILE__) . 'templates/default.html', false, stream_context_create($arrContextOptions));
        
        // Correct code. Change back on live
        // $fh = file_get_contents(plugin_dir_url(__FILE__) . "templates/default.html");
                
        // Replace blog name
        $html = str_replace('$$BLOGNAME$$', get_bloginfo('name'), $html);
        
        // Replace article permalink
        $html = str_replace('$$PERMALINK$$', get_the_permalink($this->post_id), $html);
        // Replace article title
        $html = str_replace('$$POSTTITLE$$', get_the_title($this->post_id), $html);
        
        return $html;
        
    }
    
    
    
    /**
     * Get Category Email Addresses
     * 
     * Get the contents of all `pd_cat_email` fields of the article's associated
     * categories. Returns an array of field values.
     * @return Array
     */
    private function _get_category_email_addresses() {
        
        $categories = wp_get_post_categories($this->post_id);
        $emails = array();
        
        foreach($categories as $category) {
            
            // Get the contents of the pd_cat_email meta field
            $pd_cat_email = get_term_meta($category, 'pd_cat_email', true);
            // Only add the content if it has a non-zero length.
            // Validation is handled on the initial category POST 
            if(strlen($pd_cat_email) > 0) {
                $emails[] = $pd_cat_email;
            }
        }
        
        return $emails;
        
    }
}

/* Category Auto-Email Fields ----------------------------------------------- */
add_action('category_add_form_fields', 'pd_taxonomy_add_new_meta_field', 10, 1);
add_action('category_edit_form_fields', 'pd_taxonomy_edit_meta_field', 10, 1);
//Product Cat Create page
function pd_taxonomy_add_new_meta_field() {
    ?>   
    <div class="form-field">
        <label for="pd_cat_email"><?php _e('Category Auto Email', 'pd'); ?></label>
        <input type="email" name="pd_cat_email" id="pd_cat_email">
        <p class="description"><?php _e('Enter the email address of a person to receive an email every time a post is made to this category', 'pd'); ?></p>
    </div>
    <?php
}
//Product Cat Edit page
function pd_taxonomy_edit_meta_field($term) {
    //getting term ID
    $term_id = $term->term_id;
    // retrieve the existing value(s) for this meta field.
    $pd_cat_email = get_term_meta($term_id, 'pd_cat_email', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="pd_cat_email"><?php _e('Category Auto Email', 'pd'); ?></label></th>
        <td>
            <input type="email" name="pd_cat_email" id="pd_cat_email" value="<?php echo esc_attr($pd_cat_email) ? esc_attr($pd_cat_email) : ''; ?>">
            <p class="description"><?php _e('Enter the email address of a person to receive an email every time a post is made to this category', 'pd'); ?></p>
        </td>
    </tr>
    <?php
}

add_action('edited_category', 'pd_save_taxonomy_custom_meta', 10, 1);
add_action('create_category', 'pd_save_taxonomy_custom_meta', 10, 1);
// Save extra taxonomy fields callback function.
function pd_save_taxonomy_custom_meta($term_id) {
    $pd_cat_email = filter_input(INPUT_POST, 'pd_cat_email');
    update_term_meta($term_id, 'pd_cat_email', $pd_cat_email);
}
/* End Custom Category Field ------------------------------------------------ */