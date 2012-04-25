<?php

if (defined('ABSPATH') && defined('WP_UNINSTALL_PLUGIN')) {    
    function bnm_unistall() {
        global $wpdb;
        delete_option('bnm_options');
        $wpdb->query("DROP TABLE IF EXISTS `" . BNM_USERS . "`");
    }
} else {
    exit();
}
?>