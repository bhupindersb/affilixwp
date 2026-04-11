<?php
/*
Template Name: Affiliate Dashboard
*/

if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

// Optional: hide WP admin bar for cleaner dashboard
show_admin_bar(false);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('affilixwp-dashboard-page'); ?>>

<?php echo do_shortcode('[affilixwp_dashboard]'); ?>

<?php wp_footer(); ?>
</body>
</html>