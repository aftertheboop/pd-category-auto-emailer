<?php
/**
 * PD Category Auto Emailer Admin Class
 * 
 * Handles all interactions with the admin section of the plugin
 */
class PD_Category_Auto_Emailer_Admin {
    
    public function __construct() {
                        
    }
    
    /**
     * Init Menu Item
     * 
     * Assigns the menu item
     */
    public function init_menu_item() {
        
        add_menu_page( 'Category Auto Emailer Settings', 'Auto-Emailer', 'manage_options', 'pd_category_auto_emailer_plugin', array($this, 'pd_auto_emailer_admin_init') );
    }
        
    /**
     * PD Auto Emailer Admin Init
     * 
     * Checks permissions and renders the admin section
     */
    public function pd_auto_emailer_admin_init() {
        
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        
        // Save options
        if(isset($_POST['option_page'])) {
            $this->admin_save_options();    
        }
        
        // Display form
        $this->admin_menu_html();
        
    }
    
    /**
     * Admin Save Options
     * 
     * Saves the POSTed options
     */
    private function admin_save_options() {
        
        $this->set_pd_from_name();
        $this->set_pd_from_email();
        $this->set_pd_bcc();
        $this->set_pd_body_content();
                
    }
    
    /**
     * Set PD From Name
     * 
     * Sets the FROMn NAME field
     * @return void
     */
    private function set_pd_from_name() {
        
        $option_name = 'pd_from_name' ;
        $new_value = filter_input(INPUT_POST, 'pd-auto-from-name', FILTER_SANITIZE_STRING) ;

        if ( get_option( $option_name ) !== false ) {

            // The option already exists, so we just update it.
            update_option( $option_name, $new_value );

        } else {

            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'no';
            add_option( $option_name, $new_value, $deprecated, $autoload );
        }
    }
    
    /**
     * Set PD From Email
     * 
     * Sets the FROM EMAIL field
     * @return void
     */
    private function set_pd_from_email() {
        
        $option_name = 'pd_from_email' ;
        $new_value = filter_input(INPUT_POST, 'pd-auto-from', FILTER_SANITIZE_STRING) ;

        if ( get_option( $option_name ) !== false ) {

            update_option( $option_name, $new_value );

        } else {

            $deprecated = null;
            $autoload = 'no';
            add_option( $option_name, $new_value, $deprecated, $autoload );
        }
    }
    
    /**
     * Set PD Bcc
     * 
     * Sets the email addresses to BCC
     * @return void
     */
    private function set_pd_bcc() {
        
        $option_name = 'pd_bcc' ;
        $new_value = filter_input(INPUT_POST, 'pd-auto-bcc', FILTER_SANITIZE_STRING) ;

        if ( get_option( $option_name ) !== false ) {

            update_option( $option_name, $new_value );

        } else {

            $deprecated = null;
            $autoload = 'no';
            add_option( $option_name, $new_value, $deprecated, $autoload );
        }
    }
    
    /**
     * Set PD Body Content
     * 
     * Sets the HTML body content for the emailer
     * @return void
     */
    private function set_pd_body_content() {
        $option_name = 'pd_body_content' ;
        $new_value = filter_input(INPUT_POST, 'pd-auto-body') ;

        if ( get_option( $option_name ) !== false ) {

            update_option( $option_name, $new_value );

        } else {

            $deprecated = null;
            $autoload = 'no';
            add_option( $option_name, $new_value, $deprecated, $autoload );
        }
    }
    
