<?php
/*
  Plugin Name: Genesis Featured Page Extended
  Plugin URI: http://www.enovision.net/genesis_featured_page_extended
  Description: More options for the widget for Genesis Featured Page
  Author: Johan van de Merwe (based on the Studiopress plugin)
  Author URI: http://www.enovision.net

  Version: 2.2.1

  License: GNU General Public License v2.0 (or later)
  License URI: http://www.opensource.org/licenses/gpl-license.php
 */

class Genesis_Featured_Page_Extended extends WP_Widget {

    /**
     * Holds widget settings defaults, populated in constructor.
     *
     * @var array
     */
    protected $defaults;

    /**
     * Constructor. Set the default widget options and create widget.
     *
     * @since 0.1.8
     */
    function __construct() {

        $this->defaults = array(
            'title' => '',
            'page_id' => '',
            'show_image' => 0,
            'image_url' => '',
            'image_alignment' => '',
            'image_size' => '',
            'show_title' => 0,
            'show_byline' => 0,
            'post_info' => '[post_date] ' . __('By', 'genesis-fpe') . ' [post_author_posts_link] [post_comments]',
            'show_content' => 0,
            'show_excerpt' => 0,
            'show_excerpt_more' => 0,
            'content_limit' => '',
            'more_text' => '',
        );

        $widget_ops = array(
            'classname' => 'featuredpage',
            'description' => __('Displays extended featured page with thumbnails', 'genesis-featured-page-extended'),
        );

        $control_ops = array(
            'width' => 200,
            'height' => 250,
            'id_base' => 'featuredpage-extended'
        );

        load_plugin_textdomain('genesis-fpe', false, basename(dirname(__FILE__)) . '/languages');

        parent::__construct('featuredpage-extended', __('Genesis - Featured Page Extended', 'genesis-featured-page-extended'), $widget_ops, $control_ops);
    }

    /**
     * Echo the widget content.
     *
     * @since 0.1.8
     *
     * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
     * @param array $instance The settings for the particular instance of the widget
     */
    function widget($args, $instance) {

        global $wp_query;

        extract($args);

        /** Merge with defaults */
        $instance = wp_parse_args((array) $instance, $this->defaults);

        echo $before_widget;

        /** Set up the author bio */
        if (!empty($instance['title']))
            echo $before_title . apply_filters('widget_title', $instance['title'], $instance, $this->id_base) . $after_title;

        $wp_query = new WP_Query(array('page_id' => $instance['page_id']));


        if (have_posts()) : while (have_posts()) : the_post();

                genesis_markup(array(
                    'html5' => '<article %s>',
                    'xhtml' => sprintf('<div class="%s">', implode(' ', get_post_class())),
                    'context' => 'entry',
                ));


                if (!empty($instance['show_image'])) {

                    $image = genesis_get_image(array(
                        'format' => 'html',
                        'size' => $instance['image_size'],
                        'context' => 'featured-page-widget',
                        'attr' => genesis_parse_attr('entry-image-widget'),
                    ));

                    if ($image)
                        printf('<a href="%s" title="%s" class="%s">%s</a>', get_permalink(), the_title_attribute('echo=0'), esc_attr($instance['image_alignment']), $image);
                } else {

                    if (!empty($instance['image_url'])) {

                        $file = explode('.', $instance['image_url']);
                        $fname = $file[0];
                        $ext = $file[1];
                        $sizes = genesis_get_image_sizes(); // all the image size available
                        $sz = $instance['image_size'];   // needed size (set in widget)
                        $udir = wp_upload_dir();            // Wordpress Upload Dir
                        $path = $udir['baseurl'];           // Path to upload WP dir
                        $size = $sizes[$sz]['width'] . 'x' . $sizes[$sz]['height']; // Format the image size
                        $image = $path . '/' . $fname . '-' . $size . '.' . $ext; // The actual image

                        printf(
                                '<a href="%s" title="%s" class="%s">%s</a>', get_permalink(), the_title_attribute('echo=0'), esc_attr($instance['image_alignment']), '<img width="' . $sizes[$sz]['width'] . '" height="' . $sizes[$sz]['height'] . '"
                                    src="' . $image . '" class="attachment-' . $sz . '"
                                    alt="' . the_title_attribute('echo=0') . '"></img>'
                        );
                    }
                }

                if (!empty($instance['show_title'])) {

                    if (genesis_html5())
                        printf('<header class="entry-header"><h2 class="entry-title"><a href="%s" title="%s">%s</a></h2></header>', get_permalink(), the_title_attribute('echo=0'), get_the_title());
                    else
                        printf('<h2><a href="%s" title="%s">%s</a></h2>', get_permalink(), the_title_attribute('echo=0'), get_the_title());
                }


                if (!empty($instance['show_byline']) && !empty($instance['post_info']))
                    printf(genesis_html5() ? '<p class="entry-meta">%s</p>' : '<p class="byline post-info">%s</p>', do_shortcode($instance['post_info']));


                if (!empty($instance['show_content'])) {

                    echo genesis_html5() ? '<div class="entry-content">' : '';

                    if (empty($instance['content_limit'])) {

                        global $more;
                        $more = 0;

                        the_content($instance['more_text']);
                    } else {
                        the_content_limit((int) $instance['content_limit'], esc_html($instance['more_text']));
                    }

                    echo genesis_html5() ? '</div>' : '';
                }

                if (!empty($instance['show_excerpt']) && empty($instance['show_content'])) { // mod by JJ
                    echo genesis_html5() ? '<div class="entry-content">' : '';

                    $excerpt = get_the_excerpt();

                    echo '<p>' . $excerpt;

                    if (!empty($instance['show_excerpt_more'])) {
                        echo ' <a class="read-more more-link" href="' . get_permalink(get_the_ID()) . '">' . esc_html($instance['more_text']) . '</a>';
                    }

                    echo '</p>';

                    echo genesis_html5() ? '</div>' : '';
                }

                genesis_markup(array(
                    'html5' => '</article>',
                    'xhtml' => '</div><!--end post_class()-->' . "\n\n",
                ));

            endwhile;
        endif;

        echo $after_widget;
        wp_reset_query();
    }

