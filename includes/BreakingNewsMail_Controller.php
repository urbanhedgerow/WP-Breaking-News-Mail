<?php

/**
 * Description of BreakingNewsMail_Controller
 *
 * @author daniela
 */
//define('BNM_USERS', $wpdb->get_blog_prefix() . 'bnm_users');
class BreakingNewsMail_Controller {

    private $signup_dates = array();
    private $signup_ips = array();
    private $bnm_options = array();
    private $all_public = '';
    private $all_unconfirmed = '';
    private $all_authors = '';
    private $excluded_cats = '';
    private $post_title = '';
    private $permalink = '';
    private $post_date = '';
    private $post_time = '';
    private $myname = '';
    private $myemail = '';
    private $signup_dates = array();
    private $filtered = 0;
    private $preview_email = false;
    // state variables used to affect processing
    private $action = '';
    private $email = '';
    private $message = '';
    private $excerpt_length = 55;
    // some messages
    private $please_log_in = '';
    private $profile = '';
    private $confirmation_sent = '';
    private $already_subscribed = '';
    private $not_subscribed = '';
    private $not_an_email = '';
    private $error = '';
    private $mail_sent = '';
    private $mail_failed = '';
    private $form = '';
    private $no_such_email = '';
    private $added = '';
    private $deleted = '';
    private $subscribe = '';
    private $unsubscribe = '';
    private $confirm_subject = '';
    private $options_saved = '';
    private $options_reset = '';

    function __construct() {
        $this->bnm_options = get_option('bnm_options');
        //update_option('bnm_options', $this->bnm_options);
    }

    /**
     * adds a subscriber
     * 
     * @since 1
     *
     * @param    email    $email    user's email
     * @param    ip    $ip    ip from user's compurter
     * @return   boolean  true if success 
     */
    function add_subscriptor($email, $ip) {
        global $wpdb;

        if (!is_email($email)) {
            return false;
        }
        if ($this->is_email_subcribed($email))
            return false;

        $wpdb->get_results($wpdb->prepare("INSERT INTO " . BNM_USERS . " (email, active, date, ip) VALUES (%s, %d, NOW(), %s)", $email, 0, $ip));
    }

    /**
     * confirm a subscriber
     * 
     * @since 1
     *
     * @param    email    $email    users email
     * @return   boolean  true if success 
     */
    function confirm_subscriptor($email) {
        global $wpdb;

        if (!is_email($email)) {
            return false;
        }
        if ($this->is_email_subcribed($email))
            return false;

        $wpdb->get_results("UPDATE " . BNM_USERS . " SET active='1' WHERE CAST(email as binary)='$email'");
    }

    /**
     * delete a subscriber
     * 
     * @since 1
     *
     * @param    email    $email    users email
     * @return   boolean  true if success 
     */
    function delete_subscriptor($email) {
        global $wpdb;

        if (!is_email($email)) {
            return false;
        }
        if (!$this->is_email_subcribed($email))
            return false;

        //$wpdb->get_results("DELETE FROM " . BNM_USERS . " WHERE CAST(email as binary)='$email'");
        $wpdb->get_results("UPDATE " . BNM_USERS . " SET status='0' WHERE CAST(email as binary)='$email'");
    }

    /*
     * Collects the signup date for all  subscribers
     * 
     * @since 1
     *
     * @param    email    $email    users email
     * @return   array
     */

    function get_signup_date($email = '') {
        if ('' == $email) {
            return false;
        }

        global $wpdb;
        if (!empty($this->signup_dates)) {
            return $this->signup_dates[$email];
        } else {
            $results = $wpdb->get_results("SELECT email, date FROM " . BNM_USERS . "", ARRAY_N);
            foreach ($results as $result) {
                $this->signup_dates[$result[0]] = $result[1];
            }
            return $this->signup_dates[$email];
        }
    }

    /*
     * Collects the ip address for all public subscribers
     * 
     * @since 1
     *
     * @param    email    $email    users email
     * @return   array
     */

    function get_signup_ip($email = '') {
        if ('' == $email) {
            return false;
        }

        global $wpdb;
        if (!empty($this->signup_ips)) {
            return $this->signup_ips[$email];
        } else {
            $results = $wpdb->get_results("SELECT email, ip FROM " . BNM_USERS . "", ARRAY_N);
            foreach ($results as $result) {
                $this->signup_ips[$result[0]] = $result[1];
            }
            return $this->signup_ips[$email];
        }
    }

