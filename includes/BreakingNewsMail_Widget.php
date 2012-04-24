<?php

/**
 * This class shows the widget for subscriptions
 * @author Daniela VAlero
 * @copyright 2012
 * @license http://www.gnu.org/licenses/gpl.html GPL v3 
 * @since 1.0
 * @package WP-Breaking-News-Mail
 */
class BreakingNewsMail_Widget extends WP_Widget {

    // call the parent constructor and initialize any class variables
    public function __construct() {
        parent::__construct('bnm_widget', // Base ID
                'Breaking news mail widget', // Name
                array('description' => __('The subscription form for the users', 'text_domain')), // widget options
                array('width' => 250, 'height' => 300)//control options
        );
    }

    // display a form for the widget in the admin view to customize the widget’s properties
    public function form($instance) {
        if (isset($instance['text'])) {
            $text = $instance['text'];
        } else {
            $text = __('Subscribe to our Breaking news email alerts', 'text_domain');
        }

        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('Subscribe', 'text_domain');
        }

        if (isset($instance['postto'])) {
            $postto = $instance['postto'];
        }else{
            $postto = "";
        }
        global $wpdb;
        $sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_type='page' AND post_status='publish'";
        $pages = $wpdb->get_results($sql);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title:'); ?></label> 

            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="title" 
                   value="<?php echo esc_attr($title); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('text'); ?>"><?php _e('Text to show:'); ?></label> 

            <input class="widefat" id="<?php echo $this->get_field_id('text'); ?>" 
                   name="<?php echo $this->get_field_name('text'); ?>" type="text" 
                   value="<?php echo esc_attr($text); ?>" />
        </p>
        <?php
        if (!empty($pages)) {
            echo "<p><label for=\"" . $this->get_field_name('postto') . "\">" . __('Post form content to page', 'bnm') . ":\r\n";
            echo "<select id=\"" . $this->get_field_id('postto') . "\" name=\"" . $this->get_field_name('postto') . "\">\r\n";
            echo "<option value=\"\">" . __('Use Default', 'bnm') . "</option>\r\n";
            $option = '';
            foreach ($pages as $page) {
                $option .= "<option value=\"" . $page->ID. "\"";                
                if ($page->ID == $postto) {
                    $option .= " selected=\"selected\"";
                }
                $option .= ">" . $page->post_title . "</option>\r\n";
            }
            echo $option;
            echo "</select></label></p>\r\n";
        }
    }

    //update the widget’s properties specified in the form in the admin view
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['text'] = strip_tags($new_instance['text']);
        $instance['bnm_email'] = strip_tags($new_instance['bnm_email']);
        $instance['postto'] = stripslashes($new_instance['postto']);
        return $instance;
    }

    //display the widget on the blog
    public function widget($args, $instance) {
        extract($args, EXTR_SKIP);
        $title = empty($instance['title']) ? _('Breaking news email subscription') : $instance['title'];
        $text = empty($instance['text']) ? _('Subscribe yourself') : $instance['text'];
        $postto = empty($instance['postto']) ? esc_url($_SERVER['REQUEST_URI']) : get_permalink($instance['postto']);
        echo $before_widget;
        echo $before_title . $title . $after_title;
        ?> 
        <div id='result'></div>
        <form name='bnm_subscribe_form' id='bnm_subscribe_form' method='post' action='<?php echo $postto; ?>'>
            <?php wp_nonce_field('bnm_nonce'); ?>
            <h3> <?php $text ?></h3>
            <input type="email" name='bnm_email' class="required email"  id='bnm_email' value='' required/>
            <input type="hidden" name="ip" value="<?php echo $_SERVER['REMOTE_ADDR'] ?>" />
            <input type='submit' value='Subscribe' name='bnm_subscribe_submit' id='bnm_subscribe_submit' />
        </form>
        <?php
        echo $after_widget;
    }

}
?>
