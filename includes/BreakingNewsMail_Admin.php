<?php
/**
 * The admin class, handles the activities of the admin
 * dashboard involing the plugin.
 * 
 * @author Daniela VAlero
 * @copyright 2012
 * @license http://www.gnu.org/licenses/gpl.html GPL v3 
 * @since 1.0
 * @package WP-Breaking-News-Mail
 */
require_once 'BreakingNewsMail_Controller.php';

class BreakingNewsMail_Admin {

    private $bnm_options = array();
    private $objController;

    function __construct() {
        add_action('admin_menu', array(&$this, 'bnm_add_page'));
        $this->bnm_options = get_option('bnm_options');
        $this->objController = new BreakingNewsMail_Controller();
        //update_option('bnm_options', $this->bnm_options);
    }

    function bnm_add_page() {
        add_menu_page('Breaking news mail', 'Breaking Settings', 'manage_options', 'bnm-menu', array($this, 'bnm_settings_page'));
        add_submenu_page('bnm-menu', 'Subscribers', 'Subscribers', 'manage_options', 'bnm_settings', array(&$this, 'bnm_subscribers_page'));
    }

    // Draw the option page
    function bnm_settings_page() {
        $this->bnm_options = get_option('bnm_options');
       
        ?>
        <div class="wrap">
            <div id="icon-tools" class="icon32"></div>
            <h2>Breaking News Mail Settings Page</h2>
            <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
        <?php settings_fields('bnm_settings_options'); ?>
        <?php do_settings_sections('bnm_settings'); ?>
                <br /><br />
                <?php $this->display_category_form(explode(',', $this->bnm_options['exclude'])); ?>

                <br /><br />
                Add Tracking Parameters to the Permalink:
                <input type="text" name="tracking" value="<?php echo stripslashes($this->bnm_options['tracking']) ?>" size="50" />
                <br />eg. utm_source= bnm&utm_medium=email&utm_campaign=postnotify<br /><br />

                <br /><br />
                sender_email email:
                <input type="text" name="sender_email" value="<?php echo stripslashes($this->bnm_options['sender_email']) ?>" size="50" />
                <br /><br />


                <div class="" id="bnm_template">
                    <h2>Email templates</h2>
                    <br />
                    <table width="100%" cellspacing="2" cellpadding="1" class="editform">
                        <tr><td>
                                New Post email (must not be empty):<br /> 
                                Subject line: 
                                <input type="text" name="notification_subject" value="<?php echo stripslashes($this->bnm_options['notification_subject']); ?>" size="30" />
                                <br />
                                <textarea rows="9" cols="60" name="mailtext" value="<?php echo stripslashes($this->bnm_options['mailtext']) ?>"><?php echo stripslashes($this->bnm_options['mailtext']) ?></textarea><br /><br />
                            </td><td valign="top" rowspan="3">
                                <p class="submit"><input type="submit" class="button-secondary" name="preview" value="Send Email Preview" /></p>
                                <h3>Message substitutions</h3>
                                <dl>
                                    <dt><b><em style="color: red"> IF THE FOLLOWING KEYWORDS ARE ALSO IN YOUR POST THEY WILL BE SUBSTITUTED</em></b></dt><dd></dd>
                                    <dt><b>{BLOGNAME}</b></dt><dd><?php get_option('blogname') ?></dd>
                                    <dt><b>{BLOGLINK}</b></dt><dd><?php get_option('home') ?></dd>
                                    <dt><b>{TITLE}</b></dt><dd> the post's title<br />(<i>for per-post emails only</i>) </dd>
                                    <dt><b>{POST}</b></dt><dd> the excerpt or the entire post<br />(<i>based on the subscriber's preferences</i>) </dd>		 		 		 
                                    <dt><b>{PERMALINK}</b></dt><dd> the post's permalink<br />(<i>for per-post emails only</i>) </dd>		 
                                    <dt><b>{DATE}</b></dt><dd> the date the post was made<br />(<i>for per-post emails only</i>) </dd>
                                    <dt><b>{TIME}</b></dt><dd> the time the post was made<br />(<i>for per-post emails only</i>) </dd>
                                    <dt><b>{LINK}</b></dt><dd> the generated link to confirm a request<br />(<i>only used in the confirmation email template</i>) </dd> 
                                    <dt><b>{CONFIRMATION_ACTION}</b></dt><dd> Action performed by LINK in confirmation email<br />(<i>only used in the confirmation email template</i>) </dd> 
                                    <dt><b>{UNSUBSCRIBE_ACTION}</b></dt><dd> Action performed by LINK in confirmation email<br />(<i>only used in the confirmation email template</i>) </dd> 
                                    <dt><b>{CATS}</b></dt><dd> the post's assigned categories </dd> 
                                    <dt><b>{TAGS}</b></dt><dd> the post's assigned Tags </dd> 
                                </dl></td></tr><tr><td>
                                Subscribe / Unsubscribe confirmation email:<br /> 
                                Subject Line:  
                                <input type="text" name="confirm_subject" value="<?php echo stripslashes($this->bnm_options['confirm_subject']) ?>" size="30" /><br /> 
                                <textarea rows="9" cols="60" name="confirm_email"><?php echo stripslashes($this->bnm_options['confirm_email']) ?></textarea><br /><br /> 
                            </td></tr><tr valign="top"><td> 
                                Reminder email to Unconfirmed Subscribers:<br /> 
                                Subject Line:  
                                <input type="text" name="remind_subject" value="<?php echo stripslashes($this->bnm_options['remind_subject']) ?>" size="30" /><br /> 
                                <textarea rows="9" cols="60" name="remind_email"><?php echo stripslashes($this->bnm_options['remind_email']) ?></textarea><br /><br /> 
                            </td></tr></table><br /> 
                </div> 
                <input name="Submit" type="submit" value="Save Changes" />
            </form></div>
        <?php
        $this->objController->save_settings($_POST);
    }

    // Draw the option page
    function bnm_subscribers_page() {
        global $wpdb, $bnmnonce;
      
        ?>
        <div class="wrap">
            <div id="icon-tools" class="icon32"></div>
            <h2>Breaking News Mail Subscribers Page</h2>
            <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
        <?php settings_fields('bnm_subscribers_options'); ?>
        <?php do_settings_sections('bnm_subscribers'); ?>
        <?php
        if (function_exists('wp_nonce_field')) {
            wp_nonce_field('bnm-manage_subscribers' . $bnmnonce);
        }
        ?>
                <div class="bnm_admin" id="bnm_add_subscribers">
                    <h2>Add/Remove Subscribers</h2>
                    <p>Enter addresses, one per line or comma-separated<br />
                        <textarea rows="2" cols="80" name="addresses"></textarea></p>
                    <input type="hidden" name="bnm_admin" />
                    <p class="submit" style="border-top: none;"><input type="submit" class="button-primary" name="subscribe" value="Subscribe" />
                        &nbsp;<input type="submit" class="button-primary" name="unsubscribe" value="Unsubscribe" /></p>
                </div>

        <?php // subscriber lists     ?>
                <div class="bnm_admin" id="bnm_current_subscribers">
                    <h2>Current Subscribers</h2>
                    <br /><br />
        <?php
        // show the selected subscribers 
        $alternate = 'alternate';
        ?>
                    <table class="widefat" cellpadding="2" cellspacing="2" width="100%">
        <?php $searchterm = ( $_POST['searchterm'] ) ? $_POST['searchterm'] : '0'; ?>
                        <tr class="alternate">
                            <td colspan="3">
                                <input type="text" name="searchterm" value="<?php $searchterm ?>" />
                            </td>
                            <td>
                                <input type="submit" class="button-secondary" name="search" value="Search Subscribers" />
                            </td>
        <?php   $reminderform = ( $_POST['reminderform'] ) ? $_POST['reminderform'] : '0';
        if ($reminderform) { ?>
                                <td width="25%" align="right"><input type="hidden" name="reminderemails" value="<?php $reminderemails ?>" />
                                    <input type="submit" class="button-secondary" name="remind" value="Send Reminder Email" /></td>
        <?php } else { ?>
                                <td width="25%"></td>
            <?php
        }
        if (!empty($subscribers)) {
            $exportcsv = implode(",rn", $subscribers);
            ?>
                                <td width="25%" align="right"><input type="hidden" name="exportcsv" value="" />
                                    <input type="submit" class="button-secondary" name="csv" value="Save Emails to CSV File" /></td>
                            <?php } else { ?>
                                <td width="25%"></td>
                            <?php } ?>
                        </tr>

        <?php if (!empty($subscribers)) { ?>
                            <tr><td colspan="3" align="center">
                                    <input type="submit" class="button-secondary" name="process" value="Process" />
                                </td>
                                <td colspan="3" align="right"><?php $strip ?></td>
                            </tr>
                            <?php
                        }
                        if (!empty($subscribers)) {
                            if (is_int($this->bnm_options['entries'])) {
                                $subscriber_chunks = array_chunk($subscribers, $this->bnm_options['entries']);
                            } else {
                                $subscriber_chunks = array_chunk($subscribers, 25);
                            }
                            $chunk = $page - 1;
                            $subscribers = $subscriber_chunks[$chunk];
                            ?>
                            <tr class="alternate" style="height:1.5em;">
                                <td width="4%" align="center">
                                    <img src="<?php $urlpath ?> images/accept.png" alt="&lt;" title="Confirm this email address" /></td>
                                <td width="4%" align="center">
                                    <img src="<?php $urlpath ?> images/exclamation.png" alt="&gt;" title="Unconfirm this email address" /></td>
                                <td width="4%" align="center">
                                    <img src="<?php $urlpath ?> images/cross.png" alt="X" title="Delete this email address" /></td><td colspan="3"></td></tr>
                            <tr class="">
                                <td align="center"><input type="checkbox" name="checkall" value="confirm_checkall" /></td>
                                <td align="center"><input type="checkbox" name="checkall" value="unconfirm_checkall" /></td>
                                <td align="center"><input type="checkbox" name="checkall" value="delete_checkall" /></td>
                                <td colspan ="3" align="left"><strong>Select / Unselect All</strong></td>
                            </tr>

            <?php foreach ($subscribers as $subscriber) { ?>
                                <tr class="<?php $alternate ?>" style="height:1.5em;">";
                                    <td align="center">
                <?php if (in_array($subscriber, $confirmed)) { ?>
                                        </td><td align="center">
                                            <input class="unconfirm_checkall" title="Unconfirm this email address" type="checkbox" name="unconfirm[]" value="<?php $subscriber ?>" /></td>
                                        <td align="center">
                                            <input class="delete_checkall" title="Delete this email address" type="checkbox" name="delete[]" value="<?php $subscriber ?>" />
                                        </td>
                                        <td colspan="3">
                                            <span style="color:#006600">&#x221A;&nbsp;&nbsp;</span>
                                            <abbr title="<?php $this->signup_ip($subscriber) ?>">
                                                <a href="mailto:<?php $subscriber ?>"><?php $subscriber ?></a></abbr>
                                            (<span style="color:#006600"><?php $this->signup_date($subscriber) ?></span>)

                <?php } elseif (in_array($subscriber, $unconfirmed)) { ?>
                                            <input class="confirm_checkall" title="Confirm this email address" type="checkbox" name="confirm[]" value="<?php $subscriber ?>" />
                                        </td>
                                        <td align="center"></td>
                                        <td align="center">
                                            <input class="delete_checkall" title="Delete this email address" type="checkbox" name="delete[]" value="<?php $subscriber ?>" />
                                        </td>
                                        <td colspan="3">
                                            <span style="color:#FF0000">&nbsp;!&nbsp;&nbsp;&nbsp;</span>
                                            <abbr title="<?php $this->signup_ip($subscriber) ?>"><a href="mailto:<?php $subscriber ?>">
                    <?php $subscriber ?></a>
                                            </abbr>
                                            (<span style="color:#FF0000"><?php $this->signup_date($subscriber) ?></span>)

                    <?php
                } elseif (in_array($subscriber, $all_users)) {
                    $user_info = get_user_by('email', $subscriber);
                    ?>
                                        </td><td align="center"></td>
                                        <td align="center"></td>
                                        <td colspan="3">
                                            <span style="color:#006600">&reg;&nbsp;&nbsp;</span>
                                            <abbr title="<?php $user_info->user_login ?>"><a href="mailto:<?php $subscriber ?>">
                                            <?php $subscriber ?></a>
                                            </abbr>
                                            (<a href="<?php get_option('siteurl') ?>/wp-admin/admin.php?page=bnm&amp;email=<?php urlencode($subscriber) ?>">edit</a>)
                <?php } ?>
                                    </td>
                                </tr>
                                                <?php
                                                ('alternate' == $alternate) ? $alternate = '' : $alternate = 'alternate';
                                            }
                                        } else {
                                            if ($_POST['searchterm']) {
                                                ?>
                                <tr><td colspan="6" align="center"><b>No matching subscribers found</b></td></tr>
                            <?php } else { ?>
                                <tr><td colspan="6" align="center"><b>There are not subscribers yet</b></td></tr>
                                <?php
                            }
                        }
                        if (!empty($subscribers)) {
                            ?>
                            <tr class="<?php $alternate ?>"><td colspan="3" align="center"><input type="submit" class="button-secondary" name="process" value="Process" /></td>
                                <td colspan="3" align="right"><?php $strip ?></td></tr>
                        <?php } ?>
                    </table>
                </div>
            </form>
        </div>
        <?php
        $this->objController->process_subscribers_admin_form($_POST);
    }

    /**
      Display a table of categories with checkboxes
      Optionally pre-select those categories specified
     */
    function display_category_form($selected = array(), $override = 1) {
        global $wpdb;

        if ($override == 0) {
            $all_cats = $this->objController->all_cats(true);
        } else {
            $all_cats = $this->objController->all_cats(false);
        }

        $half = (count($all_cats) / 2);
        $i = 0;
        $j = 0;
        ?>
        <label><strong>Check the categories you want to exclude from the breaking news email alerts</strong></label>
        <table width="30%" cellspacing="2" cellpadding="5" class="editform">
            <tr><td align="left" colspan="2">
                    <label><input type="checkbox" name="checkall" value="checkall_cat" /> Select / Unselect All</label>
                </td></tr>
            <tr valign="top"><td width="15%" align="left">
        <?php
        foreach ($all_cats as $cat) {
            if ($i >= $half && 0 == $j) {
                ?> </td><td width="15%" align="left"><?php
                $j++;
            }
            $catName = '';
            $parents = array_reverse(get_ancestors($cat->term_id, $cat->taxonomy));
            if ($parents) {
                foreach ($parents as $parent) {
                    $parent = get_term($parent, $cat->taxonomy);
                    $catName .= $parent->name . ' &raquo; ';
                }
            }
            $catName .= $cat->name;

            if (0 == $j) {
                echo"<label><input class=\"checkall_cat\" type=\"checkbox\" name=\"category[]\" value=\"" . $cat->term_id . "\"";
                if (in_array($cat->term_id, $selected)) {
                    echo" checked=\"checked\"";
                }
                echo" /> <abbr title=\"" . $cat->slug . "\">" . $catName . "</abbr></label><br />\r\n";
            } else {
                echo"<label><input class=\"checkall_cat\" type=\"checkbox\" name=\"category[]\" value=\"" . $cat->term_id . "\"";
                if (in_array($cat->term_id, $selected)) {
                    echo" checked=\"checked\"";
                }
                echo" /> <abbr title=\"" . $cat->slug . "\">" . $catName . "</abbr></label><br />\r\n";
            }
            $i++;
        }
        ?></td></tr>
        </table> <?php
        }


    }
?>
