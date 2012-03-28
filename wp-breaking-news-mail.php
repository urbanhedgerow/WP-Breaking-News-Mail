<?php

/*
  Plugin Name: WP Breaking News Mail
  Plugin URI: http://breakingNewsMail.wordpress.com
  Description: Notifies an email list when a Breaking News occur
  Version: 1
  Author: Daniela Valero
  Author URI: http://twitter.com/danielavalero_
 * License: GPLv2
 */
?>
<?php

/* Copyright YEAR   PLUGIN_AUTHOR_NAME  (email : PLUGIN AUTHOR EMAIL)
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

//define('BNM_PATH', trailingslashit(dirname(__FILE__)));
//define('BNM_DIR', trailingslashit(dirname(plugin_basename(__FILE__))));
//define('BNM_URL', plugin_dir_url(dirname(__FILE__)) . BNM_DIR);
define('BNM_USERS', $wpdb->get_blog_prefix() . 'bnm_users');

require_once 'includes/BreakingNewsMail_Widget.php';
require_once 'includes/BreakingNewsMail_Admin.php';


$bnm = new WP_Breaking_News_Mail_Main;

class WP_Breaking_News_Mail_Main {

    private $bnm_options = array();

    public function __construct() {
        // Call Wpsqt_Installer Class to write in WPSQT tables on activation 
        register_activation_hook(__FILE__, array(&$this,'bnm_main_install'));
        //ver donde llamo esto que no me de error
        //register_uninstall_hook(__FILE__, array(&$this,'bnm_unistall'));
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

            add_action('widgets_init', function() {
                        return register_widget('BreakingNewsMail_Widget');
                    });
        }
    }

    function bnm_main_install() {
        global $wpdb;
        $wpdb->query("CREATE TABLE IF NOT EXISTS `" . BNM_USERS . "` (
			id int(11) NOT NULL auto_increment,
			email varchar(64) NOT NULL default '',
                        id_category int(11) NULL,
			active tinyint(1) default 0,
                        status tinyint(1) default 1,
			date DATE default '" . date('Y-m-d') . "' NOT NULL,
			ip char(64) NOT NULL default 'admin',
			PRIMARY KEY (id) )  ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
    }

    function bnm_unistall() {
        global $wpdb;
        //delete any options, tables, etc the plugin created
        delete_option('bnm_options');
        $rawTables = $wpdb->get_results("SHOW TABLES LIKE  '" . $wpdb->get_blog_prefix() . "bnm_%'", ARRAY_N);
        $tables = array();
        foreach ($rawTables as $table) {
            $tables[] = $table[0];
        }
        $wpdb->query("DROP TABLE " . implode(",", $tables));
    }

    function setDefaultOptions() {
        if (empty($this->bnm_options['wpregdef'])) {
            $this->bnm_options['wpregdef'] = "no";
        } // option to check registration form box by default

        if (empty($this->bnm_options['autoformat'])) {
            $this->bnm_options['autoformat'] = "text";
        } // option for default auto-subscription email format

        if (empty($this->bnm_options['bcclimit'])) {
            $this->bnm_options['bcclimit'] = 0;
        } // option for default bcc limit on email notifications

        if (empty($this->bnm_options['tracking'])) {
            $this->bnm_options['tracking'] = "";
        } // option for tracking

        if (empty($this->bnm_options['bnmpage'])) {
            $this->bnm_options['bnmpage'] = 0;
        } // option for default WordPress page for bnm to use

        if (empty($this->bnm_options['stylesheet'])) {
            $this->bnm_options['stylesheet'] = "yes";
        } // option to include link to theme stylesheet from HTML notifications

        if (empty($this->bnm_options['pages'])) {
            $this->bnm_options['pages'] = "no";
        } // option for sending notifications for WordPress pages

        if (empty($this->bnm_options['password'])) {
            $this->bnm_options['password'] = "no";
        } // option for sending notifications for posts that are password protected


        if (empty($this->bnm_options['exclude'])) {
            $this->bnm_options['exclude'] = "";
        } // option for excluded categories

        if (empty($this->bnm_options['sender_email'])) {
            $this->bnm_options['sender_email'] = "author";
        } // option for email notification sender

        if (empty($this->bnm_options['show_meta'])) {
            $this->bnm_options['show_meta'] = "0";
        } // option to display link to bnm page from 'meta'

        if (empty($this->bnm_options['show_button'])) {
            $this->bnm_options['show_button'] = "1";
        } // option to show bnm button on Write page

        if (empty($this->bnm_options['ajax'])) {
            $this->bnm_options['ajax'] = "0";
        } // option to enable an AJAX style form

        if (empty($this->bnm_options['widget'])) {
            $this->bnm_options['widget'] = "0";
        } // option to enable bnm Widget


        if (empty($this->bnm_options['bnmmeta_default'])) {
            $this->bnm_options['bnmmeta_default'] = "0";
        } // option for bnm over ride postmeta to be checked by default

        if (empty($this->bnm_options['entries'])) {
            $this->bnm_options['entries'] = 25;
        } // option for the number of subscribers displayed on each page

        if (empty($this->bnm_options['barred'])) {
            $this->bnm_options['barred'] = "";
        } // option containing domains barred from public registration

        if (empty($this->bnm_options['exclude_formats'])) {
            $this->bnm_options['exclude_formats'] = "";
        } // option for excluding post formats as supported by the current theme

        if (empty($this->bnm_options['mailtext'])) {
            $this->bnm_options['mailtext'] = __("{BLOGNAME} has posted a new item, '{TITLE}'\n\n{POST}\n\nYou may view the latest post at\n{PERMALINK}\n\nYou received this e-mail because you asked to be notified when new updates are posted.\nBest regards,\n", "bnm");
        } // Default notification email text
        
        if (empty($this->bnm_options['notification_subject'])) {
        $this->bnm_options['notification_subject'] = "[{BLOGNAME}] TITLE";
        } // Default notification email subject

        if (empty($this->bnm_options['confirm_email'])) {
            $this->bnm_options['confirm_email'] = __("{BLOGNAME} has received a request to {CONFIRMATION_ACTION} for this email address. To complete your request please click on the link below:\n\n{LINK}\n\nIf you did not request this, please feel free to disregard this notice!\n\nThank you,\n{MYNAME}.", "bnm");
        } // Default confirmation email text

        if (empty($this->bnm_options['confirm_subject'])) {
            $this->bnm_options['confirm_subject'] = "[{BLOGNAME}] " . __('Please confirm your request', 'bnm');
        } // Default confirmation email subject

        if (empty($this->bnm_options['remind_email'])) {
            $this->bnm_options['remind_email'] = __("This email address was subscribed for notifications at {BLOGNAME} ({BLOGLINK}) but the subscription remains incomplete.\n\nIf you wish to complete your subscription please click on the link below:\n\n{LINK}\n\nIf you do not wish to complete your subscription please ignore this email and your address will be removed from our database.\n\nRegards,\n{MYNAME}", "bnm");
        } // Default reminder email text

        if (empty($this->bnm_options['remind_subject'])) {
            $this->bnm_options['remind_subject'] = "[{BLOGNAME}] " . __('Subscription Reminder', 'bnm');
            ;
        } // Default reminder email subject
    }

    function setOptions($bnm_options) {
        update_option('bnm_options', $bnm_options);
    }

}

/*
 * Al widget le quiero agregar un jquery validate para validar el formulario
 */
?>