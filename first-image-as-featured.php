<?php
/**
 * Plugin Name:       First Image as Featured
 * Plugin URI:        https://github.com/anikaizoku/first-image-as-featured
 * Description:       Scans posts, mimics a browser to download the first image, converts it, and sets it as the featured image.
 * Version:           1.3.0
 * Author:            AniKaizoku
 * Author URI:        https://anikaizoku.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       first-image-featured
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Adds the plugin's admin page to the Tools menu.
 */
function fiaf_add_admin_menu() {
    add_management_page(
        'First Image as Featured',
        'First Image as Featured',
        'manage_options',
        'first-image-as-featured',
        'fiaf_admin_page_html'
    );
}
add_action( 'admin_menu', 'fiaf_add_admin_menu' );

/**
 * Renders the HTML for the admin page.
 */
function fiaf_admin_page_html() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>This tool will scan all your published posts. If a post is missing a featured image, it will find the first image in the post's content, upload it to your media library, and set it as the featured image.</p>
        <p><strong>Note:</strong> This can be a slow process. Please be patient and do not navigate away from this page while it's running.</p>

        <?php
        if ( ! extension_loaded( 'gd' ) || ! function_exists( 'gd_info' ) ) {
            echo '<div class="notice notice-error"><p><strong>Error:</strong> The PHP GD library is not installed or enabled on your server. This plugin requires it for image conversion. Please contact your web host to enable it.</p></div>';
        } else {
        ?>
            <form method="post" action="">
                <?php
                wp_nonce_field( 'fiaf_run_process_nonce', 'fiaf_nonce' );
                ?>
                <input type="hidden" name="fiaf_run_process" value="1">
                <?php submit_button('Scan Posts and Set Featured Images'); ?>
            </form>
        <?php } ?>

        <hr>

        <div id="fiaf-log-wrapper" style="border: 1px solid #ccd0d4; background: #fff; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto;">
            <h2>Log</h2>
            <div class="fiaf-log-content">
                <?php
                if ( isset( $_POST['fiaf_run_process'] ) && check_admin_referer( 'fiaf_run_process_nonce', 'fiaf_nonce' ) ) {
                    fiaf_run_process();
                } else {
                    echo '<p>Ready to begin. Click the button above to start.</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * The core function that processes the posts.
 */
function fiaf_run_process() {
    if ( ! extension_loaded( 'gd' ) || ! function_exists( 'gd_info' ) ) {
        echo '<p style="color: red;"><strong>FATAL ERROR:</strong> GD Library is not available. Cannot process images.</p>';
        return;
    }

    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    $all_posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ) );

    if ( empty( $all_posts ) ) {
        echo '<p style="color: orange;"><strong>No published posts found to process.</strong></p>';
        return;
    }

    echo '<p>Starting process for ' . count( $all_posts ) . ' posts...</p>';

    foreach ( $all_posts as $single_post ) {
        if ( has_post_thumbnail( $single_post->ID ) ) {
            echo '<p><strong>Skipping Post #' . esc_html($single_post->ID) . '</strong> ("' . esc_html($single_post->post_title) . '"): Already has a featured image.</p>';
        } else {
            $content = $single_post->post_content;
            $matches = array();
            preg_match( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches );

            if ( isset( $matches[1] ) && ! empty( $matches[1] ) ) {
                $image_url = $matches[1];

                // Set arguments to mimic a browser request
                $args = array(
                    'timeout'     => 30,
                    'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                );
                
                $response = wp_remote_get( $image_url, $args );
                
                if ( is_wp_error( $response ) ) {
                    echo '<p style="color: red;"><strong>Error for Post #' . esc_html($single_post->ID) . '</strong>: Could not download image. Reason: ' . esc_html($response->get_error_message()) . '</p>';
                } elseif ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
                    echo '<p style="color: red;"><strong>Error for Post #' . esc_html($single_post->ID) . '</strong>: Could not download image. Server responded with code: ' . esc_html(wp_remote_retrieve_response_code( $response )) . '</p>';
                } else {
                    $image_contents = wp_remote_retrieve_body( $response );
                    $image_filename = basename( $image_url );
                    
                    $upload = wp_upload_bits( $image_filename, null, $image_contents );

                    if ( ! empty( $upload['error'] ) ) {
                        echo '<p style="color: red;"><strong>Error for Post #' . esc_html($single_post->ID) . '</strong>: Could not save temporary file. Reason: ' . esc_html($upload['error']) . '</p>';
                    } else {
                        $temp_file = $upload['file'];
                        $file_info = wp_check_filetype(basename($temp_file), null);
                        $mime_type = $file_info['type'];
                        $file_to_upload = $temp_file;
                        $new_filename = basename($image_url);

                        if ( $mime_type !== 'image/jpeg' ) {
                            $image_editor = wp_get_image_editor( $temp_file );
                            if ( ! is_wp_error( $image_editor ) ) {
                                $filename_parts = pathinfo( $new_filename );
                                $new_filename = $filename_parts['filename'] . '.jpg';
                                $saved_image = $image_editor->save( $temp_file . '.jpg', 'image/jpeg' );
                                if ( ! is_wp_error( $saved_image ) && isset( $saved_image['path'] ) ) {
                                    echo '<p style="color: blue;"><em>Info for Post #' . esc_html($single_post->ID) . ': Converted image from ' . esc_html($mime_type) . ' to JPEG.</em></p>';
                                    $file_to_upload = $saved_image['path'];
                                    @unlink($temp_file);
                                } else {
                                    echo '<p style="color: orange;"><strong>Warning for Post #' . esc_html($single_post->ID) . ':</strong> Could not convert image. Will try to upload original.</p>';
                                }
                            }
                        }

                        $file = array( 'name' => $new_filename, 'tmp_name' => $file_to_upload );
                        $attachment_id = media_handle_sideload( $file, $single_post->ID );

                        if ( is_wp_error( $attachment_id ) ) {
                            @unlink( $file_to_upload );
                            echo '<p style="color: red;"><strong>Error for Post #' . esc_html($single_post->ID) . '</strong>: ' . esc_html($attachment_id->get_error_message()) . '</p>';
                        } else {
                            set_post_thumbnail( $single_post->ID, $attachment_id );
                            echo '<p style="color: green;"><strong>Success for Post #' . esc_html($single_post->ID) . '</strong> ("' . esc_html($single_post->post_title) . '"): Featured image set.</p>';
                        }
                    }
                }
            } else {
                echo '<p><strong>Skipping Post #' . esc_html($single_post->ID) . '</strong> ("' . esc_html($single_post->post_title) . '"): No image found in content.</p>';
            }
        }

        echo '<p><i>Pausing for 1 second...</i></p>';
        flush();
        sleep(1);
    }

    echo '<p style="font-weight: bold; color: blue;">Process complete!</p>';
}
