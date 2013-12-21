<?php
/*
  Plugin Name: Simple Youtube Content
  Plugin URI: http://www.sandorkovacs.ro/simple-sticky-footer-wordpress-plugin/
  Description: Lightweight Sticky Footer plugin
  Author: Sandor Kovacs
  Version: 1.0.0
  Author URI: http://sandorkovacs.ro/
 */

/*
 * 
 * 
 * 
  http://www.youtube.com/watch?v=j6Pvsvxfg1c
  http://www.youtube.com/watch?v=KZkbE_AFOe8
  http://www.youtube.com/watch?v=QyflgtkOXVo
 */

add_action('admin_init', 'simple_youtube_content_init');
add_action('admin_menu', 'register_simple_sf_ban_submenu_page');

function simple_youtube_content_init() {
    /* Register our stylesheet. */
    wp_register_style('simple-sticky-footer', plugins_url('simple-sticky-footer.css', __FILE__));
    wp_enqueue_style('simple-sticky-footer');

    wp_enqueue_script('jquery');
}

function register_simple_sf_ban_submenu_page() {
    add_submenu_page(
            'edit.php', __('Youtube Content'), __('Youtube Content'), 'edit_posts', 'simple-simple-youtube_content', 'simple_youtube_content_callback');
}

function simple_youtube_content_callback() {
   
    
    // form submit  and save values
    if (isset($_POST['submit'])) {
        
        
        
        $simple_youtube_content = str_replace("\n\r", "\n", $_POST['simple_youtube_content']);
        $simple_youtube_content = str_replace("\r", "", $simple_youtube_content);
        $content_arr = array_filter(explode("\n", $simple_youtube_content));

        // Process youtube urls

        foreach ($content_arr as $y_url_title) {

            // Split title 
            $y_arr = explode(',', $y_url_title);
            $y_url = $y_arr[0];

            // Get video ID

            parse_str(trim($y_url), $y_info);
            $video_post['id'] = $id = reset($y_info);


            // Get video title
            if (empty($y_arr[1]) || strlen($y_arr[1]) < 5) {
                $content = file_get_contents("http://youtube.com/get_video_info?video_id=" . $id);
                parse_str($content, $ytarr);
                $video_post['title'] = $ytarr['title'];
            } else {
                $video_post['title'] = $y_arr[1];
            }

            // Get video image 
            $y_url_image = 'http://img.youtube.com/vi/' . $id . '/0.jpg';

            _e("INFO: $y_url was inserted . <br/>");
            $my_post = array(
                'post_title' => $video_post['title'],
                'post_content' => $y_url,
                'post_status' => 'publish',
                'post_date' => date("Y-m-d H:i:s"),
                'post_author' => get_current_user_id(),
                'post_type' => 'post',
                'post_category' => array($_POST['cat']),
            );

            update_option('simple_youtube_content_width', $_POST['cat']);
            
            // Insert the post into the database
            $post_id = wp_insert_post($my_post);
            update_post_meta($post_id, '_aioseop_title', $video_post['title']);

            fetch_media($y_url_image, $post_id, $video_post['id']);
        } // end foreach
    }
    ?>

    <div class="wrap" id='simple-sf'>
        <h2><?php _e('YouTube Content') ?>
        <p><?php _e('Add one or more youtube url in the following text area. ') ?></p>    

        <form action="" method="post">
            <p>
                <label for='simple_youtube_content'><strong><?php _e('Enter youtube urls, one per line! '); ?></strong></label> <br/>
                <textarea name='simple_youtube_content' id='simple-youtube_content' cols="80" rows="10"></textarea>
                <br><span><?php _e('Examples:'); ?></span>  
                <br><small><?php _e('<strong>youtube url</strong>:   http://www.youtube.com/watch?v=j6Pvsvxfg1c'); ?></small>
                <br><small><?php _e('<strong>youtube url with custom title</strong>:   http://www.youtube.com/watch?v=j6Pvsvxfg1c, Custom title'); ?></small>
            </p>

            <p><?php _e('Categories:'); ?></p>
            <p>
                <?php wp_dropdown_categories('hierarchical=1&hide_empty=0&selected=' . get_option('simple_youtube_content_width', 1)); ?>
            </p>
            <p>
                <input type='submit' name='submit' value='<?php _e('Insert All') ?>' />
            </p>


        </form>

    </div>

    <?php
}

/* Import media from url
 *
 * @param string $file_url URL of the existing file from the original site
 * @param int $post_id The post ID of the post to which the imported media is to be attached
 *
 * @return boolean True on success, false on failure
 */

function fetch_media($file_url, $post_id, $video_id) {

    if (!$post_id) {
        return false;
    }


    $dir = wp_upload_dir();
//        echo '<pre>';var_dump($dir);die;
    //directory to import to	
    //$artDir = 'wp-content/uploads/importedmedia/';
    $artDir = $dir['path'];
    //rename the file... alternatively, you could explode on "/" and keep the original file name
    $new_filename = str_replace('.jpg', '_' . $video_id . '.jpg', array_pop(explode("/", $file_url)));
    $new_filename_orig = $new_filename;
    $new_filename = str_replace('?', '', $new_filename);
//        echo '<pre>';var_dump($file_url, $ext);die;
//	$new_filename = 'blogmedia-'.$showID.".".$ext;

    if (@fclose(@fopen($file_url, "r"))) { //make sure the file actually exists
        $new_filename_path = $artDir . '/' . $new_filename;
        copy($file_url, $new_filename_path);


        $siteurl = get_option('siteurl');
        $file_info = getimagesize($new_filename_path);

        $uploads = wp_upload_dir();


        //create an array of attachment data to insert into wp_posts table
        $artdata = array();
        $artdata = array(
            'post_author' => 1,
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql'),
            'post_title' => $new_filename,
            'post_status' => 'inherit',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_name' => sanitize_title_with_dashes(str_replace("_", "-", $new_filename)), 'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql'),
            'post_parent' => $post_id,
            'post_type' => 'attachment',
            'guid' => $uploads['url'] . '/' . $new_filename,
            'post_mime_type' => $file_info['mime'],
            'post_excerpt' => '',
            'post_content' => ''
        );

        $save_path = str_replace('?', ' ', $new_filename_path);
        //insert the database record
        $attach_id = wp_insert_attachment($artdata, $save_path, $post_id);

        //generate metadata and thumbnails
        if ($attach_data = wp_generate_attachment_metadata($attach_id, $save_path)) {
            wp_update_attachment_metadata($attach_id, $attach_data);
        }

        // Set featured image
        set_post_thumbnail($post_id, $attach_id);
    } else {
        return false;
    }

    return true;
}