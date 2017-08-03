<?php

class ARS_Widget extends WP_Widget
{

    function __construct()
    {
        parent::__construct(
            'ars_widget',
            __('AdSense Rev Share', 'ars_widget_domain'),
            array('description' => __('AdSense Revenue Share widget', 'ars_widget_domain'),)
        );
    }

    public function widget($args, $instance)
    {
        $general_options = get_option('ars_settings');
        $widget_options = $instance;

        $admin_pub = $general_options['ars_general_publisher_id'];
        $percent = $general_options['ars_general_percent'];

        if (is_single())
            $post_id = $GLOBALS['post']->ID;

        if ($post_id) {
            $author_id = get_post($post_id)->post_author;

            $author_pub = get_the_author_meta('ars-publisher', $author_id);
            $author_percent = get_the_author_meta('ars-percent', $author_id);
        }

        if ($author_percent)
            $percent = $author_percent;

        if (empty($author_pub) || !preg_match('/pub-\d{16}/', $author_pub))
            $author_pub = $admin_pub;

        list($width, $height) = explode('x', $widget_options['size']);

        $display_pub = (mt_rand(1, 100) <= $percent) ? $author_pub : $admin_pub;

        if (empty($display_pub) || !preg_match('/pub-\d{16}/', $display_pub))
            $content = '';
        else
            $content = '
                <div class="adsense-banner">
                    <script type="text/javascript"><!--
                    google_ad_client = "ca-' . $display_pub . '";
                    google_ad_width = ' . $width . ';
                    google_ad_height = ' . $height . ';
                    //-->
                    </script>
                    <script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
                </div>';

        $title = apply_filters('widget_title', $instance['title']);

        echo $args['before_widget'];

        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];

        echo $content;

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('Advertisement', 'ars_widget_domain');
        }

        $size = $instance['size'];

    ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('size'); ?>"><?php _e('Size:'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>">
                <option value="300x250" <?php if ($size == '300x250') echo 'selected'; ?>>Medium rectangle (300 x 250)</option>
                <option value="336x280" <?php if ($size == '336x280') echo 'selected'; ?>>Large rectangle (336 x 280)</option>
                <option value="160x600" <?php if ($size == '160x600') echo 'selected'; ?>>Wide skyscraper (160x600)</option>
                <option value="300x600" <?php if ($size == '300x600') echo 'selected'; ?>>Large skyscraper (300 x 600)</option>
                <option value="250x250" <?php if ($size == '250x250') echo 'selected'; ?>>Square (250 x 250)</option>
                <option value="200x200" <?php if ($size == '200x200') echo 'selected'; ?>>Small square (200 x 200)</option>
            </select>
        </p>
    <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();

        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['size'] = $new_instance['size'];

        return $instance;
    }
}

// Register and load the widget
function ars_load_widget()
{
    register_widget('ARS_Widget');
}

add_action('widgets_init', 'ars_load_widget');