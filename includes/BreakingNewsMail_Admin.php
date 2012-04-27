<?php
/**
 * Description of BreakingNewsMail_Controller
 * @author Daniela Valero aka DaHe
 * @copyright 2012
 * @license http://www.gnu.org/licenses/gpl.html GPL v2
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
    }

    
     /*
     * Add the administration menues
     * @since 1
     *     
     */
    function bnm_add_page() {        
        $settings = add_menu_page('Breaking news mail', 'Breaking Settings', 'manage_options', 'bnm-menu', array($this, 'bnm_settings_page'));
        add_action("admin_print_scripts-$settings", array(&$this->objController, 'checkbox_form_js'));
        $subscrbers = add_submenu_page('bnm-menu', 'Subscribers', 'Subscribers', 'manage_options', 'bnm_settings', array(&$this, 'bnm_subscribers_page'));
        add_action("admin_print_scripts-$subscrbers", array(&$this->objController, 'checkbox_form_js'));
    }

    
     /*
     * Draw the option page
     * @since 1
     *     
     */
    function bnm_settings_page() {
         $this->bnm_options = $this->objController->save_settings($_POST);         
        ?>
        <div class="wrap">
            <div id="icon-tools" class="icon32"></div>
            <h2>Breaking News Mail Settings Page</h2>
            <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
        <?php settings_fields('bnm_settings_options'); ?>
                <h3>General settings</h3>
        <?php do_settings_sections('bnm_settings'); ?>
                <br /><br />
                <?php $this->display_category_form(explode(',', $this->bnm_options['include'])); ?>
                <br /><br />
                Add Tracking Parameters to the Permalink:
                <input type="text" name="tracking" value="<?php echo stripslashes($this->bnm_options['tracking']) ?>" size="50" />
                <br />eg. utm_source= bnm&utm_medium=email&utm_campaign=postnotify
                <br /><br />
                <h3> Email sending settings</h3>
                <br /><br />
                sender_email email:
                <input type="text" name="sender_email" value="<?php echo stripslashes($this->bnm_options['sender_email']) ?>" size="50" />
                <br /><br />                
                 Email excerpt format:
                <label>
                    <input type="radio" name="email_format" value="html"<?php checked($this->bnm_options['email_format'], 'html', true) ?> />
                    HTML
                </label>&nbsp;&nbsp;

                <label>
                   <input type="radio" name="email_format" value="text" <?php checked($this->bnm_options['email_format'], 'text', true) ?> />
                   Plain Text
                </label><br /><br />

                <label>
                    Set default page to be shown to users as confirmation message:
                    <select name="page">
                    <?php echo $this->objController->pages_dropdown($this->bnm_options['bnmpage']); ?>
                    </select>
                </label><br /><br />

                <div class="" id="bnm_template">
                    <h3>Email templates</h3>
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
                                    <dt><b>{POST}</b></dt><dd> the excerpt of the entire post </dd>
                                    <dt><b>{PERMALINK}</b></dt><dd> the post's permalink<br />(<i>for per-post emails only</i>) </dd>
                                    <dt><b>{DATE}</b></dt><dd> the date the post was made<br />(<i>for per-post emails only</i>) </dd>
                                    <dt><b>{TIME}</b></dt><dd> the time the post was made<br />(<i>for per-post emails only</i>) </dd>
                                    <dt><b>{LINK}</b></dt><dd> the generated link to confirm a request<br />(<i>only used in the confirmation email template</i>) </dd>
                                    <dt><b>{CONFIRMATION_ACTION}</b></dt><dd> Action performed by LINK in confirmation email<br />(<i>only used in the confirmation email template</i>) </dd>
                                    <dt><b>{UNSUBSCRIBE_ACTION}</b></dt><dd> This generate a link for unsubscriptions, it is used on email notifications</dd>
                                </dl></td></tr><tr><td>
                                Subscribe Subscribe confirmation email:<br />
                                Subject Line:
                                <input type="text" name="confirm_subject" value="<?php echo stripslashes($this->bnm_options['confirm_subject']) ?>" size="30" /><br />
                                <textarea rows="9" cols="60" name="confirm_email"><?php echo stripslashes($this->bnm_options['confirm_email']) ?></textarea><br /><br />
                            </td></tr>
                        <tr valign="top"><td>
                                Unsubscribe confirmation confirmation email:<br />
                                Subject Line:
                                <input type="text" name="remind_subject" value="<?php echo stripslashes($this->bnm_options['unsubscribe_subject']) ?>" size="30" /><br />
                                <textarea rows="9" cols="60" name="remind_email"><?php echo stripslashes($this->bnm_options['unsubscribe_email']) ?></textarea><br /><br />
                            </td></tr></table><br />
                </div>
                <input name="Submit" type="submit" value="Save Changes" />
            </form></div>
        <?php

    }

    
    /*
     * Draw the option page
     * @since 1
     *   
     */
    function bnm_subscribers_page() {
      global $bnmnonce;
        if (isset($_GET['bnmpage'])) {
            $page = (int) $_GET['bnmpage'];
        } else {
            $page = 1;
        }
        $search_term = isset($_POST['searchterm']) ? $_POST["searchterm"] : "";

        $getted_subscribers = $this->objController->process_subscribers_admin_form($_POST);

        $this->bnm_options = $this->objController->get_bnm_options();

        $subscribers = Array();
        foreach ($getted_subscribers as $key => $value) {
            $what = $key;
            $subscribers = $value;
        }

        $confirmed = $this->objController->get_all_emails();
        $unconfirmed = $this->objController->get_all_emails(0);

        /* Pagination */
        if (!empty($subscribers)) {
            natcasesort($subscribers);
            // Displays a page number strip - adapted from code in Akismet
            $args['what'] = $what;
            $total_subscribers = count($subscribers);
            $total_pages = ceil($total_subscribers / $this->bnm_options['entries']);
            $strip = '';
            if ($page > 1) {
                $args['bnmpage'] = $page - 1;
                $strip .= '<a class="prev" href="' . esc_url(add_query_arg($args)) . '">&laquo; ' . 'Previous Page' . '</a>' . "\n";
            }
            if ($total_pages > 1) {
                for ($page_num = 1; $page_num <= $total_pages; $page_num++) {
                    if ($page == $page_num) {
                        $strip .= "<strong>Page " . $page_num . "</strong>\n";
                    } else {
                        if ($page_num < 3 || ( $page_num >= $page - 2 && $page_num <= $page + 2 ) || $page_num > $total_pages - 2) {
                            $args['bnmpage'] = $page_num;
                            $strip .= "<a class=\"page-numbers\" href=\"" . esc_url(add_query_arg($args)) . "\">" . $page_num . "</a>\n";
                            $trunc = true;
                        } elseif ($trunc == true) {
                            $strip .= "...\n";
                            $trunc = false;
                        }
                    }
                }
            }
            if (( $page ) * $this->bnm_options['entries'] < $total_subscribers) {
                $args['bnmpage'] = $page + 1;
                $strip .= "<a class=\"next\" href=\"" . esc_url(add_query_arg($args)) . "\">" . 'Next Page' . " &raquo;</a>\n";
            }
        }

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
                    <p class="submit" style="border-top: none;">
                        <input type="submit" class="button-primary" name="subscribe" value="Subscribe" />
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
                        <tr class="alternate">
                            <td >
                                <input type="text" name="searchterm" value="<?php  $search_term ?>" />
                            </td>
                            <td>
                                <input type="submit" class="button-secondary" name="search" value="Search Subscribers" />
                            </td>
                            <td width="25%"></td>
            <?php
        if (!empty($subscribers)) {
            $exportcsv = Array();
            $exportcsv = implode(",rn", $subscribers);
            ?>
                                <td width="25%" align="right">
                                   <!-- <input type="hidden" name="exportcsv" value="<?php echo $exportcsv; ?>" /> -->
                                    <input type="submit" class="button-secondary" name="csv" value="Save Emails to CSV File" /></td>
                            <?php } else { ?>
                                <td width="25%"></td>
                            <?php } ?>
                        </tr>

        <?php if (!empty($subscribers)) { ?>
               <?php if (!empty($strip)) { ?>
                            <tr>
                                <td colspan="4" align="right">
                                    <?php echo $strip; ?>
                                </td>
                            </tr>
               <?php } ?>
       <?php
             if (is_int($this->bnm_options['entries'])) {
                 $subscriber_chunks = array_chunk($subscribers, $this->bnm_options['entries']);
             } else {
                 $subscriber_chunks = array_chunk($subscribers, 25);
             }
             $chunk = $page - 1;
             $subscribers = $subscriber_chunks[$chunk];
         ?>
                            <tr class="alternate" style="height:1.5em;">
                                <td width="8%" align="left">
                                   <strong> Delete email </strong>
                                </td>
                                <td align="center">
                                   <strong>  Email </strong>
                                </td>
                                <td align="center">
                                   <strong> Sign up date </strong>
                                </td>
                                <td align="center">
                                   <strong> IP </strong>
                                </td>

                            </tr>
                            <tr class="">
                                <td colspan ="4" align="left"><input type="checkbox" name="checkall" value="delete_checkall" />Delete all (<strong>Select / Unselect All</strong>)</td>
                            </tr>

            <?php foreach ($subscribers as $subscriber) { ?>
                            <tr class="<?php echo $alternate; ?>" style="height:1.5em;">
                                <td align="left">
            <?php if (in_array($subscriber, $confirmed)) { ?>
                                    <input class="delete_checkall" title="Delete this email address" type="checkbox" name="delete[]" value="<?php echo $subscriber; ?>" />
                                </td>
                                <td align="center">
                                    <span style="color:#006600">&#x221A;&nbsp;&nbsp;</span>
                                    <a href="mailto:<?php echo $subscriber; ?>"><?php echo $subscriber; ?></a>
                                </td>

                                <td align="center">
                                   <span style="color:#006600"><?php echo $this->objController->get_signup_date($subscriber) ?></span>
                                </td>
                                <td align="center">
                                    <abbr title="<?php echo $this->objController->get_signup_ip($subscriber) ?>"> </abbr>

                <?php } elseif (in_array($subscriber, $unconfirmed)) { ?>
                                    <input class="delete_checkall" title="Delete this email address" type="checkbox" name="delete[]" value="<?php echo $subscriber; ?>" />
                                </td>
                                <td align="center">
                                    <span style="color:#FF0000">&nbsp;! (unconfirmed)&nbsp;</span>
                                    <a href="mailto:<?php echo $subscriber; ?>"><?php echo $subscriber; ?></a>
                                </td>
                                <td align="center">
                                    <span style="color:#FF0000"><?php echo $this->objController->get_signup_date($subscriber) ?></span>
                                </td>
                                <td align="center">
                                    <abbr title="<?php echo $this->objController->get_signup_ip($subscriber) ?>"></abbr>

                    <?php } ?>
                                </td>
                                </tr>
                    <?php ($alternate == 'alternate') ? $alternate = '' : $alternate = 'alternate';
                                            } //end foreach
         }
         if (empty($subscribers)){
             if ($search_term) { ?>
                      <tr><td colspan="4" align="center"><b>No matching subscribers found</b></td></tr>
              <?php } else { ?>
              <tr><td colspan="4" align="center"><b>There are not subscribers yet</b></td></tr>
             <?php }
          }
          if (!empty($subscribers)) { ?>
                      <tr class="<?php echo $alternate; ?>">
                          <td colspan="3" align="center">
                              <input type="submit" class="button-secondary" name="process" value="Process" />
                          </td>
                          <td colspan="3" align="right"><?php echo $strip; ?></td>
                      </tr>
          <?php } ?>
               </table>
            </div>
         </form>
       </div>
  <?php

    }

    /*
     * Display a table of categories with checkboxes
     * Optionally pre-select those categories specified
     * @since 1
     *    
     */
    function display_category_form($selected = array(), $override = 1) {    
        if ($override == 0) {
            $all_cats = $this->objController->all_cats(true);
        } else {
            $all_cats = $this->objController->all_cats(false);
        }
        $half = (count($all_cats) / 2);
        $i = 0;
        $j = 0;
        ?>
        <label><strong>Check the categories you want to include from the breaking news email alerts</strong></label>
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