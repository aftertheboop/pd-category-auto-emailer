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
    
    $Auto_emailer = new PD_Category_Auto_Emailer($post->ID, $post);
    
     $Auto_emailer->_log('Prepare auto mailer');
     
     $Auto_emailer->_log($old_status . ' ' . $new_status . ' ' . json_encode($post));
    
    if ( ( $old_status === 'draft' || $old_status === 'auto-draft' || $old_status === 'pending' ) && $new_status === 'publish' ) {
        
        // Add in a checkbox option to ignore auto-send
        
        // Emailer can be sent
        if($Auto_emailer->can_send()) {
            $Auto_emailer->_log('Can Send');
            // Prepare the emailer
            if($Auto_emailer->pre_email()) {
                // Check that there are email addresses available
                if(empty($Auto_emailer->get_emails()) || strlen($Auto_emailer->get_emails(true)) == 0) {
                    
                    $Auto_emailer->_log('Cannot send email. No addresses');
                    
                    return false;
                    
                } else {
                                        
                    // Send email
                    $sent = wp_mail($Auto_emailer->get_emails(true), '[' . get_bloginfo('name') . '] New Article', $Auto_emailer->get_message(), $Auto_emailer->get_headers());
                    //$Auto_emailer->_log($Auto_emailer->get_message());
                    $Auto_emailer->_log(json_encode($sent));
                    $Auto_emailer->_log('Email Sent!');
                }
                
            }
            
        } else {
            $Auto_emailer->_log('Failed');
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
    
    public function _log($message) {
        
        //if($this->debug == false) {
        //    return false;
        //}
        
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
        $headers .= "Bcc: " . $this->get_emails(true);
        
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
        /*$arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );  
        
        $html = file_get_contents($this->template_path, false, stream_context_create($arrContextOptions));*/
        
        // Correct code. Change back on live
        $html = $this->get_html();
                        
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
    
    private function get_html() {
        return '<!doctype html>
<html>
  <head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>New Notification From $$BLOGNAME$$</title>
    <style>
    /* -------------------------------------
        INLINED WITH htmlemail.io/inline
    ------------------------------------- */
    /* -------------------------------------
        RESPONSIVE AND MOBILE FRIENDLY STYLES
    ------------------------------------- */
    @media only screen and (max-width: 620px) {
      table[class=body] h1 {
        font-size: 28px !important;
        margin-bottom: 10px !important;
      }
      table[class=body] p,
            table[class=body] ul,
            table[class=body] ol,
            table[class=body] td,
            table[class=body] span,
            table[class=body] a {
        font-size: 16px !important;
      }
      table[class=body] .wrapper,
            table[class=body] .article {
        padding: 10px !important;
      }
      table[class=body] .content {
        padding: 0 !important;
      }
      table[class=body] .container {
        padding: 0 !important;
        width: 100% !important;
      }
      table[class=body] .main {
        border-left-width: 0 !important;
        border-radius: 0 !important;
        border-right-width: 0 !important;
      }
      table[class=body] .btn table {
        width: 100% !important;
      }
      table[class=body] .btn a {
        width: 100% !important;
      }
      table[class=body] .img-responsive {
        height: auto !important;
        max-width: 100% !important;
        width: auto !important;
      }
    }

    /* -------------------------------------
        PRESERVE THESE STYLES IN THE HEAD
    ------------------------------------- */
    @media all {
      .ExternalClass {
        width: 100%;
      }
      .ExternalClass,
            .ExternalClass p,
            .ExternalClass span,
            .ExternalClass font,
            .ExternalClass td,
            .ExternalClass div {
        line-height: 100%;
      }
      .apple-link a {
        color: inherit !important;
        font-family: inherit !important;
        font-size: inherit !important;
        font-weight: inherit !important;
        line-height: inherit !important;
        text-decoration: none !important;
      }
      .btn-primary table td:hover {
        background-color: #34495e !important;
      }
      .btn-primary a:hover {
        background-color: #34495e !important;
        border-color: #34495e !important;
      }
    }
    </style>
  </head>
  <body class="" style="background-color: #f6f6f6; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
    <table border="0" cellpadding="0" cellspacing="0" class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background-color: #f6f6f6;">
      <tr>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
        <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; Margin: 0 auto; max-width: 580px; padding: 10px; width: 580px;">
          <div class="content" style="box-sizing: border-box; display: block; Margin: 0 auto; max-width: 580px; padding: 10px;">

            <!-- START CENTERED WHITE CONTAINER -->
            <span class="preheader" style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;">New Notification from $$BLOGNAME$$</span>
            <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background: #ffffff; border-radius: 3px;">

              <!-- START MAIN CONTENT AREA -->
              <tr>
                <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                  <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                    <tr>
                      <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">Hi there,</p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">A new post has just been made on <b>$$BLOGNAME$$</b> that is relevant to you.</p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">Click the link to read:</p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;"><b><a href="$$PERMALINK$$" title="$$POSTNAME$$">$$POSTTITLE$$</a></b></p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">&nbsp;</p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">Kind regards,</p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; Margin-bottom: 15px;">The <b>$$BLOGNAME$$</b> Team</p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>

            <!-- END MAIN CONTENT AREA -->
            </table>

            <!-- START FOOTER -->
            <div class="footer" style="clear: both; Margin-top: 10px; text-align: center; width: 100%;">
              <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                <tr>
                  <td class="content-block" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; font-size: 12px; color: #999999; text-align: center;">
                    <span class="apple-link" style="color: #999999; font-size: 12px; text-align: center;"></span>
                    <br>Please reply to this email if you no longer wish to receive these notifications.
                  </td>
                </tr>
              </table>
            </div>
            <!-- END FOOTER -->

          <!-- END CENTERED WHITE CONTAINER -->
          </div>
        </td>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
      </tr>
    </table>
  </body>
</html>';
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