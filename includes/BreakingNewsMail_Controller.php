<?php

/**
 * Description of BreakingNewsMail_Controller
 * @author Daniela Valero aka DaHe
 * @copyright 2012
 * @license http://www.gnu.org/licenses/gpl.html GPL v2 
 * @since 1.0
 * @package WP-Breaking-News-Mail
 */
class BreakingNewsMail_Controller {

    private $signup_dates = array();
    private $script_debug;
    private $signup_ips = array();
    private $bnm_options = array();
    private $post_title = '';
    private $permalink = '';
    private $post_date = '';
    private $post_time = '';
    private $sender_name = '';
    private $sender_email = '';
    private $filtered = 0;
    private $preview_email = false;
    // state variables used to affect processing

    private $email = '';
    private $message = '';
    private $excerpt_length = 55;
    // some messages
    private $confirmation_sent;
    private $already_subscribed;
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
    private $search_result = Array();

    function __construct() {
        $this->bnm_options = get_option('bnm_options');
        $this->messages_to_show();

        add_shortcode('BNM_CONFIRMATION_MESSAGE', array(&$this, 'show_bnm_confirmation_message'));

        if (is_admin()) {
            if (defined('SENDING_BNM_ALERT') && SENDING_BNM_ALERT) {
                return;
            } else {
                define('SENDING_BNM_ALERT', true);
                add_action('publish_post', array(&$this, 'send_breaking_new_email_alert'));
            }

            if (isset($_POST['bnm_admin']) && isset($_POST['csv']) && $_POST['csv']) {
                $date = date('Y-m-d');
                header("Content-Description: File Transfer");
                header("Content-type: application/octet-stream");
                header("Content-Disposition: attachment; filename=subscribed_users_$date.csv");
                header("Pragma: no-cache");
                header("Expires: 0");
                echo $this->exportSubscribersToCSV();
                exit(0);
            }
        } else {
            add_action('wp_enqueue_scripts', array(&$this, 'bnm_load_jquery'));
            add_action('wp_head', array(&$this, 'add_css_bnm'));

            if (isset($_GET['bnm'])) {
                //   echo "someone is confirming a request";
                if (defined('DOING_BNM_CONFIRM') && DOING_BNM_CONFIRM) {
                    return;
                } else {
                    define('DOING_BNM_CONFIRM', true);
                    add_filter('query_string', array(&$this, 'query_filter'));
                    add_filter('the_title', array(&$this, 'title_filter'));
                    add_filter('the_content', array(&$this, 'confirm_subscriptor'));
                    $this->confirm_subscriptor();
                }
            }
        }
    }

    function show_bnm_confirmation_message($atts) {
        extract(shortcode_atts(array(
                    'subscription_confirmation' => '',
                    'unsubscription_confirmation' => '',
                        ), $atts));

        return "subscription_confirmation = {$this->message}";
    }

    /* Database querys */

    /**
     * adds a subscriber
     * 
     * @since 1
     *
     * @param    email    $email    user's email
     * @param    ip    $ip    ip from user's compurter
     * @return   string  $message Its a feedback message
     */
    function add_subscriptor($email, $ip) {
        global $wpdb;
        $message = '';
        if (!is_email($email)) {
            echo $this->no_such_email;
            return false;
        }
        if ($this->is_email_subscribed($email)) {
            echo $this->already_subscribed;
            return false;
        }
        $was_deleted = $this->was_email_deleted($email);
        if ($was_deleted) {
            $result = $wpdb->get_results("UPDATE " . BNM_USERS . " SET status=1 WHERE CAST(email as binary)='$email'");
        } else {
            $result = $wpdb->get_results($wpdb->prepare("INSERT INTO " . BNM_USERS . " (email, active, date, ip) VALUES (%s, %d, NOW(), %s)", $email, 0, $ip));
        }
        $message ="";
        if ($result !== false) {
            if (!$this->is_email_active($email)) {
                $this->email = $email;
                $email_sent = $this->send_confirm();
                if ($email_sent)
                    $message = __(', a confirmation email will be sent', 'bnm');                    
                else                    
                    $message = __('error sending confirmation', 'bnm');   
            }
            return $message;
        }else{
            return false;
        }
        
    }

