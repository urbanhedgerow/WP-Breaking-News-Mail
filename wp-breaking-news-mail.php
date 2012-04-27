<?php

/*
  Plugin Name: WP Breaking News Mail
  Plugin URI: https://github.com/DanielaValero/WP-Breaking-News-Mail
  Description: Notifies an email list when a Breaking News occur. Based on Subscribe2: http://subscribe2.wordpress.com/ from Matthew Robinson
  Version: 1.03
  Author: Daniela Valero aka DaHe
  Author URI: http://twitter.com/danielavalero_
 * License: GPLv2
 */
?>
<?php

/* Copyright YEAR   PLUGIN_DaHe  (email : danielavaleroa@gmail.com)
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
?>
<?php

if (version_compare($GLOBALS['wp_version'], '3.1', '<')) {
    // wp-breaking-news-mail needs WordPress 3.1 or above, exit if not on a compatible version
    $exit_msg = sprintf(__('This version of wp-breaking-news-mail requires WordPress 3.1 or greater. Please update %1$s or use an older version of %2$s.', 'wp-breaking-news-mail'), '<a href="http://codex.wordpress.org/Updating_WordPress">Wordpress</a>', '<a href="http://wordpress.org/extend/plugins/wp-breaking-news-mail/download/">wp-breaking-news-mail</a>');
    exit($exit_msg);
}

global $wpdb;

define('BNM_USERS', $wpdb->get_blog_prefix() . 'bnm_users');
define('BNM_PATH', trailingslashit(dirname(__FILE__)));

require_once 'includes/BreakingNewsMail_Widget.php';
require_once 'includes/BreakingNewsMail_Admin.php';
require_once 'includes/BreakingNewsMail_Controller.php';

$bnm = new WP_Breaking_News_Mail_Main;

class WP_Breaking_News_Mail_Main {

    private $bnm_options = array();
    private $objBreakingNewsMail_Controller;

   

    public function __construct() {
      //  add_action('init', 'WP_Breaking_News_Mail_Main_init');
        // Call Wpsqt_Installer Class to write in WPSQT tables on activation 
        register_activation_hook(__FILE__, array(&$this, 'bnm_main_install'));       

        $this->objBreakingNewsMail_Controller = $objBreakingNewsMail_Controller = new BreakingNewsMail_Controller();
        if (is_admin()) {
            if (is_multisite()) {
                echo '<div class="error">This plugin is not fully compatible with 
                            multisite installations. </div>';
            }
            if (empty($this->bnm_options)) {
                $this->setDefaultOptions();
            }


            if (!empty($this->bnm_options) || !get_option('bnm_options')) {
                add_option("bnm_options", $this->bnm_options);
            }
            $objBreakingNewsMail_Admin = new BreakingNewsMail_Admin();

            add_action('wp_ajax_bnm_process_subscription', array(&$this, 'bnm_process_subscription'));
        } else {
            add_action('wp_ajax_nopriv_bnm_process_subscription', array(&$this, 'bnm_process_subscription'));
        }

        add_action('widgets_init', array(&$this, 'init_BreakingNewsMail_Widget'));
    }

    
     function WP_Breaking_News_Mail_Main_init() {
        $plugin_dir = basename(dirname(__FILE__));
        load_plugin_textdomain('bnm', false, $plugin_dir);
    }
    /*
     * Register the widget
     * @since 1
     *     
     */

    function init_BreakingNewsMail_Widget() {
        register_widget('BreakingNewsMail_Widget');
    }

    /*
     * This function is called by ajax on publich subscriptions
     * @since 1
     *     
     */

    function bnm_process_subscription() {
        if (!check_ajax_referer('bnm_nonce'))
            exit();
        $this->objBreakingNewsMail_Controller->proccess_public_subscribers($_POST);
        die();
    }

    /*
     * Creates the database table
     * @since 1
     *   
     */

    function bnm_main_install() {
        global $wpdb;
        $wpdb->query("CREATE TABLE IF NOT EXISTS `" . BNM_USERS . "` (
			id int(11) NOT NULL auto_increment,
			email varchar(64) NOT NULL default '',                       
			active tinyint(1) default 0,
                        status tinyint(1) default 1,
			date DATE default '" . date('Y-m-d') . "' NOT NULL,
			ip char(64) NOT NULL default 'admin',
			PRIMARY KEY (id) )  ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
    }

    /*
     * Set the default options values
     * @since 1
     *    
     */

    function setDefaultOptions() {
        if (empty($this->bnm_options['wpregdef'])) {
            $this->bnm_options['wpregdef'] = "no";
        } // option to check registration form box by default

        if (empty($this->bnm_options['email_format'])) {
            $this->bnm_options['email_format'] = "text";
        } // option for default auto-subscription email format

        if (empty($this->bnm_options['tracking'])) {
            $this->bnm_options['tracking'] = "";
        } // option for tracking

        if (empty($this->bnm_options['bnmpage'])) {
            $this->bnm_options['bnmpage'] = 0;
        } // option for default WordPress page for bnm to use        

        if (empty($this->bnm_options['include'])) {
            $this->bnm_options['include'] = "";
        } // option for included categories

        if (empty($this->bnm_options['sender_email'])) {
            $this->bnm_options['sender_email'] = "author@email.com";
        } // option for email notification sender



        if (empty($this->bnm_options['show_button'])) {
            $this->bnm_options['show_button'] = "1";
        } // option to show bnm button on Write page


        if (empty($this->bnm_options['entries'])) {
            $this->bnm_options['entries'] = 25;
        } // option for the number of subscribers displayed on each page


        if (empty($this->bnm_options['mailtext'])) {
            $this->bnm_options['mailtext'] = __("{BLOGNAME} has posted a new item, '{TITLE}'\n\n{POST}\n\nYou may view the latest post at\n{PERMALINK}\n\nYou received this e-mail because you asked to be notified when new updates are posted. If you don't want receive anymore this email please click in the following link {UNSUBSCRIBE_ACTION} \nBest regards,\n {BLOGNAME} Team", "bnm");
        } // Default notification email text

        if (empty($this->bnm_options['notification_subject'])) {
            $this->bnm_options['notification_subject'] = "[{BLOGNAME}] {TITLE}";
        } // Default notification email subject

        if (empty($this->bnm_options['confirm_email'])) {
            $this->bnm_options['confirm_email'] = __("{BLOGNAME} has received a request to {CONFIRMATION_ACTION} for this email address. To complete your request please click on the link below:\n\n{LINK}\n\nIf you did not request this, please feel free to disregard this notice!\n\nThank you,\n{BLOGNAME} Team.", "bnm");
        } // Default confirmation email text

        if (empty($this->bnm_options['confirm_subject'])) {
            $this->bnm_options['confirm_subject'] = "[{BLOGNAME}] " . __('Please confirm your request', 'bnm');
        } // Default confirmation email subject

        if (empty($this->bnm_options['unsubscribe_email'])) {
            $this->bnm_options['unsubscribe_email'] = __("{BLOGNAME} has received a request to {UNSUBSCRIBE_ACTION} for this email address. To complete your request please click on the link below:\n\n{LINK}\n\nIf you did not request this, please feel free to disregard this notice!\n\nThank you,\n{BLOGNAME} Team.", "bnm");
        } // Default reminder email text

        if (empty($this->bnm_options['unsubscribe_subject'])) {
            $this->bnm_options['unsubscribe_subject'] = "[{BLOGNAME}] " . __('Please confirm your request', 'bnm');
            ;
        } // Default reminder email subject
    }

    /*
     * Update the options field on the database
     * @since 1
     *     
     */

    function setOptions($bnm_options) {
        update_option('bnm_options', $bnm_options);
    }

}

?>