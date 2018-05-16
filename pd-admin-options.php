<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$pd_category_auto_emailer_admin = new PD_Category_Auto_Emailer_Admin();

class PD_Category_Auto_Emailer_Admin {
    
    public function __construct() {
        
        $this->init_menu_item();
        
    }
    
    private function init_menu_item() {
        
        add_menu_page( 'Category Auto Emailer Settings', 'Auto-Emailer', 'manage_options', 'pd_category_auto_emailaer-plugin', array($this, 'pd_auto_emailer_admin_init') );
        
    }
    
    public function pd_auto_emailer_admin_init() {
        
        $this->admin_menu_html();
        
    }
    
    private function admin_menu_html() {
        
        $html = '<div class="wrap">';
        $html .= '<h1>Category Auto Emailer Settings</h1>';
        $html .= '<p class="description">Configure settings for your category auto-emailer. These settings are universal across all categories.</p>';
        $html .= '<form method="post" action="options.php" novalidate="novalidate">';
        $html .= '<input type="hidden" name="option_page" value="general">';
        $html .= '<input type="hidden" name="action" value="update">';
        $html .= wp_nonce_field('pd-category-auto-emailer-save_options', '_wpnonce', true, false);
        $html .= '<table class="form-table">';
        $html .= '<tr>';
        $html .= '<th><label for="pd-auto-from">From Address</label></th>';
        $html .= '<td><input name="pd-auto-from" type="email" id="pd-auto-from" class="regular-text" value="" /><p class="description">The displayed From and Reply-To email address on the notification</p></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th><label for="pd-auto-bcc">Bcc Address(es):</label></th>';
        $html .= '<td><input name="pd-auto-bcc" type="text" id="pd-auto-bcc" class="regular-text" value="" /><p class="description">Comma-separated list of any additional addresses you would like to add to any notification</p></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<th><label for="pd-auto-body">HTML Emailer Body</label></th>';
        $html .= '<td>';
        $html .= '<p class="description">The following aliases will be substituted with content:<br/>$$BLOGNAME$$, $$PERMALINK$$, $$POSTNAME$$, $$POSTTITLE$$<br/>Use them to substitute dynamic content in your email</p>';
        $html .= '<textarea name="pd-auto-body" id="pd-auto-body" class="regular-text" style="width: 100%" rows="20"></textarea>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        
        echo $html;
    }
    
    
    
}