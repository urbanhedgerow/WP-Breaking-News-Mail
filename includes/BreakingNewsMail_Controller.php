<?php

/**
 * Description of BreakingNewsMail_Controller
 *
 * @author daniela
 */
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
    private $sender_name = '';
    private $sender_email = '';
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
        $this->messages_to_show();
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

    /* handles (un)subscribe requests
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function handle_un_suscribe_request($atts) {
        /* extract(shortcode_atts(array(
          'hide' => '',
          'id' => '',
          'url' => '',
          'nojs' => 'false',
          'link' => '',
          'size' => 20
          ), $atts));
         */
        // if link is true return a link to the page with the ajax class
        if ($link !== '' && !is_user_logged_in()) {
            $this->bnmform = "<a href=\"" . get_permalink($this->bnm_options['bnmpage']) . "\" class=\"bnmpopup\">" . $link . "</a>\r\n";
            return $this->bnmform;
        }

        // if a button is hidden, show only other
        /* if ($hide == 'subscribe') {
          $this->input_form_action = "<input type=\"submit\" name=\"unsubscribe\" value=\"" . 'Unsubscribe' . "\" />";
          } elseif ($hide == 'unsubscribe') {
          $this->input_form_action = "<input type=\"submit\" name=\"subscribe\" value=\"" . 'Subscribe' . "\" />";
          } else {
          // both form input actions
          $this->input_form_action = "<input type=\"submit\" name=\"subscribe\" value=\"" . 'Subscribe' . "\" />&nbsp;<input type=\"submit\" name=\"unsubscribe\" value=\"" . 'Unsubscribe' . "\" />";
          } */

        // if ID is provided, get permalink
        if ($id) {
            $url = get_permalink($id);
        } elseif ($this->bnm_options['bnmpage'] > 0) {
            $url = get_permalink($this->bnm_options['bnmpage']);
        } else {
            $url = get_site_url();
        }
        /* // build default form
          if ($nojs == 'true') {
          $this->form = "<form method=\"post\" action=\"" . $url . "\"><input type=\"hidden\" name=\"ip\" value=\"" . $_SERVER['REMOTE_ADDR'] . "\" /><p><label for=\"bnmemail\">" . 'Your email:' . "</label><br /><input type=\"text\" name=\"email\" id=\"bnmemail\" value=\"\" size=\"" . $size . "\" /></p><p>" . $this->input_form_action . "</p></form>";
          } else {
          $this->form = "<form method=\"post\" action=\"" . $url . "\"><input type=\"hidden\" name=\"ip\" value=\"" . $_SERVER['REMOTE_ADDR'] . "\" /><p><label for=\"bnmemail\">" . 'Your email:' . "</label><br /><input type=\"text\" name=\"email\" id=\"bnmemail\" value=\"" . 'Enter email address...' . "\" size=\"" . $size . "\" onfocus=\"if (this.value == '" . 'Enter email address...' . "') {this.value = '';}\" onblur=\"if (this.value == '') {this.value = '" . 'Enter email address...' . "';}\" /></p><p>" . $this->input_form_action . "</p></form>\r\n";
          }
          $this->bnmform = $this->form; */


        if (isset($_POST['unsubscribe']) || isset($_POST['subscribe'])) {
            global $wpdb, $user_email;
            if (!is_email($_POST['email'])) {
                $this->bnmform = $this->form . $this->not_an_email;
            } else {
                $this->email = $this->is_email($_POST['email']);


                $this->ip = $_POST['ip'];
                if (isset($_POST['subscribe'])) {
                    // lets see if they've tried to subscribe previously
                    if (!$this->is_email_subscribed($this->email)) {
                        // the user is unknown or inactive
                        $this->add_subscriptor($this->email, $this->ip);
                        $status = $this->send_confirm('add');

                        // set a variable to denote that we've already run, and shouldn't run again
                        $this->filtered = 1;
                        if ($status) {
                            $this->bnmform = $this->confirmation_sent;
                        } else {
                            $this->bnmform = $this->error;
                        }
                    } else {
                        // they're already subscribed
                        $this->bnmform = $this->already_subscribed;
                    }
                    $this->action = 'subscribe';
                } elseif (isset($_POST['unsubscribe'])) {
                    if (!$this->is_email_subscribed($this->email)) {
                        $this->bnmform = $this->form . $this->not_subscribed;
                    } else {
                        $status = $this->send_confirm('del');
                        $this->filtered = 1;
                        if ($status) {
                            $this->bnmform = $this->confirmation_sent;
                        } else {
                            $this->bnmform = $this->error;
                        }
                    }
                    $this->action = 'unsubscribe';
                }
            }
        }
        return $this->bnmform;
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
        $string = str_replace("{CATS}", $this->post_cat_names, $string);
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
                $mailtext = apply_filters('bnm_html_email', "<html><head><title>" . $subject . "</title><link rel=\"stylesheet\" href=\"" . get_stylesheet_uri() . "\" type=\"text/css\" media=\"screen\" /></head><body>" . $message . "</body></html>", $subject, $message);
            } else {
                $mailtext = apply_filters('bnm_html_email', "<html><head><title>" . $subject . "</title></head><body>" . $message . "</body></html>", $subject, $message);
            }
        } else {
            $headers = $this->headers();
            $message = preg_replace('|&[^a][^m][^p].{0,3};|', '', $message);
            $message = preg_replace('|&amp;|', '&', $message);
            $message = wordwrap(strip_tags($message), 80, "\n");
            $mailtext = apply_filters('bnm_plain_email', $message);
        }

        // Replace any escaped html symbols in subject then apply filter
        $subject = html_entity_decode($subject, ENT_QUOTES);
        $subject = apply_filters('bnm_email_subject', $subject);

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
                if (!empty($recipient) && $this->sender_email != $recipient) {
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
                if (!empty($recipient) && $this->sender_email != $recipient) {
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
                $status = @wp_mail($this->sender_email, $subject, $mailtext, $newheaders);
            }
        } else {
            $status = @wp_mail($this->sender_email, $subject, $mailtext, $headers);
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
        if (empty($this->sender_name) || empty($this->sender_email)) {

            $this->sender_email = $this->bnm_options['sender_email'];
            $this->sender_name = html_entity_decode(get_option('blogname'), ENT_QUOTES);

            if (empty($this->sender_email)) {
                // Get the site domain and get rid of www.
                $sitename = strtolower($_SERVER['SERVER_NAME']);
                if (substr($sitename, 0, 4) == 'www.') {
                    $sitename = substr($sitename, 4);
                }
                $this->sender_email = 'wordpress@' . $sitename;
            }
        }

        $header['From'] = $this->sender_name . " <" . $this->sender_email . ">";
        $header['Reply-To'] = $this->sender_name . " <" . $this->sender_email . ">";
        $header['Return-path'] = "<" . $this->sender_email . ">";
        $header['Precedence'] = "list\nList-Id: " . html_entity_decode(get_option('blogname'), ENT_QUOTES) . "";
        if ($type == 'html') {
            // To send HTML mail, the Content-Type header must be set
            $header['Content-Type'] = get_option('html_type') . "; charset=\"" . get_option('blog_charset') . "\"";
        } else {
            $header['Content-Type'] = "text/plain; charset=\"" . get_option('blog_charset') . "\"";
        }

        // apply header filter to allow on-the-fly amendments
        $header = apply_filters('bnm_email_headers', $header);
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

        if ($this->bnm_mu) {
            global $switched;
            if ($switched) {
                return;
            }
        }

        if ($preview == '') {
            // we aren't sending a Preview to the current user so carry out checks
            $bnmmail = get_post_meta($post->ID, 'bnmmail', true);
            if ((isset($_POST['bnm_meta_field']) && $_POST['bnm_meta_field'] == 'no') || strtolower(trim($bnmmail)) == 'no') {
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
                return $post;
            }

            // lets collect our subscribers
            if (!$check) {
                $public = $this->get_all_emails();
            }

            $post_cats_string = implode(',', $post_cats);

            if (empty($public) && empty($registered)) {
                return $post;
            }
        }

        // we set these class variables so that we can avoid
        // passing them in function calls a little later
        $this->post_title = "<a href=\"" . get_permalink($post->ID) . "\">" . html_entity_decode($post->post_title, ENT_QUOTES) . "</a>";
        $this->permalink = get_permalink($post->ID);
        $this->post_date = get_the_time(get_option('date_format'));
        $this->post_time = get_the_time();



        $this->sender_email = $this->bnm_options['sender_email'];
        $this->sender_name = html_entity_decode(get_option('blogname'), ENT_QUOTES);

        $this->post_cat_names = implode(', ', wp_get_post_categories($post->ID, array('fields' => 'names')));


        // Get email subject
        $subject = stripslashes(strip_tags($this->substitute_email_tags($this->bnm_options['notification_subject'])));
        // Get the message template
        $mailtext = apply_filters('bnm_email_template', $this->bnm_options['mailtext']);
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
            $this->sender_email = $preview;
            $this->sender_name = 'Plain Text Excerpt Preview';
            $this->deliver_email(array($preview), $subject, $excerpt_body);
            $this->sender_name = 'Plain Text Full Preview';
            $this->deliver_email(array($preview), $subject, $full_body);
            $this->sender_name = 'HTML Excerpt Preview';
            $this->deliver_email(array($preview), $subject, $html_excerpt_body, 'html');
            $this->sender_name = 'HTML Full Preview';
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
            $recipients = apply_filters('bnm_send_plain_excerpt_suscribers', $recipients, $post->ID);
            $this->deliver_email($recipients, $subject, $excerpt_body);

            // next we send plaintext full content emails
            $recipients = $this->get_registered("cats=$post_cats_string&format=post&author=$post->post_author");
            $recipients = apply_filters('bnm_send_plain_fullcontent_suscribers', $recipients, $post->ID);
            $this->deliver_email($recipients, $subject, $full_body);

            // next we send html excerpt content emails
            $recipients = $this->get_registered("cats=$post_cats_string&format=html_excerpt&author=$post->post_author");
            $recipients = apply_filters('bnm_send_html_excerpt_suscribers', $recipients, $post->ID);
            $this->deliver_email($recipients, $subject, $html_excerpt_body, 'html');

            // finally we send html full content emails
            $recipients = $this->get_registered("cats=$post_cats_string&format=html&author=$post->post_author");
            $recipients = apply_filters('bnm_send_html_fullcontent_suscribers', $recipients, $post->ID);
            $this->deliver_email($recipients, $subject, $html_body, 'html');
        }
    }

    /* saves the settings of the menu page
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   boolean
     */

    function save_settings($_POST) {        
        if (!empty($_POST['tracking'])) {
            $this->bnm_options['tracking'] = $_POST['tracking'];
        }
        if (!empty($_POST['sender_email'])) {
            $this->bnm_options['sender_email'] = $_POST['sender_email'];
        }
        
        if (!empty($_POST['notification_subject'])) {
            $this->bnm_options['notification_subject'] = $_POST['notification_subject'];
        }
        if (!empty($_POST['mailtext'])) {
            $this->bnm_options['mailtext'] = $_POST['mailtext'];
        }
        
        if (!empty($_POST['confirm_subject'])) {
            $this->bnm_options['confirm_subject'] = $_POST['confirm_subject'];
        }
        if (!empty($_POST['confirm_email'])) {
            $this->bnm_options['confirm_email'] = $_POST['confirm_email'];
        }
        if (!empty($_POST['remind_subject'])) {
            $this->bnm_options['remind_subject'] = $_POST['remind_subject'];
        }
        if (!empty($_POST['remind_email'])) {
            $this->bnm_options['remind_email'] = $_POST['remind_email'];
        }
        // excluded categories
        if (!empty($_POST['category'])) {
            sort($_POST['category']);
            $exclude_cats = implode(',', $_POST['category']);
        } else {
            $exclude_cats = '';
        }
        $this->bnm_options['exclude'] = $exclude_cats;

        echo "<div id=\"message\" class=\"updated fade\"><p><strong>$this->options_saved</strong></p></div>";
        update_option('bnm_options', $this->bnm_options);
    }
    
    
    function process_subscribers_admin_form($_POST){
         global $wpdb, $bnmnonce;
            $all_users = $this->get_all_emails();

// was anything POSTed ?
            if (isset($_POST['bnm_admin'])) {
                check_admin_referer('bnm-manage_subscribers' . $bnmnonce);
                if ($_POST['addresses']) {
                    $sub_error = '';
                    $unsub_error = '';
                    foreach (preg_split("|[\s,]+|", $_POST['addresses']) as $email) {
                        $email = $this->sanitize_email($email);
                        if (is_email($email) && $_POST['subscribe']) {
                            if ($this->is_email_subscribed($email) !== false) {
                                ('' == $sub_error) ? $sub_error = "$email" : $sub_error .= ", $email";
                                continue;
                            }
                            $this->add_subscriptor($email, true);
                            $message = "<div id=\"message\" class=\"updated fade\"><p><strong>" . 'Address(es) subscribed!' . "</strong></p></div>";
                        } elseif (is_email($email) && $_POST['unsubscribe']) {
                            if ($this->is_email_subscribed($email) === false) {
                                ('' == $unsub_error) ? $unsub_error = "$email" : $unsub_error .= ", $email";
                                continue;
                            }
                            $this->delete_subscriptor($email);
                            $message = "<div id=\"message\" class=\"updated fade\"><p><strong>" . 'Address(es) unsubscribed!' . "</strong></p></div>";
                        }
                    }
                    if ($sub_error != '') {
                        echo "<div id=\"message\" class=\"error\"><p><strong>" . 'Some emails were not processed, the following were already subscribed' . ":<br />$sub_error</strong></p></div>";
                    }
                    if ($unsub_error != '') {
                        echo "<div id=\"message\" class=\"error\"><p><strong>" . 'Some emails were not processed, the following were not in the database' . ":<br />$unsub_error</strong></p></div>";
                    }
                    echo $message;
                    $_POST['what'] = 'confirmed';
                } /*elseif ($_POST['process']) {
                    if ($_POST['delete']) {
                        foreach ($_POST['delete'] as $address) {
                            $this->delete_subscriptor($address);
                        }
                        echo "<div id=\"message\" class=\"updated fade\"><p><strong>" . 'Address(es) deleted!' . "</strong></p></div>";
                    }
                    if ($_POST['confirm']) {
                        foreach ($_POST['confirm'] as $address) {
                            $this->toggle($this->is_email($address));
                        }
                        $message = "<div id=\"message\" class=\"updated fade\"><p><strong>" . 'Status changed!' . "</strong></p></div>";
                    }
                    if ($_POST['unconfirm']) {
                        foreach ($_POST['unconfirm'] as $address) {
                            $this->toggle($this->is_email($address));
                        }
                        $message = "<div id=\"message\" class=\"updated fade\"><p><strong>" . 'Status changed!' . "</strong></p></div>";
                    }
                    echo $message;
                }*/ elseif ($_POST['searchterm']) {
                    $confirmed = $this->get_all_emails();
                    $unconfirmed = $this->get_all_emails(0);
                    $subscribers = array_merge((array) $confirmed, (array) $unconfirmed, (array) $all_users);
                    foreach ($subscribers as $subscriber) {
                        if (is_numeric(stripos($subscriber, $_POST['searchterm']))) {
                            $result[] = $subscriber;
                        }
                    }
                } /*elseif ($_POST['remind']) {
                    //$this->remind($_POST['reminderemails']);
                    echo "<div id=\"message\" class=\"updated fade\"><p><strong>" .'Reminder Email(s) Sent!' . "</strong></p></div>";
                } elseif ($_POST['sub_categories'] && 'subscribe' == $_POST['manage']) {
                    //$this->subscribe_registered_users($_POST['exportcsv'], $_POST['category']);
                    echo "<div id=\"message\" class=\"updated fade\"><p><strong>" . 'Registered Users Subscribed!' . "</strong></p></div>";
                } elseif ($_POST['sub_categories'] && 'unsubscribe' == $_POST['manage']) {
                    //$this->unsubscribe_registered_users($_POST['exportcsv'], $_POST['category']);
                    echo "<div id=\"message\" class=\"updated fade\"><p><strong>" . 'Registered Users Unsubscribed!' . "</strong></p></div>";
                } elseif ($_POST['sub_format']) {
                    //$this->format_change($_POST['format'], $_POST['exportcsv']);
                    echo "<div id=\"message\" class=\"updated fade\"><p><strong>" . 'Format updated for Selected Registered Users!' . "</strong></p></div>";
                } elseif ($_POST['sub_digest']) {
                    //$this->digest_change($_POST['sub_category'], $_POST['exportcsv']);
                    echo "<div id=\"message\" class=\"updated fade\"><p><strong>" . 'Digest Subscription updated for Selected Registered Users!' . "</strong></p></div>";
                } */
            }

//Get Public Subscribers once for filter
            $confirmed = $this->get_all_emails();
            $unconfirmed = $this->get_all_emails(0);
// safety check for our arrays
            if ('' == $confirmed) {
                $confirmed = array();
            }
            if ('' == $unconfirmed) {
                $unconfirmed = array();
            }
            
            if ('' == $all_users) {
                $all_users = array();
            }

            $reminderform = false;
            $urlpath = str_replace("\\", "/", BNM_PATH);
            $urlpath = trailingslashit(get_option('siteurl')) . substr($urlpath, strpos($urlpath, "wp-content/"));
            if (isset($_GET['bnmpage'])) {
                $page = (int) $_GET['bnmpage'];
            } else {
                $page = 1;
            }

            if (isset($_POST['what'])) {
                $page = 1;
                if ('all' == $_POST['what']) {
                    $what = 'all';
                    $subscribers = array_merge((array) $confirmed, (array) $unconfirmed, (array) $all_users);
                } elseif ('public' == $_POST['what']) {
                    $what = 'public';
                    $subscribers = array_merge((array) $confirmed, (array) $unconfirmed);
                } elseif ('confirmed' == $_POST['what']) {
                    $what = 'confirmed';
                    $subscribers = $confirmed;
                } elseif ('unconfirmed' == $_POST['what']) {
                    $what = 'unconfirmed';
                    $subscribers = $unconfirmed;
                    if (!empty($subscribers)) {
                        $reminderemails = implode(",", $subscribers);
                        $reminderform = true;
                    }
                } elseif (is_numeric($_POST['what'])) {
                    $what = intval($_POST['what']);
                    //$subscribers = $this->get_registered("cats=$what");
                } elseif ('registered' == $_POST['what']) {
                    $what = 'registered';
                    $subscribers = $registered;
                } elseif ('all_users' == $_POST['what']) {
                    $what = 'all_users';
                    $subscribers = $all_users;
                }
            } elseif (isset($_GET['what'])) {
                if ('all' == $_GET['what']) {
                    $what = 'all';
                    $subscribers = array_merge((array) $confirmed, (array) $unconfirmed, (array) $all_users);
                } elseif ('public' == $_GET['what']) {
                    $what = 'public';
                    $subscribers = array_merge((array) $confirmed, (array) $unconfirmed);
                } elseif ('confirmed' == $_GET['what']) {
                    $what = 'confirmed';
                    $subscribers = $confirmed;
                } elseif ('unconfirmed' == $_GET['what']) {
                    $what = 'unconfirmed';
                    $subscribers = $unconfirmed;
                    if (!empty($subscribers)) {
                        $reminderemails = implode(",", $subscribers);
                        $reminderform = true;
                    }
                } elseif (is_numeric($_GET['what'])) {
                    $what = intval($_GET['what']);
                   // $subscribers = $this->get_registered("cats=$what");
                } elseif ('registered' == $_GET['what']) {
                    $what = 'registered';
                    $subscribers = $registered;
                } elseif ('all_users' == $_GET['what']) {
                    $what = 'all_users';
                    $subscribers = $all_users;
                }
            } else {
                $what = 'all';
                $subscribers = array_merge((array) $confirmed, (array) $unconfirmed, (array) $all_users);
            }
            if ($_POST['searchterm'] ) {
                $subscribers = &$result;
                $what = 'public';
            }

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
        if ('all' == $what || 'all_users' == $what) {
            $subscribers = array_merge((array) $confirmed, (array) $unconfirmed, (array) $this->get_all_emails());
        } elseif ('confirmed' == $what) {
            $subscribers = $confirmed;
        } elseif ('unconfirmed' == $what) {
            $subscribers = $unconfirmed;
        }

        natcasesort($subscribers);

        $exportcsv = "User Email";
        $all_cats = $this->all_cats(false, 'ID');

        foreach ($all_cats as $cat) {
            $exportcsv .="," . $cat->cat_name;
            $cat_ids[] = $cat->term_id;
        }
        $exportcsv .="\r\n";

        foreach ($subscribers as $subscriber) {
            if (in_array($subscriber, $confirmed)) {
                $exportcsv .= $subscriber . ',' . 'Confirmed Public Subscriber' . "\r\n";
            } elseif (in_array($subscriber, $unconfirmed)) {
                $exportcsv .= $subscriber . ',' . 'Unconfirmed Public Subscriber' . "\r\n";
            }
        }

        return $exportcsv;
    }

    /**
      Get an object of all categories, include default and custom type
     */
    function all_cats($exclude = false, $orderby = 'slug') {
        $all_cats = array();
        $bnm_taxonomies = array('category');
        $bnm_taxonomies = apply_filters('bnm_taxonomies', $bnm_taxonomies);

        foreach ($bnm_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $all_cats = array_merge($all_cats, get_categories(array('hide_empty' => false, 'orderby' => $orderby, 'taxonomy' => $taxonomy)));
            }
        }



        if ($exclude === true) {
            // remove excluded categories from the returned object
            $excluded = explode(',', $this->bnm_options['exclude']);

            // need to use $id like this as this is a mixed array / object
            $id = 0;
            foreach ($all_cats as $cat) {
                if (in_array($cat->term_id, $excluded)) {
                    unset($all_cats[$id]);
                }
                $id++;
            }
        }

        return $all_cats;
    }

    function messages_to_show() {
        $confirmation_sent = 'Confirmation sent';
        $already_subscribed = 'Already subscribed';
        $not_subscribed = 'Not subscribed';
        $not_an_email = 'That is not an email';
        $error = 'Error';
        $mail_sent = 'Email sent';
        $mail_failed = 'Mail failed';
        $form = 'Form';
        $no_such_email = 'No such email';
        $added = 'Added';
        $deleted = 'Deleted';
        $subscribe = 'Subscribe';
        $unsubscribe = 'Unsubscribe';
        $confirm_subject = 'Confirm subject';
        $options_saved = 'Options Saved';
        $options_reset = 'Options Reset';
    }

}

?>