    /**
     * Overrides the default query when handling a (un)subscription confirmation
     * This is basically a trick: if the s2 variable is in the query string, just grab the first
     * static page and override it's contents later with title_filter()
     * 
     * @since 1
     *
     */
    function query_filter() {
        // don't interfere if we've already done our thing
        if ($this->filtered == 1) {
            return;
        }
        global $wpdb;
        if ($this->bnm_options['bnmpage'] != 0) {
            return "page_id=" . $this->bnm_options['bnmpage'];
        } else {
            $id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status='publish' LIMIT 1");
            if ($id) {
                return "page_id=$id";
            } else {
                return "showposts=1";
            }
        }
    }

    /**
     *  Overrides the page title
     * 
     * @since 1
     *     
     */
    function title_filter($title) {
        // don't interfere if we've already done our thing
        if (in_the_loop()) {
            return __('Subscription Confirmation', 'bnm');
        } else {
            return $title;
        }
    }

    /*
     * Confirm request from the link emailed to the user and email the admin
     * @since 1
     *
     * @param    content   $content   
     * @return   string  $message Its a feedback message
     */

    function confirm_subscriptor($content = '') {
        global $wpdb;
        $code = $_GET['bnm'];
        $action = intval(substr($code, 0, 1));
        $hash = substr($code, 1, 32);
        $id = intval(substr($code, 33));
        if ($id) {
            $this->email = is_email($this->get_email($id));
            if (!$this->email || $hash !== md5($this->email)) {
                $this->message = $this->no_such_email;
                return;
            }
        } else {
            $this->message = $this->no_such_email;
            return;
        }
        if ($action == 1) { // make this subscription active   
            $this->ip = $_SERVER['REMOTE_ADDR'];
            $this->confirm_subscriptor_db($this->email);
            $this->message = $this->added;
        } elseif ('0' == $action) { // remove this subscriber                     
            $this->delete_subscriptor($this->email);
            $this->message = $this->deleted;
        }
        if ('' != $this->message) {
            return $this->message;
        }
    }

    /**
     * performs the confirmaton of a subscriber on the database
     * 
     * @since 1
     *
     * @param    email    $email    users email
     * @return   boolean  true if success 
     */
    function confirm_subscriptor_db($email) {
        global $wpdb;

        if (!is_email($email)) {
            return false;
        }

        if ($this->is_email_subscribed_and_confirmed($email))
            return false;

        $wpdb->get_results("UPDATE " . BNM_USERS . " SET active=1 WHERE CAST(email as binary)='$email'");
    }

    /**
     * delete a subscriber from the db
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
        if (!$this->is_email_subscribed($email))
            return false;

        ////$wpdb->get_results("DELETE FROM " . BNM_USERS . " WHERE CAST(email as binary)='$email'");
        $result = $wpdb->get_results("UPDATE " . BNM_USERS . " SET status=0 WHERE CAST(email as binary)='$email'");
        return $result;
    }

    /* Email related functions */

    /**
     * Construct unsubscribe link
     * 
     * @since 1
     *
     * @param    email    $email    users email
     * @param    mailtext  $mailtext    the text of the email
     * @return   mailtext with the link for unsubscriptions
     */
    private function construct_unsubscribe_link($email, $mailtext) {
        $id = $this->get_id($email);
        if (!$id) {
            return false;
        }
        //  echo "<br> Email: $email -- $id <br> ";
        $url = $this->get_confirmation_page_link();
        $linkUnsubscribe = $url . "/?bnm=0";
        $linkUnsubscribe .= md5(is_email($email));
        //$linkUnsubscribe .= $email;
        $linkUnsubscribe .= $id;

        $mailtext = str_replace("{UNSUBSCRIBE_ACTION}", $linkUnsubscribe, $mailtext);
        return $mailtext;
    }

    /*
     * validate if an email is already registered
     * @since 1
     *
     * @param    email    $email    users email
     * @return   boolean
     */

    private function is_email_subscribed($email) {
        global $wpdb;
        //verifica que el usuario no esté suscrito        
        $isSuscribed = false;
        $results = $wpdb->get_results("SELECT email FROM " . BNM_USERS . " WHERE CAST(email as binary)='$email' and status=1", ARRAY_N);
        if ($results)
            $isSuscribed = true;
        foreach ($results as $result) {
            if ($result[0] == $email)
                $isSuscribed = true;
        }
        return $isSuscribed;
    }

    /*
     * validate if an email is already registered and confirmed
     * @since 1
     *
     * @param    email    $email    users email
     * @return   boolean
     */

    private function is_email_subscribed_and_confirmed($email) {
        global $wpdb;
        //verifica que el usuario no esté suscrito        
        $isSuscribed = false;
        $results = $wpdb->get_results("SELECT email FROM " . BNM_USERS . " WHERE CAST(email as binary)='$email' and status=1 and active=1", ARRAY_N);
        if ($results)
            $isSuscribed = true;
        foreach ($results as $result) {
            if ($result[0] == $email)
                $isSuscribed = true;
        }
        return $isSuscribed;
    }

