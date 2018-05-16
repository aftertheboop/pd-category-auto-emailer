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
        
        add_menu_page( 'Category Auto Emailer Admin', 'Auto-Emailer', 'manage_options', 'pd_category_auto_emailaer-plugin', array($this, 'pd_auto_emailer_admin_init') );
        
    }
    
    public function pd_auto_emailer_admin_init() {
        
        $this->admin_menu_html();
        
    }
    
    private function admin_menu_html() {
        
        echo '<div class="wrap">';
        echo '<h1>Category Auto Emailer Settings</h1>';
        echo '</div>';
        
    }
    
    
    
}