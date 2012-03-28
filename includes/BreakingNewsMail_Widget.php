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
    }

    //update the widget’s properties specified in the form in the admin view
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['text'] = strip_tags($new_instance['text']);
        $instance['bnm_email'] = strip_tags($new_instance['bnm_email']);
        return $instance;
    }

    //display the widget on the blog
    public function widget($args, $instance) {
        
        
         extract($args);
?>
        <?php echo $before_widget; ?>
            <?php echo $before_title
                . 'My Unique Widget'
                . $after_title; ?>
            Hello, World!
        <?php echo $after_widget; ?>
<?php
        
      /*  extract($args, EXTR_SKIP);
        
        $title = empty($instance['title']) ? _('Breaking news email subscription') : $instance['title'];
        $text = empty($instance['text']) ? _('Subscribe yourself') : $instance['text'];

       // $title = apply_filters('widget_title', $title);
      //  $text = apply_filters('widget_text', $text);

        ///echo "AHAHAHAHAHAH";
        echo $before_widget;
        echo $before_title . $title . $after_title;
        ?> 
        <form name='bnm_subscribe_form' id='bnm_subscribe_form' method='post' action='#porDefinir'>
            <h3> <?php $text ?> Titulo</h3>
            <input type='text' name='bnm_email' id='bnm_email' value='' />
            <input type='submit' value='Subscribe' name='bnm_subscribe_submit' id='bnm_subscribe_submit' />
        </form>
        <?php
        echo $after_widget;*/
    }

}
?>
