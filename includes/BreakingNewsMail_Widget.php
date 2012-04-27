<?php
/**
 * This class shows the widget for subscriptions
 * @author Daniela Valero aka DaHe
 * @copyright 2012
 * @license http://www.gnu.org/licenses/gpl.html GPL v2 
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
        if (isset($instance['prev_text'])) {
            $text = $instance['prev_text'];
        } else {
            $text = __('Subscribe to our Breaking news email alerts', 'bnm');
        }

        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('Subscribe', 'bnm');
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title:'); ?></label> 

            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="title" 
                   value="<?php echo esc_attr($title); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('prev_text'); ?>"><?php _e('Text to show:'); ?></label> 

            <input class="widefat" id="<?php echo $this->get_field_id('prev_text'); ?>" 
                   name="<?php echo $this->get_field_name('prev_text'); ?>" type="text" 
                   value="<?php echo esc_attr($text); ?>" />
        </p>
        <?php       
    }

    //update the widget’s properties specified in the form in the admin view
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['prev_text'] = strip_tags($new_instance['prev_text']);
        $instance['bnm_email'] = strip_tags($new_instance['bnm_email']);        
        return $instance;
    }

    //display the widget on the blog
    public function widget($args, $instance) {
        extract($args, EXTR_SKIP);
        $title = $instance['title'];
        $prev_text = $instance['prev_text'];        
        echo $before_widget;
        echo $before_title . $title . $after_title;
        ?> 
        <div id='result'></div>
        <span> <?php echo $prev_text ?></span>
        <form name='bnm_subscribe_form' id='bnm_subscribe_form' method='post' action=''>
            <?php wp_nonce_field('bnm_nonce'); ?>            
            <input type="email" name='bnm_email' class="required email"  id='bnm_email' value='' required/>
            <input type="hidden" name="ip" id='bnm_ip' value="<?php echo $_SERVER['REMOTE_ADDR'] ?>" />
            <br>
            <input type='submit' value='Subscribe' name='bnm_subscribe_submit' id='bnm_subscribe_submit' />
        </form>
        <?php
        echo $after_widget;
    }

}
?>