    /**
     * Update a particular instance.
     *
     * This function should check that $new_instance is set correctly.
     * The newly calculated value of $instance should be returned.
     * If "false" is returned, the instance won't be saved/updated.
     *
     * @since 0.1.8
     *
     * @param array $new_instance New settings for this instance as input by the user via form()
     * @param array $old_instance Old settings for this instance
     * @return array Settings to save or bool false to cancel saving
     */
    function update($new_instance, $old_instance) {

        $new_instance['title'] = strip_tags($new_instance['title']);
        $new_instance['more_text'] = strip_tags($new_instance['more_text']);
        return $new_instance;
    }

    /**
     * Echo the settings update form.
     *
     * @since 0.1.8
     *
     * @param array $instance Current settings
     */
    function form($instance) {

        /** Merge with defaults */
        $instance = wp_parse_args((array) $instance, $this->defaults);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'genesis-fpe'); ?>:</label>
            <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" class="widefat" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('page_id'); ?>"><?php _e('Page', 'genesis-fpe'); ?>:</label>
            <?php wp_dropdown_pages(array('name' => $this->get_field_name('page_id'), 'selected' => $instance['page_id'])); ?>
        </p>

        <hr class="div" />

        <p>
            <input id="<?php echo $this->get_field_id('show_image'); ?>" type="checkbox" name="<?php echo $this->get_field_name('show_image'); ?>" value="1"<?php checked($instance['show_image']); ?> />
            <label for="<?php echo $this->get_field_id('show_image'); ?>"><?php _e('Show Featured Image', 'genesis-fpe'); ?></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('image_url'); ?>"><?php _e('Image URL', 'genesis-fpe'); ?>:</label>
            <input type="text" id="<?php echo $this->get_field_id('image_url'); ?>" name="<?php echo $this->get_field_name('image_url'); ?>" value="<?php echo esc_attr($instance['image_url']); ?>" class="widefat" />
        </p>