    /*
     * Checks if an email was deleted
     * @since 1
     *
     * @param    email $email
     * @return   boolean
     */

    private function was_email_deleted($email) {
        global $wpdb;
        //verifica que el usuario no esté suscrito        
        $wasDeleted = false;
        $results = $wpdb->get_results("SELECT email FROM " . BNM_USERS . " WHERE CAST(email as binary)='$email' and status=0", ARRAY_N);
        if ($results)
            $wasDeleted = true;
        return $wasDeleted;
    }

    /*
     * validate if an email is confirmed
     * @since 1
     *
     * @param    email    $email    users email
     * @return   boolean
     */

    private function is_email_active($email) {
        global $wpdb;
        $isActive = false;

        $result = $wpdb->get_results("SELECT active FROM " . BNM_USERS . " WHERE CAST(email as binary)='$email' and status=1;", ARRAY_N);
        if ($result)
            $isActive = $result[0][0];

        return $isActive;
    }

    /*
     * Performs string substitutions for bnm mail tags
     * @since 1
     *
     * @param    string $string is the string which contain the email text
     * @return   string $string
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
        $string = str_replace("{DATE}", $this->post_date, $string);
        $string = str_replace("{TIME}", $this->post_time, $string);
        return $string;
    }

    /*
     * Construct standard set of email headers
     * @since 1
     *
     * @param    type of content    $type   text or html
     * @return   string $headers
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

    /*  Creates an sends the subscription confirmation email 
     * @since 1
     *   
     * @return   boolean 
     */

