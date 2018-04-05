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