        <p>
            <label for="<?php echo $this->get_field_id('image_size'); ?>"><?php _e('Image Size', 'genesis-fpe'); ?>:</label>
            <select id="<?php echo $this->get_field_id('image_size'); ?>" name="<?php echo $this->get_field_name('image_size'); ?>">
                <option value="thumbnail">thumbnail (<?php echo get_option('thumbnail_size_w'); ?>x<?php echo get_option('thumbnail_size_h'); ?>)</option>
                <?php
                $sizes = genesis_get_additional_image_sizes();
                foreach ((array) $sizes as $name => $size)
                    echo '<option value="' . $name . '" ' . selected($name, $instance['image_size'], FALSE) . '>' . $name . ' (' . $size['width'] . 'x' . $size['height'] . ')</option>';
                ?>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('image_alignment'); ?>"><?php _e('Image Alignment', 'genesis-fpe'); ?>:</label>
            <select id="<?php echo $this->get_field_id('image_alignment'); ?>" name="<?php echo $this->get_field_name('image_alignment'); ?>">
                <option value="alignnone">- <?php _e('None', 'genesis-fpe'); ?> -</option>
                <option value="alignleft" <?php selected('alignleft', $instance['image_alignment']); ?>><?php _e('Left', 'genesis-fpe'); ?></option>
                <option value="alignright" <?php selected('alignright', $instance['image_alignment']); ?>><?php _e('Right', 'genesis-fpe'); ?></option>
            </select>
        </p>

        <hr class="div" />

        <p>
            <input id="<?php echo $this->get_field_id('show_title'); ?>" type="checkbox" name="<?php echo $this->get_field_name('show_title'); ?>" value="1"<?php checked($instance['show_title']); ?> />
            <label for="<?php echo $this->get_field_id('show_title'); ?>"><?php _e('Show Page Title', 'genesis-fpe'); ?></label>
        </p>

        <p>
            <input id="<?php echo $this->get_field_id('show_byline'); ?>" type="checkbox" name="<?php echo $this->get_field_name('show_byline'); ?>" value="1"<?php checked($instance['show_byline']); ?> />
            <label for="<?php echo $this->get_field_id('show_byline'); ?>"><?php _e('Show Page Byline', 'genesis-fpe'); ?></label>
            <input type="text" id="<?php echo $this->get_field_id( 'post_info' ); ?>" name="<?php echo $this->get_field_name( 'post_info' ); ?>" value="<?php echo esc_attr( $instance['post_info'] ); ?>" class="widefat" />
        </p>

        <p>
            <input id="<?php echo $this->get_field_id('show_content'); ?>" type="checkbox" name="<?php echo $this->get_field_name('show_content'); ?>" value="1"<?php checked($instance['show_content']); ?> />
            <label for="<?php echo $this->get_field_id('show_content'); ?>"><?php _e('Show Page Content', 'genesis-fpe'); ?></label>
            <br/><em><?php _e('If Show Page Excerpt is also checked, it will show the content, not the excerpt !!!', 'genesis-fpe') ?></em>
        </p>

        <p>
            <input id="<?php echo $this->get_field_id('show_excerpt'); ?>" type="checkbox" name="<?php echo $this->get_field_name('show_excerpt'); ?>" value="1"<?php checked($instance['show_excerpt']); ?> />
            <label for="<?php echo $this->get_field_id('show_excerpt'); ?>"><?php _e('Show Page Excerpt', 'genesis-fpe'); ?></label>
            <br/><em><?php _e('This option requires the Page Excerpt plugin by Jeremy Massel', 'genesis-fpe') ?></em>
        </p>

        <p>
            <input id="<?php echo $this->get_field_id('show_excerpt_more'); ?>" type="checkbox" name="<?php echo $this->get_field_name('show_excerpt_more'); ?>" value="1"<?php checked($instance['show_excerpt_more']); ?> />
            <label for="<?php echo $this->get_field_id('show_excerpt_more'); ?>"><?php _e('Show More Button when Page Excerpt selected', 'genesis-fpe'); ?></label>
        </p>


        <p>
            <label for="<?php echo $this->get_field_id('content_limit'); ?>"><?php _e('Content Character Limit', 'genesis-fpe'); ?>:</label>
            <input type="text" id="<?php echo $this->get_field_id('content_limit'); ?>" name="<?php echo $this->get_field_name('content_limit'); ?>" value="<?php echo esc_attr($instance['content_limit']); ?>" size="3" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('more_text'); ?>"><?php _e('More Text', 'genesis-fpe'); ?>:</label>
            <input type="text" id="<?php echo $this->get_field_id('more_text'); ?>" name="<?php echo $this->get_field_name('more_text'); ?>" value="<?php echo esc_attr($instance['more_text']); ?>" />
        </p>
        <?php
    }

}

add_action('widgets_init', create_function('', 'return register_widget("Genesis_Featured_Page_Extended");'));