    function send_confirm() {
        if (!$this->email) {
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

        $url = $this->get_confirmation_page_link();
        $link = $url . "/?bnm=";
        // sort the headers now so we have all substitute_email_tags information
        $mailheaders = $this->construct_standard__email_headers();
        $link .= '1';
        $body = $this->substitute_email_tags(stripslashes($this->bnm_options['confirm_email']));
        $body = str_replace("{CONFIRMATION_ACTION}", $this->subscribe, $body);
        $subject = str_replace("{CONFIRMARTION_ACTION}", $this->subscribe, $this->bnm_options['confirm_subject']);
        $subject = html_entity_decode($this->substitute_email_tags(stripslashes($subject)), ENT_QUOTES);
        $link .= md5($this->email);
        $link .= $id;
        $body = str_replace("{LINK}", $link, $body);
        return @wp_mail($this->email, $subject, $body, $mailheaders);
    }

    /*
     * Sends an email notification when a new post is published
     * @since 1
     *
     * @param    post    $post   post global object
     * @param    preview    $preview   if an editor wants to see a preview  
     */

    function send_breaking_new_email_alert($post = 0, $preview = '') {
        if (( $_POST['post_status'] == 'publish' ) && ( $_POST['original_post_status'] != 'publish' )) {
            if (!$post) {
                return $post;
            }
            $post = get_post($post);

            if ($preview == '') {
                // we aren't sending a Preview to the current user so carry out checks
                $bnmmail = get_post_meta($post->ID, 'bnmmail', true);
                if ((isset($_POST['bnm_meta_field']) && $_POST['bnm_meta_field'] == 'no') || strtolower(trim($bnmmail)) == 'no') {
                    return $post;
                }
                $post_cats = wp_get_post_categories($post->ID);
                $is_post_in_the_cats = true;


                foreach (explode(',', $this->bnm_options['include']) as $cat) {
                    if (!in_array($cat, $post_cats)) {
                        $is_post_in_the_cats = false;
                    }
                }

                if (!$is_post_in_the_cats) {
                    return $post;
                } else {
                    $subscribers = $this->get_all_emails();
                }

                if (empty($subscribers)) {
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

            // Get email subject
            $subject = stripslashes(strip_tags($this->substitute_email_tags($this->bnm_options['notification_subject'])));
            // Get the message template
            $mailtext = apply_filters('bnm_email_template', $this->bnm_options['mailtext']);
            $mailtext = stripslashes($this->substitute_email_tags($mailtext));

            $gallid = '[gallery id="' . $post->ID . '"';
            $content = str_replace('[gallery', $gallid, $post->post_content);
            $content = apply_filters('the_content', $content);
            $content = str_replace("]]>", "]]&gt", $content);

            if ($this->bnm_options['email_format'] == "text") {
                $plaintext = $post->post_content;
                if (function_exists('strip_shortcodes')) {
                    $plaintext = strip_shortcodes($plaintext);
                }
                $plaintext = preg_replace('|<s*>(.*)<\/s>|', '', $plaintext);
                $plaintext = preg_replace('|<strike*>(.*)<\/strike>|', '', $plaintext);
                $plaintext = preg_replace('|<del*>(.*)<\/del>|', '', $plaintext);

                $excerpt = $post->post_excerpt;
                if ($excerpt == "") {
                    // no excerpt, is there a <!--more--> ?
                    if (strpos($plaintext, '<!--more-->') !== false) {
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

                // remove excess white space from with $excerpt and $plaintext
                $excerpt = preg_replace('|[ ]+|', ' ', $excerpt);

                // prepare mail body texts
                $excerpt_body = str_replace("{POST}", $excerpt, $mailtext);

                if ($preview != '') {
                    $this->sender_email = $preview;
                    $this->sender_name = 'Plain Text Excerpt Preview';
                    $this->deliver_email(array($preview), $subject, $excerpt_body);
                } else {
                    $subscribers = apply_filters('bnm_send_plain_excerpt_suscribers', $subscribers, $post->ID);
                    $this->deliver_email($subscribers, $subject, $excerpt_body);
                }
            } elseif ($this->bnm_options['email_format'] == "html") {
                $html_excerpt = $post->post_excerpt;
                if ($html_excerpt == "") {
                    // no excerpt, is there a <!--more--> ?
                    if (strpos($content, '<!--more-->') !== false) {
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
                $html_excerpt_body = str_replace("\r\n", "<br />\r\n", $mailtext);
                $html_excerpt_body = str_replace("{POST}", $html_excerpt, $html_excerpt_body);

                if ($preview != '') {
                    $this->sender_email = $preview;
                    $this->sender_name = 'HTML Excerpt Preview';
                    $this->deliver_email(array($preview), $subject, $html_excerpt_body, 'html');
                } else {
                    $subscribers = apply_filters('bnm_send_html_excerpt_suscribers', $subscribers, $post->ID);
                    $this->deliver_email($subscribers, $subject, $html_excerpt_body, 'html');
                }
            }
        }
    }

    /*
     * Delivers email to recipients in HTML or plaintext for the email alert
     * @since 1
     *
     * @param    array    $recipients  array of all emails
     * @param    string   $subject  the subject of the email
     * @param    string    $message  the message of the email
     * @param    string    $type  type of content of the email
     * @return   boolean
     */

    function deliver_email($recipients = array(), $subject = '', $message = '', $type = 'text') {
        if (empty($recipients) || '' == $message) {
            return;
        }
        if ($type == 'html') {
            $headers = $this->construct_standard__email_headers('html');
            $mailtext = apply_filters('bnm_html_email', "<html><head><title>" . $subject . "</title></head><body>" . $message . "</body></html>", $subject, $message);
        } else {
            $headers = $this->construct_standard__email_headers();
            $message = preg_replace('|&[^a][^m][^p].{0,3};|', '', $message);
            $message = preg_replace('|&amp;|', '&', $message);
            $message = wordwrap(strip_tags($message), 80, "\n");
            $mailtext = apply_filters('bnm_plain_email', $message);
        }

        // Replace any escaped html symbols in subject then apply filter
        $subject = html_entity_decode($subject, ENT_QUOTES);
        $subject = apply_filters('bnm_email_subject', $subject);

        natcasesort($recipients);
        foreach ($recipients as $recipient) {
            $recipient = trim($recipient);
            // sanity check -- make sure we have a valid email
            if (!is_email($recipient) || empty($recipient)) {
                continue;
            }
            $mailtext_to_send = $this->construct_unsubscribe_link($recipient, $mailtext);
            //   echo $recipient. "- ".$mailtext_to_send ."<br>";
            // Use the mail queue provided we are not sending a preview
            if (function_exists('wpmq_mail') && !$this->preview_email) {
                $status = wp_mail($recipient, $subject, $mailtext_to_send, $headers, '', 0);
            } else {
                $status = wp_mail($recipient, $subject, $mailtext_to_send, $headers);
            }
        }
        return $status;
    }

    /* Administration actions */

    /*
     * saves the settings of the menu page
     * @since 1
     *
     * @param    array   $_POST  post global var
     * @return   array $options the options after being saved
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
        if (!empty($_POST['page'])) {
            if (is_numeric($_POST['page']) && $_POST['page'] >= 0) {
                $this->bnm_options['bnmpage'] = $_POST['page'];
            }
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
        $include_cats = '';
        if (!empty($_POST['category'])) {
            sort($_POST['category']);
            $include_cats = implode(',', $_POST['category']);
            $this->bnm_options['include'] = $include_cats;
        }

        if (!empty($_POST['email_format'])) {
            $this->bnm_options['email_format'] = $_POST['email_format'];
        }


        if (isset($_POST["action"]) && $_POST["action"] == "update")
            echo "<div id=\"message\" class=\"updated fade\"><p><strong>$this->options_saved</strong></p></div>";

        update_option('bnm_options', $this->bnm_options);
        return $this->get_bnm_options();
    }

    /*
     * Procces the public petitions for subscription
     * @since 1
     *
     * @param    array   $_POST  post global var
     * @return   string  $message Its a feedback message
     */

    function proccess_public_subscribers($_POST) {
        $sub_error = '';
        $sub_error_not_email = "";

        $email = $_POST['bnm_email'];
        $email = is_email($email);

        if ($email) {
            if ($this->is_email_subscribed($email)) {
                $sub_error = "$email";
            } else {      
                $message = __('Address(es) subscribed ', 'bnm');     
                $var= $this->add_subscriptor($email, $_POST["ip"]);                
                if (gettype($var)=="boolean"){
                    $message = __('Error saving your email', 'bnm');                         
                }else{
                    $message.=$var;                    
                }   
                echo "<div id=\"message\" class=\"updated fade\"><p><strong>" .$message . "</strong></p></div>";                             
            }
        } elseif (!$email) {
            $sub_error_not_email = $_POST['bnm_email'];
        }
        if ($sub_error_not_email != '') {
            echo "<div id=\"message\" class=\"error\"><p><strong>" . __('Some emails were not processed, the following are not emails', 'bnm') . ":<br />$sub_error_not_email</strong></p></div>";
        }
        if ($sub_error != '') {
            echo "<div id=\"message\" class=\"error\"><p><strong>" . __('Some emails were not processed, the following were already subscribed', 'bnm') . ":<br />$sub_error</strong></p></div>";
        }
    }

    /*
     * Procces the admin petitions for subscription
     * @since 1
     *
     * @param    array   $_POST  post global var
     * @return   array  $subscribers all the subscribers to be shown on the admin table
     */

    function process_subscribers_admin_form($_POST) {
        global $wpdb, $bnmnonce;
        $message = "";
        $all_users = Array();
        $all_users = $this->get_all_emails();
        if (isset($_POST['bnm_admin'])) {
            check_admin_referer('bnm-manage_subscribers' . $bnmnonce);
            if ($_POST['addresses']) {
                $sub_error = '';
                $sub_error_not_email = "";
                $unsub_error = '';
                foreach (preg_split("|[\s,]+|", $_POST['addresses']) as $email) {                    
                    $email = is_email($email);
                    if (is_email($email) && $_POST['subscribe']) {
                        if ($this->is_email_subscribed($email) !== false) {
                            ( $sub_error == "") ? $sub_error = "$email" : $sub_error .= ", $email";
                            continue;
                        }
                        $this->add_subscriptor($email, true);
                        $message = "<div id=\"message\" class=\"updated fade\"><p><strong>" . __('Address(es) subscribed a confirmation message will be sent', 'bnm') . "</strong></p></div>";
                    } elseif (is_email($email) && $_POST['unsubscribe']) {
                        if ($this->is_email_subscribed($email) === false) {
                            ('' == $unsub_error) ? $unsub_error = "$email" : $unsub_error .= ", $email";
                            continue;
                        }
                        $this->delete_subscriptor($email);
                        $message = "<div id=\"message\" class=\"updated fade\"><p><strong>" . __('Address(es) unsubscribed', 'bnm') . "</strong></p></div>";
                    } elseif (!is_email($email)) {
                        ($sub_error_not_email == "") ? $sub_error_not_email = "$email" : $sub_error_not_email .= ", $email";
                        continue;
                    }
                }

                if ($sub_error_not_email != '') {
                    echo "<div id=\"message\" class=\"error\"><p><strong>" . __('Some emails were not processed, the following are not emails', 'bnm') . ":<br />$sub_error_not_email</strong></p></div>";
                }
                if ($sub_error != '') {
                    echo "<div id=\"message\" class=\"error\"><p><strong>" . __('Some emails were not processed, the following were already subscribed', 'bnm') . ":<br />$sub_error</strong></p></div>";
                }
                if ($unsub_error != '') {
                    echo "<div id=\"message\" class=\"error\"><p><strong>" . __('Some emails were not processed, the following were not in the database', 'bnm') . ":<br />$unsub_error</strong></p></div>";
                }

                echo $message;

                $_POST['what'] = 'all';
            } elseif (isset($_POST['process']) && $_POST['process']) {

                if (isset($_POST['delete']) && $_POST['delete']) {
                    foreach ($_POST['delete'] as $address) {
                        $this->delete_subscriptor($address);
                    }

                    echo "<div id=\"message\" class=\"updated fade\"><p><strong>" . __('Email deleted', 'bnm') . "</strong></p></div>";
                }
                if (isset($_POST['confirm']) && $_POST['confirm']) {
                    foreach ($_POST['confirm'] as $address) {
                        $this->toggle_confirm_status(is_email($address));
                    }
                    $message = "<div id=\"message\" class=\"updated fade\"><p><strong>" . __('Email confirmed', 'bnm') . "</strong></p></div>";
                }

                if (isset($_POST['unconfirm']) && $_POST['unconfirm']) {
                    foreach ($_POST['unconfirm'] as $address) {
                        $this->toggle_confirm_status(is_email($address));
                    }
                    $message = "<div id=\"message\" class=\"updated fade\"><p><strong>" . __('Email unconfirmed', 'bnm') . "</strong></p></div>";
                }

                echo $message;
            } elseif (isset($_POST['searchterm']) && $_POST['searchterm']) {
                $subscribers = $this->get_all_subscribers();
                foreach ($subscribers as $subscriber) {
                    // echo $subscriber;
                    if (is_numeric(stripos($subscriber, $_POST['searchterm']))) {
                        $this->search_result[] = $subscriber;
                    }
                }
            }
        }
        return $this->get_subscribers($_GET, $_POST);
    }

    /* micelanious */

    /*
     * Create and display a dropdown list of pages
     * @since 1
     *
     * @param    int   $bnmpage page id selected
     * @return   string  $option string which contains all the options to be shown
     */

    function pages_dropdown($bnmpage) {
        global $wpdb;
        $sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_type='page' AND post_status='publish'";
        $pages = $wpdb->get_results($sql);

        if (empty($pages)) {
            return;
        }

        $option = '';
        foreach ($pages as $page) {
            $option .= "<option value=\"" . $page->ID . "\"";
            if ($page->ID == $bnmpage) {
                $option .= " selected=\"selected\"";
            }
            $option .= ">" . $page->post_title . "</option>\r\n";
        }

        echo $option;
    }

    /*
     * Export subscribers information to CSV file
     * @since 1
     *  
     * @return   cvs file
     */

    function exportSubscribersToCSV() {
        $confirmed = $this->get_all_emails();
        $unconfirmed = $this->get_all_emails(0);
        $subscribers = array_merge((array) $confirmed, (array) $unconfirmed);
        natcasesort($subscribers);
        $exportcsv = "User Email,User IP,Sign Up Date\r\n";
        foreach ($subscribers as $subscriber) {
            if (in_array($subscriber, $confirmed)) {
                $exportcsv .= $subscriber . ',' . $this->get_signup_ip($subscriber) . ',' . $this->get_signup_date($subscriber) . ',Confirmed subscriber' . "\r\n";
            } elseif (in_array($subscriber, $unconfirmed)) {
                $exportcsv .= $subscriber . ',' . $this->get_signup_ip($subscriber) . ',' . $this->get_signup_date($subscriber) . ',Unconfirmed subscriber' . "\r\n";
            }
        }
        return $exportcsv;
    }

    /*
     * Get an object of all categories, include default and custom type
     * @since 1
     *
     * @param    boolean   $include included categories
     * @param    orderby   $orderby criteria for order the cats
     * @return   string  $option string which contains all the options to be shown
     */

    function all_cats($include = false, $orderby = 'slug') {
        $all_cats = array();
        $bnm_taxonomies = array('category');
        $bnm_taxonomies = apply_filters('bnm_taxonomies', $bnm_taxonomies);
        foreach ($bnm_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $all_cats = array_merge($all_cats, get_categories(array('hide_empty' => false, 'orderby' => $orderby, 'taxonomy' => $taxonomy)));
            }
        }
        if ($include === true) {
            $included = explode(',', $this->bnm_options['include']);

            // need to use $id like this as this is a mixed array / object
            $id = 0;
            foreach ($all_cats as $cat) {
                if (!in_array($cat->term_id, $included)) {
                    unset($all_cats[$id]);
                }
                $id++;
            }
        }

        return $all_cats;
    }

    /*
     * Sets all default messages to be shown
     * @since 1
     *     
     */

    function messages_to_show() {
        $this->confirmation_sent = __('Confirmation sent', 'bnm');
        $this->already_subscribed = __('Already subscribed', 'bnm');
        $this->not_subscribed = __('Not subscribed', 'bnm');
        $this->not_an_email = __('That is not an email', 'bnm');
        $this->error = __('Error', 'bnm');
        $this->mail_sent = __('Email sent', 'bnm');
        $this->mail_failed = __('Mail failed', 'bnm');
        $this->form = __('Form', 'bnm');
        $this->no_such_email = __('El correo que intenta confirmar no existe en nuestra base de datos', 'bnm');
        $this->added = __('Su email ha sido confirmado, bienvenido a nuestro Breaking News', 'bnm');
        $this->deleted = __('Su correo ha sido eliminado de nuestra lista de Breaking News', 'bnm');
        $this->subscribe = __('Subscribe', 'bnm');
        $this->unsubscribe = __('Unsubscribe', 'bnm');
        $this->confirm_subject = __('Confirm subject', 'bnm');
        $this->options_saved = __('Options Saved', 'bnm');
        $this->options_reset = __('Options Reset', 'bnm');
    }

    /*     * ****** Geters ******* */

    /*
     * Get the link of the page to be shown when a user clicks on the confirmation link
     * @since 1
     *     
     * @return   string  $url page link
     */

    function get_confirmation_page_link() {
        $url = get_site_url();
        if ($this->bnm_options['bnmpage'] > 0) {
            $page = get_page($this->bnm_options['bnmpage']);
            $url .= "/" . $page->post_name;
        }
        return $url;
    }

    /*
     * Function to add UTM tracking details to links
     * @since 1
     *
     * @param    link    $link    initial link
     * @return   link with the UTM tracking
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

    /*
     * Given a public subscriber ID, returns the email address
     * 
     * @since 1
     *
     * @param    id    $id    user id
     * @return   $email  email of the user
     */

    function get_email($id = 0) {
        global $wpdb;

        if (!$id) {
            return false;
        }

        return $wpdb->get_var("SELECT email FROM " . BNM_USERS . " WHERE id=$id and status=1");
    }

    /*
     * Collects the signup date for subscribers
     * 
     * @since 1
     *
     * @param    email    $email    users email
     * @return   signup date
     */

    function get_signup_date($email = '') {
        if ('' == $email) {
            return false;
        }
        global $wpdb;
        if (!empty($this->signup_dates)) {
            return date_format(date_create($this->signup_dates[$email]), 'd-m-Y');
        } else {
            $results = $wpdb->get_results("SELECT email, date FROM " . BNM_USERS . " WHERE status=1", ARRAY_N);
            foreach ($results as $result) {
                $this->signup_dates[$result[0]] = $result[1];
            }
            return date_format(date_create($this->signup_dates[$email]), 'd-m-Y');
        }
    }

    /*
     * Collects the ip address for subscribers
     * 
     * @since 1
     *
     * @param    email    $email    users email
     * @return   sign up ip
     */

    function get_signup_ip($email = '') {
        if ('' == $email) {
            return false;
        }
        global $wpdb;
        if (!empty($this->signup_ips)) {
            return $this->signup_ips[$email];
        } else {
            $results = $wpdb->get_results("SELECT email, ip FROM " . BNM_USERS . " WHERE status=1", ARRAY_N);
            foreach ($results as $result) {
                $this->signup_ips[$result[0]] = $result[1];
            }
            return $this->signup_ips[$email];
        }
    }

    /*
     * Gets the ip address of the visitor
     * 
     * @since 1
     *    
     * @return   ip address
     */

    function get_visitor_ip_addr() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /*
     * Collects the email address for all  confirmed  or unconfirmed subscribers
     * default confirmed ones
     * @since 1
     *
     * @param    confirmed    $confirmed    users email
     * @return   array $emails
     */

    function get_all_emails($confirmed = 1) {
        global $wpdb;
        if ($confirmed) {
            return $wpdb->get_col("SELECT email FROM " . BNM_USERS . " WHERE active=1 and status=1 ");
        } else {
            return $wpdb->get_col("SELECT email FROM " . BNM_USERS . " WHERE active=0 and status=1");
        }
    }

    /*
     * Given a public subscriber email, returns the subscriber ID
     * @since 1
     *
     * @param    email   $email    users email
     * @return  id
     */

    function get_id($email = '') {
        global $wpdb;
        if (!$email) {
            return false;
        }
        return $wpdb->get_var("SELECT id FROM " . BNM_USERS . " WHERE CAST(email as binary)='$email' and status=1");
    }

    /*
     * Get the bnm options array
     * @since 1
     *   
     * @return  array bnm options
     */

    function get_bnm_options() {
        return get_option('bnm_options');
    }

    /*
     * Get all subscribers
     * @since 1
     *        
     * @return  array subscribers
     */

    function get_all_subscribers() {
        $confirmed = $this->get_all_emails();
        $unconfirmed = $this->get_all_emails(0);
        return array_merge((array) $confirmed, (array) $unconfirmed);
    }

    /*
     * Get all subscribers to be shown on the admin page
     * @since 1
     *   
     * @param        $_GET    global var
     * @param        $_POST   global var
     * @return  array subscribers
     */

    function get_subscribers($_GET, $_POST) {
        $confirmed = $this->get_all_emails();
        $unconfirmed = $this->get_all_emails(0);

        if ($confirmed == '') {
            $confirmed = array();
        }
        if ($unconfirmed == '') {
            $unconfirmed = array();
        }
        $urlpath = str_replace("\\", "/", BNM_PATH);
        $urlpath = trailingslashit(get_option('siteurl')) . substr($urlpath, strpos($urlpath, "wp-content/"));
        if (isset($_GET['bnmpage'])) {
            $page = (int) $_GET['bnmpage'];
        } else {
            $page = 1;
        }
        if (isset($_POST['what'])) {
            $page = 1;

            if ($_POST['what'] == 'all' || $_POST['what'] == 'all_users') {
                $what = 'all';
                $subscribers = array_merge((array) $confirmed, (array) $unconfirmed);
            } elseif ($_POST['what'] == 'confirmed') {
                $what = 'confirmed';
                $subscribers = $confirmed;
            } elseif ($_POST['what'] == 'unconfirmed') {
                $what = 'unconfirmed';
                $subscribers = $unconfirmed;
            }
        } elseif (isset($_GET['what'])) {
            if ('all' == $_GET['what'] || $_GET['what'] == "all_users") {
                $what = 'all';
                $subscribers = array_merge((array) $confirmed, (array) $unconfirmed);
            } elseif ('confirmed' == $_GET['what']) {
                $what = 'confirmed';
                $subscribers = $confirmed;
            } elseif ('unconfirmed' == $_GET['what']) {
                $what = 'unconfirmed';
                $subscribers = $unconfirmed;
            }
        } else {
            $what = 'all';
            $subscribers = array_merge((array) $confirmed, (array) $unconfirmed);
        }
        if (isset($_POST['searchterm']) && $_POST['searchterm']) {
            $subscribers = &$this->search_result;
            $what = 'public';
        }
        $return = Array();
        $return [$what] = $subscribers;
        return $return;
    }

    /*
     * Load all js required for this plugin
     * @since 1
     *     
     */

    function bnm_load_jquery() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-form-val', plugin_dir_url(__FILE__) . '../js/jquery.form.js','jquery');
        wp_enqueue_script('jquery-validate', plugin_dir_url(__FILE__) . '../js/jquery.validate.js');
        wp_enqueue_script('jquery-validate-spanish', plugin_dir_url(__FILE__) . '../js/messages_es.js','jquery-validate');
        wp_enqueue_script('bnm_ajax', plugin_dir_url(__FILE__) . '../js/bnm_subscription_ajax.js','jquery-form-val');

        // Get current page protocol
        $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
        // Output admin-ajax.php URL with same protocol as current page
        $params = array(
            'ajaxurl' => admin_url('admin-ajax.php', $protocol)
        );
        wp_localize_script('bnm_ajax', 'bnm_ajax', $params);
    }

    /*
     * Adds the slyle for the feedback messages on the widget
     * @since 1
     *            
     */

    function add_css_bnm() {
        ?> 
        <style>
            label.error { float: none; color: red; padding-left: .5em; vertical-align: top; display:inline-block;}
        </style>
        <?php

    }

    /*
     * Load the javascript only in the admin page
     * @since 1
     *            
     */

    function checkbox_form_js() {
        $this->script_debug = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? '.dev' : '';
        wp_register_script('s2_checkbox', plugin_dir_url(__FILE__) . '../js/s2_checkbox' . $this->script_debug . '.js', array('jquery'), '1.1');
        wp_enqueue_script('s2_checkbox');
    }

}
?>