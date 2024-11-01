<?php
    /*
    Plugin Name: WPCREO
    Version: 2.0
    Author: WPCREO
    Author URI: http://www.wpcreo.com
    Description: Transform your WordPress into an iOS, Android and Windows apps without writing a single line of code. 
    
    Network: True
    
    License: GPLv2 or later
    
    Copyright (C) 2014 WPCREO
    */
    
    class WPCREOBroadcast {
    
        // Register actions and filters on class instanation
        function __construct() {
    
            // DEFINE PLUGIN ID
            define('WPCREOPLUGINOPTIONS_ID', 'WPCREO-plugin');

            // DEFINE PLUGIN NICK
            define('WPCREOPLUGINOPTIONS_NICK', 'WPCREO');
    
            /** Plugin Directory URL **/
            define( 'LCIBF_PLUGIN_URL', plugin_dir_path( __FILE__ ) );
    
            // Add metabox action
            //add_action( 'add_meta_boxes', array( &$this, 'register_metabox' ) );
    
            // Add save post action
            add_action( 'publish_post',  array( &$this, 'WPCREOBroadcast_post' ) );
    
    
            if ( is_admin() )
            {
                add_action('admin_init', array(&$this, 'register'));
                add_action('admin_menu', array(&$this, 'menu'));
    
                add_action( 'wp_ajax_syncAllPost', array( &$this, 'syncAllPost') );
            }
    
            //add_filter('the_content', array( &$this, 'content_with_quote'));

            // hook into WP_Http::_dispatch_request()
            //add_filter( 'http_response',  array( &$this, 'wp_log_http_requests'), 10, 3 );
        }

        function wp_log_http_requests( $response, $args, $url ) {
 
	        // set your log file location here
	        $logfile = plugin_dir_path( __FILE__ ) . '/http_requests.log';
 
	        // parse request and response body to a hash for human readable log output
	        $log_response = $response;
	        if ( isset( $args['body'] ) ) {
		        parse_str( $args['body'], $args['body_parsed'] );
	        }
	        if ( isset( $log_response['body'] ) ) {
		        parse_str( $log_response['body'], $log_response['body_parsed'] );
	        }
	        // write into logfile
	        file_put_contents( $logfile, sprintf( "### %s, URL: %s\nREQUEST: %sRESPONSE: %s\n", date( 'c' ), $url, print_r( $args, true ), print_r( $log_response, true ) ), FILE_APPEND );
 
	        return $response;
        }
    
        // WPCREOBroadcast the post
        public function WPCREOBroadcast_post( $post_id ) 
        {
            $url = get_option('magazineUrl');
    
            //error_log("WPCREOBroadcast_post URL: " . $url);
    
            // Check that I am only WPCREOBroadcasting once
            if ( $url != '' /*&& did_action( 'save_post' ) == 1 */) {
    
                // Retrieve the post
                $post = get_post( $post_id, 'ARRAY_A' );
    
                if( ( $post['post_status'] == 'publish' ) && ( $post['post_type'] == 'post' ) ) {	
                    //error_log("WPCREOBroadcast_post STEP 2");
    
                    // List of data to keep in WPCREOBroadcasted posts
                    $post_data = array(
                                    'ID', 
                                    'post_author',
                                    'post_date',
                                    'post_date_gmt',
                                    'post_content',
                                    'post_title',
                                    'post_excerpt',
                                    'post_status',
                                    'comment_status',
                                    'ping_status',
                                    'post_password',
                                    'post_name',
                                    'post_modified',
                                    'post_modified_gmt',
                                    'post_type'
                                );
    
                    // Create a new post array
                    foreach ( $post_data as $key )
                        $new_post[$key] = $post[$key];
    
                    $new_post['post_linkurl'] = get_permalink($post_id);							
    
                    if (has_post_thumbnail( $post_id )){
                        $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'single-post-thumbnail' );
                        $new_post['post_image'] = $image[0];
                    }
    
                    $categories = get_the_category($post_id);
                    if($categories){
                        $new_post['post_categories'] = json_encode($categories);
                    }
    
                    $new_post['site_language'] = get_bloginfo('language');
    
                    //error_log("WPCREOBroadcast_post Before Post");
                    error_log('WPCREO POST ARTICLE: ' . $post['ID'] .  ' => ' . $post['post_title']);

                    $response = wp_remote_post( $url, array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array(),
                        'body' => $new_post,
                        'cookies' => array()
                        )
                    );
    
                    if ( is_wp_error( $response ) ) {
                        $error_message = $response->get_error_message();
                        error_log("WPCREOBroadcast_post After Post error: " . $error_message);
    
                    } 
                    //else {
                    //    error_log("WPCREOBroadcast_post Response : " . $response );
                    //}
                }
            }
        }
    
       
        /** function/method
        * Usage: hooking the plugin options/settings
        * Arg(0): null
        * Return: void
        */
        public function register()
        {
            register_setting(WPCREOPLUGINOPTIONS_ID.'_options', 'magazineUrl');
        }
    
        function validate_setting($input) { 
             //wp_die('ssss');
             //wp_die( __('You do not have sufficient permissions to access this page.'  .  $input['hSyncMode'] ) );
        }	
    
        /** function/method
        * Usage: hooking (registering) the plugin menu
        * Arg(0): null
        * Return: void
        */
        public function menu()
        {
            // Create menu tab
            add_options_page(WPCREOPLUGINOPTIONS_NICK.' Plugin Options', WPCREOPLUGINOPTIONS_NICK, 'manage_options', WPCREOPLUGINOPTIONS_ID.'_options', array( &$this, 'options_page'));
        }
        /** function/method
        * Usage: show options/settings form page
        * Arg(0): null
        * Return: void
        */
        public function options_page()
        { 
            if (!current_user_can('manage_options')) 
            {
                wp_die( __('You do not have sufficient permissions to access this page.') );
            }
    
            $plugin_id = WPCREOPLUGINOPTIONS_ID;
            // display options page
            include( LCIBF_PLUGIN_URL . 'options.php' );
            //require_once( LCIBF_PLUGIN_URL . 'options.php' );
        }
        /** function/method
        * Usage: filtering the content
        * Arg(1): string
        * Return: string
        */
        public function content_with_quote($content)
        {
            $quote = '<p><blockquote>' . get_option('magazineUrl') . '</blockquote></p>';
            return $content . $quote;
        }
    
    
        public function syncAllPost(){
    
            $args = array( 'numberposts' => -1); 
            $posts= get_posts( $args );
            if ($posts) {
    
                try{
                    foreach ( $posts as $p ) {
                        //error_log('ASYNC POSTS ARTICLE: ' . $p->ID .  ' => ' . $p->post_title);
                        $this->WPCREOBroadcast_post($p->ID);
                    }
                } catch (Exception $e) {
                  error_log('  ERROR ASYNC POSTS ARTICLE ==> ' . $e->getMessage());  
                }
            }
    
            echo "Done!";
    
            die(); // this is required to terminate immediately and return a proper response
        }
    }
    
    // Instanate WPCREOBroadcast class	
    $WPCREOBroadcast = new WPCREOBroadcast();
?>