    /**
     * Admin Menu HTML
     * 
     * Renders the admin HTML form
     * @return void
     */
    private function admin_menu_html() {
        
        $html = '<div class="wrap">';
        $html .= '<h1>Category Auto Emailer Settings</h1>';
        $html .= '<p class="description">Configure settings for your category auto-emailer. These settings are universal across all categories.</p>';
        $html .= '<form id="pd-auto-emailer" method="post">';
        
        echo $html;
        
        settings_fields( 'pd-auto-emailer-settings' );
        do_settings_sections( 'pd-auto-emailer-settings' ); 
        /*$html .= '<input type="hidden" name="option_page" value="general">';
        $html .= '<input type="hidden" name="action" value="update">';
        $html .= wp_nonce_field('pd-category-auto-emailer-save_options', '_wpnonce', true, false);*/
        $html = '<table class="form-table">';
        $html .= '<tr>';
        $html .= '<th><label for="pd-auto-from-name">From Name</label></th>';
        $html .= '<td><input name="pd-auto-from-name" type="text" id="pd-auto-from-name" class="regular-text" value="' . $this->get_pd_from_name() . '" /><p class="description">The displayed From Name on the notifications</p></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th><label for="pd-auto-from">From Address</label></th>';
        $html .= '<td><input name="pd-auto-from" type="email" id="pd-auto-from" class="regular-text" value="' . $this->get_pd_email() . '" /><p class="description">The displayed From and Reply-To email address on the notification</p></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th><label for="pd-auto-bcc">Bcc Address(es):</label></th>';
        $html .= '<td><input name="pd-auto-bcc" type="text" id="pd-auto-bcc" class="regular-text" value="' . $this->get_pd_bcc() . '" /><p class="description">Comma-separated list of any additional addresses you would like to add to any notification</p></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th><label for="pd-auto-body">HTML Emailer Body</label></th>';
        $html .= '<td>';
        $html .= $this->body_content_field();
        //$html .= '<textarea name="pd-auto-body" id="pd-auto-body" class="regular-text" style="width: 100%" rows="20"></textarea>';
        $html .= '<p class="description">The following aliases will be substituted with content:<br/>$$BLOGNAME$$, $$PERMALINK$$, $$POSTTITLE$$<br/>Use them to substitute dynamic content in your email</p>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td></td><td>' . get_submit_button('Save Options', 'primary large', 'pd_save_options') . '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</form>';
        $html .= '</div>';
        
        
        echo $html;
    }
    
    /**
     * Get PD From Name
     * 
     * Gets the FROM NAME for the email
     * @return String
     */
    public function get_pd_from_name() {
        
        $from_name = get_option('pd_from_name', get_bloginfo('name'));
        
        return $from_name;        
        
    }
    
    /**
     * Get PD Email
     * 
     * Gets the FROM EMAIL for the mail
     * @return String
     */
    public function get_pd_email() {
        
        $from_email = get_option('pd_from_email', get_option('admin_email'));
        
        return $from_email;
        
    }
    
    /**
     * Get PD Bcc
     * 
     * Gets the BCC field for the headers. Blank if nothing
     * @return String
     */
    public function get_pd_bcc() {
        
        $bcc_emails = get_option('pd_bcc', '');
        
        return $bcc_emails;
        
    }
    
    /**
     * Get PD Body Content
     * 
     * Gets the emailer body copy
     * @return String
     */
    public function get_pd_body_content() {
        
        $body_content = get_option('pd_body_content', $this->default_body_content());
        
        return $body_content;
        
    }
    
    /**
     * Default Body Content
     * 
     * Gets a default string of body copy in the event that nothing is assigned
     * @return String
     */
    private function default_body_content() {
        $html = 'Hi there,
A new article relevant to you has been posted on $$BLOGNAME$$. Click the link to read:
<b><a href="$$PERMALINK$$" title="$$POSTTITLE$$">$$POSTTITLE$$</a></b>
<b>We welcome daily  news for placement</b> and we invite you to send us any events as they happen.

Kind regards,
The <b>$$BLOGNAME$$</b> Team';
        
        return $html;
        
    }
    
    /**
     * Body Content Field
     * 
     * Generates the WYSIWYG interface for adding the HTML content for the email
     * @return String
     */
    private function body_content_field() {
        ob_start();
        wp_editor($this->get_pd_body_content(), 'pd-auto-body', array(
            'wpautop' => false,
            'textarea_name' => 'pd-auto-body',
            'media_buttons' => false
        ));
        $ret = ob_get_contents();  
        ob_end_clean();  
        return $ret;   
        
    }
    
}