    /*
     * Collects the email address for all  confirmed  or unconfirmed subscribers
     * default confirmed
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   array
     */

    function get_all_emails($confirmed = 1) {
        global $wpdb;
        if (1 == $confirmed) {
            if ('' == $this->all_public) {
                $this->all_public = $wpdb->get_col("SELECT email FROM " . BNM_USERS . " WHERE active='1' and status='1' ");
            }
            return $this->all_public;
        } else {
            if ('' == $this->all_unconfirmed) {
                $this->all_unconfirmed = $wpdb->get_col("SELECT email FROM " . BNM_USERS . " WHERE active='0' and status='1'");
            }
            return $this->all_unconfirmed;
        }
    }

    /*
     * Given a public subscriber ID, returns the email address
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   array
     */

    function get_single_email($id = 0) {
        global $wpdb;

        if (!$id) {
            return false;
        }
        return $wpdb->get_var("SELECT email FROM " . BNM_USERS . " WHERE id=$id");
    }

    /*
     * validate if an email is already registered
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    private function is_email_subscribed($email) {
        //verifica que el usuario no esté suscrito        
        $isSuscribed = false;
        $results = $wpdb->get_results("SELECT email FROM " . BNM_USERS . "", ARRAY_N);
        foreach ($results as $result) {
            if ($result[0] == $email)
                $isSuscribed = true;
        }
        return $isSuscribed;
    }

  

    

    /* Display our form; also handles (un)subscribe requests
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function shortcode($atts) {
        extract(shortcode_atts(array(
                    'hide' => '',
                    'id' => '',
                    'url' => '',
                    'nojs' => 'false',
                    'link' => '',
                    'size' => 20
                        ), $atts));

        // if link is true return a link to the page with the ajax class
        if ($link !== '' && !is_user_logged_in()) {
            $this->s2form = "<a href=\"" . get_permalink($this->bnm_options['s2page']) . "\" class=\"s2popup\">" . $link . "</a>\r\n";
            return $this->s2form;
        }

        // if a button is hidden, show only other
        if ($hide == 'subscribe') {
            $this->input_form_action = "<input type=\"submit\" name=\"unsubscribe\" value=\"" . __('Unsubscribe', 'bnm') . "\" />";
        } elseif ($hide == 'unsubscribe') {
            $this->input_form_action = "<input type=\"submit\" name=\"subscribe\" value=\"" . __('Subscribe', 'bnm') . "\" />";
        } else {
            // both form input actions
            $this->input_form_action = "<input type=\"submit\" name=\"subscribe\" value=\"" . __('Subscribe', 'bnm') . "\" />&nbsp;<input type=\"submit\" name=\"unsubscribe\" value=\"" . __('Unsubscribe', 'bnm') . "\" />";
        }
        // if ID is provided, get permalink
        if ($id) {
            $url = get_permalink($id);
        } elseif ($this->bnm_options['s2page'] > 0) {
            $url = get_permalink($this->bnm_options['s2page']);
        } else {
            $url = get_site_url();
        }
        // build default form
        if ($nojs == 'true') {
            $this->form = "<form method=\"post\" action=\"" . $url . "\"><input type=\"hidden\" name=\"ip\" value=\"" . $_SERVER['REMOTE_ADDR'] . "\" /><p><label for=\"s2email\">" . __('Your email:', 'bnm') . "</label><br /><input type=\"text\" name=\"email\" id=\"s2email\" value=\"\" size=\"" . $size . "\" /></p><p>" . $this->input_form_action . "</p></form>";
        } else {
            $this->form = "<form method=\"post\" action=\"" . $url . "\"><input type=\"hidden\" name=\"ip\" value=\"" . $_SERVER['REMOTE_ADDR'] . "\" /><p><label for=\"s2email\">" . __('Your email:', 'bnm') . "</label><br /><input type=\"text\" name=\"email\" id=\"s2email\" value=\"" . __('Enter email address...', 'bnm') . "\" size=\"" . $size . "\" onfocus=\"if (this.value == '" . __('Enter email address...', 'bnm') . "') {this.value = '';}\" onblur=\"if (this.value == '') {this.value = '" . __('Enter email address...', 'bnm') . "';}\" /></p><p>" . $this->input_form_action . "</p></form>\r\n";
        }
        $this->s2form = $this->form;

        global $user_ID;
        get_currentuserinfo();
        if ($user_ID) {
            $this->s2form = $this->profile;
        }
        if (isset($_POST['subscribe']) || isset($_POST['unsubscribe'])) {
            global $wpdb, $user_email;
            if (!is_email($_POST['email'])) {
                $this->s2form = $this->form . $this->not_an_email;
            } elseif ($this->is_barred($_POST['email'])) {
                $this->s2form = $this->form . $this->barred_domain;
            } else {
                $this->email = $this->sanitize_email($_POST['email']);
                $this->ip = $_POST['ip'];
                // does the supplied email belong to a registered user?
                $check = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE user_email = '$this->email'");
                if ('' != $check) {
                    // this is a registered email
                    $this->s2form = $this->please_log_in;
                } else {
                    // this is not a registered email
                    // what should we do?
                    if (isset($_POST['subscribe'])) {
                        // someone is trying to subscribe
                        // lets see if they've tried to subscribe previously
                        if ('1' !== $this->is_public($this->email)) {
                            // the user is unknown or inactive
                            $this->add($this->email);
                            $status = $this->send_confirm('add');
                            // set a variable to denote that we've already run, and shouldn't run again
                            $this->filtered = 1;
                            if ($status) {
                                $this->s2form = $this->confirmation_sent;
                            } else {
                                $this->s2form = $this->error;
                            }
                        } else {
                            // they're already subscribed
                            $this->s2form = $this->already_subscribed;
                        }
                        $this->action = 'subscribe';
                    } elseif (isset($_POST['unsubscribe'])) {
                        // is this email a subscriber?
                        if (false == $this->is_public($this->email)) {
                            $this->s2form = $this->form . $this->not_subscribed;
                        } else {
                            $status = $this->send_confirm('del');
                            // set a variable to denote that we've already run, and shouldn't run again
                            $this->filtered = 1;
                            if ($status) {
                                $this->s2form = $this->confirmation_sent;
                            } else {
                                $this->s2form = $this->error;
                            }
                        }
                        $this->action = 'unsubscribe';
                    }
                }
            }
        }
        return $this->s2form;
    }

    /* Performs string substitutions for bnm mail tags
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function substitute_email_tags($string = '') {
        if ('' == $string) {
            return;
        }
        $string = str_replace("{BLOGNAME}", html_entity_decode(get_option('blogname'), ENT_QUOTES), $string);
        $string = str_replace("{BLOGLINK}", get_option('home'), $string);
        $string = str_replace("{TITLE}", stripslashes($this->post_title), $string);
        $link = "<a href=\"" . $this->get_tracking_link($this->permalink) . "\">" . $this->get_tracking_link($this->permalink) . "</a>";
        $string = str_replace("{PERMALINK}", $link, $string);
        if (strstr($string, "{TINYLINK}")) {
            $tinylink = file_get_contents('http://tinyurl.com/api-create.php?url=' . urlencode($this->get_tracking_link($this->permalink)));
            if ($tinylink !== 'Error' || $tinylink != false) {
                $tlink = "<a href=\"" . $tinylink . "\">" . $tinylink . "</a>";
                $string = str_replace("{TINYLINK}", $tlink, $string);
            } else {
                $string = str_replace("{TINYLINK}", $link, $string);
            }
        }
        $string = str_replace("{DATE}", $this->post_date, $string);
        $string = str_replace("{TIME}", $this->post_time, $string);
        $string = str_replace("{MYNAME}", stripslashes($this->myname), $string);
        $string = str_replace("{EMAIL}", $this->myemail, $string);
        $string = str_replace("{AUTHORNAME}", stripslashes($this->authorname), $string);
        $string = str_replace("{CATS}", $this->post_cat_names, $string);
        $string = str_replace("{TAGS}", $this->post_tag_names, $string);
        $string = str_replace("{COUNT}", $this->post_count, $string);

        return $string;
    }

    /* Delivers email to recipients in HTML or plaintext
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function deliver_email($recipients = array(), $subject = '', $message = '', $type = 'text') {
        if (empty($recipients) || '' == $message) {
            return;
        }

        if ('html' == $type) {
            $headers = $this->headers('html');
            if ('yes' == $this->bnm_options['stylesheet']) {
                $mailtext = apply_filters('s2_html_email', "<html><head><title>" . $subject . "</title><link rel=\"stylesheet\" href=\"" . get_stylesheet_uri() . "\" type=\"text/css\" media=\"screen\" /></head><body>" . $message . "</body></html>", $subject, $message);
            } else {
                $mailtext = apply_filters('s2_html_email', "<html><head><title>" . $subject . "</title></head><body>" . $message . "</body></html>", $subject, $message);
            }
        } else {
            $headers = $this->headers();
            $message = preg_replace('|&[^a][^m][^p].{0,3};|', '', $message);
            $message = preg_replace('|&amp;|', '&', $message);
            $message = wordwrap(strip_tags($message), 80, "\n");
            $mailtext = apply_filters('s2_plain_email', $message);
        }

        // Replace any escaped html symbols in subject then apply filter
        $subject = html_entity_decode($subject, ENT_QUOTES);
        $subject = apply_filters('s2_email_subject', $subject);

        // Construct BCC headers for sending or send individual emails
        $bcc = '';
        natcasesort($recipients);
        if (function_exists('wpmq_mail') || $this->bnm_options['bcclimit'] == 1 || count($recipients) == 1) {
            // BCCLimit is 1 so send individual emails or we only have 1 recipient
            foreach ($recipients as $recipient) {
                $recipient = trim($recipient);
                // sanity check -- make sure we have a valid email
                if (!is_email($recipient) || empty($recipient)) {
                    continue;
                }
                // Use the mail queue provided we are not sending a preview
                if (function_exists('wpmq_mail') && !$this->preview_email) {
                    @wp_mail($recipient, $subject, $mailtext, $headers, '', 0);
                } else {
                    @wp_mail($recipient, $subject, $mailtext, $headers);
                }
            }
            return true;
        } elseif ($this->bnm_options['bcclimit'] == 0) {
            // we're not using BCCLimit
            foreach ($recipients as $recipient) {
                $recipient = trim($recipient);
                // sanity check -- make sure we have a valid email
                if (!is_email($recipient)) {
                    continue;
                }
                // and NOT the sender's email, since they'll get a copy anyway
                if (!empty($recipient) && $this->myemail != $recipient) {
                    ('' == $bcc) ? $bcc = "Bcc: $recipient" : $bcc .= ", $recipient";
                    // Bcc Headers now constructed by phpmailer class
                }
            }
            $headers .= "$bcc\n";
        } else {
            // we're using BCCLimit
            $count = 1;
            $batch = array();
            foreach ($recipients as $recipient) {
                $recipient = trim($recipient);
                // sanity check -- make sure we have a valid email
                if (!is_email($recipient)) {
                    continue;
                }
                // and NOT the sender's email, since they'll get a copy anyway
                if (!empty($recipient) && $this->myemail != $recipient) {
                    ('' == $bcc) ? $bcc = "Bcc: $recipient" : $bcc .= ", $recipient";
                    // Bcc Headers now constructed by phpmailer class
                }
                if ($this->bnm_options['bcclimit'] == $count) {
                    $count = 0;
                    $batch[] = $bcc;
                    $bcc = '';
                }
                $count++;
            }
            // add any partially completed batches to our batch array
            if ('' != $bcc) {
                $batch[] = $bcc;
            }
        }
        // rewind the array, just to be safe
        reset($recipients);

        // actually send mail
        if (isset($batch) && !empty($batch)) {
            foreach ($batch as $bcc) {
                $newheaders = $headers . "$bcc\n";
                $status = @wp_mail($this->myemail, $subject, $mailtext, $newheaders);
            }
        } else {
            $status = @wp_mail($this->myemail, $subject, $mailtext, $headers);
        }
        return $status;
    }

    /*  Construct standard set of email headers
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function construct_standard__email_headers($type = 'text') {
        if (empty($this->myname) || empty($this->myemail)) {
            if ($this->bnm_options['sender'] == 'blogname') {
                $this->myname = html_entity_decode(get_option('blogname'), ENT_QUOTES);
                $this->myemail = get_option('admin_email');
            } else {
                $admin = $this->get_userdata($this->bnm_options['sender']);
                $this->myname = html_entity_decode($admin->display_name, ENT_QUOTES);
                $this->myemail = $admin->user_email;
                // fail safe to ensure sender details are not empty
                if (empty($this->myname)) {
                    $this->myname = html_entity_decode(get_option('blogname'), ENT_QUOTES);
                }
                if (empty($this->myemail)) {
                    // Get the site domain and get rid of www.
                    $sitename = strtolower($_SERVER['SERVER_NAME']);
                    if (substr($sitename, 0, 4) == 'www.') {
                        $sitename = substr($sitename, 4);
                    }
                    $this->myemail = 'wordpress@' . $sitename;
                }
            }
        }

        $header['From'] = $this->myname . " <" . $this->myemail . ">";
        $header['Reply-To'] = $this->myname . " <" . $this->myemail . ">";
        $header['Return-path'] = "<" . $this->myemail . ">";
        $header['Precedence'] = "list\nList-Id: " . html_entity_decode(get_option('blogname'), ENT_QUOTES) . "";
        if ($type == 'html') {
            // To send HTML mail, the Content-Type header must be set
            $header['Content-Type'] = get_option('html_type') . "; charset=\"" . get_option('blog_charset') . "\"";
        } else {
            $header['Content-Type'] = "text/plain; charset=\"" . get_option('blog_charset') . "\"";
        }

        // apply header filter to allow on-the-fly amendments
        $header = apply_filters('s2_email_headers', $header);
        // collapse the headers using $key as the header name
        foreach ($header as $key => $value) {
            $headers[$key] = $key . ": " . $value;
        }
        $headers = implode("\n", $headers);
        $headers .= "\n";

        return $headers;
    }

    /*  Function to add UTM tracking details to links
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function get_tracking_link($link) {
        if (!empty($this->bnm_options['tracking'])) {
            $delimiter = '?';
            if (strpos($link, $delimiter) > 0) {
                $delimiter = '&';
            }
            return $link . $delimiter . $this->bnm_options['tracking'];
        } else {
            return $link;
        }
    }
    
    
    
    
    /*  Send confirmation email to a public subscriber
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function send_confirm($what = '', $is_remind = false) {       
        if (!$this->email || !$what) {
            return false;
        }
        $id = $this->get_id($this->email);
        if (!$id) {
            return false;
        }

        // generate the URL "?bnm=ACTION+HASH+ID"
        // ACTION = 1 to subscribe, 0 to unsubscribe
        // HASH = md5 hash of email address
        // ID = user's ID in the bnm table
        // use home instead of siteurl incase index.php is not in core wordpress directory
        $link = get_option('home') . "/?bnm=";

        if ('add' == $what) {
            $link .= '1';
        } elseif ('del' == $what) {
            $link .= '0';
        }
        $link .= md5($this->email);
        $link .= $id;

        // sort the headers now so we have all substitute_email_tags information
        $mailheaders = $this->construct_standard__email_headers();

        if ($is_remind == true) {
            $body = $this->substitute_email_tags(stripslashes($this->bnm_options['remind_email']));
            $subject = $this->substitute_email_tags(stripslashes($this->bnm_options['remind_subject']));
        } else {
            $body = $this->substitute_email_tags(stripslashes($this->bnm_options['confirm_email']));
            if ('add' == $what) {
                $body = str_replace("{ACTION}", $this->subscribe, $body);
                $subject = str_replace("{ACTION}", $this->subscribe, $this->bnm_options['confirm_subject']);
            } elseif ('del' == $what) {
                $body = str_replace("{ACTION}", $this->unsubscribe, $body);
                $subject = str_replace("{ACTION}", $this->unsubscribe, $this->bnm_options['confirm_subject']);
            }
            $subject = html_entity_decode($this->substitute_email_tags(stripslashes($subject)), ENT_QUOTES);
        }

        $body = str_replace("{LINK}", $link, $body);

        if ($is_remind == true && function_exists('wpmq_mail')) {
            // could be sending lots of reminders so queue them if wpmq is enabled
            @wp_mail($this->email, $subject, $body, $mailheaders, '', 0);
        } else {
            return @wp_mail($this->email, $subject, $body, $mailheaders);
        }
    }

    /* Confirm request from the link emailed to the user and email the adminvalidate if an email is already registered
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function send_confirm_request($content = '') {
        global $wpdb;

        if (1 == $this->filtered) {
            return $content;
        }

        $code = $_GET['bnm'];
        $action = intval(substr($code, 0, 1));
        $hash = substr($code, 1, 32);
        $id = intval(substr($code, 33));
        if ($id) {
            $this->email = $this->is_email_subscribed($this->get_email($id));
            if (!$this->email || $hash !== md5($this->email)) {
                return $this->no_such_email;
            }
        } else {
            return $this->no_such_email;
        }

        // get current status of email so messages are only sent once per emailed link
        $current = $this->is_public($this->email);

        if ('1' == $action) {
            // make this subscription active
            $this->message = $this->added;
            if ('1' != $current) {
                $this->ip = $_SERVER['REMOTE_ADDR'];
                $this->toggle($this->email);
            }
            $this->filtered = 1;
        } elseif ('0' == $action) {
            // remove this subscriber
            $this->message = $this->deleted;
            if ('0' != $current) {
                $this->delete($this->email);
                if ($this->bnm_options['admin_email'] == 'unsubs' || $this->bnm_options['admin_email'] == 'both') {
                    ( '' == get_option('blogname') ) ? $subject = "" : $subject = "[" . stripslashes(html_entity_decode(get_option('blogname'), ENT_QUOTES)) . "] ";
                    $subject .= __('New Unsubscription', 'bnm');
                    $subject = html_entity_decode($subject, ENT_QUOTES);
                    $message = $this->email . " " . __('unsubscribed from email notifications!', 'bnm');
                    $role = array('fields' => array('user_email'), 'role' => 'administrator');
                    $wp_user_query = get_users($role);
                    foreach ($wp_user_query as $user) {
                        $recipients[] = $user->user_email;
                    }
                    $headers = $this->headers();
                    // send individual emails so we don't reveal admin emails to each other
                    foreach ($recipients as $recipient) {
                        @wp_mail($recipient, $subject, $message, $headers);
                    }
                }
            }
            $this->filtered = 1;
        }

        if ('' != $this->message) {
            return $this->message;
        }
    }
    

    /*  Sends an email notification of a new post
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function send_breaking_new_email_alert($post = 0, $preview = '') {
        if (!$post) {
            return $post;
        }

        if ($this->s2_mu) {
            global $switched;
            if ($switched) {
                return;
            }
        }

        if ($preview == '') {
            // we aren't sending a Preview to the current user so carry out checks
            $s2mail = get_post_meta($post->ID, 's2mail', true);
            if ((isset($_POST['s2_meta_field']) && $_POST['s2_meta_field'] == 'no') || strtolower(trim($s2mail)) == 'no') {
                return $post;
            }

            // are we doing daily digests? If so, don't send anything now
            if ($this->bnm_options['email_freq'] != 'never') {
                return $post;
            }

            // is the current post of a type that should generate a notification email?
            // uses s2_post_types filter to allow for custom post types in WP 3.0
            if ($this->bnm_options['pages'] == 'yes') {
                $s2_post_types = array('page', 'post');
            } else {
                $s2_post_types = array('post');
            }
            $s2_post_types = apply_filters('s2_post_types', $s2_post_types);
            if (!in_array($post->post_type, $s2_post_types)) {
                return $post;
            }

            // Are we sending notifications for password protected posts?
            if ($this->bnm_options['password'] == "no" && $post->post_password != '') {
                return $post;
            }

            // Is the post assigned to a format for which we should not be sending posts
            $post_format = get_post_format($post->ID);
            $excluded_formats = explode(',', $this->bnm_options['exclude_formats']);
            if ($post_format !== false && in_array($post_format, $excluded_formats)) {
                return $post;
            }

            $post_cats = wp_get_post_categories($post->ID);
            $check = false;
            // is the current post assigned to any categories
            // which should not generate a notification email?
            foreach (explode(',', $this->bnm_options['exclude']) as $cat) {
                if (in_array($cat, $post_cats)) {
                    $check = true;
                }
            }

            if ($check) {
                // hang on -- can registered users subscribe to
                // excluded categories?
                if ('0' == $this->bnm_options['reg_override']) {
                    // nope? okay, let's leave
                    return $post;
                }
            }

            // Are we sending notifications for Private posts?
            // Action is added if we are, but double check option and post status
            if ($this->bnm_options['private'] == "yes" && $post->post_status == 'private') {
                // don't send notification to public users
                $check = true;
            }

            // lets collect our subscribers
            if (!$check) {
                // if this post is assigned to an excluded
                // category, or is a private post then
                // don't send public subscribers a notification
                $public = $this->get_public();
            }
            if ($post->post_type == 'page') {
                $post_cats_string = get_all_category_ids();
            } else {
                $post_cats_string = implode(',', $post_cats);
            }
            $registered = $this->get_registered("cats=$post_cats_string");

            // do we have subscribers?
            if (empty($public) && empty($registered)) {
                // if not, no sense doing anything else
                return $post;
            }
        }

        // we set these class variables so that we can avoid
        // passing them in function calls a little later
        $this->post_title = "<a href=\"" . get_permalink($post->ID) . "\">" . html_entity_decode($post->post_title, ENT_QUOTES) . "</a>";
        $this->permalink = get_permalink($post->ID);
        $this->post_date = get_the_time(get_option('date_format'));
        $this->post_time = get_the_time();

        $author = get_userdata($post->post_author);
        $this->authorname = $author->display_name;

        // do we send as admin, or post author?
        if ('author' == $this->bnm_options['sender']) {
            // get author details
            $user = &$author;
            $this->myemail = $user->user_email;
            $this->myname = html_entity_decode($user->display_name, ENT_QUOTES);
        } elseif ('blogname' == $this->bnm_options['sender']) {
            $this->myemail = get_option('admin_email');
            $this->myname = html_entity_decode(get_option('blogname'), ENT_QUOTES);
        } else {
            // get admin details
            $user = $this->get_userdata($this->bnm_options['sender']);
            $this->myemail = $user->user_email;
            $this->myname = html_entity_decode($user->display_name, ENT_QUOTES);
        }

        $this->post_cat_names = implode(', ', wp_get_post_categories($post->ID, array('fields' => 'names')));
        $this->post_tag_names = implode(', ', wp_get_post_tags($post->ID, array('fields' => 'names')));

        // Get email subject
        $subject = stripslashes(strip_tags($this->substitute_email_tags($this->bnm_options['notification_subject'])));
        // Get the message template
        $mailtext = apply_filters('s2_email_template', $this->bnm_options['mailtext']);
        $mailtext = stripslashes($this->substitute_email_tags($mailtext));

        $plaintext = $post->post_content;
        if (function_exists('strip_shortcodes')) {
            $plaintext = strip_shortcodes($plaintext);
        }
        $plaintext = preg_replace('|<s*>(.*)<\/s>|', '', $plaintext);
        $plaintext = preg_replace('|<strike*>(.*)<\/strike>|', '', $plaintext);
        $plaintext = preg_replace('|<del*>(.*)<\/del>|', '', $plaintext);

        $gallid = '[gallery id="' . $post->ID . '"';
        $content = str_replace('[gallery', $gallid, $post->post_content);
        $content = apply_filters('the_content', $content);
        $content = str_replace("]]>", "]]&gt", $content);

        $excerpt = $post->post_excerpt;
        if ('' == $excerpt) {
            // no excerpt, is there a <!--more--> ?
            if (false !== strpos($plaintext, '<!--more-->')) {
                list($excerpt, $more) = explode('<!--more-->', $plaintext, 2);
                // strip leading and trailing whitespace
                $excerpt = strip_tags($excerpt);
                $excerpt = trim($excerpt);
            } else {
                // no <!--more-->, so grab the first 55 words
                $excerpt = strip_tags($plaintext);
                $words = explode(' ', $excerpt, $this->excerpt_length + 1);
                if (count($words) > $this->excerpt_length) {
                    array_pop($words);
                    array_push($words, '[...]');
                    $excerpt = implode(' ', $words);
                }
            }
        }
        $html_excerpt = $post->post_excerpt;
        if ('' == $html_excerpt) {
            // no excerpt, is there a <!--more--> ?
            if (false !== strpos($content, '<!--more-->')) {
                list($html_excerpt, $more) = explode('<!--more-->', $content, 2);
                // balance HTML tags and then strip leading and trailing whitespace
                $html_excerpt = trim(balanceTags($html_excerpt, true));
            } else {
                // no <!--more-->, so grab the first 55 words
                $words = explode(' ', $content, $this->excerpt_length + 1);
                if (count($words) > $this->excerpt_length) {
                    array_pop($words);
                    array_push($words, '[...]');
                    $html_excerpt = implode(' ', $words);
                    // balance HTML tags and then strip leading and trailing whitespace
                    $html_excerpt = trim(balanceTags($html_excerpt, true));
                } else {
                    $html_excerpt = $content;
                }
            }
        }

        // remove excess white space from with $excerpt and $plaintext
        $excerpt = preg_replace('|[ ]+|', ' ', $excerpt);
        $plaintext = preg_replace('|[ ]+|', ' ', $plaintext);

        // prepare mail body texts
        $excerpt_body = str_replace("{POST}", $excerpt, $mailtext);
        $full_body = str_replace("{POST}", strip_tags($plaintext), $mailtext);
        $html_body = str_replace("\r\n", "<br />\r\n", $mailtext);
        $html_body = str_replace("{POST}", $content, $html_body);
        $html_excerpt_body = str_replace("\r\n", "<br />\r\n", $mailtext);
        $html_excerpt_body = str_replace("{POST}", $html_excerpt, $html_excerpt_body);

        if ($preview != '') {
            $this->myemail = $preview;
            $this->myname = __('Plain Text Excerpt Preview', 'bnm');
            $this->deliver_email(array($preview), $subject, $excerpt_body);
            $this->myname = __('Plain Text Full Preview', 'bnm');
            $this->deliver_email(array($preview), $subject, $full_body);
            $this->myname = __('HTML Excerpt Preview', 'bnm');
            $this->deliver_email(array($preview), $subject, $html_excerpt_body, 'html');
            $this->myname = __('HTML Full Preview', 'bnm');
            $this->deliver_email(array($preview), $subject, $html_body, 'html');
        } else {
            // first we send plaintext summary emails
            $registered = $this->get_registered("cats=$post_cats_string&format=excerpt&author=$post->post_author");
            if (empty($registered)) {
                $recipients = (array) $public;
            } elseif (empty($public)) {
                $recipients = (array) $registered;
            } else {
                $recipients = array_merge((array) $public, (array) $registered);
            }
            $recipients = apply_filters('s2_send_plain_excerpt_suscribers', $recipients, $post->ID);
            $this->deliver_email($recipients, $subject, $excerpt_body);

            // next we send plaintext full content emails
            $recipients = $this->get_registered("cats=$post_cats_string&format=post&author=$post->post_author");
            $recipients = apply_filters('s2_send_plain_fullcontent_suscribers', $recipients, $post->ID);
            $this->deliver_email($recipients, $subject, $full_body);

            // next we send html excerpt content emails
            $recipients = $this->get_registered("cats=$post_cats_string&format=html_excerpt&author=$post->post_author");
            $recipients = apply_filters('s2_send_html_excerpt_suscribers', $recipients, $post->ID);
            $this->deliver_email($recipients, $subject, $html_excerpt_body, 'html');

            // finally we send html full content emails
            $recipients = $this->get_registered("cats=$post_cats_string&format=html&author=$post->post_author");
            $recipients = apply_filters('s2_send_html_fullcontent_suscribers', $recipients, $post->ID);
            $this->deliver_email($recipients, $subject, $html_body, 'html');
        }
    }

    /* saves the settings of the menu page
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function save_settings() {
        
    }

    /* Export subscriber emails and other details to CSV
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function exportSubscribersToCSV() {
        $confirmed = $this->get_public();
        $unconfirmed = $this->get_public(0);
        if ('all' == $what) {
            $subscribers = array_merge((array) $confirmed, (array) $unconfirmed, (array) $this->get_all_registered());
        } elseif ('public' == $what) {
            $subscribers = array_merge((array) $confirmed, (array) $unconfirmed);
        } elseif ('confirmed' == $what) {
            $subscribers = $confirmed;
        } elseif ('unconfirmed' == $what) {
            $subscribers = $unconfirmed;
        } elseif (is_numeric($what)) {
            $subscribers = $this->get_registered("cats=$what");
        } elseif ('registered' == $what) {
            $subscribers = $this->get_registered();
        } elseif ('all_users' == $what) {
            $subscribers = $this->get_all_registered();
        }

        natcasesort($subscribers);

        $exportcsv = "User Email,User Name";
        $all_cats = $this->all_cats(false, 'ID');

        foreach ($all_cats as $cat) {
            $exportcsv .="," . $cat->cat_name;
            $cat_ids[] = $cat->term_id;
        }
        $exportcsv .="\r\n";

        foreach ($subscribers as $subscriber) {
            if ($this->is_registered($subscriber)) {
                $user_ID = $this->get_user_id($subscriber);
                $user_info = get_userdata($user_ID);

                $cats = explode(',', get_user_meta($user_info->ID, $this->get_usermeta_keyname('bnm_subscribed'), true));
                $subscribed_cats = '';
                foreach ($cat_ids as $cat) {
                    (in_array($cat, $cats)) ? $subscribed_cats .=",Yes" : $subscribed_cats .=",No";
                }

                $exportcsv .= $user_info->user_email . ',';
                $exportcsv .= $user_info->display_name;
                $exportcsv .= $subscribed_cats . "\r\n";
            } else {
                if (in_array($subscriber, $confirmed)) {
                    $exportcsv .= $subscriber . ',' . __('Confirmed Public Subscriber', 'bnm') . "\r\n";
                } elseif (in_array($subscriber, $unconfirmed)) {
                    $exportcsv .= $subscriber . ',' . __('Unconfirmed Public Subscriber', 'bnm') . "\r\n";
                }
            }
        }

        return $exportcsv;
    }

}

?>
