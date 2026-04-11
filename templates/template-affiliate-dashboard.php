<?php
/*
Template Name: Affiliate Dashboard
*/

if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

echo do_shortcode('[affilixwp_dashboard]');