<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly 

function bccbai_refresh_nonce()
{
    // Generate a new nonce
    $new_nonce = wp_create_nonce('wp_rest');

    wp_send_json_success(array('nonce' => $new_nonce));
}

add_action('wp_ajax_bccbai_refresh_nonce', 'bccbai_refresh_nonce');
add_action('wp_ajax_nopriv_bccbai_refresh_nonce', 'bccbai_refresh_nonce');
