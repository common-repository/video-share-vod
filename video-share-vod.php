<?php
/*
Plugin Name: Video Share VOD - Turnkey Video Site Builder Script
Plugin URI: https://videosharevod.com
Description: <strong>Video Share / Video on Demand (VOD) - Turnkey Video Site Builder Script</strong> plugin enables users to share videos and others to watch on demand. Allows publishing archived VideoWhisper Live Streaming broadcasts and recorded videochat streams.  <a href='https://videowhisper.com/tickets_submit.php?topic=Video-Share-VOD'>Contact Support</a>
Version: 2.6.30
Author: VideoWhisper.com
Author URI: https://videowhisper.com/
Contributors: videowhisper, VideoWhisper.com
Text Domain: video-share-vod
Domain Path: /languages/
Requires PHP: 7.4
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . '/inc/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . '/inc/options.php';

use VideoWhisper\VideoShareVOD;

if ( ! class_exists( 'VWvideoShare' ) ) {
	class VWvideoShare {


		use VideoWhisper\VideoShareVOD\Shortcodes;
		use VideoWhisper\VideoShareVOD\Options;


		public function __construct() {         }


		public function VWvideoShare() {
			// constructor
			self::__construct();

		}


		static function install() {
			// do not generate any output here
			self::setupOptions();
			self::video_post();
			flush_rewrite_rules();
		}


		static function init() {
			self::video_post();
			self::register_widgets();

		}

		// ! Supported extensions
		static function extensions_video() {
			return array( '3gp', '3g2', 'avi', 'f4v', 'flv', 'm2v', 'm4p', 'm4v', 'mp2', 'mkv', 'mov', 'mp4', 'mpg', 'mpe', 'mpeg', 'mpv', 'mwv', 'ogv', 'ogg', 'rm', 'rmvb', 'svi', 'ts', 'qt', 'vob', 'webm', 'wmv' );
		}


		static function extensions_import() {
			return array( 'flv', 'mp4', 'f4v', 'm4v', 'webm', 'ogg' );
		}


		// Register Custom Post Type
		static function video_post() {

			$options = get_option( 'VWvideoShareOptions' );

			// only if missing
			if ( post_type_exists( $options['custom_post'] ) ) {
				return;
			}

			$labels = array(
				'name'                     => _x( 'Videos', 'Post Type General Name', 'video-share-vod' ),
				'singular_name'            => _x( 'Video', 'Post Type Singular Name', 'video-share-vod' ),
				'menu_name'                => __( 'Videos', 'video-share-vod' ),
				'parent_item_colon'        => __( 'Parent Video:', 'video-share-vod' ),
				'all_items'                => __( 'All Videos', 'video-share-vod' ),
				'view_item'                => __( 'View Video', 'video-share-vod' ),
				'add_new_item'             => __( 'Add New Video', 'video-share-vod' ),
				'add_new'                  => __( 'New Video', 'video-share-vod' ),
				'edit_item'                => __( 'Edit Video', 'video-share-vod' ),
				'update_item'              => __( 'Update Video', 'video-share-vod' ),
				'search_items'             => __( 'Search Videos', 'video-share-vod' ),
				'not_found'                => __( 'No Videos found', 'video-share-vod' ),
				'not_found_in_trash'       => __( 'No Videos found in Trash', 'video-share-vod' ),

				// BuddyPress Activity
				'bp_activity_admin_filter' => __( 'New video published', 'video-share-vod' ),
				'bp_activity_front_filter' => __( 'Videos', 'video-share-vod' ),
				'bp_activity_new_post'     => __( '%1$s posted a new <a href="%2$s">video</a>', 'video-share-vod' ),
				'bp_activity_new_post_ms'  => __( '%1$s posted a new <a href="%2$s">video</a>, on the site %3$s', 'video-share-vod' ),

			);

			$args = array(
				'label'               => __( 'video', 'video-share-vod' ),
				'description'         => __( 'Video Videos', 'video-share-vod' ),
				'labels'              => $labels,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields', 'page-attributes' ), //, 'buddypress-activity'
				'taxonomies'          => array( 'category', 'post_tag' ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'menu_position'       => 5,
				'can_export'          => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'map_meta_cap'        => true,
				'menu_icon'           => 'dashicons-video-alt3',
				'capability_type'     => 'post',
				'capabilities'        => array(
					'create_posts' => false,
				),

			);

			// BuddyPress Activity
			if ( function_exists( 'bp_is_active' ) && ( $options['bpActivityPost'] ?? false ) ) {
				if ( bp_is_active( 'activity' ) ) {
					$args['bp_activity'] = array(
						'component_id' => buddypress()->activity->id,
						'action_id'    => 'new_video',
						'contexts'     => array( 'activity', 'member' ),
						'position'     => 40,
					);
				}
			}

			register_post_type( $options['custom_post'], $args );

			// Add new taxonomy, make it hierarchical (like categories)
			$labels = array(
				'name'              => _x( 'Playlists', 'taxonomy general name' ),
				'singular_name'     => _x( 'Playlist', 'taxonomy singular name' ),
				'search_items'      => __( 'Search Playlists', 'video-share-vod' ),
				'all_items'         => __( 'All Playlists', 'video-share-vod' ),
				'parent_item'       => __( 'Parent Playlist', 'video-share-vod' ),
				'parent_item_colon' => __( 'Parent Playlist:', 'video-share-vod' ),
				'edit_item'         => __( 'Edit Playlist', 'video-share-vod' ),
				'update_item'       => __( 'Update Playlist', 'video-share-vod' ),
				'add_new_item'      => __( 'Add New Playlist', 'video-share-vod' ),
				'new_item_name'     => __( 'New Playlist Name', 'video-share-vod' ),
				'menu_name'         => __( 'Playlists', 'video-share-vod' ),
			);

			$args = array(
				'hierarchical'          => true,
				'labels'                => $labels,
				'show_ui'               => true,
				'show_admin_column'     => true,
				'update_count_callback' => '_update_post_term_count',
				'query_var'             => true,
				'rewrite'               => array( 'slug' => $options['custom_taxonomy'] ),
			);
			register_taxonomy( $options['custom_taxonomy'], array( $options['custom_post'] ), $args );

			if ( $options['tvshows'] ) {
				$labels = array(
					'name'               => _x( 'TV Shows', 'Post Type General Name', 'video-share-vod' ),
					'singular_name'      => _x( 'TV Show', 'Post Type Singular Name', 'video-share-vod' ),
					'menu_name'          => __( 'TV Shows', 'video-share-vod' ),
					'parent_item_colon'  => __( 'Parent TV Show:', 'video-share-vod' ),
					'all_items'          => __( 'All TV Shows', 'video-share-vod' ),
					'view_item'          => __( 'View TV Show', 'video-share-vod' ),
					'add_new_item'       => __( 'Add New TV Show', 'video-share-vod' ),
					'add_new'            => __( 'New TV Show', 'video-share-vod' ),
					'edit_item'          => __( 'Edit TV Show', 'video-share-vod' ),
					'update_item'        => __( 'Update TV Show', 'video-share-vod' ),
					'search_items'       => __( 'Search TV Show', 'video-share-vod' ),
					'not_found'          => __( 'No TV Shows found', 'video-share-vod' ),
					'not_found_in_trash' => __( 'No TV Shows found in Trash', 'video-share-vod' ),
				);

				$args = array(
					'label'               => __( 'TV show', 'video-share-vod' ),
					'description'         => __( 'TV Shows', 'video-share-vod' ),
					'labels'              => $labels,
					'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields', 'page-attributes' ),
					'taxonomies'          => array( 'category', 'post_tag' ),
					'hierarchical'        => false,
					'public'              => true,
					'show_ui'             => true,
					'show_in_menu'        => true,
					'show_in_nav_menus'   => true,
					'show_in_admin_bar'   => true,
					'menu_position'       => 5,
					'can_export'          => true,
					'has_archive'         => true,
					'exclude_from_search' => false,
					'publicly_queryable'  => true,
					'menu_icon'           => 'dashicons-format-video',
					'capability_type'     => 'post',
				);
				register_post_type( $options['tvshows_slug'], $args );

			}

			// extra rules
			add_rewrite_rule( 'crossdomain.xml$', 'index.php?vsv_crossdomain=1', 'top' );

			// without index.php use $1 instead of $matches[1]
			add_rewrite_rule( '^mbr\/([a-z\-]+)\/([0-9]+)\.([0-9a-z]+)$', 'wp-admin/admin-ajax.php?action=vwvs_mbr&protocol=$1&type=$3&id=$2', 'top' );

		}


		static function query_vars( $query_vars ) {

			// array of recognized query vars
			$query_vars[] = 'vsv_crossdomain';
			/*
			$query_vars[] = 'protocol';
			$query_vars[] = 'id';
			$query_vars[] = 'type';
			$query_vars[] = 'action';
			*/
			return $query_vars;
		}


		static function parse_request( &$wp ) {
			if ( array_key_exists( 'vsv_crossdomain', $wp->query_vars ) ) {
				$options = get_option( 'VWvideoShareOptions' );
				echo esc_xml( html_entity_decode( stripslashes( $options['crossdomain_xml'] ) ) );
				exit();
			}
		}


		static function cleanVideo( $post_id, $clean = 'source', $confirm = 0, $options = null ) {
			// cleans certain files for video (source, hls)

			if ( ! $options ) {
				$options = get_option( 'VWvideoShareOptions' );
			}
			if ( get_post_type( $post_id ) != $options['custom_post'] ) {
				return 0;
			}

			switch ( $clean ) {
				case 'source':
					// delete source video
					$videoPath = get_post_meta( $post_id, 'video-source-file', true );

					if ( ! file_exists( $videoPath ) ) {
						return 0;
					}

					$space += filesize( $videoPath );

					if ( $confirm ) {
						unlink( $videoPath );
					}

					break;

				case 'hls':
					// delete all generated video files
					$videoAdaptive = get_post_meta( $post_id, 'video-adaptive', true );
					if ( $videoAdaptive ) {
						$videoAlts = $videoAdaptive;
					} else {
						$videoAlts = array();
					}

					foreach ( $videoAlts as $alt ) {

						// clean segmentation
						if ( $alt['hls'] ) {
							if ( strstr( $alt['hls'], $options['uploadsPath'] ) ) {
								$space += self::sizeTree( $alt['hls'] );
								if ( $confirm ) {
									if ( is_dir( $alt['hls'] ) ) {
										self::delTree( $alt['hls'] );
									}
								}
							}
						}
					}

					break;

				case 'logs':
					// delete all generated video files
					$videoAdaptive = get_post_meta( $post_id, 'video-adaptive', true );
					if ( $videoAdaptive ) {
						$videoAlts = $videoAdaptive;
					} else {
						$videoAlts = array();
					}

					foreach ( $videoAlts as $alt ) {

						$logpath                                 = dirname( $alt['file'] );
						$log                                     = $logpath . '/' . $post_id . '-' . $alt['id'] . '.txt';
						$logc                                    = $logpath . '/' . $post_id . '-' . $alt['id'] . '-cmd.txt';
						$spaceStatistics[ $alt['id'] . '_logs' ] = 0;
						if ( file_exists( $log ) ) {
							$space += filesize( $log );
						}
						if ( file_exists( $logc ) ) {
							$space += filesize( $logc );
						}

						if ( $confirm ) {
							unlink( $log );
						}
						if ( $confirm ) {
							unlink( $logc );
						}
					}

					break;
			}

			// recalculate video space
			self::spaceVideo( $post_id );

			return $space;

		}


		static function delTree( $dir ) {
			$files = array_diff( scandir( $dir ), array( '.', '..' ) );
			foreach ( $files as $file ) {
				( is_dir( "$dir/$file" ) ) ? self::delTree( "$dir/$file" ) : unlink( "$dir/$file" );
			}
			return rmdir( $dir );
		}


		static function video_delete( $video_id ) {
			$options = get_option( 'VWvideoShareOptions' );
			if ( get_post_type( $video_id ) != $options['custom_post'] ) {
				return;
			}

			// delete source video
			$videoPath = get_post_meta( $video_id, 'video-source-file', true );
			if ( file_exists( $videoPath ) ) {
				unlink( $videoPath );
			}

			// delete all generated video files
			$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
			if ( $videoAdaptive ) {
				$videoAlts = $videoAdaptive;
			} else {
				$videoAlts = array();
			}

			foreach ( $videoAlts as $alt ) {
				if ( file_exists( $alt['file'] ) ) {
					unlink( $alt['file'] );
				}

				// clean segmentation
				if ( $alt['hls'] ) {
					if ( strstr( $alt['hls'], $options['uploadsPath'] ) ) {
						if ( file_exists( $alt['hls'] ) ) {
							$files = glob( $alt['hls'] . '/*' ); // get all file names
							foreach ( $files as $file ) { // iterate files
								if ( is_file( $file ) ) {
									unlink( $file ); // delete file
								}
							}
						}

						if ( is_dir( $alt['hls'] ) ) {
							self::delTree( $alt['hls'] );
						} else {
							unlink( $alt['hls'] );
						}
					}
				}
			}

		}


		// ! cron

		static function cron_schedules( $schedules ) {
			$schedules['min4'] = array(
				'interval' => 240,
				'display'  => __( 'Once every four minutes' ),
			);
			return $schedules;
		}


		static function setup_schedule() {
			if ( ! wp_next_scheduled( 'cron_4min_event' ) ) {
				wp_schedule_event( time(), 'min4', 'cron_4min_event' );
			}

		}




		static function wp_get_attachment_url( $url ) {
					// fixes url for attachments in video uploads folder

			$upload_dir  = wp_upload_dir();
			$uploads_url = self::path2url( $upload_dir['basedir'] );

			$relPath = substr( $url, strlen( $uploads_url ) );

			if ( @file_exists( $relPath ) ) {
				return self::path2url( $relPath );
			}

			return $url;
		}

		static function wp_get_attachment_image_src( $image ) {
			// fixes url for images in video uploads folder

			if (!$image) return $image;
			
			$upload_dir  = wp_upload_dir();
			$uploads_url = self::path2url( $upload_dir['basedir'] );

			$iurl    = $image[0] ?? '' ;
			$relPath = substr( $iurl, strlen( $uploads_url ) );

			if ( @file_exists( $relPath ) ) {
				$rurl     = self::path2url( $relPath );
				$image[0] = $rurl;
			}

			return $image;
		}


		static function plugins_loaded() {
			 $options = get_option( 'VWvideoShareOptions' );

			// translations
			load_plugin_textdomain( 'video-share-vod', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			add_filter( 'wp_get_attachment_image_src', array( 'VWvideoShare', 'wp_get_attachment_image_src' ) );
			add_filter( 'wp_get_attachment_url', array( 'VWvideoShare', 'wp_get_attachment_url' ) );

			add_action( 'wp_enqueue_scripts', array( 'VWvideoShare', 'scripts' ) );

			// prevent wp from adding <p> that breaks JS
			remove_filter( 'the_content', 'wpautop' );

			// move wpautop filter to BEFORE shortcode is processed
			add_filter( 'the_content', 'wpautop', 1 );

			// then clean AFTER shortcode
			add_filter( 'the_content', 'shortcode_unautop', 100 );

			/* Fire our meta box setup function on the post editor screen. */
			add_action( 'load-post.php', array( 'VWvideoShare', 'post_meta_boxes_setup' ) );
			add_action( 'load-post-new.php', array( 'VWvideoShare', 'post_meta_boxes_setup' ) );

			// admin listings
			add_filter( 'pre_get_posts', array( 'VWvideoShare', 'pre_get_posts' ) );

			add_filter( 'manage_' . $options['custom_post'] . '_posts_columns', array( 'VWvideoShare', 'columns_head_video' ), 10 );
			add_filter( 'manage_edit-' . $options['custom_post'] . '_sortable_columns', array( 'VWvideoShare', 'columns_register_sortable' ) );
			add_filter( 'request', array( 'VWvideoShare', 'duration_column_orderby' ) );
			add_action( 'manage_' . $options['custom_post'] . '_posts_custom_column', array( 'VWvideoShare', 'columns_content_video' ), 10, 2 );

			add_action( 'admin_head', array( 'VWvideoShare', 'admin_head' ) );

			add_filter( 'parse_query', array( 'VWvideoShare', 'parse_query' ) );

			add_action( 'before_delete_post', array( 'VWvideoShare', 'video_delete' ) );

			// add_filter( 'category_description', 'category_description' );

			// video post page
			add_filter( 'the_content', array( 'VWvideoShare', 'the_content' ) );
			// add_filter( "the_content", array('VWvideoShare','playlist_page'));

			if ( class_exists( 'VWliveStreaming' ) ) {
				if ( $options['vwls_channel'] ) {
					add_filter( 'the_content', array( 'VWvideoShare', 'channel_page' ) );
				}
			}

				add_filter( 'the_content', array( 'VWvideoShare', 'tvshow_page' ) );

			// ! shortcodes

			add_shortcode( 'videowhisper_plupload', array( 'VWvideoShare', 'videowhisper_plupload' ) );

			add_shortcode( 'videowhisper_player', array( 'VWvideoShare', 'videowhisper_player' ) );
			add_shortcode( 'videowhisper_videos', array( 'VWvideoShare', 'videowhisper_videos' ) );
			add_shortcode( 'videowhisper_upload', array( 'VWvideoShare', 'videowhisper_upload' ) );
			add_shortcode( 'videowhisper_preview', array( 'VWvideoShare', 'shortcode_preview' ) );
			add_shortcode( 'videowhisper_player_html', array( 'VWvideoShare', 'videowhisper_player_html' ) );
			add_shortcode( 'videowhisper_import', array( 'VWvideoShare', 'videowhisper_import' ) );
			add_shortcode( 'videowhisper_playlist', array( 'VWvideoShare', 'shortcode_playlist' ) );

			add_shortcode( 'videowhisper_embed_code', array( 'VWvideoShare', 'shortcode_embed_code' ) );

			add_shortcode( 'videowhisper_postvideos', array( 'VWvideoShare', 'videowhisper_postvideos' ) );
			add_shortcode( 'videowhisper_postvideos_process', array( 'VWvideoShare', 'videowhisper_postvideos_process' ) );

			add_shortcode( 'videowhisper_postvideo_assign', array( 'VWvideoShare', 'videowhisper_postvideo_assign' ) );

			add_shortcode( 'videowhisper_embed', array( 'VWvideoShare', 'videowhisper_embed' ) );

			// ! ajax
			// ajax videos
			add_action( 'wp_ajax_vwvs_videos', array( 'VWvideoShare', 'vwvs_videos' ) );
			add_action( 'wp_ajax_nopriv_vwvs_videos', array( 'VWvideoShare', 'vwvs_videos' ) );

			// ajax tools
			add_action( 'wp_ajax_vwvs_playlist_m3u', array( 'VWvideoShare', 'vwvs_playlist_m3u' ) );
			add_action( 'wp_ajax_nopriv_vwvs_playlist_m3u', array( 'VWvideoShare', 'vwvs_playlist_m3u' ) );

			add_action( 'wp_ajax_vwvs_embed', array( 'VWvideoShare', 'vwvs_embed' ) );
			add_action( 'wp_ajax_nopriv_vwvs_embed', array( 'VWvideoShare', 'vwvs_embed' ) );

			add_action( 'wp_ajax_vwvs_mbr', array( 'VWvideoShare', 'vwvs_mbr' ) );
			add_action( 'wp_ajax_nopriv_vwvs_mbr', array( 'VWvideoShare', 'vwvs_mbr' ) );

			add_filter( 'query_vars', array( 'VWvideoShare', 'query_vars' ) );

			// upload videos
			add_action( 'wp_ajax_vwvs_upload', array( 'VWvideoShare', 'vwvs_upload' ) );
			add_action( 'wp_ajax_vwvs_plupload', array( 'VWvideoShare', 'vwvs_plupload' ) );

			// disable X-Frame-Options: SAMEORIGIN
			if ( $options['disableXOrigin'] ) {
				if ( ! $options['disableXOriginRef'] || substr( $_SERVER['HTTP_REFERER'], 0, strlen( $options['disableXOriginRef'] ) ) === $options['disableXOriginRef'] ) {
					remove_action( 'admin_init', 'send_frame_options_header' );
				}
			}

			// Live Streaming support
			if ( class_exists( 'VWliveStreaming' ) ) {
				if ( $options['vwls_playlist'] ) {
					add_filter( 'vw_ls_manage_channel', array( 'VWvideoShare', 'vw_ls_manage_channel' ), 10, 2 );
					add_filter( 'vw_ls_manage_channels_head', array( 'VWvideoShare', 'vw_ls_manage_channels_head' ) );
				}
			}

			// check db and update if necessary
			/*
			$vw_db_version = "0.0";

			$installed_ver = get_option( "vwvs_db_version" );
			if( $installed_ver != $vw_db_version )
			{
				$tab_formats = $wpdb->prefix . "vwvs_formats";
				$tab_process = $wpdb->prefix . "vwvs_process";

				global $wpdb;
				$wpdb->flush();
				$sql = "";

				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
				if (!$installed_ver) add_option("vwvs_db_version", $vw_db_version);
				else update_option( "vwvs_db_version", $vw_db_version );
			}
			*/

		}


		static function archive_template( $archive_template ) {
			global $post;

			$options = get_option( 'VWvideoShareOptions' );

			if ( get_query_var( 'taxonomy' ) != $options['custom_taxonomy'] ) {
				return $archive_template;
			}

			if ( $options['playlistTemplate'] == '+plugin' ) {
				$archive_template_new = dirname( __FILE__ ) . '/taxonomy-playlist.php';
				if ( file_exists( $archive_template_new ) ) {
					return $archive_template_new;
				}
			}

			$archive_template_new = get_template_directory() . '/' . $options['playlistTemplate'];
			if ( file_exists( $archive_template_new ) ) {
				return $archive_template_new;
			} else {
				return $archive_template;
			}
		}


		/*
		static function category_description( $desc, $cat_id )
		{
			  $desc = 'Description: ' . $desc;
			  return $desc;
		}
		*/

		/*
		static function playlist_page($content)
		{
			if (!is_post_type_archive('playlist')) return $content;

			$addCode = 'Playlist [videowhisper_playlist videos=""]' . post_type_archive_title();

			return $addCode . $content;
		}
		*/

		// ! Widgets

		static function register_widgets() {
			$prefix = 'videowhisper-videos'; // $id prefix
			$name   = __( 'VSV Videos' );

			$widget_ops  = array(
				'classname'   => 'widget_videowhisper_videos',
				'description' => __( 'List videos and updates using AJAX.' ),
			);
			$control_ops = array(
				'width'   => 200,
				'height'  => 200,
				'id_base' => $prefix,
			);

			$options = get_option( 'widget_videowhisper_videos' );
			if ( isset( $options[0] ) ) {
				unset( $options[0] );
			}

			if ( ! empty( $options ) ) {
				foreach ( array_keys( $options ) as $widget_number ) {
					wp_register_sidebar_widget( $prefix . '-' . $widget_number, $name, array( 'VWvideoShare', 'widget_videowhisper_videos' ), $widget_ops, array( 'number' => $widget_number ) );
					wp_register_widget_control( $prefix . '-' . $widget_number, $name, array( 'VWvideoShare', 'widget_videowhisper_videos_control' ), $control_ops, array( 'number' => $widget_number ) );
				}
			} else {
				$options       = array();
				$widget_number = 1;
				wp_register_sidebar_widget( $prefix . '-' . $widget_number, $name, array( 'VWvideoShare', 'widget_videowhisper_videos' ), $widget_ops, array( 'number' => $widget_number ) );
				wp_register_widget_control( $prefix . '-' . $widget_number, $name, array( 'VWvideoShare', 'widget_videowhisper_videos_control' ), $control_ops, array( 'number' => $widget_number ) );
			}

			// ! widgets
			// wp_register_sidebar_widget( 'videowhisper_videos', 'Videos',  array( 'VWvideoShare', 'widget_videos'), array('description' => 'List videos and updates using AJAX.') );
			// wp_register_widget_control( 'videowhisper_videos', 'videowhisper_videos', array( 'VWvideoShare', 'widget_videos_options') );
		}


		static function widgetDefaultOptions() {
			return array(
				'title'           => 'Videos',
				'perpage'         => '6',
				'perrow'          => '6',
				'playlist'        => '',
				'order_by'        => '',
				'category_id'     => '',
				'select_category' => '1',
				'select_order'    => '1',
				'select_tags'     => '1',
				'select_name'     => '1',
				'select_page'     => '1',
				'list_id'         => '',
				'include_css'     => '0',
			);

		}


		static function widget_videowhisper_videos_control( $args = array(), $params = array() ) {

			$optionsPlugin = get_option( 'VWvideoShareOptions' );

			$prefix = 'videowhisper-videos'; // $id prefix

			$optionsAll = get_option( 'widget_videowhisper_videos' );
			if ( empty( $optionsAll ) ) {
				$optionsAll = array();
			}
			if ( isset( $optionsAll[0] ) ) {
				unset( $optionsAll[0] );
			}

			// update options array
			if ( ! empty( $_POST[ $prefix ] ) && is_array( $_POST ) ) {
				foreach ( $_POST[ $prefix ] as $widget_number => $values ) {
					if ( empty( $values ) && isset( $optionsAll[ $widget_number ] ) ) { // user clicked cancel
						continue;
					}

					if ( ! isset( $optionsAll[ $widget_number ] ) && $args['number'] == -1 ) {
						$args['number']            = $widget_number;
						$optionsAll['last_number'] = $widget_number;
					}
					$optionsAll[ $widget_number ] = $values;
				}

				// update number
				if ( $args['number'] == -1 && ! empty( $optionsAll['last_number'] ) ) {
					$args['number'] = $optionsAll['last_number'];
				}

				if ( ! array_key_exists( $args['number'], $optionsAll ) ) {
					$optionsAll[ $args['number'] ] = self::widgetDefaultOptions();
				}

				// clear unused options and update options in DB. return actual options array
				$optionsAll = self::multiwidget_update( $prefix, $optionsAll, $_POST[ $prefix ], $_POST['sidebar'], 'widget_videowhisper_videos' );
			}

			// $number - is dynamic number for multi widget, gived by WP
			// by default $number = -1 (if no widgets activated). In this case we should use %i% for inputs
			// to allow WP generate number automatically
			$number = ( $args['number'] == -1 ) ? '%i%' : $args['number'];

			// use if exists or defaults
			if ( array_key_exists( $number, $optionsAll ) ) {
				$options = $optionsAll[ $number ];
			} else {
				$options = self::widgetDefaultOptions();
			}

			// list_id used for JS
			$options['list_id'] = intval( $options['list_id'] );
			if ( ! $options['list_id'] ) {
				$options['list_id'] = $number;
			}
			if ( ! $options['list_id'] ) {
				$options['list_id'] = random_int( 100, 999 );
			}

			/*
			$options = VWvideoShare::widgetSetupOptions();

			$options['list_id'] = intval($options['list_id']);
			if (!$options['list_id']) $options['list_id'] = random_int( 100, 999);

			if (isset($_POST))
			{
				foreach ($options as $key => $value)
					if (isset($_POST[$key])) $options[$key] = trim($_POST[$key]);
					update_option('VWvideoShareWidgetOptions', $options);
			}
			*/

			?>

			<?php _e( 'Title', 'video-share-vod' ); ?>:<br />
	<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][title]" value="<?php echo esc_attr( stripslashes( $options['title'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Playlist', 'video-share-vod' ); ?>:<br />
	<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][playlist]" value="<?php echo esc_attr( stripslashes( $options['playlist'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Category ID', 'video-share-vod' ); ?>:<br />
	<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][category_id]" value="<?php echo esc_attr( stripslashes( $options['category_id'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Order By', 'video-share-vod' ); ?>:<br />
	<select name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][order_by]" id="order_by">
  <option value="post_date" <?php echo $options['order_by'] == 'post_date' ? 'selected' : ''; ?>><?php _e( 'Video Date', 'video-share-vod' ); ?></option>
	<option value="video-views" <?php echo $options['order_by'] == 'video-views' ? 'selected' : ''; ?>><?php _e( 'Views', 'video-share-vod' ); ?></option>
	<option value="video-lastview" <?php echo $options['order_by'] == 'video-lastview' ? 'selected' : ''; ?>><?php _e( 'Recently Watched', 'video-share-vod' ); ?></option>
			<?php
			if ( $optionsPlugin['rateStarReview'] ) {
				echo '<option value="rateStarReview_rating"' . ( $options['order_by'] == 'rateStarReview_rating' ? ' selected' : '' ) . '>' . __( 'Rating', 'video-share-vod' ) . '</option>';
				echo '<option value="rateStarReview_ratingNumber"' . ( $options['order_by'] == 'rateStarReview_ratingNumber' ? ' selected' : '' ) . '>' . __( 'Most Rated', 'video-share-vod' ) . '</option>';
				echo '<option value="rateStarReview_ratingPoints"' . ( $options['order_by'] == 'rateStarReview_ratingPoints' ? ' selected' : '' ) . '>' . __( 'Rate Popularity', 'video-share-vod' ) . '</option>';
			}
			?>
	<option value="rand" <?php echo $options['order_by'] == 'rand' ? 'selected' : ''; ?>><?php _e( 'Random', 'video-share-vod' ); ?></option>
</select><br /><br />

			<?php _e( 'Videos per Page', 'video-share-vod' ); ?>:<br />
	<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][perpage]" value="<?php echo esc_attr( stripslashes( $options['perpage'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Videos per Row', 'video-share-vod' ); ?>:<br />
	<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][perrow]" value="<?php echo esc_attr( stripslashes( $options['perrow'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Category Selector', 'video-share-vod' ); ?>:<br />
	<select name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][select_category]" id="select_category">
  <option value="1" <?php echo $options['select_category'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['select_category'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />

			<?php _e( 'Tags Selector', 'video-share-vod' ); ?>:<br />
	<select name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][select_tags]" id="select_order">
  <option value="1" <?php echo $options['select_tags'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['select_tags'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />

			<?php _e( 'Name Selector', 'video-share-vod' ); ?>:<br />
	<select name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][select_name]" id="select_name">
  <option value="1" <?php echo $options['select_name'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['select_name'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />

			<?php _e( 'Order Selector', 'video-share-vod' ); ?>:<br />
	<select name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][select_order]" id="select_order">
  <option value="1" <?php echo $options['select_order'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['select_order'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />

			<?php _e( 'Page Selector', 'video-share-vod' ); ?>:<br />
	<select name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][select_page]" id="select_page">
  <option value="1" <?php echo $options['select_page'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['select_page'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />

			<?php _e( 'Unique List ID', 'video-share-vod' ); ?>:<br />
	<input type="text" class="widefat" name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][list_id]" id="list_id" value="<?php echo esc_attr( stripslashes( $options['list_id'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Include CSS', 'video-share-vod' ); ?>:<br />
	<select name="<?php echo esc_attr( $prefix ); ?>[<?php echo esc_attr( $number ); ?>][include_css]" id="include_css">
  <option value="1" <?php echo $options['include_css'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['include_css'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />
			<?php
		}


		static function widget_videowhisper_videos( $args = array(), $params = array() ) {

			extract( $args );

			// get widget saved options
			$widget_number = (int) str_replace( 'videowhisper-videos-', '', @$widget_id );

			$optionsAll = get_option( 'widget_videowhisper_videos' );
			if ( ! empty( $optionsAll[ $widget_number ] ) ) {
				$options = $optionsAll[ $widget_number ];
			}

			// $options = get_option('VWvideoShareWidgetOptions');
			echo wp_kses_post( stripslashes( $args['before_widget'] ) );
			echo wp_kses_post( stripslashes( $args['before_title'] ) );
			echo esc_html( stripslashes( $options['title'] ) );
			echo wp_kses_post( stripslashes( $args['after_title'] ) );

			echo do_shortcode( '[videowhisper_videos menu="0" playlist="' . esc_attr( $options['playlist'] ) . '" category_id="' . esc_attr( $options['category_id'] ) . '" order_by="' . esc_attr( $options['order_by'] ) . '" perpage="' . esc_attr( $options['perpage'] ) . '" perrow="' . esc_attr( $options['perrow'] ) . '" select_category="' . esc_attr( $options['select_category'] ) . '" select_tags="' . esc_attr( $options['select_tags'] ) . '" select_name="' . esc_attr( $options['select_name'] ) . '" select_order="' . esc_attr( $options['select_order'] ) . '" select_page="' . esc_attr( $options['select_page'] ) . '" include_css="' . esc_attr( $options['include_css'] ) . '" id="' . esc_attr( $options['list_id'] ) . ']' );

			echo stripslashes( $args['after_widget'] );
		}


		static function multiwidget_update( $id_prefix, $options, $post, $sidebar, $option_name = '' ) {
			global $wp_registered_widgets;
			static $updated = false;

			// get active sidebar
			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset( $sidebars_widgets[ $sidebar ] ) ) {
				$this_sidebar =& $sidebars_widgets[ $sidebar ];
			} else {
				$this_sidebar = array();
			}

			// search unused options
			foreach ( $this_sidebar as $_widget_id ) {
				if ( preg_match( '/' . $id_prefix . '-([0-9]+)/i', $_widget_id, $match ) ) {
					$widget_number = $match[1];

					// $_POST['widget-id'] contain current widgets set for current sidebar
					// $this_sidebar is not updated yet, so we can determine which was deleted
					if ( ! in_array( $match[0], $_POST['widget-id'] ) ) {
						unset( $options[ $widget_number ] );
					}
				}
			}

			// update database
			if ( ! empty( $option_name ) ) {
				update_option( $option_name, $options );
				$updated = true;
			}

			// return updated array
			return $options;
		}


		// ! Post Listings
		public static function pre_get_posts( $query ) {
			/*
			//add channels to post listings
			if(is_category() || is_tag() || is_archive())
			{

				if (is_admin()) return $query;

				$query_type = get_query_var('post_type');

				if ($query_type)
				{
					if (!is_array($query_type)) $query_type = array($query_type);

					if (is_array($query_type))
						if (in_array('post', $query_type) && !in_array( $options['custom_post'], $query_type))
							$query_type[] =  $options['custom_post'];

				}
				else  //default
					{
					$query_type = array('post',  $options['custom_post']);
				}

				$query->set('post_type', $query_type);
			}
			*/
			return $query;
		}


		// ! AJAX implementation

		static function scripts() {
			wp_enqueue_script( 'jquery' );
		}


		static function vwvs_mbr() {
			// mbr/$protocol/$id.$type

			$video_id = intval( $_GET['id'] );
			$type     = sanitize_file_name( $_GET['type'] );

			$protocol = sanitize_file_name( $_GET['protocol'] );
			if ( ! $protocol ) {
				$protocol = 'http';
			}

			if ( ! $video_id ) {
				echo 'Missing video id!';
				// var_dump($_GET);
				exit;
			}

			$video = get_post( $video_id );
			if ( ! $video ) {
				echo 'Missing video!';
				exit;
			}

			$options = get_option( 'VWvideoShareOptions' );

			$mbr = array();

			// retrieve mp4 variants (conversions)
			$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );

			$hasHigh = 0;
			if ( $videoAdaptive ) {
				if ( is_array( $videoAdaptive ) ) {
					foreach ( $videoAdaptive as $alt ) {
						if ( $alt['extension'] == 'mp4' ) {
							$mbr[] = $alt;
						}
						if ( $alt['id'] == 'high' ) {
							$hasHigh = 1;
						}
					}
				}
			}

				// add original if mp4 and high format is not available
			if ( ! count( $mbr ) && $options['originalBackup'] ) {
				$videoPath = get_post_meta( $video_id, 'video-source-file', true );
				$ext       = strtolower( pathinfo( $videoPath, PATHINFO_EXTENSION ) );
				if ( in_array( $ext, array( 'mp4' ) ) ) {
					$src['file']      = $videoPath;
					$src['bitrate']   = get_post_meta( $video_id, 'video-bitrate', true );
					$src['width']     = get_post_meta( $video_id, 'video-width', true );
					$src['height']    = get_post_meta( $video_id, 'video-height', true );
					$src['extension'] = $ext;
					$src['type']      = 'video/mp4';
					$mbr[]            = $src;
				}
			}

			// high bitrate first
			function cmpmbr( $a, $b ) {
				if ( $a['bitrate'] == $b['bitrate'] ) {
					return 0;
				}
				return ( $a['bitrate'] > $b['bitrate'] ) ? -1 : 1;
			}

			usort( $mbr, 'cmpmbr' );

			// var_dump($mbr);

			// var_dump($mbr);

			$videoDuration = get_post_meta( $video_id, 'video-duration', true );

			$nl = "\r\n";

			switch ( $type ) {
				case 'f4m':
					echo '<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns="http://ns.adobe.com/f4m/1.0">
     <mimeType>video/mp4</mimeType>
     <duration>' . esc_html( $videoDuration ) . '</duration>' . $nl;

					switch ( $protocol ) {
						case 'http':
							echo '<id>Progressive Download</id>
     <streamType>recorded</streamType>
	 <deliveryType>progressive</deliveryType>' . $nl;
							foreach ( $mbr as $alt ) {
								echo '<media url="' . self::path2url( $alt['file'] ) . '" bitrate="' . esc_attr( $alt['bitrate'] ) . '" width="' . esc_attr( $alt['width'] ) . '" height="' . esc_attr( $alt['height'] ) . '" />' . $nl;
							}
							break;

						case 'rtmp':
							echo '<id>Dynamic Streaming</id>
	<baseURL>' . esc_html( $options['rtmpServer'] ) . '</baseURL>' . $nl;
							foreach ( $mbr as $alt ) {
								echo '<media url="mp4:' . self::path2stream( $alt['file'] ) . '" bitrate="' . esc_attr( $alt['bitrate'] ) . '" width="' . esc_attr( $alt['width'] ) . '" height="' . esc_attr( $alt['height'] ) . '" />
';
							}
							break;

					}

					echo '</manifest>';
					break;

				case 'm3u8':
					switch ( $protocol ) {
						case 'hls':
							echo '#EXTM3U
';
							foreach ( $mbr as $alt ) {
								$codecsCode = '';
								// $codecsCode = ',CODECS="avc1.42e00a,mp4a.40.2"';

								echo '#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=' . esc_html( $alt['bitrate'] ) . '000,RESOLUTION=' . esc_html( $alt['width'] ) . 'x' . esc_html( $alt['height'] ) . esc_html( $codecsCode ) . $nl;

								$indexULR = '';
								if ( $alt['hls'] ) {
									// static HLS conversion available
									$indexULR = self::path2url( $alt['hls'] ) . '/index.m3u8';
								} elseif ( $options['hlsServer'] ) {
									// use HLS server
									$stream   = self::path2stream( $alt['file'] );
									$stream   = 'mp4:' . $stream;
									$indexULR = $options['hlsServer'] . '_definst_/' . $stream . '/playlist.m3u8';
								}

								if ( $indexULR ) {
									echo esc_html( $indexULR ) . $nl;
								}
							}
							break;
					}
					break;

			}

			ob_clean();
			die;
		}



		static function vwvs_embed() {
			header( 'Content-Type: application/javascript' );

			$playlist = sanitize_file_name( $_GET['playlist'] );

			if ( $playlist ) {
				$htmlCode = self::shortcode_playlist(
					array(
						'name'  => $playlist,
						'embed' => 0,
					)
				);
				$htmlCode = preg_replace( "/\r?\n/", "\\n", addslashes( $htmlCode ) );
			}

			ob_clean();
			if ( $htmlCode ) {
				echo 'document.write("' . esc_html( $htmlCode ) . '");';
			}
			die;

		}


		static function vwvs_playlist_m3u() {
			$options = get_option( 'VWvideoShareOptions' );

			$playlist = sanitize_file_name( $_GET['playlist'] );

			$listCode = '#EXTM3U';

			if ( $playlist ) {
				$args = array(
					'post_type'                 => $options['custom_post'],
					'post_status'               => 'publish',
					'posts_per_page'            => 100,
					'order'                     => 'DESC',
					'orderby'                   => 'post_date',
					$options['custom_taxonomy'] => $playlist,
				);

				$postslist = get_posts( $args );

				if ( count( $postslist ) > 0 ) {
					foreach ( $postslist as $item ) {
						$listCode .= "\r\n" . self::path2url( self::videoPath( $item->ID ) );
					}
				}
			}

			ob_clean();
			echo esc_html( $listCode );
			die;

		}

//shortcodes


		static function videowhisper_import( $atts ) {

			if ( ! is_user_logged_in() ) {
				return __( 'Login is required to import videos!', 'video-share-vod' );

			}

			$options = get_option( 'VWvideoShareOptions' );

			$current_user = wp_get_current_user();
			$userName     = $options['userName'];
			if ( ! $userName ) {
				$userName = 'user_nicename';
			}
			$username = $current_user->$userName;

			if ( ! self::hasPriviledge( $options['shareList'] ) ) {
				return __( 'You do not have permissions to share videos!', 'video-share-vod' );
			}

			$atts = shortcode_atts(
				array(
					'category'    => '',
					'playlist'    => '',
					'owner'       => '',
					'path'        => '',
					'prefix'      => '',
					'tag'         => '',
					'description' => '',
				),
				$atts,
				'videowhisper_import'
			);

			if ( ! $atts['path'] ) {
				return 'videowhisper_import: Path required!';
			}

			if ( ! file_exists( $atts['path'] ) ) {
				return 'videowhisper_import: Path not found!';
			}

			if ( $atts['category'] ) {
				$categories = '<input type="hidden" name="category" id="category" value="' . $atts['category'] . '"/>';
			} else {
				$categories = '<label for="category">' . __( 'Category', 'video-share-vod' ) . ': </label><div class="videowhisperDropdown">' . wp_dropdown_categories( 'show_count=0&echo=0&name=category&hide_empty=0&class=videowhisperSelect' ) . '</div>';
			}

			if ( $atts['playlist'] ) {
				$playlists = '<br><label for="playlist">' . __( 'Playlist', 'video-share-vod' ) . ': </label>' . $atts['playlist'] . '<input type="hidden" name="playlist" id="playlist" value="' . $atts['playlist'] . '"/>';
			} elseif ( current_user_can( 'edit_posts' ) ) {
				$playlists = '<br><label for="playlist">Playlist(s): </label> <br> <input size="48" maxlength="64" type="text" name="playlist" id="playlist" value="' . $username . '"/> ' . __( '(comma separated)', 'video-share-vod' );
			} else {
				$playlists = '<br><label for="playlist">' . __( 'Playlist', 'video-share-vod' ) . ': </label> ' . $username . ' <input type="hidden" name="playlist" id="playlist" value="' . $username . '"/> ';
			}

			if ( $atts['owner'] ) {
				$owners = '<input type="hidden" name="owner" id="owner" value="' . $atts['owner'] . '"/>';
			} else {
				$owners = '<input type="hidden" name="owner" id="owner" value="' . $current_user->ID . '"/>';
			}

			if ( $atts['tag'] != '_none' ) {
				if ( $atts['tag'] ) {
					$tags = '<br><label for="playlist">' . __( 'Tags', 'video-share-vod' ) . ': </label>' . $atts['tag'] . '<input type="hidden" name="tag" id="tag" value="' . $atts['tag'] . '"/>';
				} else {
					$tags = '<br><label for="tag">' . __( 'Tag(s)', 'video-share-vod' ) . ': </label> <br> <input size="48" maxlength="64" type="text" name="tag" id="tag" value=""/> (comma separated)';
				}
			}

			if ( $atts['description'] != '_none' ) {
				if ( $atts['description'] ) {
					$descriptions = '<br><label for="description">' . __( 'Description', 'video-share-vod' ) . ': </label>' . $atts['description'] . '<input type="hidden" name="description" id="description" value="' . $atts['description'] . '"/>';
				} else {
					$descriptions = '<br><label for="description">' . __( 'Description', 'video-share-vod' ) . ': </label> <br> <input size="48" maxlength="256" type="text" name="description" id="description" value=""/>';
				}
			}

			$htmlCode = '';

			$htmlCode .= '<h3>' . __( 'Import Videos', 'video-share-vod' ) . '</h3>' . $atts['path'] . $atts['prefix'];

			$htmlCode .= '<form action="' . wp_nonce_url( self::getCurrentURLfull(), 'vwsec' ) . '" method="post">';

			$htmlCode .= $categories;
			$htmlCode .= $playlists;
			$htmlCode .= $tags;
			$htmlCode .= $descriptions;
			$htmlCode .= $owners;

			$htmlCode .= '<br>' . self::importFilesSelect( $atts['prefix'], self::extensions_video(), $atts['path'] ? $atts['path'] : $options['vwls_archive_path'] );

			$htmlCode .= '<INPUT class="button button-primary" TYPE="submit" name="import" id="import" value="Import">';

			$htmlCode .= ' <INPUT class="button button-primary" TYPE="submit" name="delete" id="delete" value="Delete">';

			$htmlCode .= '</form>';

			$htmlCode .= '<STYLE>' . html_entity_decode( stripslashes( $options['customCSS'] ) ) . '</STYLE>';

			return $htmlCode;
		}



		static function videowhisper_upload( $atts ) {

			$options = get_option( 'VWvideoShareOptions' );

			if ( ! is_user_logged_in() ) {
				return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . __( 'Login is required to upload videos!', 'video-share-vod' ) . '</div>';
			}

			$current_user = wp_get_current_user();
			$userName     = $options['userName'];
			if ( ! $userName ) {
				$userName = 'user_nicename';
			}
			$username = $current_user->$userName;

			if ( ! self::hasPriviledge( $options['shareList'] ) ) {
				return __( 'You do not have permissions to share videos!', 'video-share-vod' );
			}

			$atts = shortcode_atts(
				array(
					'category'    => '',
					'playlist'    => '',
					'owner'       => '',
					'tag'         => '',
					'description' => '',
				),
				$atts,
				'videowhisper_upload'
			);

			self::enqueueUI();

			$ajaxurl = admin_url() . 'admin-ajax.php?action=vwvs_upload';

			if ( $atts['category'] ) {
				$categories = '<input type="hidden" name="category" id="category" value="' . $atts['category'] . '"/>';
			} else {
				$categories = '<div class="field"><label for="category">' . __( 'Category', 'video-share-vod' ) . ' </label>' . wp_dropdown_categories( 'show_count=0&echo=0&name=category&hide_empty=0&class=ui+dropdown' ) . '</div>';
			}

			if ( $atts['playlist'] ) {
				$playlists = '<div class="field"><label for="playlist">' . __( 'Playlist', 'video-share-vod' ) . ' </label>' . $atts['playlist'] . '<input type="hidden" name="playlist" id="playlist" value="' . $atts['playlist'] . '"/></div>';
			} elseif ( current_user_can( 'edit_users' ) ) {
				$playlists = '<div class="field"><label for="playlist">' . __( 'Playlist(s)', 'video-share-vod' ) . ': </label> <input size="48" maxlength="64" type="text" name="playlist" id="playlist" value="' . $username . '" class="text-input" placehoder="(comma separated)"/> ';
			} else {
				$playlists = '<div class="field"><label for="playlist">' . __( 'Playlist', 'video-share-vod' ) . ' </label> ' . $username . ' <input type="hidden" name="playlist" id="playlist" value="' . $username . '"/></div> ';
			}

			if ( $atts['owner'] ) {
				$owners = '<input type="hidden" name="owner" id="owner" value="' . $atts['owner'] . '"/>';
			} else {
				$owners = '<input type="hidden" name="owner" id="owner" value="' . $current_user->ID . '"/>';
			}

			if ( $atts['tag'] != '_none' ) {
				if ( $atts['tag'] ) {
					$tags = '<div class="field"><label for="playlist">' . __( 'Tags', 'video-share-vod' ) . ' </label>' . $atts['tag'] . '<input type="hidden" name="tag" id="tag" value="' . $atts['tag'] . '"/></div>';
				} else {
					$tags = '<div class="field"><label for="tag">' . __( 'Tag(s)', 'video-share-vod' ) . ' </label><input size="48" maxlength="64" type="text" name="tag" id="tag" value="" class="text-input" placeholder="comma separated tags, for all videos that will be uploaded"/></div>';
				}
			}

			if ( $atts['description'] != '_none' ) {
				if ( $atts['description'] ) {
					$descriptions = '<div class="field"><label for="description">' . __( 'Description', 'video-share-vod' ) . ' </label>' . $atts['description'] . '<input type="hidden" name="description" id="description" value="' . $atts['description'] . '"/></div>';
				} else {
					$descriptions = '<div class="field"><label for="description">' . __( 'Description', 'video-share-vod' ) . ' </label><textarea rows="2" name="description" id="description" class="text-input" placeholder="description, for all videos that will be uploaded"/></textarea></div>';
				}
			}

					$iPod = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPod' );
				$iPhone   = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPhone' );
			$iPad         = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPad' );
			$Android      = stripos( $_SERVER['HTTP_USER_AGENT'], 'Android' );

			if ( $iPhone || $iPad || $iPod || $Android ) {
				$mobile = true;
			} else {
				$mobile = false;
			}

			if ( $mobile ) {
				// $mobiles = 'capture="camcorder"'; //preference, forces only capture on iOS
				$accepts   = 'accept="video/*;capture=camcorder"';
				$multiples = '';
				$filedrags = '';
			} else {
				$mobiles   = '';
				$accepts   = 'accept="video/mp4,video/x-m4v,video/*,capture=camcorder"';
				$multiples = 'multiple="multiple"';
				$filedrags = '<div id="filedrag">' . __( 'or Drag & Drop files to this upload area<br>(fill rest of form options first to apply for all uploads)', 'video-share-vod' ) . '</div>';
			}

			wp_enqueue_script( 'vwvs-upload', plugin_dir_url( __FILE__ ) . 'upload.js' );

			$submits = '<div id="submitbutton">
	<button class="ui button" type="submit" name="upload" id="upload">' . __( 'Upload Files', 'video-share-vod' ) . '</button>';

			$interfaceClass = $options['interfaceClass'];

			$htmlCode .= <<<EOHTML
<div class="ui $interfaceClass form">
<form id="upload" action="$ajaxurl" method="POST" enctype="multipart/form-data">

<fieldset>
$categories
$playlists
$tags
$descriptions
$owners
<input type="hidden" id="MAX_FILE_SIZE" name="MAX_FILE_SIZE" value="9000000000" />
EOHTML;

			$htmlCode .= '<legend><h3>' . __( 'Video Upload', 'video-share-vod' ) . '</h3></legend><div> <label for="fileselect">' . __( 'Videos to Upload', 'video-share-vod' ) . ' </label>';

			$htmlCode .= <<<EOHTML
	<br><input class="ui button" type="file" id="fileselect" name="fileselect[]" $mobiles $multiples $accepts />
$filedrags
$submits
</div>
EOHTML;

			$htmlCode .= <<<EOHTML
<div id="progress"></div>

</fieldset>
</form>
</div>

<script>
jQuery(document).ready(function(){
jQuery(".ui.dropdown:not(.multi,.fpsDropdown)").dropdown();
});
</script>

<STYLE>

#filedrag
{
 height: 100px;
 border: 1px solid #AAA;
 border-radius: 9px;
 color: #333;
 background: #eee;
 padding: 5px;
 margin-top: 5px;
 text-align:center;
}

#progress
{
padding: 4px;
margin: 4px;
}

#progress div {
	position: relative;
	background: #555;
	-moz-border-radius: 9px;
	-webkit-border-radius: 9px;
	border-radius: 9px;

	padding: 4px;
	margin: 4px;

	color: #DDD;

}

#progress div > span {
	display: block;
	height: 20px;

	   -webkit-border-top-right-radius: 4px;
	-webkit-border-bottom-right-radius: 4px;
	       -moz-border-radius-topright: 4px;
	    -moz-border-radius-bottomright: 4px;
	           border-top-right-radius: 4px;
	        border-bottom-right-radius: 4px;
	    -webkit-border-top-left-radius: 4px;
	 -webkit-border-bottom-left-radius: 4px;
	        -moz-border-radius-topleft: 4px;
	     -moz-border-radius-bottomleft: 4px;
	            border-top-left-radius: 4px;
	         border-bottom-left-radius: 4px;

	background-color: rgb(43,194,83);

	background-image:
	   -webkit-gradient(linear, 0 0, 100% 100%,
	      color-stop(.25, rgba(255, 255, 255, .2)),
	      color-stop(.25, transparent), color-stop(.5, transparent),
	      color-stop(.5, rgba(255, 255, 255, .2)),
	      color-stop(.75, rgba(255, 255, 255, .2)),
	      color-stop(.75, transparent), to(transparent)
	   );

	background-image:
		-webkit-linear-gradient(
		  -45deg,
	      rgba(255, 255, 255, .2) 25%,
	      transparent 25%,
	      transparent 50%,
	      rgba(255, 255, 255, .2) 50%,
	      rgba(255, 255, 255, .2) 75%,
	      transparent 75%,
	      transparent
	   );

	background-image:
		-moz-linear-gradient(
		  -45deg,
	      rgba(255, 255, 255, .2) 25%,
	      transparent 25%,
	      transparent 50%,
	      rgba(255, 255, 255, .2) 50%,
	      rgba(255, 255, 255, .2) 75%,
	      transparent 75%,
	      transparent
	   );

	background-image:
		-ms-linear-gradient(
		  -45deg,
	      rgba(255, 255, 255, .2) 25%,
	      transparent 25%,
	      transparent 50%,
	      rgba(255, 255, 255, .2) 50%,
	      rgba(255, 255, 255, .2) 75%,
	      transparent 75%,
	      transparent
	   );

	background-image:
		-o-linear-gradient(
		  -45deg,
	      rgba(255, 255, 255, .2) 25%,
	      transparent 25%,
	      transparent 50%,
	      rgba(255, 255, 255, .2) 50%,
	      rgba(255, 255, 255, .2) 75%,
	      transparent 75%,
	      transparent
	   );

	position: relative;
	overflow: hidden;
}

#progress div.success
{
    color: #DDD;
	background: #3C6243 none 0 0 no-repeat;
}

#progress div.failed
{
 	color: #DDD;
	background: #682C38 none 0 0 no-repeat;
}
</STYLE>
EOHTML;

			$htmlCode .= '<STYLE>' . html_entity_decode( stripslashes( $options['customCSS'] ) ) . '</STYLE>';

			return $htmlCode;

		}

		static	function generateName( $fn ) {
				$ext = strtolower( pathinfo( $fn, PATHINFO_EXTENSION ) );

				if ( ! in_array( $ext, self::extensions_video() ) ) {
					echo 'Extension not allowed!';
					exit;
				}

				// unpredictable name
				return md5( uniqid( $fn, true ) ) . '.' . $ext;
			}
			
		static function vwvs_upload() {

			if ( ! is_user_logged_in() ) {
				echo 'Login required!';
				exit;
			}
			$current_user = wp_get_current_user();

			$owner = $_SERVER['HTTP_X_OWNER'] ? intval( $_SERVER['HTTP_X_OWNER'] ) : intval( $_POST['owner'] );

			if ( $owner && ! current_user_can( 'edit_users' ) && $owner != $current_user->ID ) {
				echo 'Only admin can upload for others!';
				exit;
			}
			if ( ! $owner ) {
				$owner = $current_user->ID;
			}

			$playlist = $_SERVER['HTTP_X_PLAYLIST'] ? sanitize_text_field( $_SERVER['HTTP_X_PLAYLIST'] ) : sanitize_text_field( $_POST['playlist'] );

			// if csv sanitize as array
			if ( strpos( $playlist, ',' ) !== false ) {
				$playlists = explode( ',', $playlist );
				foreach ( $playlists as $key => $value ) {
					$playlists[ $key ] = sanitize_file_name( trim( $value ) );
				}
				$playlist = $playlists;
			}

			if ( ! $playlist ) {
				echo 'Playlist required!';
				exit;
			}

			$category = $_SERVER['HTTP_X_CATEGORY'] ? sanitize_text_field( $_SERVER['HTTP_X_CATEGORY'] ) : sanitize_text_field( $_POST['category'] );

			$tag = sanitize_text_field( $_SERVER['HTTP_X_TAG'] ? $_SERVER['HTTP_X_TAG'] : $_POST['tag'] );

			// if csv sanitize as array
			if ( strpos( $tag, ',' ) !== false ) {
				$tags = explode( ',', $tag );
				foreach ( $tags as $key => $value ) {
					$tags[ $key ] = sanitize_file_name( trim( $value ) );
				}
				$tag = $tags;
			} else {
				$tag = sanitize_file_name( trim( $tag ) );
			}

			$description = wp_encode_emoji( sanitize_textarea_field( $_SERVER['HTTP_X_DESCRIPTION'] ? urldecode( $_SERVER['HTTP_X_DESCRIPTION'] ) : $_POST['description'] ) );

			// echo "<br>$category<br>$playlist<br>$description";

			$options = get_option( 'VWvideoShareOptions' );

			$dir = sanitize_text_field( $options['uploadsPath'] );
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			$dir .= '/uploads';
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			$dir .= '/';

			ob_clean();
			$fn = ( isset( $_SERVER['HTTP_X_FILENAME'] ) ? sanitize_file_name( $_SERVER['HTTP_X_FILENAME'] ) : false );

			$path = '';

			if ( $fn ) {
				// AJAX call
				file_put_contents( $path = $dir . self::generateName( $fn ), file_get_contents( 'php://input' ) );
				$el    = array_shift( explode( '.', $fn ) );
				$title = ucwords( str_replace( '-', ' ', sanitize_file_name( $el ) ) );

				echo wp_kses_post( self::importFile( $path, $title, $owner, $playlist, $category, $tag, $description ) );

				// echo "Video was uploaded.";
			} else {
				// form submit
				$files = isset( $_POST['fileselect'] ) ? (array) $_POST['fileselect'] : array();

				if ( $files['error'] ) {
					if ( is_array( $files['error'] ) ) {
						foreach ( $files['error'] as $id => $err ) {
							if ( $err == UPLOAD_ERR_OK ) {
								$fn = $files['name'][ $id ];
								move_uploaded_file( $files['tmp_name'][ $id ], $path = $dir . self::generateName( $fn ) );
								$title = ucwords( str_replace( '-', ' ', sanitize_file_name( array_shift( explode( '.', $fn ) ) ) ) );

								echo wp_kses_post( self::importFile( $path, $title, $owner, $playlist, $category, $tag, $description ) ) . '<br>';

								echo 'Video was uploaded using fallback method as HTML5 drag & drop uploader JavaScript did not load/work.';
							}
						}
					}
				}
			}

			die;
		}


		static function shortcode_preview( $atts ) {
			$atts = shortcode_atts(
				array(
					'video' => '0',
					'type'  => 'auto',
				),
				$atts,
				'shortcode_preview'
			);

			$video_id = intval( $atts['video'] );
			if ( ! $video_id ) {
				return 'shortcode_preview: Missing video id!';
			}

			$video = get_post( $video_id );
			if ( ! $video ) {
				return 'shortcode_preview: Video #' . $video_id . ' not found!';
			}

			$options = get_option( 'VWvideoShareOptions' );

			// res
			$vWidth  = get_post_meta( $video_id, 'video-width', true );
			$vHeight = get_post_meta( $video_id, 'video-height', true );
			if ( ! $vWidth ) {
				$vWidth = $options['thumbWidth'];
			}
			if ( ! $vHeight ) {
				$vHeight = $options['thumbHeight'];
			}

			// snap
			$imagePath = get_post_meta( $video_id, 'video-snapshot', true );
			if ( $imagePath ) {
				if ( file_exists( $imagePath ) ) {
					$imageURL = self::path2url( $imagePath );
				} else {
					self::updatePostThumbnail( $update_id );
				}
			}

			if ( ! $imagePath ) {
				$imageURL = self::path2url( plugin_dir_path( __FILE__ ) . 'no_video.png' );
			}
				$video_url = get_permalink( $video_id );
			$htmlCode      = "<a href='$video_url'><IMG SRC='$imageURL' width='$vWidth' height='$vHeight'></a>";

			return $htmlCode;
		}


		static function shortcode_playlist( $atts ) {
			$atts = shortcode_atts(
				array(
					'name'   => '',
					'videos' => '',
					'embed'  => '1',
				),
				$atts,
				'videowhisper_playlist'
			);

			if ( ! $atts['name'] && ! $atts['videos'] ) {
				return 'No playlist or video list specified!';
			}

			$options = get_option( 'VWvideoShareOptions' );

			if ( $atts['embed'] ) {
				if ( self::hasPriviledge( $options['embedList'] ) ) {
					$showEmbed = 1;
				} else {
					$showEmbed = 0;
				}
			} else {
				$showEmbed = 0;
			}

				$player = $option['playlist_player'];
			if ( ! $player ) {
				$player = 'video-js';
			}

			switch ( $player ) {
				case 'strobe':
					$playlist_m3u = admin_url() . 'admin-ajax.php?action=vwvs_playlist_m3u&playlist=' . urlencode( $atts['name'] );

					$player_url = plugin_dir_url( __FILE__ ) . 'strobe/StrobeMediaPlayback.swf';
					$flashvars  = 'src=' . $playlist_m3u . '&autoPlay=false';

					$htmlCode .= '<object class="videoPlayer" width="480" height="360" type="application/x-shockwave-flash" data="' . $player_url . '"> <param name="movie" value="' . $player_url . '" /><param name="flashvars" value="' . $flashvars . '" /><param name="allowFullScreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="wmode" value="direct" /></object>';

					// $dfrt56 .= $htmlCode;
					$embedCode .= '<BR><a href="' . $playlist_m3u . '">Playlist M3U</a>';

					$htmlCode .= '<br><h5>Embed Flash Playlist HTML Code (Copy and Paste to your Page)</h5>';
					$htmlCode .= htmlspecialchars( $embedCode );
					break;

				case 'video-js':
					if ( $atts['name'] && ! $atts['videos'] ) {
						if ( ! taxonomy_exists( $options['custom_taxonomy'] ) ) {
							$htmlCode .= 'Error: Taxonomy does not exist: ' . $options['custom_taxonomy'];
						}

						$args = array(
							'post_type'                 => $options['custom_post'],
							'post_status'               => 'publish',
							'posts_per_page'            => 100,
							'order'                     => 'DESC',
							'orderby'                   => 'post_date',
							$options['custom_taxonomy'] => strtolower( $atts['name'] ),
							'tax_query'                 => array(
								'taxonomy' => $options['custom_taxonomy'],
								'field'    => 'name',
								'terms'    => $atts['name'],
							),

						);
						// var_dump($args);
							$id = preg_replace( '/[^A-Za-z0-9]/', '', $atts['name'] );

							$postslist = get_posts( $args );
							// var_dump($postslist);

							$listCode = '';
						if ( count( $postslist ) > 0 ) {
							foreach ( $postslist as $item ) {

								$listCode .= ( $listCode ? ",\r\n" : '' );

								$listCode .= '{ ';

								$listCode .= 'sources: [{';
								$source    = self::path2url( self::videoPath( $item->ID ) );
								$listCode .= 'src: "' . $source . '", ';
								$listCode .= 'type: "video/mp4" ';
								$listCode .= "}],\r\n";

								$listCode .= 'name: "' . esc_html( $item->post_title ) . '", ';
								$listCode .= 'description: "' . strip_tags( $item->post_content ) . '", ';

								$poster    = self::path2url( get_post_meta( $item->ID, 'video-thumbnail', true ) );
								$listCode .= ' thumbnail: [ {srcset: "' . $poster . '", type: "image/jpeg", media: "(min-width: 400px;)"}, {src: "' . $poster . '"}] ';

								$listCode .= ' }';
							}
						} else {
							$htmlCode .= 'No published videos found for playlist "' . $atts['name'] . '", taxonomy "' . $options['custom_taxonomy'] . '"';
						}
					}

						// video-js
						wp_enqueue_style( 'video-js', plugin_dir_url( __FILE__ ) . 'video-js/video-js.min.css' );
						wp_enqueue_script( 'video-js', plugin_dir_url( __FILE__ ) . 'video-js/video.min.js' );

						// video-js playlist
						wp_enqueue_script( 'video-js-playlist', plugin_dir_url( __FILE__ ) . 'video-js/playlist/videojs-playlist.min.js', array( 'video-js' ) );
						wp_enqueue_script( 'video-js-playlist-ui', plugin_dir_url( __FILE__ ) . 'video-js/playlist/videojs-playlist-ui.min.js', array( 'video-js', 'video-js-playlist' ) );

						wp_enqueue_style( 'video-js-playlist-ui', plugin_dir_url( __FILE__ ) . 'video-js/playlist/videojs-playlist-ui.css' );
						// wp_enqueue_style( 'video-js-playlist-ui', plugin_dir_url(__FILE__) .'video-js/playlist/videojs-playlist-ui.vertical.css');

						$VideoWidth = $options['playlistVideoWidth'];
						$ListWidth  = $options['playlistListWidth'];

						$htmlCode .= <<<EOCODE
 <div class="player-container">

        <div class="vjs-playlist"></div>

        <video id="video_$id" class="video-js" controls width="$VideoWidth" height="540" data-setup='{"fluid": true}' poster=""></video>
</div>

<script>
jQuery(document).ready(function()
{

var player$id = videojs('video_$id');

player$id.ready(function() {

player$id.playlist([$listCode]);

player$id.playlistUi({horizontal: true});

player$id.playlist.autoadvance(0);

});

});
</script>
EOCODE;

					if ( $showEmbed ) {
						$embedCode .= '<link rel="stylesheet" type="text/css" href="' . plugin_dir_url( __FILE__ ) . 'video-js/video-js.min.css' . '">'; // displayed with embedCode() esc_textarea, not loaded
						$embedCode .= "\r\n" . '<script src="' . plugin_dir_url( __FILE__ ) . 'video-js/video.min.js' . '" type="text/javascript"></script>'; // displayed with embedCode() esc_textarea, not loaded
						$embedCode .= "\r\n" . '<script src="' . plugin_dir_url( __FILE__ ) . 'video-js/4/videojs-playlists.min.js' . '" type="text/javascript"></script>'; // displayed with embedCode()

						$embedCode .= "\r\n\r\n" . '<script src="' . admin_url() . 'admin-ajax.php?action=vwvs_embed&playlist=' . urlencode( $atts['name'] ) . '" type="text/javascript"></script>'; // displayed with embedCode() esc_textarea, not loaded

						$embedCode .= "\r\n\r\n" . '<BR><a href="' . admin_url() . 'admin-ajax.php?action=vwvs_playlist_m3u&playlist=' . urlencode( $atts['name'] ) . '">Playlist (M3U)</a>'; // displayed with embedCode() esc_textarea, not loaded

						$htmlCode .= "\r\n\r\n" . self::embedCode( $embedCode, 'Embed Playlist HTML Code', 'Copy and Paste to your Page' );
					}

					break;
			}

				return $htmlCode;

		}


		static function embedCode( $embedCode, $title, $instructions ) {
			$htmlCode .= '<br><h5>' . esc_html( $title ) . '</h5>';
			$htmlCode .= '<textarea style="width:95%; height: 160px">';
			$htmlCode .= esc_textarea( '<script src="' . includes_url() . 'js/jquery/jquery.js" type="text/javascript"></script>' . "\r\n\r\n" ); // generating embed code
			$htmlCode .= esc_textarea( $embedCode );
			$htmlCode .= '</textarea>';
			$htmlCode .= '<br>' . esc_html( $instructions );
			return $htmlCode;
		}


		static function adVAST( $id ) {

			$options = get_option( 'VWvideoShareOptions' );

			// Ads enabled?
			$showAds = $options['adsGlobal'];

			// video exception playlists
			if ( $id ) {
				$lists = wp_get_post_terms( $id, $options['custom_taxonomy'], array( 'fields' => 'names' ) );
				if ( is_array( $lists ) ) {
					foreach ( $lists as $playlist ) {
						if ( strtolower( $playlist ) == 'sponsored' ) {
							$showAds = true;
						}
						if ( strtolower( $playlist ) == 'adfree' ) {
							$showAds = false;
						}
					}
				}
			}

			// no ads for premium users
			if ( $showAds ) {
				if ( self::hasPriviledge( $options['premiumList'] ) ) {
					$showAds = false;
				}
			}

			if ( ! $showAds ) {
				return '';
			} else {
				return $options['vast'];
			}

		}


		static function shortcode_embed_code( $atts ) {
			$options = get_option( 'VWvideoShareOptions' );

			$atts = shortcode_atts(
				array(
					'poster'      => '',
					'width'       => $options['thumbWidth'],
					'height'      => $options['thumbHeight'],
					'poster'      => $options['thumbHeight'],
					'source'      => '',
					'source_type' => '',
					'id'          => '0',
					'fallback'    => 'You must have a HTML5 capable browser to watch this video. Read more about video sharing solutions and players on <a href="https://videosharevod.com/">Video Share VOD</a> website.',
				),
				$atts,
				'videowhisper_embed_code'
			);

			$player = $options['embed_player'];
			if ( ! $player ) {
				$player = 'native';
			}

			switch ( $player ) {
				case 'native':
					if ( $atts['poster'] ) {
						$posterProp = ' poster="' . esc_attr( $atts['poster'] ) . '"';
					} else {
						$posterProp = '';
					}

					$embedCode .= "\r\n" . '<video width="' . esc_attr( $atts['width'] ) . '" height="' . esc_attr( $atts['height'] ) . '"  preload="metadata" autobuffer controls="controls"' . $posterProp . '>';
					$embedCode .= "\r\n" . ' <source src="' . esc_attr( $atts['source'] ) . '" type="' . esc_attr( $atts['source_type'] ) . '">';
					$embedCode .= "\r\n" . '</video>';
					$embedCode .= "\r\n" . "\r\n" . '<br><a href="' . esc_url( $atts['source'] ) . '">' . __( 'Download Video File', 'video-share-vod' ) . '</a> (' . __( 'right click and Save As..', 'video-share-vod' ) . ')';
					break;
			}

			return self::embedCode( $embedCode, __( 'Embed Video HTML Code', 'video-share-vod' ), __( 'Copy and Paste to your Page', 'video-share-vod' ) );
		}


		static function videowhisper_player_html( $atts ) {
			// html5 video player
			$options = get_option( 'VWvideoShareOptions' );

			$atts = shortcode_atts(
				array(
					'poster'           => '',
					'width'            => $options['thumbWidth'],
					'height'           => $options['thumbHeight'],
					'poster'           => $options['thumbHeight'],
					'source_alt'       => '',
					'source'           => '',
					'source_type'      => '',
					'source2'          => '',
					'source_type2'     => '',
					'source3'          => '',
					'source_type3'     => '',
					'source_alt_type'  => '',
					'player'           => '',
					'id'               => '0',
					'fallback_enabled' => '1',
					'fallback'         => 'You must have a HTML5 capable browser to watch this video. Read more about video sharing solutions and players on <a href="https://videosharevod.com/">Video Share VOD Script</a> website.',
				),
				$atts,
				'videowhisper_player_html'
			);

			if ( ! $atts['player'] ) {
				$player = $options['html5_player'];
			} else {
				$player = $atts['player'];
			}

			if ( $_GET['player_html'] ?? false && $options['allowDebug'] ) {
				$player = sanitize_file_name( $_GET['player_html'] );
			}

			if ( ! $player ) {
				$player = 'video-js';
			}

			$htmlCode = "<!-- videowhisper_player_html: $player -->";

			switch ( $player ) {
				case 'native':
					if ( $atts['poster'] ) {
						$posterProp = ' poster="' . $atts['poster'] . '"';
					} else {
						$posterProp = '';
					}

					$htmlCode .= '<video width="' . $atts['width'] . '" height="' . $atts['height'] . '"  preload="metadata" autobuffer controls="controls"' . $posterProp . '>';

					$htmlCode .= ' <source src="' . $atts['source'] . '" type="' . $atts['source_type'] . '">';

					if ( $atts['source2'] ) {
						$htmlCode .= ' <source src="' . $atts['source2'] . '" type="' . $atts['source_type2'] . '">';
					}
					if ( $atts['source3'] ) {
						$htmlCode .= ' <source src="' . $atts['source3'] . '" type="' . $atts['source_type3'] . '">';
					}

					if ( $atts['fallback_enabled'] ) {
						$htmlCode .= ' <div class="fallback"> <p>' . $atts['fallback'] . '</p></div> </video>';
					}

					break;

				case 'WordPress':
					$htmlCode .= do_shortcode( '[video src="' . $atts['source'] . '" poster="' . $atts['poster'] . '" width="' . $atts['width'] . '" height="' . $atts['height'] . '"]' );
					break;

				case 'video-js':
					wp_enqueue_script( 'video-js', plugin_dir_url( __FILE__ ) . 'video-js/video.min.js' );
					wp_enqueue_style( 'video-js', plugin_dir_url( __FILE__ ) . 'video-js/video-js.min.css' );

					// wp_enqueue_script('video-js-quality', plugin_dir_url(__FILE__) .'video-js/quality/videojs-contrib-quality-levels.min.js', array( 'video-js') );

					$vast = self::adVAST( $atts['id'] );

					$id = 'vwVid' . $atts['id'];

					$videojsParams = '';
					$videojsCalls  = '';

					$videojsCSS = 1;

					$htmlCode .= '<script>
				jQuery(document).ready(function(){ videojs.options.flash.swf = "' . plugin_dir_url( __FILE__ ) . 'video-js/video-js.swf' . '";});</script>';

					// source alternatives (mbr)
					if ( $atts['source_alt'] ) {

						/*
						//dash plugin included in videojs 7+
						wp_enqueue_script('video-dash', plugin_dir_url(__FILE__) .'video-js/dash/dash.all.min.js' );
						wp_enqueue_script('video-js-dash', plugin_dir_url(__FILE__) .'video-js/dash/videojs-dash.min.js', array( 'video-js', 'video-dash') );



						wp_enqueue_script('video-js6', plugin_dir_url(__FILE__) .'video-js/6/videojs-media-sources.js', array( 'video-js') );
						wp_enqueue_script('video-js7', plugin_dir_url(__FILE__) .'video-js/7/videojs-contrib-hls.min.js', array( 'video-js', 'video-js6') );

						// segment handling
						wp_enqueue_script('video-js7-1', plugin_dir_url(__FILE__) .'video-js/7/flv-tag.js', array( 'video-js', 'video-js7') );
						wp_enqueue_script('video-js7-2', plugin_dir_url(__FILE__) .'video-js/7/exp-golomb.js', array( 'video-js', 'video-js7') );
						wp_enqueue_script('video-js7-3', plugin_dir_url(__FILE__) .'video-js/7/h264-stream.js', array( 'video-js', 'video-js7') );
						wp_enqueue_script('video-js7-4', plugin_dir_url(__FILE__) .'video-js/7/aac-stream.js', array( 'video-js', 'video-js7') );
						wp_enqueue_script('video-js7-5', plugin_dir_url(__FILE__) .'video-js/7/segment-parser.js', array( 'video-js', 'video-js7') );

						//m3u8 handling
						wp_enqueue_script('video-js7-6', plugin_dir_url(__FILE__) .'video-js/7/stream.js', array( 'video-js', 'video-js7') );
						wp_enqueue_script('video-js7-7', plugin_dir_url(__FILE__) .'video-js/7/m3u8/m3u8-parser.js', array( 'video-js', 'video-js7') );
						wp_enqueue_script('video-js7-8', plugin_dir_url(__FILE__) .'video-js/7/playlist-loader.js', array( 'video-js', 'video-js7') );


						//MBR plugin
						wp_enqueue_script('video-js8-1', plugin_dir_url(__FILE__) .'video-js/8/videojs-mbr-menu-button.js', array( 'video-js', 'video-js6', 'video-js7') );
						wp_enqueue_script('video-js8', plugin_dir_url(__FILE__) .'video-js/8/videojs-mbr.js', array( 'video-js', 'video-js6', 'video-js7') );

						wp_enqueue_style( 'video-js9', plugin_dir_url(__FILE__) .'video-js/8/videojs-mbr.css');
						*/
						$videojsCSS = 0;

						// $videojsParams .= "techOrder: ['']";
						// $videojsCalls .= $id . '.mbr({autoSwitch:false});';
						$videojsCalls .= $id . '.controlBar.show();';

						$atts['source']      = $atts['source_alt'];
						$atts['source_type'] = $atts['source_alt_type'];
					}

					$htmlCode .= '<script>					
					jQuery(document).ready(function(){
					var ' . esc_attr( $id ) . ' = videojs("' . esc_attr( $id ) . '", {"fluid": true, ' . $videojsParams . '});
					
					' . esc_attr( $id ) . '.on("play", function() { this.bigPlayButton.hide(); } );
					
					' . esc_attr( $id ) . '.on("pause", function() {
					this.bigPlayButton.show();
					' . esc_attr( $id ) . '.on("play", function() { this.bigPlayButton.hide(); } );
					});

					';

					/*
					var qualityLevels = ' . esc_attr($id) . '.qualityLevels();
					qualityLevels.selectedIndex_ = 0;
					qualityLevels.trigger({ type: \'change\', selectedIndex: 0 });
					*/

					if ( $vast ) {

						wp_enqueue_script( 'video-js-ads', plugin_dir_url( __FILE__ ) . 'video-js/ads/videojs-contrib-ads.min.js', array( 'video-js' ) );
						wp_enqueue_style( 'video-js-ads', plugin_dir_url( __FILE__ ) . 'video-js/ads/videojs-contrib-ads.css' );

						if ( $options['vastLib'] == 'vast' ) {
							wp_enqueue_script( 'video-js-vastclient', plugin_dir_url( __FILE__ ) . 'video-js/ads/vast-client.min.js' );

							wp_enqueue_script( 'video-js3', plugin_dir_url( __FILE__ ) . 'video-js/3/videojs.vast.js', array( 'video-js', 'video-js-vastclient', 'video-js-ads' ) );
							wp_enqueue_style( 'video-js3', plugin_dir_url( __FILE__ ) . 'video-js/3/videojs.vast.css' );

							$videojsCalls .= $id . '.ads();';
							$videojsCalls .= $id . '.vast({ url: \'' . $options['vast'] . '\' })';
						} else {

							// wp_enqueue_script('ima3', plugin_dir_url(__FILE__) .'video-js/ads/ima3.js');  // service requires loading from Google domain
							wp_enqueue_script( 'ima3', '//imasdk.googleapis.com/js/sdkloader/ima3.js' );  // Error: IMA SDK is either not loaded from a google domain or is not a supported version. - Service Terms:  https://developers.google.com/interactive-media-ads/docs/sdks/html5/client-side/terms
							wp_enqueue_script( 'video-js-ima', plugin_dir_url( __FILE__ ) . 'video-js/ads/videojs.ima.min.js', array( 'video-js', 'ima3' ) );
							wp_enqueue_style( 'video-js-ima', plugin_dir_url( __FILE__ ) . 'video-js/ads/videojs.ima.css' );

							$videojsCalls .= $id . '.ima({ id: \'' . $id . '\', adTagUrl: \'' . $options['vast'] . '\' });';
							$videojsCalls .= $id . '.ima.requestAds();';
						}
					}

					$htmlCode .= $videojsCalls;

					$htmlCode .= '});</script>';

					if ( $atts['poster'] ) {
						$posterProp = ' poster="' . $atts['poster'] . '"';
					} else {
						$posterProp = '';
					}

					$htmlCode .= ' <video id="' . esc_attr( $id ) . '" class="video-js vjs-big-play-centered"  controls="controls" preload="metadata" width="' . $atts['width'] . '" height="' . $atts['height'] . '"' . $posterProp . ' data-setup=\'{"fluid": true}\' setup=\'{"fluid": true}\'>';

					$htmlCode .= "\r\n" . ' <source src="' . $atts['source'] . '" type="' . $atts['source_type'] . '">';

					if ( $atts['source2'] ) {
						$htmlCode .= "\r\n" . ' <source src="' . $atts['source2'] . '" type="' . $atts['source_type2'] . '">';
					}
					if ( $atts['source3'] ) {
						$htmlCode .= "\r\n" . ' <source src="' . $atts['source3'] . '" type="' . $atts['source_type3'] . '">';
					}

					if ( $atts['fallback_enabled'] ) {
						$htmlCode .= ' <div class="fallback"> <p>' . $atts['fallback'] . '</p></div>';
					}
					$htmlCode .= ' </video>';

					break;

				default:
					$htmlCode .= 'videowhisper_player_html: Player not found:' . $player;
			}

				if ( !$options['enable_exec'] ) $htmlCode .= '<div class="ui segment inverted">Warning: Server Command Execution is disabled from plugin settings and FFmpeg can NOT run for conversions. Converting videos requires <a href="https://videosharevod.com/hosting/">web hosting with FFmpeg support</a>.</div>';

			return $htmlCode;
		}


		static function videowhisper_postvideo_assign( $atts ) {
			$atts = shortcode_atts(
				array(
					'post_id'   => '',
					'meta'      => 'video_teaser',
					'content'   => 'id', // id / video_path / preview_path
					'show'      => '1',
					'showWidth' => '320',
					'user_id' => '', //include videos of this user
				),
				$atts,
				'videowhisper_postvideo_assign'
			);

			$postID = (int) $atts['post_id'];
			$meta   = sanitize_file_name( $atts['meta'] );
			$show   = (int) $atts['show'];

			if ( ! $postID ) {
				return 'No postID was specified, to assign post associated videos.';
			}
			if ( ! is_user_logged_in() ) {
				return 'Login required to assign post associated video.';
			}

			$htmlCode = '';
			$current_user = wp_get_current_user();

			$options = get_option( 'VWvideoShareOptions' );

			if ( isset($_GET['assignVideo']) && isset( $_POST['select']) ) if ( $_GET['assignVideo'] == $meta && $_POST['select'] ) {
				$value = sanitize_text_field( $_POST[ $meta ] );
				update_post_meta( $postID, $meta, $value );
				$htmlCode .= '<p>Updated...</p>';
			}

			$currentValue = get_post_meta( $postID, $meta, true );

			// query
			$args = array(
				'post_type' => $options['custom_post'],
				'author'    => $current_user->ID,
				'orderby'   => 'post_date',
				'numberposts' => -1,
				'order'     => 'DESC',
			);
			
			if ($atts['user_id']) 
			{
				$args['author__in'] = [ $current_user->ID, intval($atts['user_id']) ];
				unset($args['author']);
			}
			
			$quickCode = '';

			$postslist = get_posts( $args );
			if ( count( $postslist ) > 0 ) {
				$quickCode .= '<SELECT class="ui dropdown v-select" id="' . $meta . '" name="' . $meta . '">';
				$quickCode .= '<option value="" ' . ( ! $currentValue ? 'selected' : '' ) . '> - </option>';

				foreach ( $postslist as $item ) {
					$video_id = $item->ID;
					$value    = '';

					switch ( $atts['content'] ) {
						case 'id':
							$value = $video_id;
							break;

						case 'video_path':
							// retrieve video stream
							$streamPath = '';
							$videoPath  = get_post_meta( $video_id, 'video-source-file', true );
							$ext        = pathinfo( $videoPath, PATHINFO_EXTENSION );

							// use conversion if available
							$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
							if ( $videoAdaptive ) {
								$videoAlts = $videoAdaptive;
							} else {
								$videoAlts = array();
							}

							foreach ( array( 'high', 'mobile' ) as $frm ) {
								if ( is_array( $videoAlts ) ) {
									if ( array_key_exists( $frm, $videoAlts ) ) {
										if ( $alt = $videoAlts[ $frm ] ) {
											if ( file_exists( $alt['file'] ) ) {
												$ext        = pathinfo( $alt['file'], PATHINFO_EXTENSION );
												$streamPath = VWliveWebcams::path2stream( $alt['file'] );
												break;
											}
										}
									}
								}
							};

								// user original
							if ( ! $streamPath ) {
								if ( in_array( $ext, array( 'flv', 'mp4', 'm4v' ) ) ) {
									// use source if compatible
									$streamPath = VWliveWebcams::path2stream( $videoPath );
								}
							}

								$value = $streamPath;
							break;

						case 'preview_path':
							$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
							if ( $videoAdaptive ) {
								$videoAlts = $videoAdaptive;
							} else {
								$videoAlts = array();
							}

							if ( is_array( $videoAlts ) ) {
								if ( array_key_exists( 'preview', $videoAlts ) ) {
									$alt = $videoAlts['preview'];
								}
							}

							if ( $alt ) {
								if ( $alt['file'] ) {
									if ( file_exists( $alt['file'] ) ) {
										$streamPath = VWliveWebcams::path2stream( $alt['file'] );
									}
								}
							}
									$value = $streamPath;

							break;
					}

					if ( $value ) {
						$quickCode .= '<option value="' . $value . '" ' . ( $currentValue == $value ? 'selected' : '' ) . '>' . ( $atts['user_id'] && $item->post_author == $current_user->ID ? '* ' : '') . esc_html( $item->post_title ) . '</option>';
					}

					if ( $currentValue == $value ) {
						$showID = $video_id;
					}
				}
				$quickCode .= '</SELECT>';
			} else {
				$quickCode = 'No videos found! Please add some videos first.';
			}

			$action = add_query_arg(
				array(
					'assignVideo' => $meta,
					'postID'      => $postID,
				),
				self::getCurrentURLfull()
			);

			$htmlCode = '';

			$htmlCode .= <<<HTMLCODE
<form method="post" action="$action" name="adminForm" class="w-actionbox">
<div class="field"><label>Select Video</label> $quickCode <input class="ui button" type="submit" name="select" id="select" value="Select" /></div>
</form>
HTMLCODE;

			if ( $show ) {
				if ( $showID ?? false ) {
					$htmlCode .= '<br style="clear:both"><div style="max-width:320px">' . do_shortcode( '[videowhisper_player video="' . $showID . '" player="" width="' . $atts['showWidth'] . '"]' ) . '</div>';
				}
			}

			$htmlCode .= '<!--videowhisper_postvideo_assign:end-->';

			return $htmlCode;

		}


		static function videowhisper_postvideos( $atts ) {

			$options = get_option( 'VWvideoShareOptions' );

			$atts = shortcode_atts(
				array(
					'post'   => '',
					'path'   => '',
					'prefix' => '_channel_',
				),
				$atts,
				'videowhisper_postvideos'
			);

			if ( ! $atts['post'] ) {
				return 'No post id was specified, to manage post associated videos.';
			}

			$htmlCode = '';

			$path = $atts['path'];
			if ( ! $path ) {
				$path = $options['vwls_archive_path'];
			}

			$channel = get_post( intval( $atts['post'] ) );

			if ( $_GET['playlist_upload'] ?? false ) {
				$htmlCode .= '<A class="ui button" href="' . remove_query_arg( 'playlist_upload' ) . '">' . __( 'Done Uploading Videos', 'video-share-vod' ) . ' </A>';
			} else {

				$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment"><h3 class="ui header">' . __( 'Manage Videos', 'video-share-vod' ) . ' </h3>';

				$prefix = $atts['prefix'];
				if ( $prefix == '_channel_' ) {
					$prefix = sanitize_file_name( $channel->post_title );
				}

				$htmlCode .= '<p>Available ' . esc_html( $channel->post_title ) . ' videos: ' . self::importFilesCount( $prefix, self::extensions_import(), $path ) . '</p>';

				$link  = add_query_arg( array( 'playlist_import' => sanitize_text_field( $channel->post_title ) ), get_permalink() );
				$link2 = add_query_arg( array( 'playlist_upload' => sanitize_text_field( $channel->post_title ) ), get_permalink() );

				$htmlCode .= ' <a class="ui button" href="' . $link . '">' . __( 'Import', 'video-share-vod' ) . ' </a> ';
				$htmlCode .= ' <a class="ui button" href="' . $link2 . '">' . __( 'Upload', 'video-share-vod' ) . ' </a> ';
				$htmlCode .= '</div>';
			}

			$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment"><h4 class="ui header">' . esc_html( $channel->post_name ) . ' - ' . __( 'Videos', 'video-share-vod' ) . ' </h4>';

			$htmlCode .= do_shortcode( '[videowhisper_videos menu="0" perpage="4" playlist="' . esc_attr( $channel->post_name ) . '"]' );
			$htmlCode .= '</div>';

			return $htmlCode;
		}


		static function videowhisper_postvideos_process( $atts ) {

			$atts = shortcode_atts(
				array(
					'post'      => '',
					'post_type' => '',
					'path'      => '',
					'prefix'    => '_channel_',
				),
				$atts,
				'videowhisper_postvideos_process'
			);

			$options = get_option( 'VWvideoShareOptions' );

			$path = $atts['path'];
			if ( ! $path ) {
				$path = $options['vwls_archive_path'];
			}

			self::importFilesClean();

			$htmlCode = '';

			if ( $channel_upload = sanitize_file_name( $_GET['playlist_upload'] ?? '' ) ) {
				$htmlCode .= do_shortcode( '[videowhisper_upload playlist="' . $channel_upload . '"]' );
			}

			if ( $channel_name = sanitize_file_name( $_GET['playlist_import'] ?? '' ) ) {

				$url = add_query_arg( array( 'playlist_import' => $channel_name ), self::getCurrentURL() );

				$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' form segment"><form id="videowhisperImport" name="videowhisperImport" action="' . $url . '" method="post">';

				$htmlCode .= '<h3>Import ' . $channel_name . ' Videos to Playlist</h3>';

				$prefix = $atts['prefix'];
				if ( $prefix == '_channel_' ) {
					$prefix = $channel_name;
				}

				$htmlCode .= self::importFilesSelect( $prefix, self::extensions_import(), $path );

				$htmlCode .= '<input type="hidden" name="playlist" id="playlist" value="' . $channel_name . '">';

				// same category as post
				if ( $atts['post'] ) {
					$postID = $atts['post'];
				} else { // search by name
					global $wpdb;
					if ( $atts['post_type'] ) {
						$cfilter = "AND post_type='" . $atts['post_type'] . "'";
					}
					$postID = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $channel_name . "' $cfilter LIMIT 0,1" );
				}

				if ( $postID ) {
					$cats = wp_get_post_categories( $postID );
					if ( count( $cats ) ) {
						$category = array_pop( $cats );
					}
					$htmlCode .= '<input type="hidden" name="category" id="category" value="' . $category . '">';
				}

				$htmlCode .= '<INPUT class="ui button" TYPE="submit" name="import" id="import" value="Import">';

				$htmlCode .= ' <INPUT class="ui button" TYPE="submit" name="delete" id="delete" value="Delete">';

				$htmlCode .= '</form></div>';
			}

			return $htmlCode;
		}


		// !permission functions

		// if any key matches any listing
		public static function inList( $keys, $data ) {
			if ( ! $keys ) {
				return 0;
			}

			$list = explode( ',', strtolower( trim( $data ) ) );

			foreach ( $keys as $key ) {
				foreach ( $list as $listing ) {
					if ( strtolower( trim( $key ) ) == trim( $listing ) ) {
						return 1;
					}
				}
			}

					return 0;
		}


		public static function hasPriviledge( $csv ) {
			// determines if user is in csv list (role, id, email)

			if ( strpos( $csv, 'Guest' ) !== false ) {
				return 1;
			}

			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();

				// access keys : roles, #id, email
				if ( $current_user ) {
					$userkeys   = $current_user->roles;
					$userkeys[] = $current_user->ID;
					$userkeys[] = $current_user->user_email;
				}

				if ( self::inList( $userkeys, $csv ) ) {
					return 1;
				}
			}

			return 0;
		}


		static function hasRole( $role ) {
			if ( ! is_user_logged_in() ) {
				return false;
			}

			$current_user = wp_get_current_user();

			$role = strtolower( $role );

			if ( in_array( $role, $current_user->roles ) ) {
				return true;
			} else {
				return false;
			}
		}


		static function getRoles() {
			if ( ! is_user_logged_in() ) {
				return 'None';
			}

			$current_user = wp_get_current_user();

			return implode( ', ', $current_user->roles );
		}


		static function arrayRand( $arrX ) {
			$randIndex = array_rand( $arrX );
			return $arrX[ $randIndex ];
		}


		static function poweredBy() {
			$options = get_option( 'VWvideoShareOptions' );

			$state = 'block';
			if ( ! $options['videowhisper'] ) {
				$state = 'none';
			}

			return '<div id="VideoWhisper" style="text-align: center; display: ' . $state . ';"><p>' . self::arrayRand( array( 'Developed with', 'Published with', 'Powered by', 'Added with', 'Managed by' ) ) . ' VideoWhisper <a href="https://videosharevod.com/">Video Share VOD ' . self::arrayRand( array( 'Turnkey Site Solution', 'Site Software', 'WordPress Plugin', 'Site Script', 'for WordPress', 'Turnkey Site Builder' ) ) . '</a>.</p></div>';
		}


		// get video path
		static function videoPath( $video_id, $type = 'auto' ) {

			$options = get_option( 'VWvideoShareOptions' );

			if ( $type == 'auto' ) {
				$isMobile = (bool) preg_match( '#\b(ip(hone|od|ad)|android|opera m(ob|in)i|windows (phone|ce)|blackberry|tablet|s(ymbian|eries60|amsung)|p(laybook|alm|rofile/midp|laystation portable)|nokia|fennec|htc[\-_]|mobile|up\.browser|[1-4][0-9]{2}x[1-4][0-9]{2})\b#i', $_SERVER['HTTP_USER_AGENT'] );

				if ( $isMobile ) {
					$type = 'html5-mobile';
				} else {
					$type = 'html5';
				}
			}

			$videoPath = get_post_meta( $video_id, 'video-source-file', true );
			$ext       = pathinfo( $videoPath, PATHINFO_EXTENSION );

			switch ( $type ) {
				case 'html5':
					// use conversion - high first
					$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
					if ( $videoAdaptive ) {
						$videoAlts = $videoAdaptive;
					} else {
						$videoAlts = array();
					}

					foreach ( array( 'high', 'mobile' ) as $frm ) {
						if ( is_array( $videoAlts ) ) {
							if ( array_key_exists( $frm, $videoAlts ) ) {
								if ( $alt = $videoAlts[ $frm ] ) {
									if ( file_exists( $alt['file'] ) ) {
										return $alt['file'];

									}
								}
							}
						}
					}

					if ( $options['originalBackup'] ) {
						if ( in_array( $ext, array( 'mp4' ) ) ) {
							return $videoPath;
						}
					}

					break;

				case 'html5-mobile':
					// use conversion - mobile first
					$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
					if ( $videoAdaptive ) {
						$videoAlts = $videoAdaptive;
					} else {
						$videoAlts = array();
					}

					foreach ( array( 'mobile', 'high' ) as $frm ) {
						if ( is_array( $videoAlts ) ) {
							if ( array_key_exists( $frm, $videoAlts ) ) {
								if ( $alt = $videoAlts[ $frm ] ) {
									if ( file_exists( $alt['file'] ) ) {
										return $alt['file'];

									}
								}
							}
						}
					}

					if ( $options['originalBackup'] ) {
						if ( in_array( $ext, array( 'mp4' ) ) ) {
							return $videoPath;
						}
					}

					break;

				case 'flash':
					// use conversion
					$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
					if ( $videoAdaptive ) {
						$videoAlts = $videoAdaptive;
					} else {
						$videoAlts = array();
					}

					foreach ( array( 'high', 'mobile' ) as $frm ) {
						if ( is_array( $videoAlts ) ) {
							if ( array_key_exists( $frm, $videoAlts ) ) {
								if ( $alt = $videoAlts[ $frm ] ) {
									if ( file_exists( $alt['file'] ) ) {
										return $alt['file'];

									}
								}
							}
						}
					}

					if ( $options['originalBackup'] ) {
						if ( in_array( $ext, self::extensions_import() ) ) {
								return $videoPath;
						}
					}

					break;
			}

			return 'Missing-videoPath-' . $video_id;
		}


		// embed a video from another site
		static function videowhisper_embed( $atts ) {
			$atts = shortcode_atts(
				array(
					'provider' => 'youtube',
					'width'    => '640',
					'height '  => '390',
					'videoId'  => 'oifAEZJYKvI',
				),
				$atts,
				'videowhisper_embed'
			);

			$width   = intval( $atts['width'] );
			$height  = intval( $atts['height'] );
			$videoId = sanitize_text_field( $atts['videoId'] );

			$htmlCode = '';

			switch ( $atts['provider'] ) {
				case 'youtube':
					// $htmlCode .= '<iframe id="player" type="text/html" width="'.$width.'" height="'.$height.'" src="https://www.youtube.com/embed/'.$videoId.'?enablejsapi=1&origin='. site_url() .'" frameborder="0"></iframe>';

					$htmlCode .= '<div id="player"></div>';

					$jsCode .= <<<EOT
      var tag = document.createElement('script');

      tag.src = "https://www.youtube.com/iframe_api";
      var firstScriptTag = document.getElementsByTagName('script')[0];
      firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

      var player;
      function onYouTubeIframeAPIReady() {
        player = new YT.Player('player', {
          width: '$width',
          height: '$height',
          videoId: '$videoId',
          events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
          }
        });
      }

      function onPlayerReady(event) {
        //event.target.playVideo();
      }

      var done = false;
      function onPlayerStateChange(event) {
        if (event.data == YT.PlayerState.PLAYING ) {

        }
      }

EOT;

					wp_add_inline_script( 'ytp-embed-js', $jsCode );

					break;
			}

			return $htmlCode;
		}


		static function videowhisper_player( $atts ) {

			$atts = shortcode_atts(
				array(
					'video'            => '0',
					'embed'            => '1',
					'player'           => '',
					'width'            => '',
					'height'           => '',
					'fallback_enabled' => '1',
				),
				$atts,
				'videowhisper_player'
			);

			$video_id = intval( $atts['video'] );
			if ( ! $video_id ) {
				return 'videowhisper_player: Missing video id = ' . $atts['video'];
			}

			$video = get_post( $video_id );
			if ( ! $video ) {
				return 'videowhisper_player: Video #' . $video_id . ' not found!';
			}

			$vWidth = $atts['width']; // string
			$pWidth = intval( $atts['width'] ); // force width if numeric

			$options = get_option( 'VWvideoShareOptions' );

			// VOD
			$deny = '';

			// global
			if ( ! self::hasPriviledge( $options['watchList'] ) ) {
				$deny = 'Your current membership does not allow watching videos.';
			}

			// by playlists
			$lists = wp_get_post_terms( $video_id, $options['custom_taxonomy'], array( 'fields' => 'names' ) );

			if ( ! is_array( $lists ) ) {
				if ( is_wp_error( $lists ) ) {
					echo 'Error: Can not retrieve "playlist" terms for video post: ' . esc_html( $lists->get_error_message() );
				}

				$lists = array();
			}

			// playlist role required?
			if ( $options['vod_role_playlist'] ) {
				foreach ( $lists as $key => $playlist ) {
					$lists[ $key ] = $playlist = strtolower( trim( $playlist ) );

					// is role
					if ( get_role( $playlist ) ) {
						$deny = 'This video requires special membership. Your current membership: ' . self::getRoles() . '.';
						if ( self::hasRole( $playlist ) ) {
							$deny = '';
							break;
						}
					}
				}
			}

			// exceptions
			if ( in_array( 'free', $lists ) ) {
				$deny = '';
			}

			if ( in_array( 'registered', $lists ) ) {
				if ( is_user_logged_in() ) {
					$deny = '';
				} else {
					$deny = 'Only registered users can watch this videos. Please login first.';
				}
			}

			if ( in_array( 'unpublished', $lists ) ) {
				$deny = 'This video has been unpublished.';
			}

			if ( $deny ) {
				$htmlCode .= str_replace( '#info#', $deny, html_entity_decode( stripslashes( $options['accessDenied'] ) ) );
				$htmlCode .= '<br>';
				$htmlCode .= do_shortcode( '[videowhisper_preview video="' . $video_id . '"]' ) . self::poweredBy();
				return $htmlCode;
			}

			// update stats
			$views = get_post_meta( $video_id, 'video-views', true );
			if ( ! $views ) {
				$views = 0;
			}
			$views++;
			update_post_meta( $video_id, 'video-views', $views );
			update_post_meta( $video_id, 'video-lastview', time() );

			// postProcess

			self::postProcess( $video_id );

			// snap
			$imagePath = get_post_meta( $video_id, 'video-snapshot', true );
			if ( $imagePath ) {
				if ( file_exists( $imagePath ) ) {
					$imageURL   = self::path2url( $imagePath );
					$posterVar  = '&poster=' . urlencode( $imageURL );
					$posterProp = ' poster="' . $imageURL . '"';
				} else {
					self::updatePostThumbnail( $video_id );
				}
			}

			// embed code?
			if ( $atts['embed'] ) {
				if ( self::hasPriviledge( $options['embedList'] ) ) {
					$showEmbed = 1;
				} else {
					$showEmbed = 0;
				}
			} else {
				$showEmbed = 0;
			}

				$player = $options['player_default'];

				// Detect special conditions browsers & devices
				$iPod    = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPod' );
				$iPhone  = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPhone' );
				$iPad    = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPad' );
				$Android = stripos( $_SERVER['HTTP_USER_AGENT'], 'Android' );

				$Safari = ( stripos( $_SERVER['HTTP_USER_AGENT'], 'Safari' ) && ! stripos( $_SERVER['HTTP_USER_AGENT'], 'Chrome' ) );

				$Mac     = stripos( $_SERVER['HTTP_USER_AGENT'], 'Mac OS' );
				$Firefox = stripos( $_SERVER['HTTP_USER_AGENT'], 'Firefox' );

			if ( $Mac && $Firefox ) {
				$player = $options['player_firefox_mac'];
			}

			if ( $Safari ) {
				$player = $options['player_safari'];
			}

			if ( $Android ) {
				$player = $options['player_android'];
			}

			if ( $iPod || $iPhone || $iPad ) {
				$player = $options['player_ios'];
			}

				// force a player from shortcode
			if ( $atts['player'] ) {
				$player = $atts['player'];
			}

			if ( $_GET['player'] ?? false && $options['allowDebug'] ) {
				$player = sanitize_file_name( $_GET['player'] );
			}

			if ( ! $player ) {
				$player = $options['player_default'];
			}

				// res
			if ( ! $vWidth ?? 0 ) {
				$vWidth = get_post_meta( $video_id, 'video-width', true );
			}
			if ( ! ($vHeight ?? 0) ) {
				$vHeight = get_post_meta( $video_id, 'video-height', true );
			}

			if ( ! $vWidth ?? 0 ) {
				$vWidth = $options['thumbWidth'];
			}
			if ( ! $vHeight ?? 0 ) {
				$vHeight = $options['thumbHeight'];
			}

			if ( strstr( $vWidth, '%' ) ) {
				$vHeight = '62%';
			} elseif ( $pWidth ) {
				$pHeight = $pWidth * $vHeight / $vWidth;
				$vWidth  = $pWidth;
				$vHeight = $pHeight;
			}

				$htmlCode = "<!--videoPlayer:$player|Mac$Mac|Ff$Firefox|iPh$iPhone|iPa$iPad|An$Android|Sa$Safari|vw$vWidth|vh$vHeight|pw$pWidth-->";

			switch ( $player ) {
				case 'strobe':
					$videoPath = get_post_meta( $video_id, 'video-source-file', true );
					$videoURL  = self::path2url( $videoPath );

					// $videoURLmbr =  site_url() . '/mbr/http/' . $video_id . '.f4m' ;

					$vast = self::adVAST( $atts['video'] );

					$player_url = plugin_dir_url( __FILE__ ) . 'strobe/StrobeMediaPlayback.swf';

					$flashvars = 'src=' . urlencode( $videoURL ) . '&autoPlay=false' . $posterVar;

					if ( $vast ) {
						// $flashvars .= '&plugin_mast=' .  urlencode(plugin_dir_url(__FILE__) . 'strobe/MASTPlugin.swf');
						// $flashvars .= '&src_mast_uri=' .  urlencode(plugin_dir_url(__FILE__) . 'strobe/mast_vast_2_wrapper.xml');
						// $flashvars .= 'src_namespace_mast=https://www.akamai.com/mast/1.0';

						// $flashvars .= '&src_namespace_mast=' .  urlencode(plugin_dir_url(__FILE__) . 'strobe/mast_vast_2_wrapper.xml');
					}

					$htmlCode .= '<object class="videoPlayer" width="' . $vWidth . '" height="' . $vHeight . '" type="application/x-shockwave-flash" data="' . $player_url . '"> <param name="movie" value="' . $player_url . '" /><param name="flashvars" value="' . $flashvars . '" /><param name="allowFullScreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="wmode" value="direct" /></object>';

					if ( $showEmbed ) {
						$embedCode  = htmlspecialchars( $htmlCode );
						$embedCode .= htmlspecialchars( '<br><a href="' . $videoURL . '">' . __( 'Download Video File', 'video-share-vod' ) . '</a> (' . __( 'right click and Save As..', 'video-share-vod' ) . ')' );

						$htmlCode .= '<br><h5>' . __( 'Embed Flash Video Code (Copy & Paste to your Page)', 'video-share-vod' ) . '</h5>';
						$htmlCode .= $embedCode;

					}
					break;

				case 'strobe-rtmp':
					$videoPath = get_post_meta( $video_id, 'video-source-file', true );
					$ext       = pathinfo( $videoPath, PATHINFO_EXTENSION );

					// use conversion if available
					$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
					if ( $videoAdaptive ) {
						$videoAlts = $videoAdaptive;
					} else {
						$videoAlts = array();
					}

					foreach ( array( 'high', 'mobile' ) as $frm ) {
						if ( is_array( $videoAlts ) ) {
							if ( array_key_exists( $frm, $videoAlts ) ) {
								if ( $alt = $videoAlts[ $frm ] ) {
									if ( file_exists( $alt['file'] ) ) {
										$ext    = pathinfo( $alt['file'], PATHINFO_EXTENSION );
										$stream = self::path2stream( $alt['file'] );
										break;
									}
								}
							}
						}
					};

					// user original
					if ( ! $stream ) {
						if ( in_array( $ext, array( 'flv', 'mp4', 'm4v' ) ) ) {
							// use source if compatible
							$stream = self::path2stream( $videoPath );
						}
					}

					if ( ! $stream ) {
						$htmlCode .= 'Adaptive format required but missing for this video!';
					}

					$videoRTMP = $options['rtmpServer'] . '/' . $stream;

					// mbr support
					$videoURLmbr = site_url() . '/mbr/rtmp/' . $video_id . '.f4m';

					if ( $stream ) {

						if ( $ext == 'mp4' ) {
							$stream = 'mp4:' . $stream;
						}

						$player_url = plugin_dir_url( __FILE__ ) . 'strobe/StrobeMediaPlayback.swf';
						$flashvars  = 'src=' . urlencode( $videoURLmbr ) . '&autoPlay=false' . $posterVar;

						$htmlCode .= '<object class="videoPlayer" width="' . $vWidth . '" height="' . $vHeight . '" type="application/x-shockwave-flash" data="' . $player_url . '"> <param name="movie" value="' . $player_url . '" /><param name="flashvars" value="' . $flashvars . '" /><param name="allowFullScreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="wmode" value="direct" /></object>';
					} else {
						$htmlCode .= 'Stream not found!';
					}

					if ( $showEmbed ) {
						$embedCode  = htmlspecialchars( $htmlCode );
						$embedCode .= htmlspecialchars( '<br><a href="' . $videoURL . '">Download Video File</a> (right click and Save As..)' );

						$htmlCode .= '<br><h5>Embed Flash Video Code (Copy & Paste to your Page)</h5>';
						$htmlCode .= $embedCode;
					}

					break;

				case 'html5':
					$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
					if ( $videoAdaptive ) {
						$videoAlts = $videoAdaptive;
					} else {
						$videoAlts = array();
					}

					foreach ( array( 'high', 'mobile' ) as $frm ) {
						if ( is_array( $videoAlts ) ) {
							if ( array_key_exists( $frm, $videoAlts ) ) {
								if ( $alt = $videoAlts[ $frm ] ) {
									if ( file_exists( $alt['file'] ) ) { // conversion must exist
										if ( filesize( $alt['file'] ) > 0 ) {
											$videoURL  = self::path2url( $alt['file'] );
											$videoType = $alt['type'];
											$width     = $alt['width'];
											$height    = $alt['height'];
											break;
										}
									}
								}
							}
						}
					};

					// backup: use original if mp4
					if ( ! ($videoURL ?? '') ) {
						$videoPath = get_post_meta( $video_id, 'video-source-file', true );
						$ext       = pathinfo( $videoPath, PATHINFO_EXTENSION );

						if ( $ext == 'mp4' ) {
							$videoURL  = self::path2url( $videoPath );
							$videoType = 'video/mp4';

							$width  = $vWidth;
							$height = $vHeight;

							$htmlCode .= '<!-- VideoShareVOD: HTML5 - Using original MP4 as no valid conversion is available, yet -->';
						} else {
							$htmlCode .= '<!-- VideoShareVOD: HTML5 - Original is not MP4 and no valid conversion is available, yet -->';
						}
					} else {
						$htmlCode .= "<!-- VideoShareVOD: HTML5 - $videoType / $videoURL / $width x $height -->";
					}

					if ( !isset($videoURL) ) {
						$htmlCode .= __( 'No HTML5 format (mp4) is currently available for this video! Conversion processing takes some time. If issue persists, contact site administrator because proper FFmpeg version & configuration, file permissions or process limits may not be available on current web host.', 'video-share-vod' );
						self::convertProcessQueue();
					}

					if ( isset( $videoURL ) ) {
						
						$imagePath = get_post_meta( $video_id, 'video-snapshot', true );
						if ( $imagePath )  if ( file_exists( $imagePath ) ) $imageURL   = self::path2url( $imagePath );
					
						$htmlCode .= do_shortcode( '[videowhisper_player_html source="' . $videoURL . '" source_type="' . $videoType . '" poster="' . $imageURL . '" width="' . $vWidth . '" height="' . $vHeight . '" id="' . $video_id . '" fallback_enabled ="' . $atts['fallback_enabled'] . '"]' );

						if ( $showEmbed ) {
							$htmlCode .= do_shortcode( '[videowhisper_embed_code source="' . $videoURL . '" source_type="' . $videoType . '" poster="' . $imageURL . '" width="' . $vWidth . '" height="' . $vHeight . '" id="' . $video_id . '"]' );
						}
					}

					break;

				case 'html5-mobile':
					// only mobile sources

					$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
					if ( $videoAdaptive ) {
						$videoAlts = $videoAdaptive;
					} else {
						$videoAlts = array();
					}

					if ( is_array( $videoAlts ) ) {
						if ( array_key_exists( 'mobile', $videoAlts ) ) {
							if ( $alt = $videoAlts['mobile'] ) {
								if ( file_exists( $alt['file'] ) ) {
									if ( filesize( $alt['file'] ) > 0 ) {
										$videoURL  = self::path2url( $alt['file'] );
										$videoType = $alt['type'];
										$width     = $alt['width'];
										$height    = $alt['height'];

									} else {
										$htmlCode .= 'Mobile adaptive format file is empty for this video: ' . $alt['file'];
									}
								} else {
									$htmlCode .= 'Mobile adaptive format file missing for this video: ' . $alt['file'];
								}
							} else {
												$htmlCode .= 'Mobile adaptive format missing for this video!';
							}
						} else {
												$htmlCode .= 'Mobile adaptive format key missing for this video and configured for playback.';
						}
					}

					if ( ( $videoURL ) ) {
						$htmlCode .= do_shortcode( '[videowhisper_player_html source="' . $videoURL . '" source_type="' . $videoType . '" poster="' . $imageURL . '" width="' . $vWidth . '" height="' . $vHeight . '" id="' . $video_id . '"]' );
					}

					break;

				case 'html5-hls':
					// use conversion

					$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
					if ( $videoAdaptive ) {
						$videoAlts = $videoAdaptive;
					} else {
						$videoAlts = array();
					}

					foreach ( array( 'high', 'mobile' ) as $frm ) {
						if ( is_array( $videoAlts ) ) {
							if ( array_key_exists( $frm, $videoAlts ) ) {
								if ( $alt = $videoAlts[ $frm ] ) {
									if ( file_exists( $alt['file'] ) ) {
										$stream    = self::path2stream( $alt['file'] );
										$videoType = $alt['type'];
										$width     = $alt['width'];
										$height    = $alt['height'];
										break;

									}
								}
							}
						}
					}

					if ( ! $stream ) {
						$htmlCode .= 'HLS: Mobile adaptive format missing for this video!';
					}

					if ( $stream ) {
						$stream = 'mp4:' . $stream;

						if ( $options['hlsServer'] ) {
							// hls
							$streamURL2   = $options['hlsServer'] . '_definst_/' . $stream . '/playlist.m3u8';
							$source_type2 = 'application/x-mpegURL';

							// mpeg
							$streamURL3   = $options['hlsServer'] . '_definst_/' . $stream . '/manifest.mpd';
							$source_type3 = 'application/dash+xml';
						} $htmlCode .= 'HLS: No HLS server configured!';

						// use static .ts segments if available
						if ( $options['convertHLS'] ) {
							$source      = $videoURLmbr = site_url() . '/mbr/hls/' . $video_id . '.m3u8';
							$source_type = $source_alt_type = 'application/x-mpegURL';
						} else {
							$source      = $streamURL2;
							$source_type = $source_type2;
						}

						$htmlCode .= do_shortcode( '[videowhisper_player_html source_alt="' . $videoURLmbr . '" source_alt_type="' . $source_alt_type . '" source="' . $source . '" source_type="' . $source_type . '" source2="' . $streamURL2 . '" source_type2="' . $source_type2 . '" source3="' . $streamURL3 . '" source_type3="' . $source_type3 . '"  poster="' . $imageURL . '" width="' . $vWidth . '" height="' . $vHeight . '" id="' . $video_id . '"]' );

					} else {
						$htmlCode .= 'HLS: Stream not found!';
					}

					break;

				default:
					$htmlCode .= 'Player not found:' . $player;

			}

				return $htmlCode . self::poweredBy();
		}


		// ! Custom Post Pages

		static function single_template( $single_template ) {

			if ( ! is_single() ) {
				return $single_template;
			}

			$options = get_option( 'VWvideoShareOptions' );

			$postID = get_the_ID();
			if ( get_post_type( $postID ) != $options['custom_post'] ) {
				return $single_template;
			}

			if ( $options['postTemplate'] == '+plugin' ) {
				$single_template_new = dirname( __FILE__ ) . '/template-video.php';
				if ( file_exists( $single_template_new ) ) {
					return $single_template_new;
				}
			}

			$single_template_new = get_template_directory() . '/' . $options['postTemplate'];

			if ( file_exists( $single_template_new ) ) {
				return $single_template_new;
			} else {
				return $single_template;
			}
		}



		static function the_content( $content ) {
			if ( ! is_single() ) {
				return $content;
			}
			$postID = get_the_ID();

			$options = get_option( 'VWvideoShareOptions' );

			if ( get_post_type( $postID ) != $options['custom_post'] ) {
				return $content;
			}

			if ( $options['videoWidth'] ) {
				$wCode = ' width="' . trim( $options['videoWidth'] ) . '"';
			} else {
				$wCode = '';
			}

			$addCode = '<div class="videowhisperPlayerContainer"><!--video_page:' . $postID . $wCode . '-->[videowhisper_player video="' . $postID . '" embed="1" ' . $wCode . ']</div>';

			// playlist
			global $wpdb;

			$terms = get_the_terms( $postID, $options['custom_taxonomy'] );

			if ( $terms && ! is_wp_error( $terms ) ) {

				$paymentRequired = '';
				$addCode        .= '<span class="w-actionbo">';
				foreach ( $terms as $term ) {

					if ( class_exists( 'VWliveStreaming' ) ) {
						if ( $options['vwls_channel'] ) {

							$channelID = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $term->slug . "' and post_type='channel' LIMIT 0,1" );

							if ( $channelID ) {
								$addCode .= ' <a title="' . __( 'Channel', 'video-share-vod' ) . ': ' . $term->name . '" class="videowhisper_playlist_channel button g-btn type_red size_small mk-button dark-color  mk-shortcode two-dimension small ui tag label" href="' . get_post_permalink( $channelID ) . '">' . $term->name . ' Channel</a> ';
								$current_user = wp_get_current_user();
								if ( ! VWliveStreaming::userPaidAccess( $current_user->ID, $channelID ) ) {
									return '<h4>Paid Channel Video</h4><p>This video is only accessible after paying for channel: <a class="button" href="' . get_permalink( $channelID ) . '">' . $term->slug . '</a></p> <h5>Paid Video Preview</h5>' . do_shortcode( '[videowhisper_preview video="' . $postID . '"]' );
								}
							}
						}
					}

					$addCode .= ' <a title="' . __( 'Playlist', 'video-share-vod' ) . ': ' . $term->name . '" class="videowhisper_playlist button g-btn type_secondary size_small mk-button dark-color  mk-shortcode two-dimension small ui tag label" href="' . get_term_link( $term->slug, $options['custom_taxonomy'] ) . '">' . $term->name . '</a> ';

				}
				$addCode .= '</span>';

			}

			$views = get_post_meta( $postID, 'video-views', true );
			if ( ! $views ) {
				$views = 0;
			}

			$addCode .= '<span class="videowhisper_views ui label pointing up">' . __( 'Video Views', 'video-share-vod' ) . ': ' . $views . '</span>';

			// ! show reviews
			if ( $options['rateStarReview'] ) {
				// tab : reviews
				if ( shortcode_exists( 'videowhisper_review' ) ) {
					$addCode .= '<h3>' . __( 'My Review', 'video-share-vod' ) . '</h3>' . do_shortcode( '[videowhisper_review content_type="video" post_id="' . $postID . '" content_id="' . $postID . '"]' );
				} else {
					$addCode .= 'Warning: shortcodes missing. Plugin <a target="_plugin" href="https://wordpress.org/plugins/rate-star-review/">Rate Star Review</a> should be installed and enabled or feature disabled.';
				}

				if ( shortcode_exists( 'videowhisper_reviews' ) ) {
					$addCode .= '<h3>' . __( 'Reviews', 'video-share-vod' ) . '</h3>' . do_shortcode( '[videowhisper_reviews post_id="' . $postID . '"]' );
				}
			}

			$addCode .= '<STYLE>' . html_entity_decode( stripslashes( $options['containerCSS'] ) ) . '</STYLE>';

			return $addCode . $content;
		}


		static function channel_page( $content ) {
			if ( ! is_single() ) {
				return $content;
			}
			$postID = get_the_ID();

			if ( get_post_type( $postID ) != 'channel' ) {
				return $content;
			}

			$channel = get_post( $postID );

			$addCode = '<div class="w-actionbox color_alternate"><h3>' . __( 'Channel Playlist', 'video-share-vod' ) . '</h3> ' . '[videowhisper_videos menu="0" playlist="' . $channel->post_name . '"] </div>';

			return $addCode . $content;

		}


		static function tvshow_page( $content ) {
			if ( ! is_single() ) {
				return $content;
			}

			$options = get_option( 'VWvideoShareOptions' );
			$postID  = get_the_ID();
			if ( get_post_type( $postID ) != $options['tvshows_slug'] ) {
				return $content;
			}

			$tvshow = get_post( $postID );

			$imageCode         = '';
			$post_thumbnail_id = get_post_thumbnail_id( $postID );
			if ( $post_thumbnail_id ) {
				$post_featured_image = wp_get_attachment_image_src( $post_thumbnail_id, 'featured_preview' );
			}

			if ( $post_featured_image ) {
				$imageCode = '<IMG style="padding-bottom: 20px; padding-right:20px" SRC ="' . $post_featured_image[0] . '" WIDTH="' . $post_featured_image[1] . '" HEIGHT="' . $post_featured_image[2] . '" ALIGN="LEFT">';
			}

			$addCode = '<br style="clear:both"><div class="w-actionbox color_alternate"><h3>' . __( 'Episodes', 'video-share-vod' ) . '</h3> ' . '[videowhisper_videos menu="0" playlist="' . $tvshow->post_name . '" select_category="0"] </div>';

			return $imageCode . $content . $addCode;

		}


		// ! Conversions


		// if $action was already done in last $expire, return false
		static function timeTo( $action, $expire = 60, $options = '' ) {
			if ( ! $options ) {
				$options = get_option( 'VWvideoShareOptions' );
			}

			$cleanNow = false;

			$ztime = time();

			$lastClean     = 0;
			$lastCleanFile = $options['uploadsPath'] . '/' . $action . '.txt';

			if ( ! file_exists( $dir = dirname( $lastCleanFile ) ) ) {
				mkdir( $dir );
			} elseif ( file_exists( $lastCleanFile ) ) {
				$lastClean = file_get_contents( $lastCleanFile );
			}

			if ( ! $lastClean ) {
				$cleanNow = true;
			} elseif ( $ztime - $lastClean > $expire ) {
				$cleanNow = true;
			}

			if ( $cleanNow ) {
				file_put_contents( $lastCleanFile, $ztime );
			}

				return $cleanNow;
		}


		static function timeToGet( $action, $options = '' ) {
			if ( ! $options ) {
				$options = get_option( 'VWvideoShareOptions' );
			}

			$lastCleanFile = $options['uploadsPath'] . '/' . $action . '.txt';

			if ( file_exists( $lastCleanFile ) ) {
				$lastClean = file_get_contents( $lastCleanFile );
			}

			return $lastClean;
		}


		static function optimumBitrate( $width, $height, $options ) {
			if ( ! $width ) {
				return 500;
			}
			if ( ! $height ) {
				return 500;
			}

			if ( ! $options ) {
				$options = get_option( 'VWvideoShareOptions' );
			}

			$bitrateHD = $options['bitrateHD'];
			if ( ! $bitrateHD ) {
				$bitrateHD = 8192;
			}

			$pixels = $width * $height;

			/*
			$bitrate = 500;
			if ($pixels >= 640*360) $bitrate = 1000;
			if ($pixels >= 854*480) $bitrate = 2500;
			if ($pixels >= 1280*720) $bitrate = 5000;
			if ($pixels >= 1920*1080) $bitrate = 8000;
			*/

			$bitrate = floor( $pixels * $bitrateHD / 2073600 );

			if ( $bitrate < 500 ) {
				return 500;
			}

			return $bitrate;
		}


		static function postProcess( $post_id ) {
			// retrieve current alternate videos
				$videoAdaptive = get_post_meta( $post_id, 'video-adaptive', true );
			if ( $videoAdaptive ) {
				$videoAlts = $videoAdaptive;
			} else {
				$videoAlts = array();
			}

				$needUpdate = 0;

			foreach ( $videoAlts as $key => $alt ) {
				if ( ! array_key_exists( 'postprocessed', $alt ) ) {
					if ( array_key_exists( 'attach', $alt ) ) {
						if ( $alt['attach'] ) {
							if ( file_exists( $alt['file'] ) ) {
								if ( filemtime( $alt['file'] ) ) {
									if ( time() - filemtime( $alt['file'] ) > 10 ) {
										// attach to media library
										if ( array_key_exists( 'attach', $alt ) ) {
											if ( $alt['attach'] ) {
														// filter wp_get_attachment_url for file outside uploads folder

														$filetype = wp_check_filetype( $alt['filename'] );

														$attachment_args = array(
															'guid'           => self::path2url( $alt['file'] ),
															'post_parent'    => $post_id,
															'post_mime_type' => $filetype['type'],
															'post_title'     => $alt['filename'],
															'post_content'   => '',
															'post_status'    => 'inherit',
														);

														// delete previous, if already present
														$attachments = get_posts( $attachment_args );
														if ( $attachments ) {
															foreach ( $attachments as $attachment ) {
																						wp_delete_attachment( $attachment->ID, true );
															}
														}

														$attach_id = wp_insert_attachment( $attachment_args, $alt['file'] );

														// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
														require_once ABSPATH . 'wp-admin/includes/media.php';
														require_once ABSPATH . 'wp-admin/includes/image.php';

														// Generate the metadata for the attachment, and update the database record.
														$attach_data = wp_generate_attachment_metadata( $attach_id, $alt['file'] );
														if ( ! empty( $attach_data ) ) {
															wp_update_attachment_metadata( $attach_id, $attach_data );
														}
											}
										}

										$videoAlts[ $key ]['postprocessed'] = 1;
										$needUpdate                         = 1;

									}
								}
							}
						}
					}
				}
			}

			if ( $needUpdate ) {
				update_post_meta( $post_id, 'video-adaptive', $videoAlts );
			}

		}


		static function convertVideo( $post_id, $overwrite = false, $verbose = false, $singleFormat = '' ) {

			if ( $verbose ) {
				echo '<BR>' . esc_html( "convertVideo($post_id, $overwrite , $verbose, $singleFormat)" );
			}

			if ( ! $post_id ) {
				return;
			}

			$options = get_option( 'VWvideoShareOptions' );

			if ( ! $options['convertMobile'] && ! $options['convertHigh'] && ! $overwrite ) {
				return;
			}

			$videoPath = get_post_meta( $post_id, 'video-source-file', true );
			$videoSize = intval( @filesize( $videoPath ) );

			if ( $verbose ) {
				echo '<BR>' . esc_html( "<BR>path: $videoPath size: $videoSize" );
			}

			if ( ! $videoPath ) {
				return;
			}

			$sourceExt = pathinfo( $videoPath, PATHINFO_EXTENSION );

			$videoWidthM  = $videoWidth = get_post_meta( $post_id, 'video-width', true );
			$videoHeightM = $videoHeight = get_post_meta( $post_id, 'video-height', true );
			if ( $verbose ) {
				echo "<BR>" . esc_html( "width, height: $videoWidth, $videoHeight" );
			}
			if ( $verbose ) {
				if ( ! $videoWidth ) {
					echo ' - Update Info';
				}
			}

			if ( ! $videoWidth ) {
				return; // no size detected yet
			}

				$videoCodec = get_post_meta( $post_id, 'video-codec-video', true );
			$audioCodec     = get_post_meta( $post_id, 'video-codec-audio', true );

			if ( $verbose ) {
				echo '<BR>' . esc_html( "codecs: $videoCodec, $audioCodec" );
			}

			if ( ! $videoCodec ) {
				return; // no codec detected yet
			}

			$videoBitrate = get_post_meta( $post_id, 'video-bitrate', true );

			$rotate = intval( get_post_meta( $post_id, 'video-rotate', true ) );

			// valid mp4 for html5 playback?
			if ( ( $sourceExt == 'mp4' ) && ( $videoCodec == 'h264' ) && ( $audioCodec = 'aac' ) ) {
				$isMP4 = 1;
			} else {
				$isMP4 = 0;
			}

			// convertWatermark
			$cmdW = '';
			if ( $options['convertWatermark'] ) {
				if ( file_exists( $options['convertWatermark'] ) ) {
					$cmdW = ' -i "' . $options['convertWatermark'] . '" -filter_complex "[0:v][1:v] overlay=' . $options['convertWatermarkPosition'] . '[outv]" -map "[outv]" -map 0:a ';
				}
			}

			// retrieve current alternate videos
			$videoAdaptive = get_post_meta( $post_id, 'video-adaptive', true );

			if ( $videoAdaptive ) {
				if ( is_array( $videoAdaptive ) ) {
					$videoAlts = $videoAdaptive;
				} else {
					$videoAlts = unserialize( $videoAdaptive );
				}
			} else {
				$videoAlts = array();
			}

				// conversion formats
				$formats = array();

			if ( ! $singleFormat || $singleFormat == 'preview' ) {
				// preview format

				if ( $options['convertPreview'] ) {

					$switchResolution = ( abs( $rotate ) / 90 ) % 2;
					if ( $switchResolution ) {
						$oW          = $videoWidth;
						$videoWidth  = $videoHeight;
						$videoHeight = $oW;
					}
					// preview : thumbnail size

					// crop input to fit thumb ratio
					$Aspect = $videoWidth / $videoHeight;
					if ( floatval( $options['thumbHeight'] ) ) {
						$newAspect = floatval( $options['thumbWidth'] ) / floatval( $options['thumbHeight'] );
					} else {
						$newAspect = 1.33;
					}

					$newX = 0;
					$newY = 0;

					if ( $newAspect > $Aspect ) {
						$newWidth  = $videoWidth;
						$newHeight = floor( $videoWidth / $newAspect );
						$newY      = floor( ( $videoHeight - $newHeight ) / 2 );

					} else // cut width
					{
						$newWidth  = floor( $videoHeight * $newAspect );
						$newX      = floor( ( $videoWidth - $newWidth ) / 2 );
						$newHeight = $videoHeight;
					}

					$cmdCrop = '';
					if ( $Aspect != $newAspect ) {
						$cmdCrop = 'crop=' . $newWidth . ':' . $newHeight . ':' . $newX . ':' . $newY . ', '; // crop only if different aspect
					}

					$videoWidthM  = $options['thumbWidth'];
					$videoHeightM = $options['thumbHeight'];
					$cmdFilters   = ' -vf "' . $cmdCrop . 'scale=' . $videoWidthM . ':' . $videoHeightM . '"'; // crop if needed, then scale remaining

					$newBitrate = self::optimumBitrate( $videoWidthM, $videoHeightM, $options );
					// no need to use more than before
					if ( $videoBitrate ) {
						if ( $newBitrate > $videoBitrate - 50 ) {
													$newBitrate = $videoBitrate - 50;
						}
					}

					$formats[] = array(
						// Mobile: MP4/H.264, Baseline profile, max 1024, for wide compatibility
						// -noautorotate
						'id'          => 'preview',
						'cmd'         => ' ' . $options['convertPreviewInput'] . $cmdFilters . ' -b:v ' . $newBitrate . 'k -maxrate ' . $newBitrate . 'k -bufsize ' . $newBitrate . 'k ' . $options['codecVideoPreview'] . ' ' . $options['codecAudioPreview'] . ' ' . $options['convertPreviewOutput'],
						'width'       => $videoWidthM,
						'height'      => $videoHeightM,
						'bitrate'     => $newBitrate + 64,
						'type'        => 'video/mp4',
						'extension'   => 'mp4',
						'noWatermark' => 1,
						'noHLS'       => 1,
						'attach'      => $options['attachPreview'],
					);
				} else {
					// delete old file if present
					$oldFile = $videoAlts['preview']['file'];
					if ( $oldFile ) {
						if ( file_exists( $oldFile ) ) {
													unlink( $oldFile );
						}
					}

					unset( $videoAlts['preview'] );
				}
			}

			if ( ! $singleFormat || $singleFormat == 'mobile' ) {
				// mobile format
				if ( $options['convertMobile'] == 2 || ( ! $isMP4 && $options['convertMobile'] == 1 ) ) {

					$videoWidthM  = $videoWidth;
					$videoHeightM = $videoHeight;

					if ( $videoWidthM * $videoHeightM > 1024 * 768 ) {
						$videoWidthM  = 1024;
						$videoHeightM = ceil( $videoHeight * 1024 / $videoWidth );
					}

					$newBitrate = 600;

					// no need to use more than before
					if ( $videoBitrate ) {
						if ( $newBitrate > $videoBitrate - 50 ) {
													$newBitrate = $videoBitrate - 50;
						}
					}

					$formats[] = array(
						// Mobile: MP4/H.264, Baseline profile, max 1024, for wide compatibility
						// -pix_fmt yuv420p -force_key_frames "expr:gte(t,n_forced*5)"
						'id'        => 'mobile',
						'cmd'       => $options['codecVideoMobile'] . ' -b:v ' . $newBitrate . 'k -maxrate ' . $newBitrate . 'k -bufsize ' . $newBitrate . 'k ' . ' -pix_fmt yuv420p ' . $options['codecAudioMobile'], // ' -s '.$videoWidthM.'x'.$videoHeightM. ' '
						'width'     => $videoWidthM,
						'height'    => $videoHeightM,
						'bitrate'   => $newBitrate + 64,
						'type'      => 'video/mp4',
						'extension' => 'mp4',
						'attach'    => 0,
					);
				} else {
					// delete old file if present
					if (isset($videoAlts['mobile'])) $oldFile = $videoAlts['mobile']['file']; else $oldFile = '';
					
					if ( $oldFile ) {
						if ( file_exists( $oldFile ) ) {
													unlink( $oldFile );
						}
					}

					unset( $videoAlts['mobile'] );
				}
			}

				// !high format

				// convertHigh
				// 0 = No
				// 1 = Auto
				// 2 = Auto + Bitrate
				// 3 = Always

			if ( ! $singleFormat || $singleFormat == 'high' ) {

				$newBitrate = self::optimumBitrate( $videoWidth, $videoHeight, $options );
				if ( $videoBitrate ) {
					if ( $newBitrate > $videoBitrate - 96 ) {
											$newBitrate = $videoBitrate - 96; // don't increase (also includes 96 sound)
					}
				}

				if ( $options['convertHigh'] == 3 || ( ! $isMP4 && $options['convertHigh'] >= 1 ) || ( ( $videoBitrate > $newBitrate ) && $options['convertHigh'] >= 2 ) ) {
					// high quality mp4
					// video
					// -force_key_frames "expr:gte(t,n_forced*5)"

					$cmdV = $options['codecVideoHigh'] . ' -b:v ' . $newBitrate . 'k -maxrate ' . $newBitrate . 'k -bufsize ' . $newBitrate . 'k -pix_fmt yuv420p ';

					// if h264 copy for auto or autobitrate if lower
					if ( $videoCodec == 'h264' && $options['convertHigh'] == 1 || ( $videoCodec == 'h264' && ( $videoBitrate <= $newBitrate ) && $options['convertHigh'] == 2 ) ) {
						$cmdV       = '-c:v copy';
						$newBitrate = $videoBitrate;
					}

					// audio
					$cmdA = $options['codecAudioHigh'];
					if ( $audioCodec == 'aac' && $options['convertHigh'] == 1 ) {
						$cmdA = '-c:a copy';
					}

					$formats[] = array(
						'id'        => 'high',
						'cmd'       => $cmdV . ' ' . $cmdA,
						'width'     => $videoWidth,
						'height'    => $videoHeight,
						'bitrate'   => intval( $newBitrate ) + 96,
						'type'      => 'video/mp4',
						'extension' => 'mp4',
						'attach'    => $options['attachHigh'],
					);

				} else {
					// delete old file if present
					$oldFile = $videoAlts['high']['file'];
					if ( $oldFile ) {
						if ( file_exists( $oldFile ) ) {
													unlink( $oldFile );
						}
					}

					unset( $videoAlts['high'] );
				}
			}

			if ( $verbose ) {
				echo '<h4>Conversion Formats</h4><pre>';
				var_dump( $formats );
				echo '</pre>';
			}

				// hook formats
				$formats = apply_filters( 'videosharevod_formats', $formats );

				$path = dirname( $videoPath );

				$cmdS  = ''; //single process (string)
				$cmdHS = array();

				// generate missing formats (or overwrite all)
			foreach ( $formats as $format ) {
				if ( ! isset( $videoAlts[ $format['id'] ] ) || $overwrite ) {
					if ( ! $singleFormat || $singleFormat == $format['id'] ) {
						$alt = $format;

						$newFile         = $post_id . '_' . $alt['id'] . '_' . md5( uniqid( $post_id . $alt['id'], true ) ) . '.' . $alt['extension'];
						$alt['filename'] = $newFile;
						$alt['file']     = $path . '/' . $newFile;

						// delete old file
						if (isset($videoAlts[ $format['id'] ]['file'])) $oldFile = $videoAlts[ $format['id'] ]['file']; else $oldFile = '';
						
						if ( $oldFile ) {
							if ( $oldFile != $alt['file'] ) {
								if ( file_exists( $oldFile ) ) {
									unlink( $oldFile );
								}
							}
						}

						$cmdS .= ' ' . $format['cmd'] . ' "' . ( $alt['file'] ?? '_none_' ) . '"';

						unset( $alt['cmd'] );

						// a process for each output file
						if ( ! $options['convertSingleProcess'] ) {
							$logPath = $path . '/' . $post_id . '-' . $alt['id'] . '.txt';
							$cmdPath = $path . '/' . $post_id . '-' . $alt['id'] . '-cmd.txt';

							$cmd = 'ulimit -t 7200;  time (' . sanitize_text_field( $options['ffmpegControl'] ) . ' ' . sanitize_text_field( $options['ffmpegPath'] ) . ' -y -threads 1 -i "' . $videoPath . '"' . ( isset($alt['noWatermark']) ? '' : $cmdW ) . ' ' . $format['cmd'] . ' "' . $alt['file'] . '") &>' . $logPath . ' &';

							self::convertAdd( $cmd );

							if ( $options['enable_exec'] ) {
								exec( escapeshellcmd( "echo '$cmd' >> $cmdPath" ), $output, $returnvalue );
							}

							$alt['log'] = $logPath;
							$alt['cmd'] = $cmd;

							if ( $verbose ) {
								echo '<BR>' . esc_html( " + Queue Conversion: $cmd" );
							}
						}

						// segment output for HLS
						if ( $options['convertHLS'] ) {

							// clean  previous segmentation
							if ( $alt['hls'] ?? false) {
								if ( strstr( $alt['hls'], $path ) ) {
									if ( file_exists( $alt['hls'] ) ) {
										$files = glob( $alt['hls'] . '/*' ); // get all file names
										foreach ( $files as $file ) { // iterate files
											if ( is_file( $file ) ) {
												unlink( $file ); // delete file
											}
										}
									}

									unlink( $alt['hls'] );
								}
							}

							if ( !isset($alt['noHLS']) || !$alt['noHLS'] ) {
								$newF = $path . '/' . $post_id . '_' . $alt['id'] . '_' . md5( uniqid( $post_id . $alt['id'], true ) );
								if ( ! file_exists( $newF ) ) {
									mkdir( $newF );
								}

								$alt['hls'] = $newF;

								$logPath = $path . '/' . $post_id . '-' . $alt['id'] . '-hls.txt';
								$cmdPath = $path . '/' . $post_id . '-' . $alt['id'] . '-hls-cmd.txt';

								$cmdH = 'ulimit -t 7200; ' . sanitize_text_field( $options['ffmpegControl'] ) . ' ' . sanitize_text_field( $options['ffmpegPath'] ) . ' -y -threads 1 -i "' . $alt['file'] . '" -flags -global_header -map 0 -f segment -segment_list "' . $alt['hls'] . '/index.m3u8" -segment_time 2 -segment_list_type m3u8 ' . $alt['hls'] . '/segment%05d.ts' . ' &>' . $logPath . ' &';

								// if input exists start now, otherwise start later
								if ( ! $options['convertSingleProcess'] && file_exists( $alt['file'] ) ) {
									self::convertAdd( $cmdH );
									if ( $verbose ) {
										echo '<BR>' . esc_html( " + Queue HLS 1: $cmdH" );
									}
								} else {
														$cmdHS[] = $cmdH;
								}

								if ( $options['enable_exec'] ) {
									exec( escapeshellcmd( "echo '$cmdH' >> $cmdPath" ), $output, $returnvalue );
								}

								$alt['logHLS'] = $logPath;
								$alt['cmdHLS'] = $cmdH;

							}
						}

						// update alternatives info
						$videoAlts[ $alt['id'] ] = $alt;

					}
				}
			}

				// run all conversions in a single process (one input, multiple outputs)
			if ( $options['convertSingleProcess'] && $cmdS ) {
				$logPath = $path . '/' . $post_id . '-convert.txt';
				$cmdPath = $path . '/' . $post_id . '-convert-cmd.txt';

				$cmd = 'ulimit -t 7200; ' . sanitize_text_field( $options['ffmpegControl'] ) . ' ' . sanitize_text_field( $options['ffmpegPath'] ) . ' -y -threads 1 -i ' . $videoPath . $cmdW . ' ' . $cmdS . ' &>' . $logPath . ' &';

				self::convertAdd( $cmd );
				if ( $options['enable_exec'] ) {
					exec( escapeshellcmd( "echo '$cmd' >> $cmdPath" ), $output, $returnvalue );
				}

				if ( $verbose ) {
					echo '<BR>' . esc_html( " + Queue Conversion SingleProcess: $cmdS" );
				}
			}

				// after conversions, do segmentations
			if ( $options['convertHLS'] ) {
				if ( $cmdHS ) {
					if ( count( $cmdHS ) ) {
						foreach ( $cmdHS as $cmdH ) {
							self::convertAdd( $cmdH );
							if ( $verbose ) {
									echo '<BR>' . esc_html( " + Queue HLS 2: $cmdH" );
							}
						}
					}
				}
			}

						// save adaptive formats records
					update_post_meta( $post_id, 'video-adaptive', $videoAlts );

					update_post_meta( $post_id, 'convert-queued', time() );

			if ( $verbose ) {
				echo '<h4>All Video Formats</h4><pre>';
				var_dump( $videoAlts );
				echo '</pre>';
			}

		}


		static function convertAdd( $cmd ) {
			$options = get_option( 'VWvideoShareOptions' );
			if ( ! $options['enable_exec'] ) {
				return;
			}

			if ( $options['enable_exec'] ) {
				if ( $options['convertInstant'] ) {
					exec( $cmd, $output, $returnvalue );
				} elseif ( ! strstr( $options['convertQueue'], $cmd ) ) {
					$options['convertQueue'] .= ( $options['convertQueue'] ? "\r\n" : '' ) . $cmd;
					update_option( 'VWvideoShareOptions', $options );
					self::convertProcessQueue();
				}
			}

		}


		static function varSave( $path, $var ) {
			file_put_contents( $path, serialize( $var ) );
		}


		static function varLoad( $path ) {
			if ( ! file_exists( $path ) ) {
				return false;
			}

			return unserialize( file_get_contents( $path ) );
		}


		static function convertProcessQueue( $verbose = 0 ) {
			$options = get_option( 'VWvideoShareOptions' );

			if ( ! $options['enable_exec'] ) {
				echo 'Execution is disabled!';
				return;
			}

			$minTime = 12;
			// not more often than $minTime s
			if ( ! self::timeTo( 'processQueue', $minTime, $options ) ) {
				if ( $verbose ) {
					echo 'Too fast to check again right now! Wait between checks at least (seconds): ' . esc_html( $minTime );
				}
				return;
			}

			if ( ! $options['convertQueue'] ) {
				if ( $verbose ) {
					echo 'Conversion queue is empty: No conversions need to be started!';
				}
				return;

			}
			// detect if ffmpeg is running
			$cmd = "ps aux | grep '" . sanitize_text_field( $options['ffmpegPath'] ) . ' -y -threads 1 -i' . "'";
			if ( $options['enable_exec'] ) {
				exec( $cmd, $output, $returnvalue );
			}

			$transcoding = 0;
			foreach ( $output as $line ) {
				if ( ! strstr( $line, 'grep' ) ) {
					$columns = preg_split( '/\s+/', $line );
					if ( $verbose ) {
						echo ( $transcoding ? '' : '<br>FFMPEG Active:' ) . '<br>' . esc_html( $line ) . '';
					}
					$transcoding = 1;
				}
			}

			if ( ! $transcoding ) {
				if ( $verbose ) {
					echo '<BR>No conversion process detected. System is available to start new conversions.';
				}

				// extract first command
				$cmds = explode( "\r\n", trim( $options['convertQueue'] ) );
				$cmd  = array_shift( $cmds );

				// save new queue
				$options['convertQueue'] = implode( "\r\n", $cmds );
				update_option( 'VWvideoShareOptions', $options );

				if ( $cmd ) {
					$output = '';
					if ( $options['enable_exec'] ) {
						exec( $cmd, $output, $returnvalue );
					}
					if ( $verbose ) {
						echo '<BR>Starting: ' . esc_html( $cmd );
						if ( is_array( $output ) ) {
							foreach ( $output as $line ) {
								echo '<br>' . esc_html( $line );
							}
						}
					}

					$lastConversion = array(
						'command'    => $cmd,
						'time'       => time(),
						'queueCount' => count( $cmds ),
					);

					$uploadsPath = $options['uploadsPath'];
					if ( ! file_exists( $uploadsPath ) ) {
						mkdir( $uploadsPath );
					}
					$lastConversionPath = $uploadsPath . '/lastConversion.txt';

					self::varSave( $lastConversionPath, $lastConversion );
				}
			}

		}


		// ! Snapshots
		static function generateSnapshots( $post_id, $verbose = false ) {
			
			if ( ! $post_id ) {
				return;
			}

			if ($verbose) echo '<br>generateSnapshots: ';


			$videoPath = sanitize_text_field( get_post_meta( $post_id, 'video-source-file', true ) );
			if ( ! $videoPath ) {
				return;
			}

			$options = get_option( 'VWvideoShareOptions' );
			if ( ! $options['enable_exec'] ) {
				return;
			}

			$path      = dirname( $videoPath );
			$imagePath = $path . '/' . $post_id . '.jpg';
			$thumbPath = $path . '/' . $post_id . '_thumb.jpg';
			$logPath   = $path . '/' . $post_id . '-snap.txt';
			$cmdPath   = $path . '/' . $post_id . '-snap-cmd.txt';

			$snapTime      = 9;
			$videoDuration = get_post_meta( $post_id, 'video-duration', true );
			if ( $videoDuration ) {
				if ( $videoDuration < $snapTime ) {
					$snapTime = floor( $videoDuration / 2 );
				}
			}
			
			if (!$videoDuration) $snapTime = 1;

			$cmd = sanitize_text_field( $options['ffmpegControl'] ) . ' ' . sanitize_text_field( $options['ffmpegPath'] ) . ' -y -threads 1 -i "' . $videoPath . '" -ss 00:00:0' . $snapTime . '.000 -f image2 -vframes 1 "' . $imagePath . '" >& ' . $logPath . ' &';

			if ( $options['enable_exec'] ) {
				exec( $cmd, $output, $returnvalue );
			}
			if ( $options['enable_exec'] ) {
				exec( escapeshellcmd( "echo '$cmd' >> $cmdPath" ), $output, $returnvalue );
			}
			
			if ($verbose) echo ' CMD: ' . esc_html($cmd) . ' RESULT:'; 
			
			update_post_meta( $post_id, 'video-snapshot', $imagePath );

			// probably source snap not ready, yet
			update_post_meta( $post_id, 'video-thumbnail', $thumbPath );

			sleep( 2 );// sleept 2 seconds to allow FFmpeg to finish
			
			if ($verbose) echo '<pre>' . esc_html(file_get_contents($logPath)) . '</pre>';
			
			list($width, $height) = self::generateThumbnail( $imagePath, $thumbPath );
			if ( $width ) {
				update_post_meta( $post_id, 'video-width', $width );
			}
			if ( $height ) {
				update_post_meta( $post_id, 'video-height', $height );
			}
		}


		static function generateThumbnail( $src, $dest ) {
			if ( ! file_exists( $src ) ) {
				return;
			}

			$options = get_option( 'VWvideoShareOptions' );

			// generate thumb
			$thumbWidth  = intval( $options['thumbWidth'] );
			$thumbHeight = intval( $options['thumbHeight'] );

			$srcImage = @imagecreatefromjpeg( $src );
			if ( ! $srcImage ) {
				return;
			}

			list($width, $height) = getimagesize( $src );
			
			if ( !$height ) return; //error getting source size

			$destImage = imagecreatetruecolor( $thumbWidth, $thumbHeight );
			
			// cut to fit thumb aspect
			
					$Aspect = $width / $height; //source aspect 
					if ( floatval( $options['thumbHeight'] ) ) {
						$newAspect = floatval( $options['thumbWidth'] ) / floatval( $options['thumbHeight'] );
					} else {
						$newAspect = 1.33;
					}

					$newX = 0;
					$newY = 0;

					if ( $newAspect > $Aspect ) { //cut height
						$newWidth  = $width;
						$newHeight = floor( $width / $newAspect );
						$newY      = floor( ( $height - $newHeight ) / 2 );

					} else // cut width
					{
						$newWidth  = floor( $height * $newAspect );
						$newX      = floor( ( $width - $newWidth ) / 2 );
						$newHeight = $height;
					}

			imagecopyresampled( $destImage, $srcImage, 0, 0, $newX, $newY, $thumbWidth, $thumbHeight, $newWidth, $newHeight );
			
			imagejpeg( $destImage, $dest, 95 );

			// return source dimensions
			return array( $width, $height );
		}


		static function updatePostThumbnail( $post_id, $overwrite = false, $verbose = false ) {
			$imagePath = get_post_meta( $post_id, 'video-snapshot', true );
			$thumbPath = get_post_meta( $post_id, 'video-thumbnail', true );

			if ( $verbose ) {
				echo '<BR>' . esc_html( "<br>Updating thumbnail ($post_id, $imagePath,  $thumbPath)" );
							}

			if ( ! $imagePath ) {
				self::generateSnapshots( $post_id, $verbose );
			} elseif ( ! file_exists( $imagePath ) ) {
				self::generateSnapshots( $post_id, $verbose );
			} elseif ( $overwrite ) {
				self::generateSnapshots( $post_id, $verbose );
			}

			if ( ! $thumbPath ) {
				self::generateSnapshots( $post_id, $verbose );
			} elseif ( $overwrite || ! file_exists( $thumbPath ) ) {
				list($width, $height) = self::generateThumbnail( $imagePath, $thumbPath );
			}

			if ( $verbose ) {
				if (!file_exists($imagePath)) echo '<BR>Image file does not exist: ' . esc_html($imagePath);
				if (!file_exists($thumbPath)) echo '<BR>Thumb file does not exist: ' . esc_html($thumbPath);

				echo '<BR>' . esc_html( "Video $post_id Thumbnail: $width x $height / $thumbPath" );
			}

			if ( $imagePath ) {

				// wp thumb from original image
				$attach_id = get_post_thumbnail_id( $post_id );

				if ( ! $attach_id ) {
					$wp_filetype = wp_check_filetype( basename( $imagePath ), null );

					$attachment = array(
						'guid'           => $imagePath,
						'post_mime_type' => $wp_filetype['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $imagePath, '.jpg' ) ),
						'post_content'   => '',
						'post_status'    => 'inherit',
					);

					// Insert the attachment.
					$attach_id = wp_insert_attachment( $attachment, $imagePath, $post_id );
					set_post_thumbnail( $post_id, $attach_id );

				} else // just update
					{
					$attach_id = get_post_thumbnail_id( $post_id );
					// $thumbPath = get_attached_file($attach_id);
				}

				// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
				require_once ABSPATH . 'wp-admin/includes/image.php';

				// Generate the metadata for the attachment, and update the database record.
				$attach_data = wp_generate_attachment_metadata( $attach_id, $imagePath );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				if ( $verbose ) {
					var_dump( $attach_data );
				}

				if ( $verbose ) {
					echo '<BR>' . esc_html( "Post $post_id Thumbnail: $attach_id - $imagePath" );
				}
				
			// BuddyPress Activity			
			$activity_id = get_post_meta( $post_id, 'bp_activity_id', true );
			if (!$activity_id) if ( function_exists( 'bp_activity_add' ) && ( $options['bpActivityInsert'] ?? false ) ) 
			{	
			$post = get_post($post_id);		
			$user = get_userdata( $post->post_author );
			$args = array(
				'action'       => '<a href="' . bp_members_get_user_url( $post->post_author ) . '">' . sanitize_text_field( $user->display_name ) . '</a> ' . __( 'posted a new video', 'paid-membership' ) . ': <a href="' . get_permalink( $post_id ) . '">' . esc_html( $post->post_title )  . '</a>  ',
				'component'    => 'videos-share-vod',
				'type'         => 'video_new',
				'primary_link' => get_permalink( $post_id ),
				'user_id'      => $post->post_author,
				'item_id'      => $post_id,
				'content'      => '<a href="' . get_permalink( $post_id ) . '">' . get_the_post_thumbnail( $post_id, array( 150, 150 ), array( 'class' => 'ui small rounded spaced image' ) ) . '</a>',
			);

			$activity_id = bp_activity_add( $args );			
			update_post_meta( $post_id, 'bp_activity_id', $activity_id );
			
				if ( $verbose ) 
				{
					echo "<br>BP activity post:";
					var_dump($args);
				}
			}
			
				if ( $verbose ) echo "<br>BP activity #" . $activity_id;

			}

			/*
			if ($width) update_post_meta( $post_id, 'video-width', $width );
			if ($height) update_post_meta( $post_id, 'video-height', $height );
			*/
			// do any conversions after detection
			self::convertVideo( $post_id );
		}


		static function updateVideo( $post_id, $overwrite = false, $verbose = false ) {

			// if ($verbose) ini_set('display_errors', 1);

			if ( $verbose ) {
				echo '<BR>' . esc_html( "<BR>updateVideo($post_id, $overwrite, $verbose)" );
			}

			if ( ! $post_id ) {
				return;
			}

			$videoPath = sanitize_text_field( get_post_meta( $post_id, 'video-source-file', true ) );
			if ( ! $videoPath ) {
				return; // source missing
			}

			$videoDuration = get_post_meta( $post_id, 'video-duration', true );
			if ( $videoDuration && ! $overwrite ) {
				return;
			}

			$options = get_option( 'VWvideoShareOptions' );
			if ( ! $options['enable_exec'] ) {
				return;
			}

			$path    = dirname( $videoPath );
			$logPath = $path . '/' . $post_id . '-dur.txt';
			$cmdPath = $path . '/' . $post_id . '-dur-cmd.txt';

			$cmd = sanitize_text_field( $options['ffmpegPath'] ) . ' -y -threads 1 -analyzeduration 4000000 -probesize 4000000 -i "' . $videoPath . '" 2>&1';

			if ( $options['enable_exec'] ) {
				exec( $cmd, $output, $returnvalue );
			}
			$info = implode( "\n", $output );
			if ( $options['enable_exec'] ) {
				exec( escapeshellcmd( "echo '$info' >> $logPath" ), $output, $returnvalue );
			}
			if ( $options['enable_exec'] ) {
				exec( escapeshellcmd( "echo '$cmd' >> $cmdPath" ), $output, $returnvalue );
			}

			if ( $verbose ) {
				echo '<BR>' . esc_html($cmd) . "<br><textarea rows='5' cols='100'>" . esc_textarea( $info ) . '</textarea>';
			}

			$matches = array();

			// duration
			preg_match( '/Duration: (.*?),/', $info, $matches );
			$duration = explode( ':', $matches[1] );

			if ( is_array($duration) && count($duration) )
			{
				$videoDuration = intval( $duration[0] ) * 3600 + intval( $duration[1] ) * 60 + intval( $duration[2] );
				if ( $videoDuration ) {
					update_post_meta( $post_id, 'video-duration', $videoDuration );
				}
				if ( $verbose ) {
					echo '<BR>' . esc_html( "videoDuration:$videoDuration" );
					}
			}

			// bitrate
			preg_match( '/bitrate:\s(?<bitrate>\d+)\skb\/s/', $info, $matches );
			$videoBitrate = $matches['bitrate'] ?? 0;


			if ( $videoBitrate ) {
				update_post_meta( $post_id, 'video-bitrate', $videoBitrate );
			}
			if ( $verbose ) {
				echo '<BR>' . esc_html( "videoBitrate:$videoBitrate" );
			}

			$videoSize = filesize( $videoPath );
			
			if ( $videoSize ) {
				update_post_meta( $post_id, 'video-source-size', $videoSize );
			}
			if ( $verbose ) {
				echo '<BR>' . esc_html( "videoSize:$videoSize" );
			}

			// get resolution

			if ( ! preg_match( '/Stream #(?:[0-9\.]+)(?:.*)\: Video: (?P<videocodec>.*) (?P<width>[0-9]*)x(?P<height>[0-9]*)/', $info, $matches ) ) {
				preg_match( '/Could not find codec parameters \(Video: (?P<videocodec>.*) (?P<width>[0-9]*)x(?P<height>[0-9]*)\)/', $info, $matches );
			}

			if ( ! empty( $matches['width'] ) && ! empty( $matches['height'] ) ) {
				$width  = $matches['width'];
				$height = $matches['height'];

				if ( $width ) {
					update_post_meta( $post_id, 'video-width', $width );
				}
				if ( $height ) {
					update_post_meta( $post_id, 'video-height', $height );
				}
			}

			// https://regex101.com

			$rotate = 0;
			preg_match( '/rotate\s+:\s*(?<rotate>\d+)\n/', $info, $matches );
			if ( array_key_exists( 'rotate', $matches ) ) {
				$rotate = $matches['rotate'];
			}
			if ( $verbose ) {
				echo '<br>Rotate: ' . esc_html( $rotate );
			}
			update_post_meta( $post_id, 'video-rotate', $rotate );

			/*
			if(strpos($info, 'Video:') !== false)
			{
				preg_match('/\s(?<width>\d+)[x](?<height>\d+)\s\[/', $info, $matches);
				$width = $matches['width'];
				$height = $matches['height'];

				if ($width) update_post_meta( $post_id, 'video-width', $width );
				if ($height) update_post_meta( $post_id, 'video-height', $height );
			}
			else if ($verbose) echo '<br>Missing "Video:" stream!';
			*/
			if ( $verbose ) {
				if ( $width && $height ) {
					echo '<BR>Resolution:' . esc_html( $width ) . ' x ' . esc_html( $height );
				} else {
					echo '<br>ERROR: Could not retrieve "Video:" stream parameters!';
				}
			}

				// codecs

				// video
			if ( ! preg_match( '/Stream #(?:[0-9\.]+)(?:.*)\: Video: (?P<videocodec>.*)/', $info, $matches ) ) {
				preg_match( '/Could not find codec parameters \(Video: (?P<videocodec>.*)/', $info, $matches );
			}
				list($videoCodec) = explode( ' ', $matches[1] );
			if ( $videoCodec ) {
				update_post_meta( $post_id, 'video-codec-video', strtolower( $videoCodec ) );
			}
			if ( $verbose ) {
				echo '<BR>' . esc_html( "videoCodec:$videoCodec" );
			}

			// audio
			$matches = array();
			if ( ! preg_match( '/Stream #(?:[0-9\.]+)(?:.*)\: Audio: (?P<audiocodec>.*)/', $info, $matches ) ) {
				preg_match( '/Could not find codec parameters \(Audio: (?P<audiocodec>.*)/', $info, $matches );
			}

		// var_dump($matches);
		if (isset($matches[1]))
		{
			list($videoCodecAudio) = explode( ' ', $matches[1] );
			if ( $videoCodecAudio ) {
				update_post_meta( $post_id, 'video-codec-audio', strtolower( $videoCodecAudio ) );
			}
		}

			// do any conversions after detection
			self::convertVideo( $post_id );

			return $videoDuration;
		}


		// ! VideoWhisper Live Streaming integration filters
		static function vw_ls_manage_channel( $val, $cid ) {
			return do_shortcode( "[videowhisper_postvideos post=\"$cid\"]" );
		}


		static function vw_ls_manage_channels_head( $val ) {
			return do_shortcode( '[videowhisper_postvideos_process post_type="channel"]' );
		}


		// ! Utility Functions
		static function humanDuration( $t, $f = ':' ) {
			// t = seconds, f = separator

			$t = intval( $t );
			return sprintf( '%02d%s%02d%s%02d', floor( $t / 3600 ), $f, floor( $t / 60 ) % 60, $f, $t % 60 );
		}


		static function humanAge( $t ) {
			$t = intval( $t );
			if ( $t < 30 ) {
				return 'NOW';
			}
			return sprintf( '%d%s%d%s%d%s', floor( $t / 86400 ), 'd ', floor( $t / 3600 ) % 24, 'h ', floor( $t / 60 ) % 60, 'm' ) . ' ago';
		}


		static function humanFilesize( $bytes, $decimals = 2 ) {
			$sz     = 'BKMGTP';
			$factor = floor( ( strlen( $bytes ) - 1 ) / 3 );
			return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . @$sz[ $factor ];
		}


		static function path2url( $file, $Protocol = 'http://' ) {

			if ( is_ssl() && $Protocol == 'http://' ) {
				$Protocol = 'https://';
			}

			$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? parse_url( get_site_url(), PHP_URL_HOST)); 
			$url = $Protocol . $host . '/';

			// on godaddy hosting uploads is in different folder like /var/www/clients/ ..
			$upload_dir = wp_upload_dir();
			if ( strstr( $file, $upload_dir['basedir'] ) ) {
				return $upload_dir['baseurl'] . str_replace( $upload_dir['basedir'], '', $file );
			}

			// folder under WP path
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( strstr( $file, get_home_path() ) ) {
				return site_url() . '/' . str_replace( get_home_path(), '', $file );
			}

			// under document root
			if ( strstr( $file, $_SERVER['DOCUMENT_ROOT'] ) ) {
				return $url . str_replace( $_SERVER['DOCUMENT_ROOT'], '', $file );
			}

			return $url . $file;
		}


		static function path2stream( $path, $withExtension = true ) {
			$options = get_option( 'VWvideoShareOptions' );

			$stream = substr( $path, strlen( $options['streamsPath'] ) );
			if ( $stream[0] == '/' ) {
				$stream = substr( $stream, 1 );
			}

			if ( ! file_exists( $options['streamsPath'] . '/' . $stream ) ) {
				return '';
			} elseif ( $withExtension ) {
				return $stream;
			} else {
				return pathinfo( $stream, PATHINFO_FILENAME );
			}
		}


		// ! import
		static function importFilesClean() {
			$options = get_option( 'VWvideoShareOptions' );

			if ( ! $options['importClean'] ) {
				return;
			}
			if ( ! file_exists( $options['importPath'] ) ) {
				return;
			}
			if ( ! file_exists( $options['uploadsPath'] ) ) {
				return;
			}

			// last cleanup
			$lastClean = 0;
			$lastFile = $options['uploadsPath'] . '/importCleanLast.txt';
			if ( file_exists( $lastFile ) ) {
				$lastClean = file_get_contents( $lastFile );
			}

			// cleaned recently
			if ( $lastClean > time() - 3600 ) {
				return;
			}

			// start clean

			// save time
			$myfile = fopen( $lastFile, 'w' );
			if ( ! $myfile ) {
				return;
			}
			fwrite( $myfile, time() );
			fclose( $myfile );

			// scan files and clean
			$folder         = $options['importPath'];
			$extensions     = self::extensions_video();
			$ignored        = array( '.', '..', '.svn', '.htaccess' );
			$expirationTime = time() - $options['importClean'] * 86400;

			$fileList = scandir( $folder );
			foreach ( $fileList as $fileName ) {
				if ( in_array( $fileName, $ignored ) ) {
					continue;
				}
				if ( ! in_array( strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) ), $extensions ) ) {
					continue;
				}

				if ( filemtime( $folder . $fileName ) < $expirationTime ) {
					unlink( $folder . $fileName );
				}
			}

		}


		static function importFilesSelect( $prefix, $extensions, $folder ) {
			if ( ! file_exists( $folder ) ) {
				return "<div class='error segment'>Video folder not found: $folder !</div>";
			}

			$folder = rtrim( $folder, '/' ) . '/';

			$options = self::getOptions();

			global $wp;

			self::importFilesClean();

			$htmlCode = '';

			// import files
			if ( $_POST['import'] ) {

					$importFiles = isset( $_POST['importFiles'] ) ? (array) $_POST['importFiles'] : array();
				if ( count( $importFiles ) ) {

					$owner = (int) $_POST['owner'];

					$current_user = wp_get_current_user();

					if ( ! $owner ) {
						$owner = $current_user->ID;
					} elseif ( $owner != $current_user->ID && ! current_user_can( 'edit_users' ) ) {
						return 'Only admin can import for others!';
					}

					// handle one or many playlists
					$playlist = sanitize_text_field( $_POST['playlist'] );

					// if csv sanitize as array
					if ( strpos( $playlist, ',' ) !== false ) {
						$playlists = explode( ',', $playlist );
						foreach ( $playlists as $key => $value ) {
							$playlists[ $key ] = sanitize_file_name( trim( $value ) );
						}
						$playlist = $playlists;
					}

					if ( ! $playlist ) {
						return 'Importing requires a playlist name!';
					}

					// handle one or many tags
					$tag = sanitize_text_field( $_POST['tag'] );

					// if csv sanitize as array
					if ( strpos( $tag, ',' ) !== false ) {
						$tags = explode( ',', $playlist );
						foreach ( $tags as $key => $value ) {
							$tags[ $key ] = sanitize_file_name( trim( $value ) );
						}
						$tag = $tags;
					}

					$description = sanitize_textarea_field( $_POST['description'] );

					$category = sanitize_file_name( $_POST['category'] );

					foreach ( $importFiles as $fileName ) {
						// $fileName = sanitize_file_name($fileName);
						$ext = pathinfo( $fileName, PATHINFO_EXTENSION );
						if ( ! $ztime = filemtime( $folder . $fileName ) ) {
							$ztime = time();
						}
						$videoName = basename( $fileName, '.' . $ext ) . ' ' . date( 'M j', $ztime );

						$htmlCode .= self::importFile( $folder . $fileName, $videoName, $owner, $playlist, $category, $tag, $description );
					}
				} else {
					$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' warning segment">No files selected to import!</div>';
				}
			}

			// delete files
			if ( $_POST['delete'] ?? false) {

				$importFiles = isset( $_POST['importFiles'] ) ? (array) $_POST['importFiles'] : array();
				if ( count( $importFiles ) ) {
					foreach ( $importFiles as $fileName ) {
						$htmlCode .= '<BR>Deleting ' . $fileName . ' ... ';
						$fileName  = sanitize_file_name( $fileName );
						if ( ! unlink( $folder . $fileName ) ) {
							$htmlCode .= 'Removing file failed!';
						} else {
							$htmlCode .= 'Success.';
						}
					}
				} else {
					$htmlCode .= '<div class="warning">No files selected to delete!</div>';
				}
			}

			// preview file
			if ( $preview_name = sanitize_text_field( $_GET['import_preview'] ?? '' ) ) {
				$ext = pathinfo( $preview_name, PATHINFO_EXTENSION );

				// $preview_name = sanitize_file_name($preview_name);
				$preview_url = self::path2url( $folder . $preview_name );

				$htmlCode .= '<h4>Preview ' . $preview_name . '</h4>';

				if ( in_array( $ext, array( 'flv', 'f4v' ) ) ) {
					$player_url = plugin_dir_url( __FILE__ ) . 'strobe/StrobeMediaPlayback.swf';
					$flashvars  = 'src=' . urlencode( $preview_url ) . '&autoPlay=true&verbose=true';

					$htmlCode .= '<object class="previewPlayer" width="480" height="360" type="application/x-shockwave-flash" data="' . $player_url . '"> <param name="movie" value="' . $player_url . '" /><param name="flashvars" value="' . $flashvars . '" /><param name="allowFullScreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="wmode" value="direct" /></object>';
				} elseif ( in_array( $ext, array( 'mp4', 'webm', 'ogg' ) ) ) {
					$htmlCode .= '<video src="' . $preview_url . '" controls>
  Your browser does not support the video tag.
</video>';

					if ( $ext != 'mp4' ) {
						if ( stripos( $_SERVER['HTTP_USER_AGENT'], 'Safari' ) !== false ) {
							$htmlCode .= '<br>* Video extension may not be supported in Safari. Try <a href="https://brave.com/vid857">Brave</a>, Chrome, Firefox.';
						}
					}
				} else {
					$htmlCode .= 'Extension not supported: ' . $ext;
				}
			}

			// list files
			$fileList = scandir( $folder );

			$ignored = array( '.', '..', '.svn', '.htaccess' );

			$prefixL = strlen( $prefix );

			// list by date
			$files = array();
			foreach ( $fileList as $fileName ) {

				if ( in_array( $fileName, $ignored ) ) {
					continue;
				}
				if ( ! in_array( strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) ), $extensions ) ) {
					continue;
				}
				if ( $prefixL ) {
					if ( substr( $fileName, 0, $prefixL ) != $prefix ) {
						continue;
					}
				}

					$files[ $fileName ] = filemtime( $folder . $fileName );
			}

			arsort( $files );
			$fileList = array_keys( $files );

			if ( ! $fileList ) {
				$htmlCode .= "<div class='warning'>No matching $prefix videos found at '$folder'!</div>";
			} else {
				$htmlCode .=
					'<script language="JavaScript">
function toggleImportBoxes(source, checkboxes_name) {
  var checkboxes = new Array();
  checkboxes = document.getElementsByName(checkboxes_name);
  for(var i=0, n=checkboxes.length; i<n; i++)
    checkboxes[i].checked = source.checked;
}
</script>';
				$htmlCode .= "<table class='widefat videowhisperTable'>";
				$htmlCode .= '<thead class=""><tr><th><input type="checkbox" onClick="toggleImportBoxes(this,\'importFiles[]\')" /></th><th>File Name</th><th>Preview</th><th>Size</th><th>Date</th></tr></thead>';

				$tN = 0;
				$tS = 0;

				foreach ( $fileList as $fileName ) {
					$fsize = filesize( $folder . $fileName );
					$tN++;
					$tS += $fsize;

					$htmlCode .= '<tr>';
					$htmlCode .= '<td><input type="checkbox" name="importFiles[]" value="' . $fileName . '"' . ( $fileName == $preview_name ? ' checked' : '' ) . '></td>';
					$htmlCode .= "<td>$fileName</td>";
					$htmlCode .= '<td>';
					$link      = add_query_arg(
						array(
							'playlist_import' => ( $prefix ? $prefix : '_channel_' ),
							'import_preview'  => $fileName,
						),
						self::getCurrentURLfull()
					);

					$htmlCode .= " <a class='ui small button' href='" . esc_url( $link ) . "'>Play</a> ";
					echo '</td>';
					$htmlCode .= '<td>' . self::humanFilesize( $fsize ) . '</td>';
					$htmlCode .= '<td>' . date( 'jS F Y H:i:s', filemtime( $folder . $fileName ) ) . '</td>';
					$htmlCode .= '</tr>';
				}
				$htmlCode .= '<tr><td></td><td>' . esc_html( $tN ) . ' files</td><td></td><td>' . self::humanFilesize( $tS ) . '</td><td></td></tr>';
				$htmlCode .= '</table>';

			}
			return $htmlCode;

		}


		static function importFilesCount( $prefix, $extensions, $folder ) {
			if ( ! file_exists( $folder ) ) {
				return '';
			}

			$folder = rtrim( $folder, '/' ) . '/';

			$kS = $k = 0;

			$fileList = scandir( $folder );

			$ignored = array( '.', '..', '.svn', '.htaccess' );

			$prefixL = strlen( $prefix );

			foreach ( $fileList as $fileName ) {

				if ( in_array( $fileName, $ignored ) ) {
					continue;
				}
				if ( ! in_array( strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) ), $extensions ) ) {
					continue;
				}
				if ( $prefixL ) {
					if ( substr( $fileName, 0, $prefixL ) != $prefix ) {
						continue;
					}
				}

					$k++;
				$kS += filesize( $folder . $fileName );
			}

			return $k . ' (' . self::humanFilesize( $kS ) . ')';
		}


		public static function importFile( $path, $name, $owner, $playlists, $category = '', $tags = '', $description = '', &$post_id = null, $guest = false) {
			
			if (!$guest)
			{
				if ( $owner == '' ) {
					return '<br>Missing owner! Specify owner id or use guest mode.';
				}
				if ( ! $playlists ) {
					return '<br>Missing playlists!';
				}
			}

			if (!$owner) $owner = 0;
			if (!$playlists) $playlists = 'Guest';

			$options = get_option( 'VWvideoShareOptions' );
			if ( ! self::hasPriviledge( $options['shareList'] ) ) {
				return '<br>' . __( 'You do not have permissions to share videos!', 'video-share-vod' );
			}

			if ( ! file_exists( $path ) ) {
				return "<br>$name: File missing: $path";
			}

			// handle one or many playlists
			if ( is_array( $playlists ) ) {
				$playlist = sanitize_file_name( current( $playlists ) );
			} else {
				$playlist = sanitize_file_name( $playlists );
			}

			if ( ! $playlist ) {
				return '<br>Missing playlist!';
			}

			$htmlCode = '';

			// uploads/owner/playlist/src/file
			$dir = $options['uploadsPath'];
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			$dir .= '/' . $owner;
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			$dir .= '/' . $playlist;
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			// $dir .= '/src';
			// if (!file_exists($dir)) mkdir($dir);

			if ( ! $ztime = filemtime( $path ) ) {
				$ztime = time();
			}

			$ext     = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
			$newFile = md5( uniqid( $owner, true ) ) . '.' . $ext;
			$newPath = $dir . '/' . $newFile;

			// $htmlCode .= "<br>Importing $name as $newFile ... ";

			if ( $options['deleteOnImport'] ) {
				if ( ! rename( $path, $newPath ) ) {
					$htmlCode .= 'Rename failed. Trying copy ...';
					if ( ! copy( $path, $newPath ) ) {
						$htmlCode .= 'Copy also failed. Import failed!';
						return $htmlCode;
					}
					// else $htmlCode .= 'Copy success ...';

					if ( ! unlink( $path ) ) {
						$htmlCode .= 'Removing original file failed!';
					}
				}
			} else {
				// just copy
				if ( ! copy( $path, $newPath ) ) {
					$htmlCode .= 'Copy failed. Import failed!';
					return $htmlCode;
				}
			}

			// $htmlCode .= 'Moved source file ...';
			$timeZone = get_option( 'gmt_offset' ) * 3600;
			$postdate = date( 'Y-m-d H:i:s', $ztime + $timeZone );

			$post = array(
				'post_name'    => $name,
				'post_title'   => $name,
				'post_author'  => $owner,
				'post_type'    => $options['custom_post'],
				'post_status'  => 'publish',
				'post_date'    => $postdate,
				'post_content' => $description,
			);

			if ( ! self::hasPriviledge( $options['publishList'] ) ) {
				$post['post_status'] = 'pending';
			}

			$post_id = wp_insert_post( $post );
			if ( $post_id ) {
				update_post_meta( $post_id, 'video-source-file', $newPath );
				update_post_meta( $post_id, 'video-views', 0 );
				update_post_meta( $post_id, 'video-lastview', 0 );

				wp_set_object_terms( $post_id, $playlists, $options['custom_taxonomy'] );

				if ( $tags ) {
					wp_set_object_terms( $post_id, $tags, 'post_tag' );
				}

				if ( $category ) {
					wp_set_post_categories( $post_id, array( $category ) );
				}

				self::updateVideo( $post_id, true );
				self::updatePostThumbnail( $post_id, true );
				// VWvideoShare::convertVideo($post_id, true);

					// output
				if ( $post['post_status'] == 'pending' ) {
					$htmlCode .= __( 'Video was submitted and is pending approval.', 'video-share-vod' );
				} else {
					
					$htmlCode .= '<br>' . __( 'Video was published', 'video-share-vod' ) . ': <a href=' . get_post_permalink( $post_id ) . '> #' . $post_id . ' ' . $name . '</a> <br><small>' . __( 'Snapshot, video info and thumbnail will be processed shortly.', 'video-share-vod' ) . '</small><br><b>' . $name . '</b><br><i>' . $description . '</i>';
					
				}

				// import original to media library
				if ( $options['originalLibrary'] && ! $options['deleteOnImport'] ) {
						$filetype = wp_check_filetype( $newPath );

						$attachment_args = array(
							'guid'           => self::path2url( $newPath ),
							'post_parent'    => $post_id,
							'post_mime_type' => $filetype['type'],
							'post_title'     => $name,
							'post_content'   => '',
							'post_status'    => 'inherit',
						);

						$attach_id = wp_insert_attachment( $attachment_args, $newPath );

						// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
						require_once(ABSPATH . 'wp-admin/includes/image.php');
						require_once( ABSPATH . 'wp-admin/includes/media.php' );

						// Generate the metadata for the attachment, and update the database record.
						$attach_data = wp_generate_attachment_metadata( $attach_id, $newPath );
						if ( ! empty( $attach_data ) ) {
							wp_update_attachment_metadata( $attach_id, $attach_data );
						}
				}
			} else {
				$htmlCode .= '<br>Video post creation failed!';
			}

			return $htmlCode;
		}


		// ! Admin Area
		/* Meta box setup function. */
		static function post_meta_boxes_setup() {
			/* Add meta boxes on the 'add_meta_boxes' hook. */
			add_action( 'add_meta_boxes', array( 'VWvideoShare', 'add_post_meta_boxes' ) );

			/* Update post meta on the 'save_post' hook. */
			add_action( 'save_post', array( 'VWvideoShare', 'save_post_meta' ), 10, 2 );

		}


		/* Create one or more meta boxes to be displayed on the post editor screen. */
		static function add_post_meta_boxes() {

			add_meta_box(
				'video-post',      // Unique ID
				esc_html__( 'Video Post' ),    // Title
				array( 'VWvideoShare', 'post_meta_box' ),   // Callback function
				'video',         // Admin page (or post type)
				'normal',         // Context
				'high'         // Priority
			);

		}


		/* Display the post meta box. */
		static function post_meta_box( $object, $box ) {
			?>
 <p>This is a special post type: In backend, videos can be uploaded from Video Share VOD > Upload menu or imported from Video Share VOD > Import menu, if files are already on server.
	 <br>Videos can also be added (uploaded or imported) from frontend if sections are setup (see <a href="https://videosharevod.com/features/quick-start-tutorial/">Setup Tutorial</a> and <a href="admin.php?page=video-share-docs">Plugin Documentation</a>).
	 <br>Custom fields are automatically generated and updated by the plugin. Do not alter custom fields manually as this can result in unexpected behaviour.
  </p>
			<?php

		}


		static function save_post_meta( $post_id, $post ) {
			$options = get_option( 'VWvideoShareOptions' );

			// tv show : setup seasons
			if ( $post->post_type == $options['tvshows_slug'] ) {
				$meta_value = get_post_meta( $post_id, 'tvshow-seasons', true );
				if ( ! $meta_value ) {
					update_post_meta( $post_id, 'tvshow-seasons', '1' );
					$meta_value = 1;
				}

				if ( $post->post_title ) {
					if ( ! term_exists( sanitize_text_field( $post->post_title ), sanitize_text_field( $options['custom_taxonomy'] ) ) ) {
						$args = array( 'description' => 'TV Show: ' . sanitize_text_field( $post->post_title ) );
						wp_insert_term( sanitize_text_field( $post->post_title ), sanitize_text_field( $options['custom_taxonomy'] ) );
					}

					$term = get_term_by( 'name', sanitize_text_field( $post->post_title ), sanitize_text_field( $options['custom_taxonomy'] ) );

					if ( $meta_value > 1 ) {
						for ( $i = 1; $i <= $meta_value; $i++ ) {
							if ( ! term_exists( sanitize_text_field( $post->post_title ) . ' ' . $i, sanitize_text_field( $options['custom_taxonomy'] ) ) ) {
								$args = array(
									'parent'      => $term->term_id,
									'description' => 'TV Show: ' . sanitize_text_field( $post->post_title ),
								);

								wp_insert_term( sanitize_text_field( $post->post_title ) . ' ' . $i, sanitize_text_field( $options['custom_taxonomy'] ), $args );

							}
						}
					}
				}
			}

		}


		static function getCurrentURL() {
			//without params
			$currentURL  = ( @$_SERVER['HTTPS'] == 'on' ) ? 'https://' : 'http://';
			$currentURL .= $_SERVER['SERVER_NAME'];

			if ( $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ) {
				$currentURL .= ':' . $_SERVER['SERVER_PORT'];
			}

			$uri_parts = explode( '?', $_SERVER['REQUEST_URI'], 2 );

			$currentURL .= $uri_parts[0];
			return $currentURL;
		}

		static function getCurrentURLfull() {
			//with params
			$currentURL  = ( @$_SERVER['HTTPS'] == 'on' ) ? 'https://' : 'http://';
			$currentURL .= $_SERVER['SERVER_NAME'];

			if ( $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ) {
				$currentURL .= ':' . $_SERVER['SERVER_PORT'];
			}

			$currentURL .= $_SERVER['REQUEST_URI'];

			return $currentURL;
		}


		static function videoFilePath( $video_id, $format ) {
			$videoAdaptive = get_post_meta( $video_id, 'video-adaptive', true );
			if ( $videoAdaptive ) {
				$videoAlts = $videoAdaptive;
			} else {
				$videoAlts = array();
			}

			if ( $alt = $videoAlts[ $format ] ) {
				if ( file_exists( $alt['file'] ) ) {
					return $alt['file'];
				}
			}
			return '';
		}


		static function adminExport() {
			$options = self::setupOptions();

			if ( isset( $_POST ) ) {
				foreach ( $options as $key => $value ) {
					if ( isset( $_POST[ $key ] ) ) {
						$options[ $key ] = trim( sanitize_textarea_field( $_POST[ $key ] ) );
					}
				}
					update_option( 'VWvideoShareOptions', $options );
			}

			$this_page = add_query_arg( array( 'page' => 'video-share-export' ), self::getCurrentURL() );

			screen_icon();
			?>
<h2>Export Videos to Folder</h2>
	Use this tool to mass export and download videos.

<BR><a class="button" href="<?php echo add_query_arg( array( 'export' => 'current' ), $this_page ); ?>">Export Current</A> Export list of videos from their current location.
<BR><a class="button" href="<?php echo add_query_arg( array( 'export' => 'download' ), $this_page ); ?>">Export to Download Folder</A> Export after creating links to all videos in a download folder (also uses video name).
<BR><a class="button" href="<?php echo add_query_arg( array( 'export' => 'download-category' ), $this_page ); ?>">Export to Download Folder by Category</A> Export after creating links to all videos in a download folder organised by category (sub folders).

			<?php

			$export = sanitize_file_name( $_GET['export'] );

			if ( $export ) {
				echo '<h3>Exporting Current File List</h3>';

				if ( $export == 'download' ) {
					if ( ! file_exists( $options['exportPath'] ) ) {
						mkdir( $options['exportPath'], 0777 );
					}
				}

				$args = array(
					'post_type'      => sanitize_text_field( $options['custom_post'] ),
					'posts_per_page' => sanitize_text_field( $options['exportCount'] ),
					'order'          => 'DESC',
					'orderby'        => 'post_date',
					'post_status'    => 'any',
				);

				$postslist = get_posts( $args );

				$codePaths = '';
				$codeUrls  = '';
				foreach ( $postslist as $video ) {
					$noVideos ++;
					$path = self::videoFilePath( $video->ID, 'high' );

					if ( $path ) {
						$noPaths ++;
						if ( file_exists( $path ) ) {
							if ( filesize( $path ) > 0 ) {
								$noFiles ++;
								switch ( $export ) {
									case 'current':
										$codePaths .= "\r\n" . $path;
										$codeUrls  .= "\r\n" . self::path2url( $path );
										break;

									case 'download':
										$newName = sanitize_file_name( $video->post_title );
										if ( ! $newName ) {
											$newName = $video->ID;
										}

										if ( $newName ) {
											$noLinks++;
											$newName .= '.mp4';
											$newPath  = $options['exportPath'] . $newName;

											// remove previous link if exists
											if ( file_exists( $newPath ) ) {
												if ( is_link( $newPath ) ) {
													unlink( $newPath );
												}
											}

											if ( ! file_exists( $newPath ) ) {
												link( $path, $newPath );
											}

											$codePaths .= "\r\n" . $newPath;
											$codeUrls  .= "\r\n" . self::path2url( $newPath );
										}
										break;

									case 'download-category':
										$newName = sanitize_file_name( $video->post_title );
										if ( ! $newName ) {
											$newName = $video->ID;
										}

										$categories = wp_get_post_categories( $video->ID, array( 'fields' => 'names' ) );

										if ( ! $categories ) {
											$categories = array( '_NA' );
										}

										foreach ( $categories as $category ) {
											if ( $category ) {
												$noLinks ++;
												$catLinks[ $category ]++;

												$newName .= '.mp4';
												$newPath  = $options['exportPath'] . $category . '/' . $newName;

												if ( ! file_exists( $options['exportPath'] . $category ) ) {
													mkdir( $options['exportPath'] . $category, 0777 );
												}

												// remove previous link if exists
												if ( file_exists( $newPath ) ) {
													if ( is_link( $newPath ) ) {
														unlink( $newPath );
													}
												}
												if ( ! file_exists( $newPath ) ) {
													link( $path, $newPath );
												}

												$codePaths .= "\r\n" . $newPath;
												$codeUrls  .= "\r\n" . self::path2url( $newPath );
											}
										}

										break;
								}
							}
						}
					}
				}

				echo 'Video Paths (download by FTP, scripts, terminal):<br><textarea cols="150" rows="5">' . esc_textarea( $codePaths ) . '</textarea>';
				echo '<br>Video URLs (use with a download manager):<br><textarea cols="150" rows="5">' . esc_textarea( $codeUrls ) . '</textarea>';
				echo '<br>Videos: ' . esc_html( $noVideos ) . '<br>Paths (conversion queued): ' . esc_html( $noPaths ) . '<br>Files (conversion at least started, size>0):' . esc_html( $noFiles ) . '<br>*Only existing files (generated by conversion) are listed.';

				if ( $catLinks ) {
					echo '<br>By category:';
					foreach ( $catLinks as $cat => $no ) {
						echo '<br>' . esc_html( $cat ) . ': ' . esc_html( $no );
					}
				}
			}

			?>
<h3>Export Settings</h3>
<form method="post" action="<?php echo esc_url( $this_page ); ?>">

<h4>Maximum Videos</h4>
Maximum video number to export
<br><input name="exportCount" type="text" id="exportCount" size="20" maxlength="32" value="<?php echo esc_attr( $options['exportCount'] ); ?>"/>
<br>Ex: 500


<h4>Videos Offset</h4>
Where to start listing from (if exporting in parts)
<br><input name="exportOffset" type="text" id="exportOffset" size="20" maxlength="32" value="<?php echo esc_attr( $options['exportOffset'] ); ?>"/>
<br>Ex: 0

<h4>Download Path</h4>
Server path where to create video file links for easy download
<br><input name="exportPath" type="text" id="exportPath" size="100" maxlength="256" value="<?php echo esc_attr( $options['exportPath'] ); ?>"/>
<br>Ex: /home/[your-account]/public_html/download/


			<?php submit_button(); ?>
</form>


			<?php

		}



		static function adminLiveStreaming() {
			 $options = get_option( 'VWvideoShareOptions' );

			screen_icon();
			?>

<h3>Import Archived Channel Videos</h3>
This allows importing stream archives to playlist of their video channel. <a target="_blank" href="https://videosharevod.com/features/live-streaming/">About Live Streaming...</a><br>
			<?php

			if ( $channel_name = sanitize_file_name( $_GET['playlist_import'] ) ) {

				$url = add_query_arg( array( 'playlist_import' => $channel_name ), admin_url( 'admin.php?page=video-share-ls' ) );

				echo '<form action="' . esc_url( $url ) . '" method="post">';
				echo '<h4>Import Archived Videos to Playlist <b>' . esc_html( $channel_name ) . '</b></h4>';
				echo self::importFilesSelect( $channel_name, self::extensions_import(), esc_attr( $options['vwls_archive_path'] ) );
				echo '<INPUT class="button button-primary" TYPE="submit" name="import" id="import" value="Import">';
				global $wpdb;
				$postID = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . sanitize_file_name( $channel_name ) . "' and post_type='channel' LIMIT 0,1" );

				if ( $postID ) {
					$channel = get_post( $postID );
					$owner   = $channel->post_author;

					$cats = wp_get_post_categories( $postID );
					if ( count( $cats ) ) {
						$category = array_pop( $cats );
					}
				} else {
					$current_user = wp_get_current_user();
					$userName     = sanitize_text_field( $options['userName'] );
					if ( ! $userName ) {
						$userName = 'user_nicename';
					}
					$username = $current_user->$userName;

					$owner = $current_user->ID;
					echo ' as ' . esc_html( $username );
				}

				echo '<input type="hidden" name="playlist" id="playlist" value="' . esc_attr( $channel_name ) . '">';
				echo '<input type="hidden" name="owner" id="owner" value="' . esc_attr( $owner ) . '">';
				echo '<input type="hidden" name="category" id="category" value="' . esc_attr( $category ) . '">';

				echo ' <INPUT class="button button-primary" TYPE="submit" name="delete" id="delete" value="Delete">';

				echo '</form>';
			}

			echo '<h4>Recent Activity</h4>';

			function format_age( $t ) {
				if ( $t < 30 ) {
					return 'LIVE';
				}
				return sprintf( '%d%s%d%s%d%s', floor( $t / 86400 ), 'd ', ( $t / 3600 ) % 24, 'h ', ( $t / 60 ) % 60, 'm' );
			}

			global $wpdb;
			$table_name3 = $wpdb->prefix . 'vw_lsrooms';
			$items       = $wpdb->get_results( "SELECT * FROM `$table_name3` ORDER BY edate DESC LIMIT 0, 100" );
			echo "<table class='wp-list-table widefat'><thead><tr><th>Channel</th><th>Videos</th><th>Actions</th><th>Last Access</th><th>Type</th></tr></thead>";
			if ( $items ) {
				foreach ( $items as $item ) {
					if ( ( $fcount = self::importFilesCount( $item->name, self::extensions_import(), $options['vwls_archive_path'] ) ) != '0 (0.00B)' ) {
						echo '<tr><th>' . esc_html( $item->name ) . '</th>';

						echo '<td>' . esc_html( $fcount ) . '</td>';

						$link = add_query_arg( array( 'playlist_import' => $item->name ), admin_url( 'admin.php?page=video-share-ls' ) );

						echo '<td><a class="button button-primary" href="' . esc_url( $link ) . '">Import</a></td>';
						echo '<td>' . format_age( time() - $item->edate ) . '</td>';
						echo '<td>' . ( $item->type == 2 ? 'Premium' : 'Standard' ) . '</td>';
						echo '</tr>';
					}
				}
			}
				echo '<tr><th>Total</th><th colspan="4">' . self::importFilesCount( '', self::extensions_import(), esc_attr( $options['vwls_archive_path'] ) ) . '</th></tr>';
			echo '</table>';
		}


		// fc above
	}


}

// instantiate
if ( class_exists( 'VWvideoShare' ) ) {
	$videoShare = new VWvideoShare();
}

// Actions and Filters
if ( isset( $videoShare ) ) {

	register_activation_hook( __FILE__, array( &$videoShare, 'install' ) );
	register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );


	add_action( 'init', array( &$videoShare, 'init' ), 0 );
	add_action( 'admin_menu', array( &$videoShare, 'admin_menu' ) );
	add_action( 'admin_bar_menu', array( &$videoShare, 'admin_bar_menu' ), 100 );

	add_action( 'plugins_loaded', array( &$videoShare, 'plugins_loaded' ) );

	add_action( 'parse_request', array( &$videoShare, 'parse_request' ) );

	// archive
	add_filter( 'archive_template', array( 'VWvideoShare', 'archive_template' ) );


	// cron
	add_filter( 'cron_schedules', array( &$videoShare, 'cron_schedules' ) );
	add_action( 'cron_4min_event', array( &$videoShare, 'convertProcessQueue' ) );

	add_action( 'init', array( &$videoShare, 'setup_schedule' ) );

	// page template
	add_filter( 'single_template', array( &$videoShare, 'single_template' ) );

	// add_action( 'bp_init', array(&$videoShare,'buddypress_activity') );
}

// dev only: instead of Save Permalinks
// flush_rewrite_rules();

?>
