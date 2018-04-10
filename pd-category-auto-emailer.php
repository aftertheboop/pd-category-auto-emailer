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
Text Domain: pd-category-auto-emailer
*/

add_action( 'transition_post_status', 'pd_category_auto_emailer_init', 10, 3 );

function pd_category_auto_emailer_init($new_status, $old_status, $post) {
    
    if ( ( $old_status === 'draft' || $old_status === 'auto-draft'  ) && $new_status === 'publish' ) {
        
        $Auto_emailer = new PD_Category_Auto_Emailer($post->ID, $post);
        
        // Add in a checkbox option to ignore auto-send
        
        // Emailer can be sent
        if($Auto_emailer->can_send()) {
            // Prepare the emailer
            if($Auto_emailer->pre_email()) {
                // Check that there are email addresses available
                if((is_array($Auto_emailer->get_emails()) && empty($Auto_emailer->get_emails())) || strlen($Auto_emailer->get_emails() > 0)) {
                    
                    return false;
                    
                } else {
                    // Send email
                    wp_mail($Auto_emailer->get_emails(), '[' . bloginfo('name') . '] New Article', $Auto_emailer->get_message(), $Auto_emailer->get_headers());
                }
                
            }
            
        } else {
            
            return new WP_Error('pd_auto_emailer_failed', __( 'Article Auto-emailer could not be sent', 'pd-category-auto-emailer'));
            
        }
        
    }    
    
}


/*
function pd_category_auto_emailer_init($post_ID, $post, $update) {
    
    $Auto_emailer = new PD_Category_Auto_Emailer($post_ID, $post, $update);
   
    if($Auto_emailer->can_send()) {
    
        $Auto_emailer->pre_email();
        
    }
    
    
}*/

class PD_Category_Auto_Emailer {
    
    public function __construct($post_ID, $post) {
        
        $this->post_id = $post_ID;
        $this->post = $post;
        $this->template_path = plugin_dir_url(__FILE__) . 'templates/default.html';
        
        // debugging / logging
        $this->debug = true;
        
        $this->emails = array();
        $this->template = '';
    }
    
    private function _log($message) {
        
        if($this->debug == false) {
            return false;
        }
        
        $log = fopen(plugin_dir_path( __FILE__ ) . 'errorlog.txt', "a") or die('Could not open log file');
        fwrite($log, '[' . date('Y-m-d H:i:s') . '] - ' . $message . "\r\n");
        fclose($log);

    }
    
    
    
    /**
     * Get Headers
     * 
     * Returns email headers
     * @return string
     */
    public function get_headers() {
        
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        
        return $headers;
        
    }
    
    /**
     * Get Message
     * 
     * Returns the message body
     * @return string 
     */
    public function get_message() {
        
        return $this->template;
        
    }
    
    /**
     * Get Emails
     * 
     * Returns a list of all email addresses to send to
     * @param bool $string
     * @return array|string
     */
    public function get_emails($string = false) {
        
        if(!$string) {
            return $this->emails;
        } else {
            return implode(', ', $this->emails);
        }
        
    }
    
    /**
     * Can Send
     * 
     * Checks whether the plugin can proceeed to process and send the notification
     * @return boolean
     */
    public function can_send() {
        
        $sent_status = $this->_get_sent_status();
        $post_status = $this->post->post_status;
                
        // Email must be unsent and post must be published
        if($sent_status == 0 && $post_status == 'publish') {
            
            $this->_log('Can send email');
            
            return true;
            
        } else {
            
            $this->_log('Cannot send email: Sent Status: ' . $sent_status . ' Post Status: ' . $post_status);
            
            return false;
            
        }
        
    }
    
    /**
     * Pre Email
     * 
     * Prepares the content, addresses, sent flag and authority to send the auto
     * email
     */
    public function pre_email() {
        
        // Get all addresses to end to
        try {
            $this->emails = $this->_get_category_email_addresses();
            // Set email template
            $this->template = $this->_prepare_template();
            
            $this->_log('Emails: ' . json_encode($this->emails));
            $this->_log('Template fetched');
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
                
        
        
    }
    
    /**
     * Get Sent Status
     * 
     * Gets the value of the email sent flag
     * @return int
     */
    private function _get_sent_status() {
        
        $status = get_post_meta($this->post_id, 'pd_cat_email_sent', true);
        
        if(strlen($status) > 0) {
            return $status;
        } else {
            return 0;
        }
        
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
        
        $html = file_get_contents($this->template_path, false, stream_context_create($arrContextOptions));
        
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