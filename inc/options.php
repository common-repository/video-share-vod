<?php
// Backend Options & Features
namespace VideoWhisper\VideoShareVOD;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


trait Options {



		// ! Feature Pages and Menus

static function setupPagesList( $options = null ) {

	if ( ! $options ) {
		$options = get_option( 'VWvideoShareOptions' );
	}

	// shortcode pages
	$pages = array(
		'videowhisper_videos'    => 'Videos',
		'videowhisper_plupload'  => 'Upload Video',
		'videowhisper_my_videos' => 'My Videos',
			// 'videowhisper_recorder' => 'Record Video', //old flash app
	);

	if ( shortcode_exists( 'videowhisper_html5recorder' ) ) {
		$pages['videowhisper_html5recorder'] = 'Record Webcam';
	}

		return $pages;
}

static function setupPagesContent( $options = null ) {

	if ( ! $options ) {
		$options = get_option( 'VWvideoShareOptions' );
	}

	return array(
		'videowhisper_my_videos' => '[videowhisper_videos user_id="-1"]',
	);
}


static function setupPages() {
	$options = get_option( 'VWvideoShareOptions' );
	if ( $options['disableSetupPages'] ) {
		return;
	}

	$pages  = self::setupPagesList( $options );
	$noMenu = array( 'videowhisper_recorder' );

	$parents = array(
		'videowhisper_plupload'      => array( 'Peformer', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
		'videowhisper_recorder'      => array( 'Peformer', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
		'videowhisper_videos'        => array( 'Videos', 'Webcams', 'Channels', 'Content' ),
		'videowhisper_my_videos'     => array( 'Peformer', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
		'videowhisper_html5recorder' => array( 'Peformer', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
	);

		// custom content (not shortcode)
		$content = self::setupPagesContent();

	$duplicate = array( 'videowhisper_videos' );

	// create a menu and add pages
	$menu_name   = 'VideoWhisper';
	$menu_exists = wp_get_nav_menu_object( $menu_name );

	if ( ! $menu_exists ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	} else {
		$menu_id = $menu_exists->term_id;
	}
	$menuItems = array();

	// create pages if not created or existant
	foreach ( $pages as $key => $value ) {
			$pid  = $options[ 'p_' . $key ] ?? 0; 
			
			if ($pid) $page = get_post( $pid );
			else $page = null;
			
		if ( ! $page ) {
			$pid = 0;
		}

		if ( ! $pid ) {
			global $user_ID;
			$page                   = array();
			$page['post_type']      = 'page';
			$page['post_parent']    = 0;
			$page['post_status']    = 'publish';
			$page['post_title']     = $value;
			$page['comment_status'] = 'closed';

			if ( array_key_exists( $key, $content ) ) {
				$page['post_content'] = $content[ $key ]; // custom content
			} else {
				$page['post_content'] = '[' . $key . ']';
			}

			$pid = wp_insert_post( $page );

			$options[ 'p_' . $key ] = $pid;
			$link                   = get_permalink( $pid );

			// get updated menu
			if ( $menu_id ) {
				$menuItems = wp_get_nav_menu_items( $menu_id, array( 'output' => ARRAY_A ) );
			}

			// find if menu exists, to update
			$foundID = 0;
			foreach ( $menuItems as $menuitem ) {
				if ( $menuitem->title == $value ) {
					$foundID = $menuitem->ID;
					break;
				}
			}

			if ( ! in_array( $key, $noMenu ) ) {
				if ( $menu_id ) {
					// select menu parent
					$parentID = 0;
					if ( array_key_exists( $key, $parents ) ) {
						foreach ( $parents[ $key ] as $parent ) {
							foreach ( $menuItems as $menuitem ) {
								if ( $menuitem->title == $parent ) {
													$parentID = $menuitem->ID;
													break 2;
								}
							}
						}
					}

							// update menu for page
							$updateID = wp_update_nav_menu_item(
								$menu_id,
								$foundID,
								array(
									'menu-item-title'     => $value,
									'menu-item-url'       => $link,
									'menu-item-status'    => 'publish',
									'menu-item-object-id' => $pid,
									'menu-item-object'    => 'page',
									'menu-item-type'      => 'post_type',
									'menu-item-parent-id' => $parentID,
								)
							);

							// duplicate menu, only first time for main menu
					if ( ! $foundID ) {
						if ( ! $parentID ) {
							if ( intval( $updateID ) ) {
								if ( in_array( $key, $duplicate ) ) {
									wp_update_nav_menu_item(
										$menu_id,
										0,
										array(
											'menu-item-title'  => $value,
											'menu-item-url'    => $link,
											'menu-item-status' => 'publish',
											'menu-item-object-id' => $pid,
											'menu-item-object' => 'page',
											'menu-item-type'   => 'post_type',
											'menu-item-parent-id' => $updateID,
										)
									);
								}
							}
						}
					}
				}
			}
		}
	}

			update_option( 'VWvideoShareOptions', $options );
}

// menus

static function admin_bar_menu( $wp_admin_bar ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$options = get_option( 'VWvideoShareOptions' );

	if ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) {

		// find VideoWhisper menu
			$nodes = $wp_admin_bar->get_nodes();
			if ( ! $nodes )
			{
				$nodes = array();
			}
			$found = 0;
			foreach ( $nodes as $node )
			{
				if ( $node->title == 'VideoWhisper' )
				{
					$found = 1;
				}
			}
			if ( ! $found )
			{
				$wp_admin_bar->add_node(
					array(
						'id'    => 'videowhisper',
						'title' => '👁 VideoWhisper',
						'href'  => admin_url( 'plugin-install.php?s=videowhisper&tab=search&type=term' ),
					)
				);

				// more VideoWhisper menus

				$wp_admin_bar->add_node(
					array(
						'parent' => 'videowhisper',
						'id'     => 'videowhisper-add',
						'title'  => __( 'Add Plugins', 'paid-membership' ),
						'href'   => admin_url( 'plugin-install.php?s=videowhisper&tab=search&type=term' ),
					)
				);
				
				$wp_admin_bar->add_node(
					array(
						'parent' => 'videowhisper',
						'id'     => 'videowhisper-consult',
						'title'  => __( 'Consult Developers', 'paid-membership' ),
						'href'   => 'https://consult.videowhisper.com/'),
					);

				$wp_admin_bar->add_node(
					array(
						'parent' => 'videowhisper',
						'id'     => 'videowhisper-contact',
						'title'  => __( 'Contact Support', 'paid-membership' ),
						'href'   => 'https://videowhisper.com/tickets_submit.php?topic=WordPress+Plugins+' . urlencode( $_SERVER['HTTP_HOST'] ),
					)
				);
			}

		$menu_id = 'video-share-vod';

		$wp_admin_bar->add_node(
			array(
				'parent' => 'videowhisper',
				'id'     => $menu_id,
				'title'  => '🎞 VideoShareVOD',
				'href'   => admin_url( 'admin.php?page=video-share' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-upload',
				'title'  => __( 'Upload Videos', 'video-share-vod' ),
				'href'   => admin_url( 'admin.php?page=video-share-upload' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-import',
				'title'  => __( 'Import Videos', 'video-share-vod' ),
				'href'   => admin_url( 'admin.php?page=video-share-import' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-manage',
				'title'  => __( 'Manage Videos', 'video-share-vod' ),
				'href'   => admin_url( 'admin.php?page=video-manage' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-posts',
				'title'  => __( 'Video Posts', 'video-share-vod' ),
				'href'   => admin_url( 'edit.php?post_type=' . $options['custom_post'] ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-conversions',
				'title'  => __( 'Conversions', 'video-share-vod' ),
				'href'   => admin_url( 'admin.php?page=video-share-conversion' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-export',
				'title'  => __( 'Export Videos', 'video-share-vod' ),
				'href'   => admin_url( 'admin.php?page=video-share-export' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-space',
				'title'  => __( 'Statistics & Space', 'video-share-vod' ),
				'href'   => admin_url( 'admin.php?page=video-stats' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-pages',
				'title'  => __( 'Frontend Pages', 'video-share-vod' ),
				'href'   => admin_url( 'admin.php?page=video-share&tab=pages' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-settings',
				'title'  => __( 'Settings', 'video-share-vod' ),
				'href'   => admin_url( 'admin.php?page=video-share' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-docs',
				'title'  => __( 'Documentation', 'video-share-vod' ),
				'href'   => admin_url( 'admin.php?page=video-share-docs' ),
			)
		);
		
				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-wpdiscuss',
						'title'  => __( 'Discuss WP Plugin', 'video-share-vod' ),
						'href'   => 'https://wordpress.org/support/plugin/video-share-vod/',
					)
				);

				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-wpreview',
						'title'  => __( 'Review WP Plugin', 'video-share-vod' ),
						'href'   => 'https://wordpress.org/support/plugin/video-share-vod/reviews/#new-post',
					)
				);

				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-vsv',
						'title'  => __( 'Video Hosting', 'video-share-vod' ),
						'href'   => 'https://videosharevod.com/hosting/',
					)
				);
				
	}

	$user_id = get_current_user_id();

	if ( ! $options['disableSetupPages'] ) {
		if ( $options['p_videowhisper_videos'] ?? false ) {
			if ( self::hasPriviledge( $options['watchList'] || current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ) {
				$wp_admin_bar->add_node(
					array(
						'parent' => 'my-account',
						'id'     => 'videowhisper_videos',
						'title'  => __( 'Browse Videos', 'video-share-vod' ),
						'href'   => get_permalink( $options['p_videowhisper_videos'] ),
					)
				);
			}
		}

		if ( $options['p_videowhisper_plupload'] ?? false ) {
			if ( self::hasPriviledge( $options['publishList'] || current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ) {
				$wp_admin_bar->add_node(
					array(
						'parent' => 'my-account',
						'id'     => 'videowhisper_plupload',
						'title'  => __( 'Upload Videos', 'video-share-vod' ),
						'href'   => get_permalink( $options['p_videowhisper_plupload'] ),
					)
				);
			}
		}
	}

}



static function admin_menu() {
	$options = get_option( 'VWvideoShareOptions' );

	add_menu_page( 'Video Share VOD', 'Video Share VOD', 'manage_options', 'video-share', array( 'VWvideoShare', 'adminOptions' ), 'dashicons-video-alt3', 81 );
	add_submenu_page( 'video-share', 'Video Share VOD', 'Options', 'manage_options', 'video-share', array( 'VWvideoShare', 'adminOptions' ) );
	add_submenu_page( 'video-share', 'Conversions', 'Conversions', 'manage_options', 'video-share-conversion', array( 'VWvideoShare', 'adminConversion' ) );

	add_submenu_page( 'video-share', 'Upload', 'Upload', 'manage_options', 'video-share-upload', array( 'VWvideoShare', 'adminUpload' ) );
	add_submenu_page( 'video-share', 'Import', 'Import', 'manage_options', 'video-share-import', array( 'VWvideoShare', 'adminImport' ) );
	add_submenu_page( 'video-share', 'Export', 'Export', 'manage_options', 'video-share-export', array( 'VWvideoShare', 'adminExport' ) );

	if ( class_exists( 'VWliveStreaming' ) ) {
		add_submenu_page( 'video-share', 'Live Streaming', 'Live Streaming', 'manage_options', 'video-share-ls', array( 'VWvideoShare', 'adminLiveStreaming' ) );
	}
	add_submenu_page( 'video-share', 'Manage Videos', 'Manage Videos', 'manage_options', 'video-manage', array( 'VWvideoShare', 'adminManage' ) );
	add_submenu_page( 'video-share', 'Statistics & Space', 'Statistics & Space', 'manage_options', 'video-stats', array( 'VWvideoShare', 'adminStats' ) );
	add_submenu_page( 'video-share', 'Documentation', 'Documentation', 'manage_options', 'video-share-docs', array( 'VWvideoShare', 'adminDocs' ) );

}


		// ! Admin Videos
static function columns_head_video( $defaults ) {
	$defaults['featured_image'] = 'Thumbnail';
	$defaults['duration']       = 'Duration &amp; Info';

	return $defaults;
}


static function columns_register_sortable( $columns ) {
	$columns['duration'] = 'duration';

	return $columns;
}


static function admin_head() {

	echo '<!-- admin_head --><style type="text/css">
        .column-featured_image 
        {
        text-align: left; 
        width:240px !important; 
        overflow:hidden;
        }
        
        .column-featured_image IMG
        {
        max-width:240px !important; 
        }
        
        
        .column-duration 
        {
         text-align: left; width:200px !important; overflow:hidden 
        }
        
        	.column-title
			{
				width: 150px !important;
			}
			
			.column-edate
			{
				width: 75px !important;
			}
			

    </style>';
}


static function columns_content_video( $column_name, $post_id ) {

	if ( $column_name == 'featured_image' ) {

		$post_thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $post_thumbnail_id ) {

			$post_featured_image = wp_get_attachment_image_src( $post_thumbnail_id, 'thumbnail' );

			if ( $post_featured_image ) {
				// correct url

				$upload_dir  = wp_upload_dir();
				$uploads_url = self::path2url( $upload_dir['basedir'] );

				$iurl    = $post_featured_image[0];
				$relPath = substr( $iurl, strlen( $uploads_url ) );

				if ( file_exists( $relPath ) ) {
					$rurl = self::path2url( $relPath );
				} else {
					$rurl = $iurl;
				}

				echo '<img src="' . esc_url( $rurl ) . '" width="' . esc_attr( $post_featured_image[1] ) . '" height="' . esc_attr( $post_featured_image[2] ) . '"/>';

				echo '<br>Size (thumbnail): ' . esc_html( $post_featured_image[1] ) . 'x' . esc_html( $post_featured_image[2] );
			} else {
				echo 'No image  for ' . esc_html( $post_thumbnail_id );
			}

			$url = add_query_arg( array( 'updateThumb' => $post_id ), admin_url( 'admin.php?page=video-manage' ) );
			echo '<br><a href="' . esc_url( $url ) . '">' . __( 'Update Thumbnail', 'video-share-vod' ) . '</a>';

			// set views
			$videoViews    = intval( get_post_meta( $post_id, 'video-views', true ) );
			$videoLastView = intval( get_post_meta( $post_id, 'video-lastview', true ) );
			if ( ! $videoViews ) {
				update_post_meta( $post_id, 'video-views', 0 );
			}
			if ( ! $videoLastView ) {
				update_post_meta( $post_id, 'video-lastview', 0 );
			}

			echo '<br>' . __( 'Views', 'video-share-vod' ) . ': ' . esc_html( $videoViews );

		} else {
			echo 'Generating ... ';
			self::updatePostThumbnail( $post_id );

		}
	}

	if ( $column_name == 'duration' ) {
			$videoPath = get_post_meta( $post_id, 'video-source-file', true );
		if ( ! $videoPath ) {
			echo '<BR>Error: This entry has no video-source-file!';
		} elseif ( ! file_exists( $videoPath ) ) {
			echo '<BR>Error: Video file missing: ' . esc_html( $videoPath );
		} elseif ( ! filesize( $videoPath ) ) {
			echo '<BR>Error: Video file is empty (filesize 0): ' . esc_html( $videoPath );
		} else {
			$videoPath = get_post_meta( $post_id, 'video-source-file', true );
		}
		if ( file_exists( $videoPath ) ) {
			echo '<a href="' . self::path2url( $videoPath ) . '">source file</a> ';
			echo '<br>Source Size: ' . self::humanFilesize( filesize( $videoPath ) );
		}

				$videoDuration = get_post_meta( $post_id, 'video-duration', true );
		if ( $videoDuration ) {
			echo '<br>Duration: ' . self::humanDuration( $videoDuration );
			echo '<br>Resolution: ' . esc_html( get_post_meta( $post_id, 'video-width', true ) ) . 'x' . esc_html( get_post_meta( $post_id, 'video-height', true ) );
			echo '<br>Rotate: ' . esc_html( get_post_meta( $post_id, 'video-rotate', true ) ) . '';
			echo '<br>Total Space: ' . self::humanFilesize( self::spaceVideo( $post_id ) );
			echo '<br>Bitrate: ' . esc_html( get_post_meta( $post_id, 'video-bitrate', true ) ) . ' kbps';

			echo '<br>Codecs: ' . esc_html( ( $codec = get_post_meta( $post_id, 'video-codec-video', true ) ) ) . ', ' . esc_html( get_post_meta( $post_id, 'video-codec-audio', true ) );

			if ( ! $codec ) {
				self::updateVideo( $post_id, true );
			}
			echo '<br>Files: ';

			$videoAdaptive = get_post_meta( $post_id, 'video-adaptive', true );
			if ( $videoAdaptive ) {
				$videoAlts = $videoAdaptive;
			} else {
				$videoAlts = array();
			}

			foreach ( $videoAlts as $alt ) {
				if ( file_exists( $alt['file'] ) ) {
					echo '<br>-<a href="' . self::path2url( $alt['file'] ) . '">' . esc_html( $alt['id'] ) . '</a> ' . esc_html( $alt['bitrate'] ) . 'kbps ' . self::humanFilesize( filesize( $alt['file'] ) ) . ' <a href="' . add_query_arg(
						array(
							'convert' => $post_id,
							'format'  => $alt['id'],
						),
						admin_url( 'admin.php?page=video-manage' )
					) . '">Convert</a>';
				} else {
					echo '<br>-' . esc_html( $alt['id'] ) . ' missing ' . ' <a href="' . add_query_arg(
						array(
							'convert' => $post_id,
							'format'  => $alt['id'],
						),
						admin_url( 'admin.php?page=video-manage' )
					) . '">Convert</a>';
				}
			}

						$url = add_query_arg( array( 'updateInfo' => $post_id ), admin_url( 'admin.php?page=video-manage' ) );
					$url2    = add_query_arg( array( 'convert' => $post_id ), admin_url( 'admin.php?page=video-manage' ) );
					$url3    = add_query_arg( array( 'troubleshoot' => $post_id ), admin_url( 'admin.php?page=video-manage' ) );

					echo '<br>+ <a href="' . esc_url( $url ) . '">' . __( 'Update Info', 'video-share-vod' ) . '</a> ';
					echo '<br>+ <a href="' . esc_url( $url2 ) . '">' . __( 'Reconvert All', 'video-share-vod' ) . '</a> ';
					echo '<br>+ <a href="' . esc_url( $url3 ) . '">' . __( 'Troubleshoot', 'video-share-vod' ) . '</a>';

		} else {
			echo '<br>Retrieving Info...';

			self::updateVideo( $post_id, true );
			$url = add_query_arg( array( 'updateInfo' => $post_id ), admin_url( 'admin.php?page=video-manage' ) );
			echo '<br>+ <a href="' . esc_url( $url ) . '">' . __( 'Update Info', 'video-share-vod' ) . '</a> ';
		}
	}

}


public static function parse_query( $query ) {
	/*
	global $pagenow;

	if (is_admin() && $pagenow=='edit.php' && isset($_GET['post_type']) && $_GET['post_type']=='video')
	{
	}
	*/
}


static function duration_column_orderby( $vars ) {
	if ( isset( $vars['orderby'] ) && 'duration' == $vars['orderby'] ) {
		$vars = array_merge(
			$vars,
			array(
				'meta_key' => 'video-duration',
				'orderby'  => 'meta_value_num',
			)
		);
	}

	return $vars;
}


static function adminConversion() {

	$options = get_option( 'VWvideoShareOptions' );

	if ( isset( $_GET['cancelConversions'] ) ) {
		$options['convertQueue'] = '';
		update_option( 'VWvideoShareOptions', $options );
	}
	?>
				<div class="wrap">
		<h2>Conversions - Video Share / Video on Demand (VOD)</h2>

<h4><?php _e( 'Conversion Queue', 'video-share-vod' ); ?></h4>
Queued conversions take some time to process. Queuing processes videos sequencially, one conversion at a time, without affecting regular site usage by resource overload. This also reduces failures due to resource limitations. Configure conversions from <a href="<?php echo esc_url( admin_url( 'admin.php?page=video-share&tab=convert' ) ); ?>">Convert Settings</a>.

<br><textarea name="convertQueue_" id="convertQueue" readonly="readonly" cols="120" rows="12"><?php echo esc_textarea( $options['convertQueue'] ); ?></textarea>
<BR>
	<?php

	if ( $options['convertQueue'] ) {
			$cmds = explode( "\r\n", $options['convertQueue'] );
		if ( count( $cmds ) ) {
			echo 'Conversions in queue: ' . ( count( $cmds ) );
		}
			echo ' <a href="' . get_permalink() . 'admin.php?page=video-share-conversion&cancelConversions=1' . '">Cancel Conversions</a>';
	} else {
		echo 'No conversions in queue.';
	}

	self::convertProcessQueue( 1 );
	echo '<BR>Next automated check (WP Cron, 4 min or more depending on site activity): in ' . ( wp_next_scheduled( 'cron_4min_event' ) - time() ) . 's';

	$lastCheck = self::timeToGet( 'processQueue', $options );
	if ( $lastCheck ) {
		echo '<BR>Last Check: ' . ( time() - $lastCheck ) . 's ago';
	}

	?>
<h3><?php _e( 'Troubleshooting' ); ?></h3>
<h4><?php _e( 'Last Conversion' ); ?></h4>

	<?php
	$uploadsPath = $options['uploadsPath'];
	if ( ! file_exists( $uploadsPath ) ) {
		mkdir( $uploadsPath );
	}
	$lastConversionPath = $uploadsPath . '/lastConversion.txt';

	$lastConversion = self::varLoad( $lastConversionPath );

	if ( $lastConversion ) {
		echo 'Command:' . esc_html( $lastConversion['command'] ) . '<BR>Time: ' . date( DATE_RFC2822, $lastConversion['time'] ) . '';
	}

	?>
<h4><?php _e( 'Hosting' ); ?></h4>

<br>For a quick, hassle free setup, see <a href="https://videosharevod.com/hosting/" target="_vsvhost">VideoShareVOD turnkey managed hosting plans</a> for business video hosting, from $20/mo, including plugin installation, configuration.</p>

This section should aid in troubleshooting conversion issues.
	<?php

	if ( $options['enable_exec'] ) {
			$fexec = 0;

			echo '<BR>exec: ';
		if ( function_exists( 'exec' ) ) {
			echo 'function is enabled';

			if ( exec( 'echo EXEC' ) == 'EXEC' ) {
				echo ' and works';
				$fexec = 1;
			} else {
				echo ' <b>but does not work</b>';
			}
		} else {
			echo '<b>function is not enabled</b><BR>PHP function "exec" is required to run FFMPEG. Current hosting settings are not compatible with this functionality.';
		}

		if ( $fexec ) {

			echo '<BR>FFMPEG: ';
			$cmd = sanitize_text_field( $options['ffmpegPath'] ) . ' -version';
			exec( escapeshellcmd( $cmd ), $output, $returnvalue );
			if ( $returnvalue == 127 ) {
				echo '<b>Warning: not detected:' . esc_html( $cmd ) . '</b>'; } else {
				echo 'detected';
				echo '<BR>' . esc_html( $output[0] );
				echo '<BR>' . esc_html( $output[1] );
				}

				$cmd = sanitize_text_field( $options['ffmpegPath'] ) . ' -codecs';
				exec( escapeshellcmd( $cmd ), $output, $returnvalue );

				// detect codecs
				if ( $output ) {
					if ( count( $output ) ) {
									echo '<br>Codec libraries:';
						foreach ( array( 'h264', 'vp6', 'vp8', 'vp9', 'speex', 'nellymoser', 'opus', 'h263', 'mpeg', 'mp3', 'fdk_aac', 'faac' ) as $cod ) {
							$det  = 0;
							$outd = '';
							echo '<BR>' . esc_html( "$cod : " );
							foreach ( $output as $outp ) {
								if ( strstr( $outp, $cod ) ) {
														$det  = 1;
														$outd = $outp;
								}
							};
							if ( $det ) {
								echo 'detected (' . esc_html( $outd ) . ' )';
							} else {
								echo '<b>missing: configure and install FFMPEG with lib' . esc_html( $cod ) . " if you don't have another library for that codec and need it for input or output</b>";
							}
						}
					}
				}
				?>
<BR>You need only 1 AAC codec. Depending on <a href="https://trac.ffmpeg.org/wiki/Encode/AAC#libfaac">AAC library available on your system</a> you may need to update transcoding parameters. Latest FFMPEG also includes a native encoder (aac).

			<?php
		}
	} else echo '<H5>Running FFmpeg requires enabling Server Command Execution setting.</H5>';
	?>
<h4><?php _e( 'CloudLinux Shared Hosting Requirements' ); ?></h4>
CPU Speed: FFMPEG will be called with "-threads 1" to use just 1 thread (meaning 100% of 1 cpu core). That means on cloud limited environments account will need at least 100% CPU speed (to use at least 1 full core) to run conversions.
<BR>Memory: Depending on settings, conversions can fail with "x264 [error]: malloc" error if memory limit does not permit doing conversion. While "mobile" conversion can usually be done with 512Mb memory limit, for "high" quality settings (HD) 768Mb or more would be needed.

<h4><?php _e( 'System Process Limitations' ); ?></h4>
User limits can prevent conversions. Setting cpu limit to 7200 to prevent early termination:<br>
	<?php

	if ( $options['enable_exec'] ) {
		if ( $fexec ) {
			$cmd    = 'ulimit -t 7200; ulimit -a';
			$output = '';
			exec( escapeshellcmd( $cmd ), $output, $returnvalue );
			foreach ( $output as $outp ) {
				echo esc_html( $outp ) . '<br>';
			}
		} else {
			echo 'Not functional without exec.';
		}
	} else echo '<H5>Running FFmpeg requires enabling Server Command Execution setting.</H5>';
	
}


static function adminUpload() {
	?>
		<div class="wrap">
		<h2>Video Share / Video on Demand (VOD)</h2>
	<?php
			echo do_shortcode( '[videowhisper_plupload]' );
	?>
		Use this page to upload one or multiple videos to server. 
		<br>Playlist(s): Assign videos to multiple playlists, as comma separated values. Ex: subscriber, premium
		</div>

<h3>Troubleshoot Uploads</h3>
PHP Limitations (for a request, script call):
<BR>post_max_size: <?php echo ini_get( 'post_max_size' ); ?>
<BR>upload_max_filesize: <?php echo ini_get( 'upload_max_filesize' ); ?> - The maximum size of an uploaded file. Web uploads and processing are also limited by memory limits. 
<BR>memory_limit: <?php echo ini_get( 'memory_limit' ); ?> - This sets the maximum amount of memory in bytes that a script is allowed to allocate. This helps prevent poorly written scripts for eating up all available memory on a server. This does not overwrite hosting limits per account.
<BR>max_execution_time: <?php echo ini_get( 'max_execution_time' ); ?> - This sets the maximum time in seconds a script is allowed to run before it is terminated by the parser. This helps prevent poorly written scripts from tying up the server. The default setting is 90.
<BR>max_input_time: <?php echo ini_get( 'max_input_time' ); ?>  - This sets the maximum time in seconds a script is allowed to parse input data, like POST, GET and file uploads.

<p>mod_security: Uploads may be prevented by this rule (that can be disabled) - 920420: Request content type is not allowed by policy	</p>
<p>Important: For adding big videos (512Mb or higher), best way is to upload by FTP and use <a href="admin.php?page=video-share-import">Import</a> feature. Trying to upload big files by HTTP (from web page) may result in failure due to request limitations, hosting plan resources, timeouts depending on client upload connection speed.</p>

	<?php
}


static function adminStats() {
	$options = get_option( 'VWvideoShareOptions' );

	?>
		<div class="wrap">
		<h2>Video Statistics</h2>
	<?php

			$post_count = wp_count_posts( $options['custom_post'] );

			echo '<h3>Video Count</h3>';
	foreach ( $post_count as $key => $value ) {
		echo '<BR>' . esc_html( $key ) . ' : <a href="edit.php?post_type=' . esc_attr( $options['custom_post'] ) . '&post_status=' . esc_attr( $key ) . '">' . esc_html( $value ) . '</a>';
	}

			echo '<h3>Video Space Usage</h3>';

	function get_meta_values( $key = '', $type = 'video' ) {
		global $wpdb;
		if ( empty( $key ) ) {
			return;
		}
		$r = $wpdb->get_col(
			$wpdb->prepare(
				"
        SELECT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = '%s'
        AND p.post_type = '%s'
    ",
				$key,
				$type
			)
		);
		return $r;
	}

			$metas = get_meta_values( 'space-statistics', $options['custom_post'] );

			echo 'Stats available for: ' . count( $metas ) . ' videos';
			$totalStats = array();

	foreach ( $metas as $meta ) {
		$spaceStats = unserialize( $meta );
		foreach ( $spaceStats as $key => $value ) {
			$totalStats['total'] += $value;
			$totalStats[ $key ]  += $value;
		}
	}

			echo '<BR>Space used by videos: ';
	foreach ( $totalStats as $key => $value ) {
		echo '<BR>' . esc_html( $key ) . ': ' . self::humanFilesize( $value );
	}

			echo '<h3>Content Folder Space Usage</h3>';

	if ( file_exists( $options['uploadsPath'] ) ) {
		echo 'VideoShareVOD (' . esc_html( $options['uploadsPath'] ) . '): ' . self::humanFilesize( self::sizeTree( $options['uploadsPath'] ) );
	}

	if ( file_exists( $options['importPath'] ) ) {
		echo '<BR>Import (' . esc_html( $options['importPath'] ) . '): ' . self::humanFilesize( self::sizeTree( $options['importPath'] ) );
	}

	if ( file_exists( $options['streamsPath'] ) ) {
		echo '<BR>Streams (' . esc_html( $options['streamsPath'] ) . '): ' . self::humanFilesize( self::sizeTree( $options['streamsPath'] ) );
	}

	if ( file_exists( $options['exportPath'] ) ) {
		echo '<BR>Exports (' . esc_html( $options['exportPath'] ) . '): ' . self::humanFilesize( self::sizeTree( $options['exportPath'] ) );
	}

	if ( file_exists( $options['uploadsPath'] . '/plupload' ) ) {
		echo '<BR>Web Uploads from PLupload (' . esc_html( $options['uploadsPath'] ) . '/plupload' . '): ' . self::humanFilesize( self::sizeTree( $options['uploadsPath'] . '/plupload' ) );
	}

	if ( file_exists( $options['uploadsPath'] . '/uploads' ) ) {
		echo '<BR>Web Uploads from old uploader (' . esc_html( $options['uploadsPath'] ) . '/uploads' . '): ' . self::humanFilesize( self::sizeTree( $options['uploadsPath'] . '/uploads' ) );
	}

	?>
			<h3>Video Space Tools</h3>
			+ <a href="admin.php?page=video-manage&updateSpace=1">Calculate Current Space Usage for All Videos</a>

		<BR>+ <a href="admin.php?page=video-manage&clean=source">Delete Sources</a> (not recommended, required to generate/update conversions and snapshots)
		<BR> + <a href="admin.php?page=video-manage&clean=logs">Delete Logs</a> (required to troubleshoot)
		<BR> + <a href="admin.php?page=video-manage&clean=hls">Delete HLS Segments</a> (required for web HLS playback, can be re-generated from source)

		<?php
}


static function sizeTree( $dir ) {

	if ( ! file_exists( $dir ) ) {
		return 0;
	}

	$files = array_diff( scandir( $dir ), array( '.', '..' ) );

	$space = 0;
	foreach ( $files as $file ) {
		$space += ( is_dir( "$dir/$file" ) ) ? self::sizeTree( "$dir/$file" ) : filesize( "$dir/$file" );
	}
	return $space;
}


static function spaceVideo( $post_id ) {
	// calculate statistics for video

	if ( ! $post_id ) {
		return;
	}
	$options = get_option( 'VWvideoShareOptions' );

	$spaceStatistics = array();

	// source
	$space     = 0;
	$videoPath = get_post_meta( $post_id, 'video-source-file', true );
	if ( file_exists( $videoPath ) ) {
		$space = filesize( $videoPath );
	}
	$spaceStatistics['source'] = $space;

	// all generated video files
	$videoAdaptive = get_post_meta( $post_id, 'video-adaptive', true );
	if ( $videoAdaptive ) {
		$videoAlts = $videoAdaptive;
	} else {
		$videoAlts = array();
	}

	$space = 0;
	foreach ( $videoAlts as $alt ) {
		if ( file_exists( $alt['file'] ) ) {
			$spaceStatistics[ $alt['id'] ] = filesize( $alt['file'] );
		}

		$logpath                                 = dirname( $alt['file'] );
		$log                                     = $logpath . '/' . $post_id . '-' . $alt['id'] . '.txt';
		$logc                                    = $logpath . '/' . $post_id . '-' . $alt['id'] . '-cmd.txt';
		$spaceStatistics[ $alt['id'] . '_logs' ] = 0;
		if ( file_exists( $log ) ) {
			$spaceStatistics[ $alt['id'] . '_logs' ] += filesize( $log );
		}
		if ( file_exists( $logc ) ) {
			$spaceStatistics[ $alt['id'] . '_logs' ] += filesize( $logc );
		}

		if ( file_exists( $alt['file'] ) ) {     // hls space
			if ( isset($alt['hls']) ) {
				if ( strstr( $alt['hls'], $options['uploadsPath'] ) ) {
					if ( file_exists( $alt['hls'] ) ) {
						if ( is_dir( $alt['hls'] ) ) {
							$spaceStatistics[ $alt['id'] . '_hls' ] = self::sizeTree( $alt['hls'] );
						}
					}
				}
			}
		}
	}

	update_post_meta( $post_id, 'space-statistics', $spaceStatistics );

	$spaceTotal = 0;
	foreach ( $spaceStatistics as $value ) {
		$spaceTotal += $value;
	}

	update_post_meta( $post_id, 'space-total', $spaceTotal );

	// var_dump($spaceStatistics);

	return $spaceTotal;
}


static function troubleshootVideo( $post_id ) {
	$post = get_post( $post_id );
	echo '<H4>Troubleshooting: ' . esc_html( $post->post_title ) . '</H4>';

	$videoAdaptive = get_post_meta( $post_id, 'video-adaptive', true );
	if ( $videoAdaptive ) {
		$videoAlts = $videoAdaptive;
	} else {
		$videoAlts = array();
	}
	foreach ( $videoAlts as $id => $alt ) {
		echo '<BR><B>' . esc_html( $id ) . '</B>';
		echo '<BR>+ Conversion file ';
		if ( file_exists( $alt['file'] ) ) {
			echo 'exists';
		} else {
			echo 'does NOT exist';
		}
		echo '<br> ' . esc_html( $alt['file'] );
		if ( file_exists( $alt['file'] ) ) {
			echo ' Size: ' . filesize( $alt['file'] );
			// echo ' Time C: ' . date(DATE_RFC2822, $tc = filectime($alt['file']));
			echo '<br> File Time: ' . date( DATE_RFC2822, $tf = filemtime( $alt['file'] ) );

		}

		echo '<BR>+ Conversion log ';
		if ( file_exists( $alt['log'] ) ) {
			echo 'exists <br><textarea cols=100 rows=5>' . esc_textarea( file_get_contents( $alt['log'] ) ) . '</textarea>';
		} else {
			echo 'does NOT exist';
		}

		if ( file_exists( $alt['log'] ) ) {
			echo '<br>Log Time: ' . date( DATE_RFC2822, $tl = filemtime( $alt['log'] ) );
		}

		echo '<BR>+ Conversion command:' . esc_html( $alt['cmd'] );

		echo '<BR>+ Conversion data:<br><textarea cols=100 rows=5>';
		var_dump( $alt );
		echo '</textarea>';
	}
}


static function adminManage() {

	$options = get_option( 'VWvideoShareOptions' );

	?>
		<div class="wrap">
		<h2>Manage Videos</h2>
	<?php

	if ( $clean = sanitize_text_field( $_GET['clean'] ?? '' ) ) {

		echo 'Cleaning video files. Finding posts older than 3 days (to avoid processing of unconverted posts) ...';

		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM  {$wpdb->posts} WHERE post_type = '%s'AND post_date < NOW() - INTERVAL 3 DAY", sanitize_file_name( $options['custom_post'] ) ) );

		echo '<BR>Videos to clean for: ' . count( $ids );

		$confirm = sanitize_text_field( $_GET['confirm'] ?? '' );

		echo '<BR>' . esc_html( $confirm );
		foreach ( $ids as $post_id ) {
			$value += self::cleanVideo( $post_id, $clean, $confirm, $options );
			echo ' .';
		}

		echo '<BR>Total clean space: ' . self::humanFilesize( $value );

		if ( $confirm == '1' ) {
			echo '<BR>Successfully cleaned!';
		} else {
			echo '<BR>Are you sure you want to delete these files?<BR>This is not reversible: <a class="button" href="admin.php?page=video-manage&clean=' . esc_attr( $clean ) . '&confirm=1">Confirm Deletion</a>';
		}
	}

	if ( $_GET['updateSpace'] ?? false ) {
		echo '<BR>Calculating space usage for all videos...';
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM  {$wpdb->posts} WHERE post_type = '%s'", sanitize_file_name( $options['custom_post'] ) ) );

		echo '<BR>Videos to calculate for: ' . count( $ids );

		foreach ( $ids as $post_id ) {
			$value += self::spaceVideo( $post_id );
		}

		echo '<BR>Total space usage calculated: ' . self::humanFilesize( $value );
		echo '<BR>See current <a href="admin.php?page=video-stats">Full Space Usage Statistics</a>';
	}

	if ( $update_id = intval( $_GET['updateInfo'] ?? 0 ) ) {
		echo '<BR>Updating Video #' . intval( $update_id ) . '... <br>';
		self::updateVideo( $update_id, true, true );
		unset( $_GET['updateInfo'] );
		self::postProcess( $update_id );

	}

	if ( $update_id = intval( $_GET['updateThumb'] ?? 0 ) ) {
		echo '<BR>Updating Thumbnail for Video #' . intval( $update_id ) . '... <br>';
		self::updatePostThumbnail( $update_id, true, true );
		unset( $_GET['updateThumb'] );
	}

	if ( $update_id = intval( $_GET['convert'] ?? 0 ) ) {
		$format = sanitize_file_name( $_GET['format'] ?? '' );
		echo '<BR>Converting Video #' . intval( $update_id ) . ' - overwriting previous conversions. Also update info. Queued conversions takes some time to start and process. <br>';
		self::updateVideo( $update_id, true, true );
		self::convertVideo( $update_id, true, true, $format );
		$url3 = add_query_arg( array( 'troubleshoot' => $update_id ), admin_url( 'admin.php?page=video-manage' ) );
		echo '<BR> - <a href="' . esc_url( $url3 ) . '">' . __( 'Troubleshoot Video Conversions', 'video-share-vod' ) . '</a>';

		unset( $_GET['convert'] );
	}

	if ( $troubleshoot_id = intval( $_GET['troubleshoot'] ?? 0 ) ) {
		echo '<h3>Troubleshooting Video #' . intval( $troubleshoot_id ) . ' </h3>';

		$post_id       = $troubleshoot_id;
		$videoDuration = get_post_meta( $post_id, 'video-duration', true );
		if ( $videoDuration ) {
			echo 'Duration: ' . self::humanDuration( $videoDuration );
			echo '<br>Resolution: ' . esc_html( get_post_meta( $post_id, 'video-width', true ) ) . 'x' . esc_html( get_post_meta( $post_id, 'video-height', true ) );
			echo '<br>Rotate: ' . esc_html( get_post_meta( $post_id, 'video-rotate', true ) ) . '';
			echo '<br>Source Size: ' . self::humanFilesize( get_post_meta( $post_id, 'video-source-size', true ) );
			echo '<br>Source Bitrate: ' . esc_html( get_post_meta( $post_id, 'video-bitrate', true ) ) . ' kbps';
			echo '<br>Total Space: ' . self::humanFilesize( self::spaceVideo( $post_id ) );
			echo '<br>Codecs: ' . esc_html( ( $codec = get_post_meta( $post_id, 'video-codec-video', true ) ) ) . ', ' . esc_html( get_post_meta( $post_id, 'video-codec-audio', true ) );
		}

			echo '<br>Video Files: ';

			$videoPath = get_post_meta( $post_id, 'video-source-file', true );
		if ( file_exists( $videoPath ) ) {
			echo ' <a href="' . self::path2url( $videoPath ) . '">source</a> ';
		}

			$videoAdaptive = get_post_meta( $post_id, 'video-adaptive', true );
		if ( $videoAdaptive ) {
			$videoAlts = $videoAdaptive;
		} else {
			$videoAlts = array();
		}

		foreach ( $videoAlts as $alt ) {
			if ( file_exists( $alt['file'] ) ) {
				echo '<br>-<a href="' . self::path2url( $alt['file'] ) . '">' . esc_html( $alt['id'] ) . '</a> ' . esc_html( $alt['bitrate'] ) . 'kbps ' . self::humanFilesize( filesize( $alt['file'] ) ) . ' <a href="' . add_query_arg(
					array(
						'convert' => $post_id,
						'format'  => $alt['id'],
					),
					admin_url( 'admin.php?page=video-manage' )
				) . '">Convert</a>';
			} else {
				echo '<br>-' . esc_html( $alt['id'] ) . ' missing ' . ' <a href="' . add_query_arg(
					array(
						'convert' => $post_id,
						'format'  => $alt['id'],
					),
					admin_url( 'admin.php?page=video-manage' )
				) . '">Convert</a>';
			}
		}

						$url  = add_query_arg( array( 'updateInfo' => $post_id ), admin_url( 'admin.php?page=video-manage' ) );
						$url2 = add_query_arg( array( 'convert' => $post_id ), admin_url( 'admin.php?page=video-manage' ) );
						$url3 = add_query_arg( array( 'troubleshoot' => $post_id ), admin_url( 'admin.php?page=video-manage' ) );

						echo '<br>+ <a href="' . esc_url( $url ) . '">' . __( 'Update Info', 'video-share-vod' ) . '</a> ';
						echo '<br>+ <a href="' . esc_url( $url2 ) . '">' . __( 'Reconvert All', 'video-share-vod' ) . '</a> ';
						echo '<br>+ <a href="' . esc_url( $url3 ) . '">' . __( 'Troubleshoot', 'video-share-vod' ) . '</a>';

						self::troubleshootVideo( $troubleshoot_id );
						unset( $_GET['troubleshoot'] );

		?>
				<h4>Conversion Time Clarifications</h4>
				Real is wall clock time - time from start to finish of the call. This is all elapsed time including time slices used by other processes and time the process spends blocked (for example if it is waiting for I/O to complete).

				<br><br>User is the amount of CPU time spent in user-mode code (outside the kernel) within the process. This is only actual CPU time used in executing the process. Other processes and time the process spends blocked do not count towards this figure.

				<br><br>Sys is the amount of CPU time spent in the kernel within the process. This means executing CPU time spent in system calls within the kernel, as opposed to library code, which is still running in user-space. Like 'user', this is only CPU time used by the process. See below for a brief description of kernel mode (also known as 'supervisor' mode) and the system call mechanism.
				<br><br>User+Sys will tell you how much actual CPU time your process used. Note that this is across all CPUs, so if the process has multiple threads (and this process is running on a computer with more than one processor) it could potentially exceed the wall clock time reported by Real (which usually occurs).
				<?php
	}
	?>
		<h3>Video Information</h3>

		<BR>+ Review how conversions progress in the <a href="admin.php?page=video-share-conversion">Conversions Queue</a>.

		<BR>+ Individual videos can be managed from <a href="edit.php?post_type=<?php echo esc_attr( $options['custom_post'] ); ?>">Videos</a> section (options to convert again, troubleshoot conversions available). Browsing videos updates space usage calculations.
		<BR>+ Current space usage statistics is available in <a href="admin.php?page=video-stats">Statistics</a> section.

		<h3>Clean Videos</h3>
		+ <a href="admin.php?page=video-manage&clean=source">Delete Sources</a> (not recommended, required to generate/update conversions and snapshots)
		<BR> + <a href="admin.php?page=video-manage&clean=logs">Delete Logs</a> (required to troubleshoot)
		<BR> + <a href="admin.php?page=video-manage&clean=hls">Delete HLS Segments</a> (required for web HLS playback, can be re-generated from source)

	<?php
}


		// ! Documentation
static function adminDocs() {

	$options = get_option( 'VWvideoShareOptions' );

	?>
		<div class="wrap">
		<h2>Video Share / Video on Demand (VOD)</h2>
		<h3>External Documentation</h3>
			   + <a href="https://videosharevod.com/features/quick-start-tutorial/">Setup Tutorial</a>
		 <BR> + <a href="https://videosharevod.com/hosting/">Hosting Requirements and Options</a>
		 <BR> + <a href="https://www.videowhisper.com/?p=VideoWhisper+Script+Installation">Paid Installation</a> (on compatible hosting)
		 <BR> + <a href="https://www.videowhisper.com/tickets_submit.php">Contact Support</a> (for clarifications, custom development)


<h3>Quick Setup Tutorial</h3>
<ol>
<li>If you have <a href="https://videosharevod.com/hosting/">FFmpeg web hosting</a>, you should be able to configure and use this plugin, depending on web host. </li>
<li>From <a href="admin.php?page=video-share&tab=server">VideoShareVOD > Settings: Server</a> Save settings with FFmpeg parameters and video uploads location.</li>
<li>From <a href="options-permalink.php">Settings > Permalinks</a> enable a SEO friendly structure (ex. Post name)</li>
<li>From <a href="nav-menus.php">Appearance > Menus</a> add Videos and Upload Video pages to main site menu, as needed.
</li>
<li>Optional: Install and enable the <a href="https://ppvscript.com/micropayments/">MicroPayments</a> plugin to enable authors to manage their videos and optionally monetization options from frontend.</li>
<li>Setup <a href="edit-tags.php?taxonomy=category&post_type=video">video categories</a>, common to site content.</li>
</ol>


<h3>VideoShareVOD Installation Overview</h3>

<PRE>
- Site visitors can browse videos at:
	<?php echo get_permalink( $options['p_videowhisper_videos'] ); ?>


- Site users can upload videos from:
	<?php echo get_permalink( $options['p_videowhisper_plupload'] ); ?>


- Setup site categories from:
	<?php echo admin_url(); ?>edit-tags.php?taxonomy=category&post_type=video

- Configure listings from:
	<?php echo admin_url(); ?>admin.php?page=video-share&tab=listings

- Customize further as described at:
https://videosharevod.com/features/quick-start-tutorial/#customize

- Upgrade to more advanced features (software and hosting capabilities) with higher plans from:
https://videosharevod.com/turnkey-site/
</PRE>

		<h3>Shortcodes</h3>

		<h4>[videowhisper_videos playlist="" category_id="" order_by="" perpage="" perrow="" select_category="1" select_order="1" select_page="1" include_css="1" user_id="0" id=""]</h4>
		Displays video list. Loads and updates by AJAX. Optional parameters: video playlist name, maximum videos per page, maximum videos per row.
		<br>order_by: post_date / video-views / video-lastview
		<br>select attributes enable controls to select category, order, page
		<br>include_css: includes the styles (disable if already loaded once on same page)
		<br>user_id: if different than 0 only show videos of specified user, -1 will show from current logged in user [videowhisper_videos user_id="-1"]
		<br>id is used to allow multiple instances on same page (leave blank to generate)

		<h4>[videowhisper_plupload playlist="" category="" owner=""]</h4>
		Displays interface to upload videos with  PLupload .
		<br>playlist: If not defined owner name is used as playlist for regular users. Admins with edit_users capability can write any playlist name. Multiple playlists can be provided as comma separated values.
		<br>category: If not define a dropdown is listed.
		<br>owner: User is default owner. Only admins with edit_users capability can use different.

		<h4>[videowhisper_upload playlist="" category="" owner=""]</h4>
		Displays interface to upload videos with old HTML5 uploader.
		<br>playlist: If not defined owner name is used as playlist for regular users. Admins with edit_users capability can write any playlist name. Multiple playlists can be provided as comma separated values.
		<br>category: If not define a dropdown is listed.
		<br>owner: User is default owner. Only admins with edit_users capability can use different.

	   <h4>[videowhisper_import path="" playlist="" category="" owner=""]</h4>
		Displays interface to import videos.
		<br>path: Path where to import from.
		<br>playlist: If not defined owner name is used as playlist for regular users. Admins with edit_users capability can write any playlist name. Multiple playlists can be provided as comma separated values.
		<br>category: If not define a dropdown is listed.
		<br>owner: User is default owner. Only admins with edit_users capability can use different.

		<h4>[videowhisper_player video="0" player="" width=""]</h4>
		Displays video player. Video post ID is required.
		<br>Player: html5/html5-mobile/strobe/strobe-rtmp/html5-hls/ blank to use settings & detection
		<br>Width: Force a fixed width in pixels (ex: 640) and height will be adjusted to maintain aspect ratio. Leave blank to use video size.

		<h4>[videowhisper_preview video="0"]</h4>
		Displays video preview (snapshot) with link to video post. Video post ID is required.
		Used to display VOD inaccessible items.


		<h4>[videowhisper_playlist name="playlist-name"]</h4>
		Displays playlist player.


		<h4>[videowhisper_player_html source="" source_type="" source_alt="" source_alt_type="" poster="" width="" height="" player=""]</h4>
		Displays configured HTML5 player for a specified video source.
		<br>Player: native/wordpress/video-js leave blank to use settings & detection
		<br>source_alt, source_alt_type for multi bitrate source & type like m3u8 supported by videojs
		<br>Ex. [videowhisper_player_html source="http://test.com/test.mp4" type="video/mp4" poster="http://test.com/test.jpg"]

		<h4>[videowhisper_embed_code source="" source_type="" poster="" width="" height=""]</h4>
		Displays html5 embed code.

	<h4>[videowhisper_postvideos post="post id"]</h4>
		Manage post associated videos. Required: post

	<h4>[videowhisper_postvideos_process post="" post_type=""]</h4>
		Process post associated videos (needs to be on same page with [videowhisper_postvideos] for that to work).

	<h4>[videowhisper_postvideo_assign post_id="" meta="video_teaser" content="id" show="1" showWidth="320"]</h4>
	Displays a form to select a video for a post and also shows current setting (including video player if "show" enabled).
	<br>meta: meta name that will contain the video info
	<br>content: id / video_path / preview_path
	<br>show: 1 / 0
	<br>showWidth: width of player in pixels

		<h3>Troubleshooting</h3>
		+ Check FFMPEG installation and codecs in <a href="admin.php?page=video-share&tab=server">server tab</a>.
		<br>+ Troubleshoot conversions in <a href="admin.php?page=video-share-conversion">conversions tab</a>.
		<br>+ Configure conversions in <a href="admin.php?page=video-share&tab=convert">conversions settings tab</a>.

		<br>+ If playlists don't show up right on your theme, copy taxonomy-playlist.php from this plugin folder to your theme folder.
		<h3>More...</h3>
		Read more details about <a href="https://videosharevod.com/features/">available features</a> on <a href="https://videosharevod.com/">official plugin site</a> and <a href="https://videowhisper.com/tickets_submit.php">contact us</a> anytime for questions, clarifications.
		</div>
	<?php
}

static function adminImport() {
	$options        = self::setupOptions();
	$optionsDefault = self::adminOptionsDefault();

	if ( isset( $_POST ) ) {
		if ( ! empty( $_POST ) ) {

			$nonce = $_REQUEST['_wpnonce'];
			if ( ! wp_verify_nonce( $nonce, 'vwsec' ) ) {
					echo 'Invalid nonce!';
					exit;
			}

			foreach ( $options as $key => $value ) {
				if ( isset( $_POST[ $key ] ) ) {
					$options[ $key ] = trim( sanitize_textarea_field( $_POST[ $key ] ) );
				}
			}
			update_option( 'VWvideoShareOptions', $options );
		}
	}

	?>
<h2>Import Videos from Folder</h2>
	Use this to mass import any number of videos already existent on server.


	<?php
	if ( file_exists( $options['importPath'] ) ) {
		echo do_shortcode( '[videowhisper_import path="' . esc_attr( $options['importPath'] ) . '"]' );
	} else {
		echo 'Import folder not found on server: ' . esc_html( $options['importPath'] );
	}
	?>
* Some formats/codecs will not archive in FLV container (like VP8, Opus from WebRTC). Transcoding is required and archived transcoded streams (starting with "i_") can be used.
<br>Videos in subfolders are not listed.

<h3>Import Settings</h3>
<form method="post" action="<?php echo wp_nonce_url( $_SERVER['REQUEST_URI'], 'vwsec' ); ?>">
<h4>Import Path</h4>
<p>Server path to import videos from</p>
	<?php
	if ( $options['importPath'] == sanitize_text_field( $optionsDefault['importPath'] ) ) {
		if ( file_exists( ABSPATH . 'streams' ) ) {
				$options['importPath'] = ABSPATH . 'streams/';
				echo 'Save to apply! Detected: ' . esc_html( $options['importPath'] ) . '<br>';
		}
	}
	?>
<input name="importPath" type="text" id="importPath" size="100" maxlength="256" value="<?php echo esc_attr( $options['importPath'] ); ?>"/>
<br>Ex: /home/[youraccount]/public_html/streams/ (ending in /)
<br>Common paths that may contain video files:
<br>
	<?php
	echo esc_attr( $options['uploadsPath'] ) . '/plupload/';
	echo ' ' . self::humanFilesize( self::sizeTree( $options['uploadsPath'] . '/plupload/' ) );
	?>
 - web upload from PLupload
<br>
	<?php
	echo esc_attr( $options['uploadsPath'] ) . '/uploads/';
	echo ' ' . self::humanFilesize( self::sizeTree( $options['uploadsPath'] . '/uploads/' ) );
	?>
 - web uploads from old HTML5 uploader
<br>
	<?php
	echo esc_attr( $options['exportPath'] );
	echo ' ' . self::humanFilesize( self::sizeTree( $options['exportPath'] ) );
	?>
 - exports (configured path)
<br>
	<?php
	echo esc_attr( $options['vwls_archive_path'] );
	echo ' ' . self::humanFilesize( self::sizeTree( $options['vwls_archive_path'] ) );
	?>
 - BroadcastLiveVideo / Live Streaming archive (configured path)
<br>
	<?php
	echo esc_attr( $options['streamsPath'] );
	echo ' ' . self::humanFilesize( self::sizeTree( $options['streamsPath'] ) );
	?>
 - streams from live streaming server (configured path)



<h4>Delete Original on Import</h4>
<select name="deleteOnImport" id="deleteOnImport">
  <option value="1" <?php echo $options['deleteOnImport'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['deleteOnImport'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Remove original file after copy to new location.

<h4>Import Clean</h4>
<p>Delete videos older than:</p>
<input name="importClean" type="text" id="importClean" size="5" maxlength="8" value="<?php echo esc_attr( $options['importClean'] ); ?>"/>days
<br>Set 0 to disable automated cleanup (not recommended as an active site can fill up server disks with broadcast archives). Cleanup does not occur more often than 10h to prevent high load.

<h4>Add Original to Media Library</h4>
<select name="originalLibrary" id="originalLibrary">
  <option value="1" <?php echo $options['originalLibrary'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['originalLibrary'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Requires keeping originals (do not remove or clean).

<h4>BuddyPress/BuddyBoss Activity Post</h4>
<select name="bpActivityPost" id="bpActivityPost">
  <option value="1" <?php echo $options['bpActivityPost'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['bpActivityPost'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Integrate post with BP/BB activity.

<h4>BuddyPress/BuddyBoss Activity Insert</h4>
<select name="bpActivityInsert" id="bpActivityInsert">
  <option value="1" <?php echo $options['bpActivityInsert'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['bpActivityInsert'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Insert BP/BB activity after creating video snapshot (so it includes thumbnail).

	<?php submit_button(); ?>
</form>
	<?php

}




		// ! Settings

static function adminOptionsDefault() {
	$root_url   = get_bloginfo( 'url' ) . '/';
	$upload_dir = wp_upload_dir();

	return array(
		
		'bpActivityPost' => 1,
		'bpActivityInsert' => 1,
	
		'listingsMenu'                    => 1, //show menu section for listings
		
		'enable_exec'              => 0, // disabled by default for security confirmation

		'attachHigh'               => 1,
		'attachPreview'            => 0,

		'themeMode' => '',
		'interfaceClass'           => '',

		'userName'                 => 'user_nicename',

		'rateStarReview'           => '1',
		'order_by'                 => 'post_date',

		'editURL'                  => $root_url . 'edit-content?editID=',
		'editContent'              => 'all',
		'allowDebug'               => '1',

		'disableSetupPages'        => '0',
		'vwls_playlist'            => '1',

		'vwls_archive_path'        => '/home/youraccount/public_html/streams/',
		'importPath'               => '/home/youraccount/public_html/streams/',

		'exportPath'               => '/home/youraccount/public_html/download/',
		'exportCount'              => '500',
		'exportOffset'             => '0',

		'importClean'              => '45',
		'deleteOnImport'           => '1',
		'originalLibrary'          => 0,

		'vwls_channel'             => '1',
		'ffmpegPath'               => '/usr/local/bin/ffmpeg',
		'ffmpegConfiguration'      => '1',
		'ffmpegControl'            => 'nice',

		'convertPreviewInput'      => '-ss 5',
		'convertPreviewOutput'     => '-t 10',

		'codecVideoPreview'        => '-c:v libx264 -movflags +faststart -profile:v main -level 3.1',
		'codecVideoMobile'         => '-c:v libx264 -profile:v main -level 3.1',
		'codecVideoHigh'           => '-c:v libx264 -profile:v main -level 3.1',

		'codecAudioPreview'        => '-c:a libfaac -ac 2 -ab 64k',
		'codecAudioMobile'         => '-c:a libfaac -ac 2 -ab 64k',
		'codecAudioHigh'           => '-c:a libfaac -ac 2 -ab 128k',

		'bitrateHD'                => '8192',
		'convertSingleProcess'     => '0',
		'convertQueue'             => '',
		'convertInstant'           => '0',
		'convertMobile'            => '0', // 0 off, 1 auto, 2 always
		'convertHigh'              => '2',
		'convertHLS'               => '0',
		'convertPreview'           => '1',
		'convertWatermark'         => '',
		'convertWatermarkPosition' => '5:5',
		'originalBackup'           => '1',

		'custom_post'              => 'video',
		'custom_taxonomy'          => 'playlist',

		'postTemplate'             => '+plugin',
		'playlistTemplate'         => '+plugin',

		'videoWidth'               => '',

		'player_default'           => 'html5',
		'html5_player'             => 'video-js',
		'player_ios'               => 'html5',
		'player_safari'            => 'html5',
		'player_android'           => 'html5',
		'player_firefox_mac'       => 'html5',
		'playlist_player'          => 'video-js',

		'thumbWidth'               => '240',
		'thumbHeight'              => '180',
		'perPage'                  => '6',
		'perRow'                   => '0',

		'playlistVideoWidth'       => '960',
		'playlistListWidth'        => '350',

		'shareList'                => 'Super Admin, Administrator, Editor, Author, Contributor, Performer, Broadcaster',
		'publishList'              => 'Super Admin, Administrator, Editor, Author, Performer, Broadcaster',
		'embedList'                => 'None',

		'watchList'                => 'Super Admin, Administrator, Editor, Author, Contributor, Subscriber, Performer, Broadcaster, Client, Guest',
		'accessDenied'             => '<h3>Access Denied</h3>
<p>#info#</p>',
		'vod_role_playlist'        => '1',
		'vastLib'                  => 'iab',
		'vast'                     => '',
		'adsGlobal'                => '0',
		'premiumList'              => '',
		'tvshows'                  => '1',
		'tvshows_slug'             => 'tvshow',
		'uploadsPath'              => $upload_dir['basedir'] . '/vw_videoshare',
		'rtmpServer'               => 'rtmp://your-site.com/videowhisper-x2',
		'streamsPath'              => '/home/youraccount/public_html/streams/',
		'hlsServer'                => 'http://your-site.com:1935/videowhisper-x2/',
		'containerCSS'             => '

.videowhisperPlayerContainer
{
-webkit-align-content: center;
align-content: center;
text-align: center;
margin-left: auto;
margin-right: auto;
display: block;
padding: 1px;
}
',

		'customCSS'                => <<<HTMLCODE
.videowhisperVideoEdit
{
position: absolute;
top:34px;
right:0px;
margin:8px;
font-size: 11px;
color: #FFF;
text-shadow:1px 1px 1px #333;
background: rgba(0, 100, 255, 0.7);
padding: 3px;
border-radius: 3px;
z-index: 10;
}

.videowhisperVideo
{
position: relative;
display:inline-block;

border:1px solid #aaa;
background-color:#777;
padding: 0px;
margin: 2px;

width: 240px;
height: 180px;
overflow: hidden;
z-index: 0;
}

.videowhisperVideo:hover {
	border:1px solid #fff;
}

.videowhisperVideo IMG
{
position: absolute;
left:0px;
top:0px;
padding: 0px;
margin: 0px;
border: 0px;
z-index: 1;
}

.videowhisperVideo VIDEO
{
position: absolute;
left:0px;
top:0px;
padding: 0px;
margin: 0px;
border: 0px;
z-index: 1;
}


.videowhisperVideoTitle
{
position: absolute;
top:0px;
left:0px;
right:40px;
margin:5px;
font-size: 12px;
color: #FFF;
text-shadow:1px 1px 1px #333;
z-index: 10;
}

.videowhisperVideoRating
{
position: absolute;
bottom: 25px;
left:5px;
font-size: 15px;
color: #FFF;
text-shadow:1px 1px 1px #333;
z-index: 10;
}

.videowhisperVideoDuration
{
position: absolute;
bottom:0px;
left:0px;
margin:5px;
font-size: 12px;
color: #FFF;
text-shadow:1px 1px 1px #333;
background: rgba(30, 30, 30, 0.5);
padding: 2px;
border-radius: 4px;
z-index: 10;
}


.videowhisperVideoResolution
{
position: absolute;
top:0px;
right:0px;
margin:5px;
font-size: 12px;
color: #FFF;
text-shadow:1px 1px 1px #333;
background: rgba(255, 50, 0, 0.5);
padding: 2px;
border-radius: 4px;
z-index: 10;

}

.videowhisperVideoDate
{
position: absolute;
bottom:0px;
right:0px;
margin: 5px;
padding: 2px;
font-size: 10px;
color: #FFF;
text-shadow:1px 1px 1px #333;
z-index: 10;

}

.videowhisperVideoViews
{
position: absolute;
bottom:10px;
right:0px;
margin: 5px;
padding: 2px;
font-size: 10px;
color: #FFF;
text-shadow:1px 1px 1px #333;
z-index: 10;
}

HTMLCODE
				,
		'disableXOrigin'           => '0',
		'disableXOriginRef'        => '',
		'crossdomain_xml'          => '<cross-domain-policy>
<allow-access-from domain="*"/>
<site-control permitted-cross-domain-policies="master-only"/>
</cross-domain-policy>',

		'videowhisper'             => '0',
	);

}

static function getOptions() {
	$options = get_option( 'VWvideoShareOptions' );
	if ( ! empty( $options ) ) {
		return $options;
	} else {
		return self::adminOptionsDefault();
	}
}	

static function setupOptions() {

	$adminOptions = self::adminOptionsDefault();

	$options = get_option( 'VWvideoShareOptions' );
	if ( ! empty( $options ) ) {
		foreach ( $options as $key => $option ) {
			$adminOptions[ $key ] = $option;
		}
	}
	update_option( 'VWvideoShareOptions', $adminOptions );

	return $adminOptions;
}



static function adminOptions() {
			$options = self::setupOptions();

			// if ($options['convertQueue']) $options['convertQueue'] = trim($options['convertQueue']);

	if ( isset( $_POST ) ) {
		if ( ! empty( $_POST ) ) {

					$nonce = $_REQUEST['_wpnonce'];
			if ( ! wp_verify_nonce( $nonce, 'vwsec' ) ) {
				echo 'Invalid nonce!';
				exit;
			}

			foreach ( $options as $key => $value ) {
				if ( isset( $_POST[ $key ] ) ) {
					$options[ $key ] = trim( sanitize_textarea_field( $_POST[ $key ] ) );
				}
			}
			
				// sanitize html options	
				foreach (['listingTemplate' ] as $optionName) if ( isset( $_POST[$optionName] ) )
				$options[$optionName] = wp_kses_post( $_POST[$optionName] );

				
				//sanitize xml
				foreach ([ 'crossdomain_xml' ] as $optionName) if ( isset( $_POST[$optionName] ) )
				$options[$optionName] =  htmlspecialchars_decode( sanitize_textarea_field( htmlspecialchars( $_POST[$optionName] ) ) ) ;



						update_option( 'VWvideoShareOptions', $options );
		}
	}

			// self::setupPages();

			$optionsDefault = self::adminOptionsDefault();

			$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'support';
	?>


<div class="wrap">
<h2>Video Share / Video on Demand (VOD)</h2>
<h2 class="nav-tab-wrapper">
	<a href="admin.php?page=video-share&tab=server" class="nav-tab <?php echo $active_tab == 'server' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Server', 'video-share-vod' ); ?></a>
	<a href="admin.php?page=video-share&tab=pages" class="nav-tab <?php echo $active_tab == 'pages' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Pages', 'video-share-vod' ); ?></a>
	<a href="admin.php?page=video-share&tab=convert" class="nav-tab <?php echo $active_tab == 'convert' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Convert', 'video-share-vod' ); ?></a>
	<a href="admin.php?page=video-share&tab=listings" class="nav-tab <?php echo $active_tab == 'listings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Listings', 'video-share-vod' ); ?></a>
	<a href="admin.php?page=video-share&tab=players" class="nav-tab <?php echo $active_tab == 'players' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Players', 'video-share-vod' ); ?></a>
	<a href="admin.php?page=video-share&tab=share" class="nav-tab <?php echo $active_tab == 'share' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Publish: Video Share', 'video-share-vod' ); ?></a>
	<a href="admin.php?page=video-share&tab=vod" class="nav-tab <?php echo $active_tab == 'vod' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Access: Membership / VOD', 'video-share-vod' ); ?></a>	
	<a href="admin.php?page=video-share&tab=ls" class="nav-tab <?php echo $active_tab == 'ls' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Live Streams', 'video-share-vod' ); ?></a>
	<a href="admin.php?page=video-share&tab=tvshows" class="nav-tab <?php echo $active_tab == 'tvshows' ? 'nav-tab-active' : ''; ?>"><?php _e( 'TV Shows', 'video-share-vod' ); ?></a>
	<a href="admin.php?page=video-share&tab=vast" class="nav-tab <?php echo $active_tab == 'vast' ? 'nav-tab-active' : ''; ?>"><?php _e( 'VAST/IAB', 'video-share-vod' ); ?></a>
	<a href="admin.php?page=video-share&tab=reset" class="nav-tab <?php echo $active_tab == 'reset' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Reset', 'video-share-vod' ); ?></a>
	<a href="admin.php?page=video-share&tab=support" class="nav-tab <?php echo $active_tab == 'support' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Support', 'video-share-vod' ); ?></a>
</h2>

<form method="post" action="<?php echo wp_nonce_url( $_SERVER['REQUEST_URI'], 'vwsec' ); ?>">

	<?php
	switch ( $active_tab ) {

		case 'pages';

			/*
			'videowhisper_webcams' => __('Webcams', 'ppv-live-webcams'),
				'videowhisper_webcams_performer' => __('Performer Dashboard', 'ppv-live-webcams'),
				'videowhisper_webcams_studio' => __('Studio Dashboard', 'ppv-live-webcams'),
				'videowhisper_webcams_logout' => __('Chat Logout', 'ppv-live-webcams'),
				'videowhisper_cam_random' =>  __('Random Cam', 'ppv-live-webcams'),
				'videowhisper_webcams_client' => __('Client Dashboard', 'ppv-live-webcams'),
				*/
			?>
<h3>Setup Frontend Pages</h3>

			<?php
			if ( $_POST['submit'] ?? false ) {
				echo '<p>Saving pages setup.</p>';
				self::setupPages();
			}

			submit_button( __( 'Update Pages', 'ppv-live-webcams' ) );
			?>
Use this to setup pages on your site. Pages with main feature shortcodes are required to access main functionality. After setting up these pages you should add the feature pages to site menus for users to access.
A sample VideoWhisper menu will also be added when adding pages: can be configured to show in a menu section depending on theme.
<br>You can manage these anytime from backend: <a href="edit.php?post_type=page">pages</a> and <a href="nav-menus.php">menus</a>.

<h4>Setup Pages</h4>
<select name="disableSetupPages" id="disableSetupPages">
  <option value="0" <?php echo $options['disableSetupPages'] ? '' : 'selected'; ?>>Yes</option>
  <option value="1" <?php echo $options['disableSetupPages'] ? 'selected' : ''; ?>>No</option>
</select>
<br>Create pages for main functionality. Also creates a menu with these pages (VideoWhisper) that can be added to themes.
<br>After login performers are redirected to the dashboard page and clients to webcams page.


<h3>Feature Pages</h3>
These pages are required for specific turnkey site solution functionality. If you edit pages with shortcodes to add extra content, make sure shortcodes remain present.
			<?php

			$pages   = self::setupPagesList();
			$content = self::setupPagesContent();

			// get all pages
			$args   = array(
				'sort_order'   => 'asc',
				'sort_column'  => 'post_title',
				'hierarchical' => 1,
				'post_type'    => 'page',
				'numberposts' => 100,
			);
			$sPages = get_posts( $args );

			foreach ( $pages as $shortcode => $title ) {
				$pid = intval( $options[ 'p_' . $shortcode ] ?? 0 );
				if ( $pid != '' ) {

					echo '<h4>' . esc_html( $title ) . '</h4>';
					echo '<select name="p_' . esc_attr( $shortcode ) . '" id="p_' . esc_attr( $shortcode ) . '">';
					echo '<option value="0">Undefined: Reset</option>';
					foreach ( $sPages as $sPage ) {
						echo '<option value="' . esc_attr( $sPage->ID ) . '" ' . ( ( $pid == $sPage->ID ) ? 'selected' : '' ) . '>' . esc_html( $sPage->ID ) . '. ' . esc_html( $sPage->post_title ) . ' - ' . esc_html( $sPage->post_status ) . '</option>' . "\r\n";
					}
					echo '</select><br>';
					if ( $pid ) {
						echo '<a href="' . get_permalink( $pid ) . '">view</a> | ';
					}
					if ( $pid ) {
						echo '<a href="post.php?post=' . esc_attr( $pid ) . '&action=edit">edit</a> | ';
					}
					echo 'Default content: ' . ( array_key_exists( $shortcode, $content ) ? esc_html( $content[ $shortcode ] ) : esc_html( "[$shortcode]" ) ) . '';

				}
			}

			echo '<h3>VideoShareVOD Frontend Feature Pages</h3>';

			$noMenu = array( 'videowhisper_recorder' );

			foreach ( $pages as $shortcode => $title ) {
				if ( ! in_array( $shortcode, $noMenu ) ) {
								$pid = intval( $options[ 'p_' . $shortcode ] ?? 0 );
					if ( $pid ) {
						$url = get_permalink( $pid );
						echo '<p> - ' . esc_html( $title ) . ':<br>';
						echo '<a href="' . esc_url( $url ) . '">' . esc_html( $url ) . '</a></p>';

					}
				}
			}

			break;

		case 'support':
				// ! Support
			?>

<h3>Hosting Requirements</h3>
<UL>
<LI><a href="https://videosharevod.com/hosting/">Hosting Features Required</a> Video hosting specific feature required.
<LI><a href="http://videowhisper.com/?p=Video-Hosting-Business">Business Video Hosting</a> High volume hosting options.</LI>
</UL>

<h3>Solution Documentation</h3>
<UL>
<LI><a href="https://videosharevod.com/features/quick-start-tutorial/">VideoShareVOD Setup Tutorial</a> Tutorial to setup the Video Share VOD plugin.</LI>
<LI><a href="admin.php?page=video-share-docs">Backend Documentation</a> Includes documents shortcodes, external documentation links.</LI>
<LI><a href="https://videosharevod.com">VideoShareVOD Homepage</a> Solution site: features listing, snapshots, demos, downloads, suggestions.</LI>
<LI><a href="https://wordpress.org/plugins/video-share-vod/">WordPress Plugin</a> Plugin page on WordPress repository.</LI>
</UL>

<h3>Recommended Plugins</h3>
Here are some plugins that work in combination with VideShareVOD:
<UL>
<LI><a href="https://wordpress.org/plugins/video-posts-webcam-recorder/">Webcam Video Recorder</a> Site users can record videos from webcam. Can also be used to setup reaction recording: record webcam while playing an Youtube video.</LI>
<LI><a href="https://broadcastlivevideo.com">Broadcast Live Video</a> Broadcast live video channels from webcam, IP cameras, desktop/mobile encoder apps. Archive these videos, import and publish on site.</LI>
<LI><a href="https://paidvideochat.com">Paid Videochat</a> Run a turnkey pay per minute videochat site where performers can archive live shows or upload videos for their fans.</LI>
<LI><a href="https://wordpress.org/plugins/paid-membership/">Paid Membership and Content</a> Sell videos (per item) from frontend, sell membership subscriptions. Based on MyCred & TeraWallet/WooWallet (WooCommerce) tokens that can be purchased with real money gateways or earned on site.</LI>
</UL>


<h3>Premium Plugins / Addons</h3>
<ul>
	<LI><a href="http://themeforest.net/popular_item/by_category?category=wordpress&ref=videowhisper">Premium Themes</a> Professional WordPress themes.</LI>
	<LI><a href="https://woocommerce.com/?aff=18336&cid=1980980">WooCommerce</a> Free shopping cart plugin, supports multiple free and premium gateways with TeraWallet/WooWallet plugin and various premium eCommerce plugins.</LI>

	<LI><a href="https://woocommerce.com/products/woocommerce-memberships/?aff=18336&cid=1980980">WooCommerce Memberships</a> Setup paid membership as products. Leveraged with Subscriptions plugin allows membership subscriptions.</LI>

	<LI><a href="https://woocommerce.com/products/woocommerce-subscriptions/?aff=18336&cid=1980980">WooCommerce Subscriptions</a> Setup subscription products, content. Leverages Membership plugin to setup membership subscriptions.</LI>

	<LI><a href="https://woocommerce.com/products/woocommerce-bookings/?aff=18336&cid=1980980">WooCommerce Bookings</a> Let your customers book reservations, appointments on their own.</LI>

	<LI><a href="https://woocommerce.com/products/follow-up-emails/?aff=18336&cid=1980980">WooCommerce Follow Up</a> Follow Up by emails and twitter automatically, drip campaigns.</LI>

	<LI><a href="https://updraftplus.com/?afref=924">Updraft Plus</a> Automated WordPress backup plugin. Free for local storage. For production sites external backups are recommended (premium).</LI>
</ul>

<h3>Contact and Feedback</h3>
<a href="https://videowhisper.com/tickets_submit.php">Sumit a Ticket</a> with your questions, inquiries and VideoWhisper support staff will try to address these as soon as possible.
<br>Although the free solution does not include any services (as installation and troubleshooting), VideoWhisper staff can clarify requirements, features, installation steps or suggest paid services like installations, customisations, hosting you may need for your project.

<h3>Review and Discuss</h3>
You can publicly <a href="https://wordpress.org/support/plugin/video-share-vod/reviews/#new-post">review this WP plugin</a> on the official WordPress site (after <a href="https://wordpress.org/support/register.php">registering</a>). You can describe how you use it and mention your site for visibility. You can also post on the <a href="https://wordpress.org/support/plugin/video-share-vod">WP support forums</a> - these are not monitored by support so <a href="https://consult.videowhisper.com/">Open a Conversation</a> if you want to contact VideoWhisper.

				<?php
			break;

		case 'reset':
			?>
<h3><?php _e( 'Reset Options', 'video-share-vod' ); ?></h3>
This resets some options to defaults. Useful when upgrading plugin and new defaults are available for new features and for fixing broken installations.
			<?php

			$confirm = sanitize_text_field( $_GET['confirm'] );

			if ( $confirm == '1' ) {
				echo '<h4>Resetting...</h4>';
			} else {
				echo '<p><A class="button" href="' . get_permalink() . 'admin.php?page=video-share&tab=reset&confirm=1">Yes, Reset These Settings!</A></p>';
			}

			$resetOptions = array( 'customCSS', 'thumbWidth', 'thumbHeight', 'containerCSS', 'convertSingleProcess', 'convertInstant', 'custom_post', 'custom_taxonomy' );

			foreach ( $resetOptions as $opt ) {
				echo '<BR> - ' . esc_html( $opt );
				if ( $confirm ) {
					$options[ $opt ] = $optionsDefault[ $opt ] ;
				}
			}

			if ( $confirm ) {
				update_option( 'VWvideoShareOptions', $options );
			}

			break;

		case 'convert':
			// ! convert options
			?>
<h3><?php _e( 'Video Conversions', 'video-share-vod' ); ?></h3>
Uploaded videos are converted to formats and bitrates that can play on most devices, including a small preview to show in listings. Video conversion also optimizes bitrate usage and may include a custom watermark logo. See current progress queue on <a href="admin.php?page=video-share-conversion">Conversions Page</a>. Conversions require <a href="https://videosharevod.com/hosting/">web hosting with FFmpeg</a> and high resources (CPU & memory). 

<h4>Server Command Execution</h4>
<select name="enable_exec" id="enable_exec">
  <option value="0" <?php echo $options['enable_exec'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['enable_exec'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<BR>By default, all features that require executing server commands are disabled, for security reasons. Enable only after making sure your server is configured to safely execute server commands like FFmpeg. If you have own server, isolation is recommended with <a href="https://docs.cloudlinux.com/cloudlinux_os_components/#cagefs">CageFS</a> or similar tools.

	<?php
		
		if ( $options['enable_exec'] )
		{
			$fexec = 0;

			echo '<BR>exec: ';
			if ( function_exists( 'exec' ) ) {
				echo 'function is enabled';

				if ( exec( 'echo EXEC' ) == 'EXEC' ) {
					echo ' and works';
					$fexec = 1;
				} else {
					echo ' <b>but does not work</b>';
				}
			} else {
				echo '<b>function is not enabled</b><BR>PHP function "exec" is required to run FFmpeg. Current hosting settings are not compatible with this functionality.';
			}

			if ( $fexec ) {

				echo '<BR>FFMPEG: ';
				$cmd = sanitize_text_field( $options['ffmpegPath'] ) . ' -version';
				exec( escapeshellcmd( $cmd ), $output, $returnvalue );
				if ( $returnvalue == 127 ) {
					echo '<b>Warning: not detected: ' . esc_html( $cmd ) . '</b>'; } else {
							echo 'detected';
							echo '<BR>' . esc_html( $output[0] );
							echo '<BR>' . esc_html( $output[1] );
					}
			}
		} else echo '<H5>Running FFmpeg requires enabling Server Command Execution setting.</H5>';		
?>

<h4>Conversion Watermark</h4>
<input name="convertWatermark" type="text" id="convertWatermark" size="100" maxlength="256" value="<?php echo esc_attr( $options['convertWatermark'] ); ?>"/>
<BR>Add a floating watermark image over video (encoded in video when converting). Involves extra processing resources (CPU & memory) for conversions. Specify absolute path to image file on server (transparent PNG recommended), not web URL. Leave blank to disable.
<br> If you can apply watermark before uploading, that is a better option better as advanced quality/options can be achieved with desktop tools. Also less resources are taken away from web hosting account for processing.
<br> Warning: Leave blank to disable and reduce processing resource requirements. Conversions may fail when high processing (cpu threads & memory) is required, as for applying watermark and/or high bitrate. 
<br>
			<?php
			echo 'Ex:' . plugin_dir_path( __FILE__ ) . 'logo.png';

			if ( $options['convertWatermark'] ) {
				if ( file_exists( $options['convertWatermark'] ) ) {
					echo '<br>File found: ' . esc_html( $options['convertWatermark'] );
				} else {
					echo '<br>NOT Found: ' . esc_html( $options['convertWatermark'] );
				}
			}
			?>
<h4>Conversion Watermark Position</h4>
<input name="convertWatermarkPosition" type="text" id="convertWatermarkPosition" size="100" maxlength="256" value="<?php echo esc_attr( $options['convertWatermarkPosition'] ); ?>"/>
<BR>Position for <a href="https://ffmpeg.org/ffmpeg-filters.html#overlay-1">overlay filter</a>. In example, 4px from top right corner: main_w-overlay_w-4:4


<h4><?php _e( 'Allow Original Video as Backup', 'video-share-vod' ); ?></h4>
<select name="originalBackup" id="originalBackup">
  <option value="1" <?php echo ( $options['originalBackup'] == '1' ) ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['originalBackup'] ? '' : 'selected'; ?>>No</option>
</select>
<BR>Use original as backup playback solution if it's in appropriate format and no suitable conversion is available.
<BR>Viewer will see original video (without watermark).
<BR>This can be useful to make video accessible fast, before conversion is done, if available in suitable format.

<h4><?php _e( 'Convert to High HTML5 Format', 'video-share-vod' ); ?></h4>
<select name="convertHigh" id="convertHigh">
  <option value="3" <?php echo ( $options['convertHigh'] == '3' ) ? 'selected' : ''; ?>>Always</option>
  <option value="2" <?php echo ( $options['convertHigh'] == '2' ) ? 'selected' : ''; ?>>Auto & Bitrate</option>
  <option value="1" <?php echo ( $options['convertHigh'] == '1' ) ? 'selected' : ''; ?>>Auto</option>
  <option value="0" <?php echo $options['convertHigh'] ? '' : 'selected'; ?>>No</option>
</select>
<BR>Convert video to high quality mp4 (h264,aac). This is required on most setups.
<BR><b>Auto</b> converts only if source is not mp4 and copies h264/aac tracks if available.
<BR><b>Auto & Bitrate</b>  converts if source is not mp4 and/or bitrate is higher that <a href="http://www.videochat-scripts.com/recommended-h264-video-bitrate-based-on-resolution/">recommended</a> (which could cause interruptions, buffering for users and high server bandwidth usage without major quality benefits).
<BR><b>Always</b> will convert anyway (and apply watermark if configured).

<h4>High HD Bitrate</h4>
<input name="bitrateHD" type="text" id="bitrateHD" size="12" maxlength="20" value="<?php echo esc_attr( $options['bitrateHD'] ); ?>"/>
<BR>Bitrate for 1920x1080 resolution. For other resolutions bitrate is adjusted proportional to number of pixels. Default: <?php echo esc_attr( $optionsDefault['bitrateHD'] ); ?>


<h4>High Video Codec</h4>
<input name="codecVideoHigh" type="text" id="codecVideoHigh" size="100" maxlength="256" value="<?php echo esc_attr( $options['codecVideoHigh'] ); ?>"/>
<BR>Bitrate is calculated depending on resolution (do not include in encoding parameters).
<BR>Ex: -c:v libx264 -profile:v main -level 3.1


<h4>High Audio Codec</h4>
<input name="codecAudioHigh" type="text" id="codecAudioHigh" size="100" maxlength="256" value="<?php echo esc_attr( $options['codecAudioHigh'] ); ?>"/>
<BR>Ex.(latest FFMPEG with libfdk_aac): -c:a libfdk_aac -b:a 128k
<BR>Ex.(latest FFMPEG with native aac): -c:a aac -b:a 128k
<BR>Ex.(older FFMPEG with libfaac): -c:a libfaac -ac 2 -ar 44100 -ab 128k

<h4>Attach to Media Library (High)</h4>
<select name="attachHigh" id="attachHigh">
  <option value="1" <?php echo ( $options['attachHigh'] == '1' ) ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['attachHigh'] ? '' : 'selected'; ?>>No</option>
</select>

<h4><?php _e( 'Convert to Mobile HTML5 Format', 'video-share-vod' ); ?></h4>
<select name="convertMobile" id="convertMobile">
  <option value="2" <?php echo ( $options['convertMobile'] == '2' ) ? 'selected' : ''; ?>>Always</option>
  <option value="1" <?php echo ( $options['convertMobile'] == '1' ) ? 'selected' : ''; ?>>Auto</option>
  <option value="0" <?php echo $options['convertMobile'] ? '' : 'selected'; ?>>No</option>
</select>
<BR>Convert video to mobile quality mp4 (h264,aac) at 600kbps. This is optional, for supporting older devices and low connection users.
<BR>Auto converts only if source is not mp4.
<BR>When targetting latest devices "high" format can be used for all players and "mobile" format disabled. When using multi bitrate (MBR) sources, mobile variant can be used on slow connections (like mobile connection with poor signal) to permit adaptive bitrate (ABR) playback.

<h4>Mobile Video Codec</h4>
<input name="codecVideoMobile" type="text" id="codecVideoMobile" size="100" maxlength="256" value="<?php echo esc_attr( $options['codecVideoMobile'] ); ?>"/>
<BR>Bitrate is fixed (do not include in encoding parameters). Recent mobiles support high profiles.
<BR>-c:v libx264 -profile:v main -level 3.1
<BR>-c:v libx264 -movflags +faststart -profile:v baseline -level 3.1

<h4>Mobile Audio Codec</h4>
<input name="codecAudioMobile" type="text" id="codecAudioMobile" size="100" maxlength="256" value="<?php echo esc_attr( $options['codecAudioMobile'] ); ?>"/>
<BR>Ex.(latest FFMPEG with libfdk_aac): -c:a libfdk_aac -b:a 64k
<BR>Ex.(latest FFMPEG with native aac): -c:a aac -b:a 64k
<BR>Ex.(older FFMPEG with libfaac): -c:a libfaac -ac 2 -ar 22050 -ab 64k



<h4><?php _e( 'Convert to Preview Format', 'video-share-vod' ); ?></h4>
<select name="convertPreview" id="convertPreview">
  <option value="1" <?php echo ( $options['convertPreview'] == '1' ) ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['convertPreview'] ? '' : 'selected'; ?>>No</option>
</select>
<BR>Generates a thumbnail resolution sized, short preview. Preview is cropped to match thumbnail aspect ratio.

<h4>Preview Input Parameters</h4>
<input name="convertPreviewInput" type="text" id="convertPreviewInput" size="100" maxlength="256" value="<?php echo esc_attr( $options['convertPreviewInput'] ); ?>"/>
<BR>Start from 5s: -ss 5

<h4>Preview Output Parameters</h4>
<input name="convertPreviewOutput" type="text" id="convertPreviewOutput" size="100" maxlength="256" value="<?php echo esc_attr( $options['convertPreviewOutput'] ); ?>"/>
<BR>Duration 10s: -t 10

<h4>Preview Video Codec</h4>
<input name="codecVideoPreview" type="text" id="codecVideoPreview" size="100" maxlength="256" value="<?php echo esc_attr( $options['codecVideoPreview'] ); ?>"/>
<BR>Bitrate is calculated depending on resolution (do not include in encoding parameters).
<BR>Ex: -c:v libx264 -movflags +faststart -profile:v baseline -level 3.1

<h4>Preview Audio Codec</h4>
<input name="codecAudioPreview" type="text" id="codecAudioPreview" size="100" maxlength="256" value="<?php echo esc_attr( $options['codecAudioPreview'] ); ?>"/>
<BR>Ex.(latest FFMPEG with libfdk_aac): -c:a libfdk_aac -b:a 64k
<BR>Ex.(older FFMPEG with libfaac): -c:a libfaac -ac 2 -ar 22050 -ab 64k


<h4>Attach to Media Library (Preview)</h4>
<select name="attachPreview" id="attachPreview">
  <option value="1" <?php echo ( $options['attachPreview'] == '1' ) ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['attachPreview'] ? '' : 'selected'; ?>>No</option>
</select>


<h4><?php _e( 'Generate HLS Segments', 'video-share-vod' ); ?></h4>
<select name="convertHLS" id="convertHLS">
  <option value="1" <?php echo ( $options['convertHLS'] == '1' ) ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['convertHLS'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Not usually required.
<BR>Generates static .ts segments and .m3u8 playlist for HLS playback of conversions. Accessible at /mbr/hls/[video-id].m3u8
<BR>Segmentation is triggered with conversion (no conversions results in no segmentation).
<BR>Space Warning: This more than doubles necessary storage space for videos. It's a faster alternative to using a HLS server that generates segments live. Uses space but considerably improves latency and server load.
Best performance for VOD is to deliver existing videos directly trough web server or pre-segmented as HLS.
Playtime segmentation trough a streaming server involves higher latency and high server load (reduced viewer capacity) as video needs to be processed per viewer access (different viewers start watching at different times and need different position packets).
It's best to stream live sources trough streaming server (stream is packetized once during broadcast) and videos trough regular web server (that passes existing content without processing).

<!-- Advanced Options, for development
<h4><?php _e( 'Multiple Formats in Single Process', 'video-share-vod' ); ?></h4>
<select name="convertSingleProcess" id="convertSingleProcess">
  <option value="1" <?php echo $options['convertSingleProcess'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['convertSingleProcess'] ? '' : 'selected'; ?>>No</option>
</select>
<BR>Creates all required video formats (high, mobile) in a single conversion process. This can increase overall performance (source is only read once) but involves higher memory requirements. If disabled each format is created in a different process (recommended).
<br>Warning: Enabling this on shared hosting often results in conversion failure due to resource limitations.

<h4><?php _e( 'Instant Conversion', 'video-share-vod' ); ?></h4>
<select name="convertInstant" id="convertInstant">
  <option value="1" <?php echo $options['convertInstant'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['convertInstant'] ? '' : 'selected'; ?>>No</option>
</select>
<BR>Starts conversion instantly, without using a conversion queue. Not recommended as multiple conversion processes at same time could temporary freeze server and/or fail.
<br>Warning: Enabling this on shared hosting often results in conversion failure due to resource limitations.
-->

<h3><?php _e( 'Troubleshooting' ); ?></h3>
<br>For a quick, hassle free setup, see <a href="https://videosharevod.com/hosting/" target="_vsvhost">VideoShareVOD turnkey managed hosting plans</a> for business video hosting, from $20/mo, including plugin installation, configuration.</p>


This section should aid in troubleshooting conversion issues.
			<?php
				
			if ( $options['enable_exec'] )
			{
				
			$fexec = 0;

			echo '<BR>exec: ';
			if ( function_exists( 'exec' ) ) {
				echo 'function is enabled';

				if ( exec( 'echo EXEC' ) == 'EXEC' ) {
					echo ' and works';
					$fexec = 1;
				} else {
					echo ' <b>but does not work</b>';
				}
			} else {
				echo '<b>function is not enabled</b><BR>PHP function "exec" is required to run FFMPEG. Current hosting settings are not compatible with this functionality.';
			}

			if ( $fexec ) {

				echo '<BR>FFMPEG: ';
				$cmd = sanitize_text_field( $options['ffmpegPath'] ) . ' -version';
				exec( escapeshellcmd( $cmd ), $output, $returnvalue );
				if ( $returnvalue == 127 ) {
					echo '<b>Warning: not detected: ' . esc_html( $cmd ) . '</b>'; } else {
							echo 'detected';
							echo '<BR>' . esc_html( $output[0] );
							echo '<BR>' . esc_html( $output[1] );
					}

					$cmd = sanitize_text_field( $options['ffmpegPath'] ) . ' -codecs';
					exec( escapeshellcmd( $cmd ), $output, $returnvalue );

					// detect codecs
					if ( $output ) {
						if ( count( $output ) ) {
							echo '<br>Codec libraries:';
							foreach ( array( 'h264', 'vp6', 'vp8', 'vp9', 'speex', 'nellymoser', 'opus', 'h263', 'mpeg', 'mp3', 'fdk_aac', 'faac' ) as $cod ) {
								$det  = 0;
								$outd = '';
								echo '<BR>' . esc_html( "$cod : " );
								foreach ( $output as $outp ) {
									if ( strstr( $outp, $cod ) ) {
										$det  = 1;
										$outd = $outp;
									}
								};
								if ( $det ) {
									echo esc_html( "detected ($outd)" );
								} else {
									echo esc_html( "missing: configure and install FFMPEG with lib$cod if you don't have another library for that codec and need it for input or output" );
								}
							}
						}
					}
					?>
<BR>You need only 1 AAC codec. Depending on <a href="https://trac.ffmpeg.org/wiki/Encode/AAC#libfaac">AAC library available on your system</a> you may need to update transcoding parameters. Latest FFMPEG also includes a native encoder (aac).

				<?php
			}
		} else echo '<H5>Running FFmpeg requires enabling Server Command Execution setting.</H5>';
			?>
<h4><?php _e( 'CloudLinux Shared Hosting Requirements' ); ?></h4>
CPU Speed: FFMPEG will be called with "-threads 1" to use just 1 thread (meaning 100% of 1 cpu core). That means on cloud limited environments account will need at least 100% CPU speed (to use at least 1 full core) to run conversions.
<BR>Memory: Depending on settings, conversions can fail with "x264 [error]: malloc" error if memory limit does not permit doing conversion. While "mobile" conversion can usually be done with 512Mb memory limit, for "high" quality settings (HD) 768Mb or more would be needed.

<h4><?php _e( 'System Process Limitations' ); ?></h4>
User limits can prevent conversions. Setting cpu limit to 7200 to prevent early termination:<br>
			<?php
			if ( $fexec ) {
				$cmd    = 'ulimit -t 7200; ulimit -a';
				$output = '';
				exec( escapeshellcmd( $cmd ), $output, $returnvalue );
				foreach ( $output as $outp ) {
					echo esc_html( $outp ) . '<br>';
				}
			} else {
				echo 'Not functional without exec.';
			}

			break;

		case 'tvshows':
			// ! tvshows options
			?>
<h3><?php _e( 'TV Shows', 'video-share-vod' ); ?></h3>

<h4><?php _e( 'Enable TV Shows Post Type', 'video-share-vod' ); ?></h4>
Allows setting up TV Shows as custom post types. Plugin will automatically generate playlists for all TV shows so videos can be assigned to TV shows.
<br><select name="tvshows" id="tvshows">
  <option value="1" <?php echo $options['tvshows'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['tvshows'] ? '' : 'selected'; ?>>No</option>
</select>

<h4><?php _e( 'TV Shows Slug', 'video-share-vod' ); ?></h4>
<input name="tvshows_slug" type="text" id="tvshows_slug" size="16" maxlength="32" value="<?php echo esc_attr( $options['tvshows_slug'] ); ?>"/>
			<?php
			break;
				// ! server options
		case 'server':
			?>
<h3><?php _e( 'Server Configuration', 'video-share-vod' ); ?></h3>
For best experience with implementing all plugin features and site performance, take a look at these <a href="https://videosharevod.com/hosting/">premium video streaming hosting plans and servers</a> we recommend. Installation and configuration is included with these plans.


<h4><?php _e( 'Uploads Path', 'video-share-vod' ); ?></h4>
			<?php
			if ( $options['uploadsPath'] == $optionsDefault['uploadsPath'] ) {
				if ( file_exists( ABSPATH . 'streams' ) ) {
						$options['uploadsPath'] = ABSPATH . 'streams/_video-share-vod';
						echo 'Save to apply! Suggested: ' . esc_html( $options['uploadsPath'] ) . '<br>';
				}
			}
			?>
<p><?php _e( 'Path where video files will be stored. Make sure you use a location outside plugin folder to avoid losing files on updates and plugin uninstallation.', 'video-share-vod' ); ?></p>
<input name="uploadsPath" type="text" id="uploadsPath" size="100" maxlength="256" value="<?php echo esc_attr( $options['uploadsPath'] ); ?>"/>
<br>Ex: /home/-your-account-/public_html/wp-content/uploads/vw_videoshare
<br>Ex: /home/-your-account-/public_html/streams/_vsv - with RTMP VOD in streams folder
<br>Ex: C:/Inetpub/vhosts/-your-account-/httpdocs/streams/_vsv - on Windows
<br>If you need to setup RTMP delivery, this needs to be inside the streams folder configured for VOD delivery with RTMP server/application.
<br>If you ever decide to change this, previous files must remain in old location.

			<?php
			if ( ! file_exists( $options['uploadsPath'] ) ) {
				echo '<br><b>Warning: Folder does not exist. If this warning persists after first access check path permissions:</b> ' . esc_html( $options['uploadsPath'] );
			}
			if ( ! strstr( $options['uploadsPath'], get_home_path() ) ) {
				echo '<br><b>Warning: Uploaded files may not be accessible by web (path is not within WP installation path).</b>';
			}

			echo '<br>WordPress Path: ' . get_home_path();
			echo '<br>WordPress URL: ' . get_site_url();
			?>
<br>wp_upload_dir()['basedir'] : 
			<?php
			$wud = wp_upload_dir();
			echo esc_html( $wud['basedir'] );
			?>
<br>$_SERVER['DOCUMENT_ROOT'] : <?php echo esc_html( $_SERVER['DOCUMENT_ROOT'] ); ?>


<h4>Server Command Execution</h4>
<select name="enable_exec" id="enable_exec">
  <option value="0" <?php echo $options['enable_exec'] ? '' : 'selected'; ?>>Disabled</option>
  <option value="1" <?php echo $options['enable_exec'] ? 'selected' : ''; ?>>Enabled</option>
</select>
<BR>By default, all features that require executing server commands are disabled, for security reasons. Enable only after making sure your server is configured to safely execute server commands like FFmpeg. If you have own server, isolation is recommended with <a href="https://docs.cloudlinux.com/cloudlinux_os_components/#cagefs">CageFS</a> or similar tools.

					<?php

					if ( $options['enable_exec'] ) {
						?>

<h4><?php _e( 'FFMPEG Path', 'video-share-vod' ); ?></h4>
						<?php
							$cmd    = 'which ffmpeg';
							$output = '';
						if ( function_exists( 'exec' ) ) {
							exec( escapeshellcmd( $cmd ), $output, $returnvalue );
						} else {
							$ffmpegPath = '';
						}

							$ffmpegPath = implode( $output );

						if ( $options['ffmpegConfiguration'] ) {
							if ( $ffmpegPath ) {
								if ( $ffmpegPath[0] == '/' ) {
																$options['ffmpegPath'] = $ffmpegPath;
								}
							}
						}

						?>
<p><?php _e( 'Path to latest FFmpeg. FFmpeg is a compulsory requirement for extracting snapshots, video info and converting videos.', 'video-share-vod' ); ?>
<br>For a quick, hassle free setup, see <a href="https://videosharevod.com/hosting/" target="_vsvhost">VideoShareVOD turnkey managed hosting plans</a> for business video hosting, from $20/mo, including plugin installation, configuration.</p>
<input name="ffmpegPath" type="text" id="ffmpegPath" size="100" maxlength="256" value="<?php echo esc_attr( $options['ffmpegPath'] ); ?>"/>
						<?php

							$fexec = 0;

							echo '<BR>exec: ';
						if ( function_exists( 'exec' ) ) {
							echo 'function is enabled';

							if ( exec( 'echo EXEC' ) == 'EXEC' ) {
								echo ' and works';
								$fexec = 1;
							} else {
								echo ' <b>but does not work</b>';
							}
						} else {
							echo '<b>function is not enabled</b><BR>PHP function "exec" is required to run FFMPEG. Current hosting settings are not compatible with this functionality.';
						}

						if ( $fexec ) {

							echo '<BR>FFMPEG: ';

							echo '<br>exec("which ffmpeg"): ';
							echo esc_html( $ffmpegPath );

							if ( file_exists( $options['ffmpegPath'] ) ) {
								echo '<br>File exists: ' . esc_html( $options['ffmpegPath'] );
							} else {
								echo '<br>File does not exist: ' . esc_html( $options['ffmpegPath'] );
							}

							$cmd = sanitize_text_field( $options['ffmpegPath'] ) . ' -version';
							exec( escapeshellcmd( $cmd ), $output, $returnvalue );
							if ( $returnvalue == 127 ) {
								echo '<b>Warning: not detected: ' . esc_html( $cmd ) . '</b>'; } else {
								echo '<br>exec ffmpeg returned: ' . esc_html( $returnvalue );
								echo '<BR>' . esc_html( $output[0] );
								echo '<BR>' . esc_html( $output[1] );
								}

								$cmd = sanitize_text_field( $options['ffmpegPath'] ) . ' -codecs';
								exec( escapeshellcmd( $cmd ), $output, $returnvalue );

								// detect codecs
								$hlsAudioCodec = 'aac'; // hlsAudioCodec

								if ( $output ) {
									if ( count( $output ) ) {
											echo '<br>Codec libraries:';
										foreach ( array( 'h264', 'vp6', 'vp8', 'vp9', 'speex', 'nellymoser', 'opus', 'h263', 'mpeg', 'mp3', 'aacplus', 'vo_aacenc', 'faac', 'fdk_aac' ) as $cod ) {
											$det  = 0;
											$outd = '';
											echo '<BR>' . esc_html( "$cod : " );
											foreach ( $output as $outp ) {
												if ( strstr( $outp, $cod ) ) {
																$det  = 1;
																$outd = $outp;
												}
											};

											if ( $det ) {
												echo 'detected (' . esc_html( $outd ) . ')';
											} elseif ( in_array( $cod, array( 'aacplus', 'vo_aacenc', 'faac', 'fdk_aac' ) ) ) {
												echo 'lib' . esc_html( $cod ) . ' is missing but other aac codec may be available';
											} else {
												echo '<b>missing: configure and install FFMPEG with lib' . esc_html( $cod ) . " if you don't have another library for that codec</b>";
											}

											if ( $det && in_array( $cod, array( 'aacplus', 'vo_aacenc', 'faac', 'fdk_aac' ) ) ) {
												$hlsAudioCodec = 'lib' . $cod;
											}
										}
									}
								}
								?>
					<BR>You need at least 1 AAC codec. Depending on <a href="https://trac.ffmpeg.org/wiki/Encode/AAC#libfaac">AAC library available on your system</a> you may need to update transcoding parameters. Latest FFMPEG also includes a native encoder (aac).
					<BR>Detected AAC: <?php echo esc_html( $hlsAudioCodec ); ?>.
							<?php
						}
						?>

<h4>FFMPEG Codec Configuration</h4>
<select name="ffmpegConfiguration" id="ffmpegConfiguration">
  <option value="0" <?php echo $options['ffmpegConfiguration'] ? '' : 'selected'; ?>>Manual</option>
  <option value="1" <?php echo $options['ffmpegConfiguration'] == 1 ? 'selected' : ''; ?>>Auto</option>
</select>
<BR>Auto will configure based on detected AAC codec libraries (recommended). Requires saving settings to apply.

						<?php
							$hlsAudioCodecReadOnly = '';

						if ( $options['ffmpegConfiguration'] ) {
							if ( ! $hlsAudioCodec ) {
								$hlsAudioCodec = 'aac';
							}

							$options['codecAudioHigh']    = " -c:a $hlsAudioCodec -b:a 128k ";
							$options['codecAudioMobile']  = " -c:a $hlsAudioCodec -b:a 64k ";
							$options['codecAudioPreview'] = " -c:a $hlsAudioCodec -b:a 64k ";

							$hlsAudioCodecReadOnly = 'readonly';
						}
						?>

<h4>High Audio Codec</h4>
<input name="codecAudioHigh" type="text" id="codecAudioHigh" <?php echo esc_attr( $hlsAudioCodecReadOnly ); ?> size="100" maxlength="256" value="<?php echo esc_attr( $options['codecAudioHigh'] ); ?>"/>


<h4>Mobile Audio Codec</h4>
<input name="codecAudioMobile" type="text" id="codecAudioMobile" <?php echo esc_attr( $hlsAudioCodecReadOnly ); ?> size="100" maxlength="256" value="<?php echo esc_attr( $options['codecAudioMobile'] ); ?>"/>


<h4>Preview Audio Codec</h4>
<input name="codecAudioPreview" type="text" id="codecAudioPreview" <?php echo esc_attr( $hlsAudioCodecReadOnly ); ?> size="100" maxlength="256" value="<?php echo esc_attr( $options['codecAudioPreview'] ); ?>"/>


<h4><?php _e( 'FFMPEG Process Control', 'video-share-vod' ); ?></h4>
						<?php
							echo 'Detection: exec("which cpulimit"): ';
							$cmd    = 'which cpulimit';
							$output = '';
							exec( escapeshellcmd( $cmd ), $output, $returnvalue );
							$cpulimitPath = implode( $output );
							echo esc_html( $cpulimitPath );

						if ( $options['ffmpegConfiguration'] ) {
							$options['ffmpegControl'] = 'nice';
							if ( $cpulimitPath ) {
								if ( $cpulimitPath[0] == '/' ) {
									$options['ffmpegControl'] = $cpulimitPath . ' -z --limit 75';
								}
							}
						}
						?>
<p><?php _e( 'Process control command, to make sure ffmpeg does not slow down site or cause failures due to hosting limits', 'video-share-vod' ); ?>
<br><input name="ffmpegControl" type="text" id="ffmpegControl" <?php echo esc_attr( $hlsAudioCodecReadOnly ); ?> size="100" maxlength="256" value="<?php echo esc_attr( $options['ffmpegControl'] ); ?>"/>
<br>When using cpulimit, configure processing power as percent of available cores. If plan resources allow 100% cpu (1 core), a 75 limit will leave only 25% for web requests during conversions.
<br>Ex:
<br>nice
<br>/usr/bin/nice
<br>/usr/bin/cpulimit -z --limit 75

<?php
} else echo '<H5>Running FFmpeg requires enabling Server Command Execution setting.</H5>'; // if  ($options['enable_exec'])

	?>

<h4>Delete Original on Import</h4>
<select name="deleteOnImport" id="deleteOnImport">
  <option value="1" <?php echo $options['deleteOnImport'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['deleteOnImport'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Remove original video file after copy to new location.

<h4>Add Original to Media Library</h4>
<select name="originalLibrary" id="originalLibrary">
  <option value="1" <?php echo $options['originalLibrary'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['originalLibrary'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Requires keeping originals (do not remove or clean) to prevent orphaned entries. Conversions can also be added to Media Library if enabled from Conversion settings.


<h4>Import Path</h4>
<p>Server path to import videos from</p>
						<?php
						if ( $options['importPath'] == $optionsDefault['importPath'] ) {
							if ( file_exists( ABSPATH . 'streams' ) ) {
								$options['importPath'] = ABSPATH . 'streams/';
								echo 'Save to apply! Detected: ' . esc_html( $options['importPath'] ) . '<br>';
							}
						}
						?>
<input name="importPath" type="text" id="importPath" size="100" maxlength="256" value="<?php echo esc_attr( $options['importPath'] ); ?>"/>
<br>Ex: /home/[youraccount]/public_html/streams/ (ending in /)

<h4>Import Clean</h4>
<p>Delete videos older than:</p>
<input name="importClean" type="text" id="importClean" size="5" maxlength="8" value="<?php echo esc_attr( $options['importClean'] ); ?>"/>days
<br>Set 0 to disable automated cleanup on imports folder (not recommended as an active site can fill up server disks with broadcast archives). Cleanup does not occur more often than 10h to prevent high load.

<h4>RTMP Address</h4>
<p>Optional: Required only for RTMP playback. As Flash players are no longer supported in major web browsers this can only be used for mobile/pc apps that stream video over RTMP. Recommended: <a href="https://webrtchost.com/hosting-plans/" target="_blank">Wowza RTMP Hosting</a>.
<br>RTMP application address for playback.</p>
<input name="rtmpServer" type="text" id="rtmpServer" size="100" maxlength="256" value="<?php echo esc_attr( $options['rtmpServer'] ); ?>"/>
<br>Ex: rtmp://your-site.com/videowhisper-x2
<br>Do not use a rtmp address that requires some form of authentication or verification done by another web script as player will not be able to connect.
<br>Avoid using a shared rtmp address. Setup a special rtmp application for playback of videos. For Wowza configure &lt;StreamType&gt;file&lt;/StreamType&gt;.

<h4>RTMP Streams Path</h4>
						<?php
						if ( $options['streamsPath'] == $optionsDefault['streamsPath'] ) {
							if ( file_exists( ABSPATH . 'streams' ) ) {
								$options['streamsPath'] = ABSPATH . 'streams/';
								echo 'Save to apply! Detected: ' . esc_html( $options['streamsPath'] ) . '<br>';
							}
						}
						?>

<p>Optional: Required only for RTMP playback.
<br>Path where rtmp server is configured to stream videos from. Uploads path must be a subfolder of this path to allow rtmp access to videos. </p>
<input name="streamsPath" type="text" id="streamsPath" size="100" maxlength="256" value="<?php echo esc_attr( $options['streamsPath'] ); ?>"/>
<br>This must be a substring of, or same as Uploads Path.
<br>Ex: /home/your-account/public_html/streams
						<?php
						if ( ! strstr( $options['uploadsPath'], $options['streamsPath'] ) ) {
							echo '<br><b class="error">Current value seems wrong!</b>';
						} else {
							echo '<br>Current value seems fine.';
						}
						?>

<h4>Path to Video Archive</h4>
						<?php
						if ( $options['vwls_archive_path'] == $optionsDefault['vwls_archive_path'] ) {
							if ( file_exists( ABSPATH . 'streams' ) ) {
								$options['vwls_archive_path'] = ABSPATH . 'streams/';
								echo 'Save to apply! Detected: ' . esc_html( $options['vwls_archive_path'] ) . '<br>';
							}
						}
						?>

<input name="vwls_archive_path" type="text" id="vwls_archive_path" size="100" maxlength="256" value="<?php echo esc_attr( $options['vwls_archive_path'] ); ?>"/>
<br>Ex: /home/your-account/public_html/streams/
<br>When using Wowza Streaming Engine configure [install-dir]/conf/Server.xml to save as FLV instead of MP4:
<br>&lt;DefaultStreamPrefix&gt;flv&lt;/DefaultStreamPrefix&gt;
<br>FLV includes support for web based flash audio codecs.


<h4>HLS URL</h4>
<p>Optional: Required only for HLS playback. Not recommend, for performance reasons (use HLS pre-segmentation during conversion instead if you want to deliver as HLS).
<br>HTTPS address to access by HTTP Live Streaming (HLS).</p>
<input name="hlsServer" type="text" id="hlsServer" size="100" maxlength="256" value="<?php echo esc_attr( $options['hlsServer'] ); ?>"/>
<br>Ex: https://your-site.com:1935/videowhisper-x2/
<br>Streaming server needs to be configured with a SSL certificate for HTTPS delivery.
<br>For Wowza disable live packetizers: &lt;LiveStreamPacketizers&gt;&lt;/LiveStreamPacketizers&gt;.
<br>Performance Warning: Best performance for VOD is to deliver existing videos directly trough web server or pre-segmented as HLS.
Playtime segmentation trough a streaming server involves higher latency and high server load (reduced viewer capacity) as video needs to be processed per viewer access (different viewers start watching at different times and need different position packets).
It's best to stream live sources trough streaming server (stream is packetized once during broadcast) and videos trough regular web server (that passes existing content without processing).
						<?php
							break;
							
						case 'ls':
							// ! ls options
							?>
<h3>Live Streams</h3>
Video Share VOD can import and manage videos generated by archiving live streams (from broadcasts and videochats). Multiple VideoWhisper video communication plugins can use this functionality for managing stream archives.

<h4>Path to Video Archive</h4>
							<?php
							if ( $options['vwls_archive_path'] == $optionsDefault['vwls_archive_path'] ) {
								if ( file_exists( ABSPATH . 'streams' ) ) {
									$options['vwls_archive_path'] = ABSPATH . 'streams/';
									echo 'Save to apply! Detected: ' . esc_html( $options['vwls_archive_path'] ) . '<br>';
								}
							}
							?>

<input name="vwls_archive_path" type="text" id="vwls_archive_path" size="100" maxlength="256" value="<?php echo esc_attr( $options['vwls_archive_path'] ); ?>"/>
<br>Ex: /home/your-account/public_html/streams/
<br>When using Wowza Streaming Engine configure [install-dir]/conf/Server.xml to save as FLV instead of MP4:
<br>&lt;DefaultStreamPrefix&gt;flv&lt;/DefaultStreamPrefix&gt;
<br>FLV includes support for web based flash audio codecs.


<h3>Broadcast Live Video - Live Streaming</h3>
<p>
Find more about <a target="_blank" href="https://videosharevod.com/features/live-streaming/">VideoShareVOD Live Streaming functionality</a> and <a href="http://broadcastlivevideo.com/">Broadcast Live Video turnkey live streaming site solution</a>. <br>
VideoWhisper Live Streaming is a plugin that allows users to broadcast live video channels.
<br>Detection:
							<?php
							if ( class_exists( 'VWliveStreaming' ) ) {
								echo 'Installed.';
							} else {
								echo 'Not detected. Please install and activate <a href="https://wordpress.org/plugins/videowhisper-live-streaming-integration/">WordPress Broadcast Live Video - Live Streaming plugin</a> to use this functionality.';
							}
							?>
</p>

<h4>Import Live Streaming Playlists</h4>
Enables Live Streaming channel owners to import archived streams. Videos must be archived locally.
<br><select name="vwls_playlist" id="vwls_playlist">
  <option value="1" <?php echo $options['vwls_playlist'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['vwls_playlist'] ? '' : 'selected'; ?>>No</option>
</select>

<h4>List Channel Videos</h4>
List videos on channel page.
<br><select name="vwls_channel" id="vwls_channel">
  <option value="1" <?php echo $options['vwls_channel'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['vwls_channel'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Videos are associated to channel using a playlist with same name as channel. If channel requires payment (setup with <a href="https://wordpress.org/plugins/paid-membership/">Paid Membership & Content</a>), channel videos are only accessible if user paid for the channel.


							<?php
							break;
						case 'players':
							$options['crossdomain_xml'] = stripslashes( $options['crossdomain_xml'] );
							$options['containerCSS']    = stripslashes( $options['containerCSS'] );

							$crossdomain_url = site_url() . '/crossdomain.xml';

							?>
<h3><?php _e( 'Players', 'video-share-vod' ); ?></h3>
							<?php _e( 'Strobe RTMP supports multi bitrate sources provided by plugin as:', 'video-share-vod' ); ?> /mbr/rtmp/[video-id].f4m
<br><?php _e( 'HTML playback is supported on most browsers and devices.', 'video-share-vod' ); ?>

<h4><?php _e( 'HTML5 Player', 'video-share-vod' ); ?></h4>
<select name="html5_player" id="html5_player">
  <option value="native" <?php echo $options['html5_player'] == 'native' ? 'selected' : ''; ?>><?php _e( 'Native HTML5 Tag', 'video-share-vod' ); ?></option>
  <option value="wordpress" <?php echo $options['html5_player'] == 'WordPress' ? 'selected' : ''; ?>><?php _e( 'WordPress Player (MediaElement.js)', 'video-share-vod' ); ?></option>
  <option value="video-js" <?php echo $options['html5_player'] == 'video-js' ? 'selected' : ''; ?>><?php _e( 'Video.js', 'video-share-vod' ); ?></option>
 </select>

<h3><?php _e( 'Player Compatibility', 'video-share-vod' ); ?></h3>
							<?php _e( 'Setup appropriate player type and video source depending on OS and browser.', 'video-share-vod' ); ?>
<h4><?php _e( 'Default Player Type', 'video-share-vod' ); ?></h4>
<select name="player_default" id="player_default">
  <option value="strobe" <?php echo $options['player_default'] == 'strobe' ? 'selected' : ''; ?>><?php _e( 'Strobe (Flash)', 'video-share-vod' ); ?></option>
  <option value="html5" <?php echo $options['player_default'] == 'html5' ? 'selected' : ''; ?>><?php _e( 'HTML5', 'video-share-vod' ); ?></option>
  <option value="html5-mobile" <?php echo $options['player_default'] == 'html5-mobile' ? 'selected' : ''; ?>><?php _e( 'HTML5 Mobile', 'video-share-vod' ); ?></option>
	<option value="html5-hls" <?php echo $options['player_default'] == 'html5-hls' ? 'selected' : ''; ?>><?php _e( 'HTML5 HLS', 'video-share-vod' ); ?></option>

   <option value="strobe-rtmp" <?php echo $options['player_default'] == 'strobe-rtmp' ? 'selected' : ''; ?>><?php _e( 'Strobe RTMP', 'video-share-vod' ); ?></option>
</select>
<BR><?php _e( 'HTML5 Mobile plays lower profile converted video, for mobile support, even if source video is MP4.', 'video-share-vod' ); ?>

<h4><?php _e( 'Player on iOS', 'video-share-vod' ); ?></h4>
<select name="player_ios" id="player_ios">
  <option value="html5" <?php echo $options['player_ios'] == 'html5' ? 'selected' : ''; ?>><?php _e( 'HTML5', 'video-share-vod' ); ?></option>
  <option value="html5-mobile" <?php echo $options['player_ios'] == 'html5-mobile' ? 'selected' : ''; ?>><?php _e( 'HTML5 Mobile', 'video-share-vod' ); ?></option>
   <option value="html5-hls" <?php echo $options['player_ios'] == 'html5-hls' ? 'selected' : ''; ?>><?php _e( 'HTML5 HLS', 'video-share-vod' ); ?></option>
</select>
<br><?php _e( 'If enabled, use HTML5 mobile for lower bitrate conversion that loads faster in mobile networks.', 'video-share-vod' ); ?>

<h4><?php _e( 'Player on Safari', 'video-share-vod' ); ?></h4>
<select name="player_safari" id="player_safari">
  <option value="strobe" <?php echo $options['player_safari'] == 'strobe' ? 'selected' : ''; ?>>Strobe</option>
  <option value="html5" <?php echo $options['player_safari'] == 'html5' ? 'selected' : ''; ?>><?php _e( 'HTML5', 'video-share-vod' ); ?></option>
  <option value="html5-mobile" <?php echo $options['player_safari'] == 'html5-mobile' ? 'selected' : ''; ?>><?php _e( 'HTML5 Mobile', 'video-share-vod' ); ?></option>
   <option value="strobe-rtmp" <?php echo $options['player_safari'] == 'strobe-rtmp' ? 'selected' : ''; ?>><?php _e( 'Strobe RTMP', 'video-share-vod' ); ?></option>
   <option value="html5-hls" <?php echo $options['player_safari'] == 'html5-hls' ? 'selected' : ''; ?>><?php _e( 'HTML5 HLS', 'video-share-vod' ); ?></option>
</select>
<BR><?php _e( 'Safari requires user to confirm flash player load. Use HTML5 player to avoid this.', 'video-share-vod' ); ?>

<h4><?php _e( 'Player on Firefox for MacOS', 'video-share-vod' ); ?></h4>
<select name="player_firefox_mac" id="player_firefox_mac">
  <option value="html5" <?php echo $options['player_firefox_mac'] == 'html5' ? 'selected' : ''; ?>><?php _e( 'HTML5', 'video-share-vod' ); ?></option>
  <option value="strobe" <?php echo $options['player_firefox_mac'] == 'strobe' ? 'selected' : ''; ?>>Strobe</option>
   <option value="strobe-rtmp" <?php echo $options['player_firefox_mac'] == 'strobe-rtmp' ? 'selected' : ''; ?>><?php _e( 'Strobe RTMP', 'video-share-vod' ); ?></option>
  <option value="html5-mobile" <?php echo $options['player_firefox_mac'] == 'html5-mobile' ? 'selected' : ''; ?>><?php _e( 'HTML5 Mobile', 'video-share-vod' ); ?></option>
   <option value="html5-hls" <?php echo $options['player_firefox_mac'] == 'html5-hls' ? 'selected' : ''; ?>><?php _e( 'HTML5 HLS', 'video-share-vod' ); ?></option>
</select>
<BR><?php _e( 'Older Firefox for Mac did not support MP4 HTML5 playback. See <a href="https://bugzilla.mozilla.org/show_bug.cgi?id=851290">bug status</a>.', 'video-share-vod' ); ?>

<h4><?php _e( 'Player on Android', 'video-share-vod' ); ?></h4>
<select name="player_android" id="player_android">
  <option value="html5" <?php echo $options['player_android'] == 'html5' ? 'selected' : ''; ?>><?php _e( 'HTML5', 'video-share-vod' ); ?></option>
  <option value="html5-mobile" <?php echo $options['player_android'] == 'html5-mobile' ? 'selected' : ''; ?>><?php _e( 'HTML5 Mobile', 'video-share-vod' ); ?></option>

  <option value="html5-hls" <?php echo $options['player_android'] == 'html5-hls' ? 'selected' : ''; ?>><?php _e( 'HTML5 HLS', 'video-share-vod' ); ?></option>
  <option value="strobe" <?php echo $options['player_android'] == 'strobe' ? 'selected' : ''; ?>><?php _e( 'Flash Strobe', 'video-share-vod' ); ?></option>
   <option value="strobe-rtmp" <?php echo $options['player_android'] == 'strobe-rtmp' ? 'selected' : ''; ?>><?php _e( 'Flash Strobe RTMP', 'video-share-vod' ); ?></option>
</select>
<BR><?php _e( 'Latest Android no longer supports Flash in default browser, so HTML5 is recommended.', 'video-share-vod' ); ?>

<h4>Video Post Template Filename</h4>
<input name="postTemplate" type="text" id="postTemplate" size="20" maxlength="64" value="<?php echo esc_attr( $options['postTemplate'] ); ?>"/>
<br>Template file located in current theme folder, that should be used to render webcam post page. Ex: page.php, single.php
							<?php
							if ( $options['postTemplate'] != '+plugin' ) {
								$single_template = get_template_directory() . '/' . $options['postTemplate'];
								echo '<br>' . esc_html( $single_template ) . ' : ';
								if ( file_exists( $single_template ) ) {
									echo 'Found.';
								} else {
									echo 'Not Found! Use another theme file!';
								}
							}
							?>
<br>Set "+plugin" to use a template provided by this plugin, instead of theme templates.


<h4><?php _e( 'Video Width', 'video-share-vod' ); ?></h4>
<input name="videoWidth" type="text" id="videoWidth" size="4" maxlength="4" value="<?php echo esc_attr( $options['videoWidth'] ); ?>"/>
<br><?php _e( 'Leave blank to use video width dynamically for player (for HD videos may be bigger than screen resolution and require scrolling). Does not apply for VideoJS as that uses adaptive fluid fill.', 'video-share-vod' ); ?>

<h4><?php _e( 'Player Container CSS', 'video-share-vod' ); ?></h4>
<textarea name="containerCSS" id="containerCSS" cols="64" rows="3"><?php echo esc_textarea( $options['containerCSS'] ); ?></textarea>
<br>Defaults:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['containerCSS'] ); ?></textarea>

<h4><?php _e( 'Playlist Video Width', 'video-share-vod' ); ?></h4>
<input name="playlistVideoWidth" type="text" id="playlistVideoWidth" size="4" maxlength="4" value="<?php echo esc_attr( $options['playlistVideoWidth'] ); ?>"/>

<h4><?php _e( 'Playlist List Width', 'video-share-vod' ); ?></h4>
<input name="playlistListWidth" type="text" id="playlistListWidth" size="4" maxlength="4" value="<?php echo esc_attr( $options['playlistListWidth'] ); ?>"/>

<h4>Playlist Template Filename</h4>
<input name="playlistTemplate" type="text" id="playlistTemplate" size="20" maxlength="64" value="<?php echo esc_attr( $options['playlistTemplate'] ); ?>"/>
<br>Template file located in current theme folder, that should be used to render playlist post page. Ex: page.php, single.php
							<?php
							if ( $options['postTemplate'] != '+plugin' ) {
								$single_template = get_template_directory() . '/' . $options['playlistTemplate'];
								echo '<br>' . esc_html( $single_template ) . ' : ';
								if ( file_exists( $single_template ) ) {
									echo 'Found.';
								} else {
									echo 'Not Found! Use another theme file!';
								}
							}
							?>
<br>Set "+plugin" to use a template provided by this plugin, instead of theme templates.

<h4><?php _e( 'Allow Debugging', 'video-share-vod' ); ?></h4>
<select name="allowDebug" id="allowDebug">
  <option value="1" <?php echo $options['allowDebug'] == '1' ? 'selected' : ''; ?>><?php _e( 'Yes', 'video-share-vod' ); ?></option>
  <option value="0" <?php echo $options['allowDebug'] == '0' ? 'selected' : ''; ?>><?php _e( 'No', 'video-share-vod' ); ?></option>
</select>
<br><?php _e( 'Allows forcing players at runtime using url parameters like ?player=html5-hls&player_html=video-js', 'video-share-vod' ); ?>


<h4><?php _e( 'Cross Domain Policy', 'video-share-vod' ); ?></h4>
<textarea name="crossdomain_xml" id="crossdomain_xml" cols="100" rows="4"><?php echo esc_textarea( $options['crossdomain_xml'] ); ?></textarea>
<br>This is required for Flash and Air based players to access videos and scripts from site.
<br>After updating permalinks (<a href="options-permalink.php">Save Changes on Permalinks page</a>) this should become available as <a href="<?php echo esc_url( $crossdomain_url ); ?>"><?php echo esc_url( $crossdomain_url ); ?></a>.
<br>This works if file doesn't already exist. You can also create the file for faster serving.
<br>Defaults:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['crossdomain_xml'] ); ?></textarea>

<h4><?php _e( 'Disable', 'video-share-vod' ); ?> X-Frame-Options: SAMEORIGIN</h4>
<select name="disableXOrigin" id="disableXOrigin">
  <option value="1" <?php echo $options['disableXOrigin'] == '1' ? 'selected' : ''; ?>><?php _e( 'Yes', 'video-share-vod' ); ?></option>
  <option value="0" <?php echo $options['disableXOrigin'] == '0' ? 'selected' : ''; ?>><?php _e( 'No', 'video-share-vod' ); ?></option>
</select>
<br>Disable X-Frame-Options: SAMEORIGIN security feature from /wp_admin (send_frame_options_header), to allow embeds from external IFRAMEs.

<h4><?php _e( 'Referral for Disable', 'video-share-vod' ); ?> X-Frame-Options: SAMEORIGIN</h4>
<input name="disableXOriginRef" type="text" id="disableXOriginRef" size="100" maxlength="256" value="<?php echo esc_attr( $options['disableXOriginRef'] ); ?>"/>
<br>Disable 'send_frame_options_header' only for referrals that start with this string. Highly recommended when you just need to embed on one site you know. Ex: https://subdomain.embeddingsite.com

							<?php
							break;

						case 'listings':
							// ! display options

							$options['customCSS']       = htmlentities( stripslashes( $options['customCSS'] ) );
							$options['custom_post']     = preg_replace( '/[^\da-z]/i', '', strtolower( $options['custom_post'] ) );
							$options['custom_taxonomy'] = preg_replace( '/[^\da-z]/i', '', strtolower( $options['custom_taxonomy'] ) );

							?>
<h3><?php _e( 'Listings', 'video-share-vod' ); ?></h3>

<h4>Theme Mode (Dark/Light/Auto)</h4> 
<select name="themeMode" id="themeMode">
  <option value="" <?php echo $options['themeMode'] ? '' : 'selected'; ?>>None</option>
  <option value="light" <?php echo $options['themeMode'] == 'light' ? 'selected' : ''; ?>>Light Mode</option>
  <option value="dark" <?php echo $options['themeMode'] == 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
  <option value="auto" <?php echo $options['themeMode'] == 'auto' ? 'selected' : ''; ?>>Auto Mode</option>
</select>
<br>This will use JS to apply ".inverted" class to Fomantic ".ui" elements mainly on AJAX listings. When using the <a href="https://fanspaysite.com/theme">FansPaysSite theme</a> this will be discarded and the dynamic theme mode will be used.

<h4>Interface Class(es)</h4>
<input name="interfaceClass" type="text" id="interfaceClass" size="30" maxlength="128" value="<?php echo esc_attr( $options['interfaceClass'] ); ?>"/>
<br>Extra class to apply to interface (using Semantic UI). Use inverted when theme uses a static dark mode (a dark background with white text) or for contrast. Ex: inverted
<br>Some common Semantic UI classes: inverted = dark mode or contrast, basic = no formatting, secondary/tertiary = greys, red/orange/yellow/olive/green/teal/blue/violet/purple/pink/brown/grey/black = colors . Multiple classes can be combined, divided by space. Ex: inverted, basic pink, secondary green, secondary

<h4>Listings Menu</h4>
<select name="listingsMenu" id="listingsMenu">
  <option value="1" <?php echo $options['listingsMenu'] == '1' ? 'selected' : ''; ?>>Menu</option>
  <option value="0" <?php echo $options['listingsMenu'] == '0' ? 'selected' : ''; ?>>Dropdowns</option>
</select>
<br>Show categories and order options as menu.


<h4><a target="_plugin" href="https://wordpress.org/plugins/rate-star-review/">Rate Star Review</a> - Enable Star Reviews</h4>
							<?php
							if ( is_plugin_active( 'rate-star-review/rate-star-review.php' ) ) {
								echo 'Detected:  <a href="admin.php?page=rate-star-review">Configure</a>';
							} else {
								echo 'Not detected. Please install and activate Rate Star Review by VideoWhisper.com from <a href="plugin-install.php?s=videowhisper+rate+star+review&tab=search&type=term">Plugins > Add New</a>!';
							}
							?>
<BR><select name="rateStarReview" id="rateStarReview">
  <option value="0" <?php echo $options['rateStarReview'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['rateStarReview'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables Rate Star Review integration. Shows star ratings on listings and review form, reviews on item pages.


<h4>Setup Pages</h4>
<select name="disableSetupPages" id="disableSetupPages">
  <option value="0" <?php echo $options['disableSetupPages'] ? '' : 'selected'; ?>>Yes</option>
  <option value="1" <?php echo $options['disableSetupPages'] ? 'selected' : ''; ?>>No</option>
</select>
<br>Create pages for main functionality. Also creates a menu with these pages (VideoWhisper) that can be added to themes. If you delete the pages this option recreates these if not disabled.
<br>Additionally shows menus to pages in top bar user menu when enabled (disable this to hide menus).

<h4>Video Post Name</h4>
<input name="custom_post" type="text" id="custom_post" size="16" maxlength="32" value="<?php echo esc_attr( $options['custom_post'] ); ?>"/>
<br>Custom post name for videos (only alphanumeric, lower case). Will be used for video urls. Ex: video, clip, videosharevod
<br><a href="options-permalink.php">Save permalinks</a> to activate new url scheme.
<br>Warning: Changing post type name at runtime will hide previously added items. Previous posts will only show when their post type name is restored.

<h4>Video Post Taxonomy Name</h4>
<input name="custom_taxonomy" type="text" id="custom_taxonomy" size="12" maxlength="32" value="<?php echo esc_attr( $options['custom_taxonomy'] ); ?>"/>
<br>Special taxonomy for organising videos. Ex: playlist

<h4><?php _e( 'Default Order By', 'video-share-vod' ); ?></h4>
<select name="order_by" id="order_by">
  <option value="post_date" <?php echo $options['order_by'] == 'post_date' ? 'selected' : ''; ?>>Date</option>
  <option value="video-views" <?php echo $options['order_by'] == 'video-views' ? 'selected' : ''; ?>>Views</option>
  <option value="video-lastview" <?php echo $options['order_by'] == 'video-lastview' ? 'selected' : ''; ?>>Recently Viewed</option>
							<?php
							if ( $options['rateStarReview'] ) {
								echo '<option value="rateStarReview_rating"' . ( $options['order_by'] == 'rateStarReview_rating' ? ' selected' : '' ) . '>' . __( 'Rating', 'video-share-vod' ) . '</option>';
								echo '<option value="rateStarReview_ratingNumber"' . ( $options['order_by'] == 'rateStarReview_ratingNumber' ? ' selected' : '' ) . '>' . __( 'Most Rated', 'video-share-vod' ) . '</option>';
								echo '<option value="rateStarReview_ratingPoints"' . ( $options['order_by'] == 'rateStarReview_ratingPoints' ? ' selected' : '' ) . '>' . __( 'Rate Popularity', 'video-share-vod' ) . '</option>';
							}
							?>
  <option value="rand" <?php echo $options['order_by'] == 'rand' ? 'selected' : ''; ?>>Random</option>
</select>

<h4><?php _e( 'Default Videos Per Page', 'video-share-vod' ); ?></h4>
<input name="perPage" type="text" id="perPage" size="3" maxlength="3" value="<?php echo esc_attr( $options['perPage'] ); ?>"/>

<h4><?php _e( 'Default Videos Per Row', 'video-share-vod' ); ?></h4>
<input name="perRow" type="text" id="perRow" size="3" maxlength="3" value="<?php echo esc_attr( $options['perRow'] ); ?>"/>
<br>Leave 0 to show as many as container space permits.

<h4><?php _e( 'Thumbnail Width', 'video-share-vod' ); ?></h4>
<input name="thumbWidth" type="text" id="thumbWidth" size="4" maxlength="4" value="<?php echo esc_attr( $options['thumbWidth'] ); ?>"/>px
<BR>Thumbnail width in pixels.

<h4><?php _e( 'Thumbnail Height', 'video-share-vod' ); ?></h4>
<input name="thumbHeight" type="text" id="thumbHeight" size="4" maxlength="4" value="<?php echo esc_attr( $options['thumbHeight'] ); ?>"/>px
<BR>Thumbnail height in pixels.

<h4><?php _e( 'Custom CSS', 'video-share-vod' ); ?></h4>
<textarea name="customCSS" id="customCSS" cols="64" rows="5"><?php echo esc_textarea( $options['customCSS'] ); ?></textarea>
<BR><?php _e( 'Styling used in elements added by this plugin. Must include CSS container &lt;style type=&quot;text/css&quot;&gt; &lt;/style&gt; .', 'video-share-vod' ); ?>
If a plugin update alters listings, just reset CSS to current defaults:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['customCSS'] ); ?></textarea>

<h4><?php _e( 'Show VideoWhisper Powered by', 'video-share-vod' ); ?></h4>
<select name="videowhisper" id="videowhisper">
  <option value="0" <?php echo $options['videowhisper'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['videowhisper'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>
							<?php
							_e(
								'Show a mention that videos were posted with VideoWhisper plugin.
',
								'video-share-vod'
							);
							?>
							<?php
							break;

						case 'share':
							// ! share options
							$current_user = wp_get_current_user();

							?>
<h3><?php _e( 'Video Sharing', 'video-share-vod' ); ?></h3>

<h4>Username</h4>
<select name="userName" id="userName">
  <option value="display_name" <?php echo $options['userName'] == 'display_name' ? 'selected' : ''; ?>>Display Name (<?php echo esc_html( $current_user->display_name ); ?>)</option>
  <option value="user_login" <?php echo $options['userName'] == 'user_login' ? 'selected' : ''; ?>>Login (<?php echo esc_html( $current_user->user_login ); ?>)</option>
  <option value="user_nicename" <?php echo $options['userName'] == 'user_nicename' ? 'selected' : ''; ?>>Nicename (<?php echo esc_html( $current_user->user_nicename ); ?>)</option>
  <option value="ID" <?php echo $options['userName'] == 'ID' ? 'selected' : ''; ?>>ID (<?php echo intval( $current_user->ID ); ?>)</option>
</select>
<br>Used for default user playlists. Your username with current settings:
							<?php
							$userName = $options['userName'];
							if ( ! $userName ) {
								$userName = 'user_nicename';
							}
							echo esc_html( $username = $current_user->$userName );
							?>

<h4><?php _e( 'Users allowed to share videos', 'video-share-vod' ); ?></h4>
<textarea name="shareList" cols="64" rows="2" id="shareList"><?php echo esc_textarea( $options['shareList'] ); ?></textarea>
<BR><?php _e( 'Who can share videos: comma separated Roles, user Emails, user ID numbers.', 'video-share-vod' ); ?>
<BR><?php _e( '"Guest" will allow everybody including guests (unregistered users).', 'video-share-vod' ); ?>

<h4><?php _e( 'Users allowed to directly publish videos', 'video-share-vod' ); ?></h4>
<textarea name="publishList" cols="64" rows="2" id="publishList"><?php echo esc_textarea( $options['publishList'] ); ?></textarea>
<BR><?php _e( 'Users not in this list will add videos as "pending".', 'video-share-vod' ); ?>
<BR><?php _e( 'Who can publish videos: comma separated Roles, user Emails, user ID numbers.', 'video-share-vod' ); ?>
<BR><?php _e( '"Guest" will allow everybody including guests (unregistered users).', 'video-share-vod' ); ?>

<h4><?php _e( 'Users allowed to get embed codes', 'video-share-vod' ); ?></h4>
<textarea name="embedList" cols="64" rows="2" id="embedList"><?php echo esc_textarea( $options['embedList'] ); ?></textarea>
<BR><?php _e( 'Who can see embed code for videos: comma separated Roles, user Emails, user ID numbers.', 'video-share-vod' ); ?>
<BR><?php _e( '"Guest" will allow everybody including guests (unregistered users).', 'video-share-vod' ); ?>
<BR><?php _e( 'Add code below to your .htaccess file for successful resource embeds:', 'video-share-vod' ); ?>
<BR># Apache config: allow embeds on other sites
<BR>Header set Access-Control-Allow-Origin "*"


<h4>BuddyPress/BuddyBoss Activity Post</h4>
<select name="bpActivityPost" id="bpActivityPost">
  <option value="1" <?php echo $options['bpActivityPost'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['bpActivityPost'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Integrate post with BP/BB activity.

<h4>BuddyPress/BuddyBoss Activity Insert</h4>
<select name="bpActivityInsert" id="bpActivityInsert">
  <option value="1" <?php echo $options['bpActivityInsert'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['bpActivityInsert'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Insert BP/BB activity after creating video snapshot (so it includes thumbnail).


<h3>Troubleshoot Uploads</h3>
PHP Limitations (for a request, script call):
<BR>post_max_size: <?php echo ini_get( 'post_max_size' ); ?>
<BR>upload_max_filesize: <?php echo ini_get( 'upload_max_filesize' ); ?> - The maximum size of an uploaded file. Web uploads are also limited by hosting / request memory limitations.
<BR>memory_limit: <?php echo ini_get( 'memory_limit' ); ?> - This sets the maximum amount of memory in bytes that a script is allowed to allocate. This helps prevent poorly written scripts for eating up all available memory on a server.
<BR>max_execution_time: <?php echo ini_get( 'max_execution_time' ); ?> - This sets the maximum time in seconds a script is allowed to run before it is terminated by the parser. This helps prevent poorly written scripts from tying up the server. The default setting is 90.
<BR>max_input_time: <?php echo ini_get( 'max_input_time' ); ?>  - This sets the maximum time in seconds a script is allowed to parse input data, like POST, GET and file uploads.

<p>Important: For adding big videos (512Mb or higher), best way is to upload by FTP and use <a href="admin.php?page=video-share-import">Import</a> feature. Trying to upload big files by HTTP (from web page) may result in failure due to request limitations, hosting plan resources, timeouts depending on client upload connection speed.</p>
							<?php
							break;

						case 'vod':
							// ! vod options
							$options['accessDenied'] = htmlentities( stripslashes( $options['accessDenied'] ) );

							?>
<h3>Membership Video On Demand</h3>
<a target="_blank" href="https://videosharevod.com/features/video-on-demand/">About Video On Demand...</a>

<h4>Members allowed to watch video</h4>
<textarea name="watchList" cols="64" rows="3" id="watchList"><?php echo esc_textarea( $options['watchList'] ); ?></textarea>
<BR>Global video access list: comma separated Roles, user Emails, user ID numbers. Ex: <i>Subscriber, Author, submit.ticket@videowhisper.com, 1</i>
<BR>"Guest" will allow everybody including guests (unregistered users) to watch videos.

<h4>Role Playlists</h4>
Enables access by role playlists: Assign video to a playlist that is a role name.
<br><select name="vod_role_playlist" id="vod_role_playlist">
  <option value="1" <?php echo $options['vod_role_playlist'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['vod_role_playlist'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Multiple roles can be assigned to same video. User can have any of the assigned roles, to watch. If user has required role, access is granted even if not in global access list.
<br>Videos without role playlists are accessible as per global video access.

<h4>Exceptions</h4>
Assign videos to these Playlists:
<br><b>free</b> : Anybody can watch, including guests.
<br><b>registered</b> : All members can watch.
<br><b>unpublished</b> : Video is not accessible.

<h4>Access denied message</h4>
<textarea name="accessDenied" cols="64" rows="3" id="accessDenied"><?php echo esc_textarea( $options['accessDenied'] ); ?>
</textarea>
<BR>HTML info, shows with preview if user does not have access to watch video.
<br>Including #info# will mention rule that was applied.

<h4>Paid Membership and Content</h4>
Solution was tested and developed in combination with <a href="https://wordpress.org/plugins/paid-membership/">Paid Membership and Content</a>: Sell membership and content based on virtual wallet credits/tokens. Credits/tokens can be purchased with real money.
<br> - Pay per Video: This plugin also allows users to sell individual videos (will get an edit button to set price and duration).
<br> - Pay per Channel: When using Broadcast Live Video - Live Streaming plugin, videos are associated to channel using a playlist with same name as channel. If channel requires payment, channel videos are only accessible if user paid for the channel.

<BR>Paid Membership and Content:
							<?php

							if ( is_plugin_active( 'paid-membership/paid-membership.php' ) ) {
								echo '<a href="admin.php?page=paid-membership">Detected</a>';

								$optionsPM = get_option( 'VWpaidMembershipOptions' );
								if ( $optionsPM['p_videowhisper_content_edit'] ) {
									$editURL = add_query_arg( 'editID', '', get_permalink( $optionsPM['p_videowhisper_content_edit'] ) ) . '=';
								}
							} else {
								echo 'Not detected. Please install and activate <a target="_mycred" href="https://wordpress.org/plugins/paid-membership/">Paid Membership and Content with Credits</a> from <a href="plugin-install.php">Plugins > Add New</a>!';
							}

							?>


<h4>Frontend Contend Edit</h4>
<select name="editContent" id="editContent">
  <option value="0" <?php echo $options['editContent'] ? '' : 'selected'; ?>>No</option>
  <option value="all" <?php echo $options['editContent'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Allow owner and admin to edit content options like price for videos, from frontend. This will show an edit button on listings that can be edited by current user.

<h4>Edit Content URL</h4>
<input name="editURL" type="text" id="editURL" size="100" maxlength="256" value="<?php echo esc_attr( $options['editURL'] ); ?>"/>
<BR>Detected: <?php echo esc_url( $editURL ); ?>

							<?php

							break;

						case 'vast':
							// ! vast options
							$options['vast'] = trim( $options['vast'] );

							?>
<h3>Video Ad Serving Template (VAST) / Interactive Media Ads (IMA)</h3>
VAST/IMA is currently supported with Video.js HTML5 player.
<br>VAST data structure configures: (1) The ad media that should be played (2) How should the ad media be played (3) What should be tracked as the media is played. In example pre-roll video ads can be implemented with VAST.
<br>IMA enables ad requests to DoubleClick for Publishers (DFP), the Google AdSense network for Video (AFV) or Games (AFG) or any VAST-compliant ad server.

<h4>Video Ads</h4>
Enable ads for all videos.
<br><select name="adsGlobal" id="adsGlobal">
  <option value="1" <?php echo $options['adsGlobal'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['adsGlobal'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Exception Playlists:
<br><b>sponsored</b>: Show ads.
<br><b>adfree</b>: Do not show ads.

<h4>VAST Mode</h4>
<select name="vastLib" id="vastLib">
  <option value="iab" <?php echo $options['vastLib'] == 'iab' ? '' : 'selected'; ?>>Google Interactive Media Ads (IMA)</option>
  <option value="vast" <?php echo $options['vastLib'] == 'vast' ? 'selected' : ''; ?>>Video Ad Serving Template (VAST) </option>
</select>
<br>The Google Interactive Media Ads (IMA) enables publishers to display linear, non-linear, and companion ads in videos and games. Supports VAST 2, VAST 3, VMAP. Recommended: IMA

<h4>VAST compliant / IMA adTagUrl Address</h4>
<textarea name="vast" cols="64" rows="2" id="vast"><?php echo esc_textarea( $options['vast'] ); ?>
</textarea>
<br>Ex: https://pubads.g.doubleclick.net/gampad/ads?sz=640x480&iu=/124319096/external/single_ad_samples&ciu_szs=300x250&impl=s&gdfp_req=1&env=vp&output=vast&unviewed_position_start=1&cust_params=deployment%3Ddevsite%26sample_ct%3Dskippablelinear&correlator=
<br>Try more <a href="https://developers.google.com/interactive-media-ads/docs/sdks/html5/tags">IMA samples</a>. Leave blank to disable video ads.

<h4>Premium Users List</h4>
<p>Premium uses watch videos without advertisements (exception for VAST).</p>
<textarea name="premiumList" cols="64" rows="3" id="premiumList"><?php echo esc_textarea( $options['premiumList'] ); ?>
</textarea>
<BR>Ads excepted users: comma separated Roles, user Emails, user ID numbers. Ex: <i>Author, Editor, submit.ticket@videowhisper.com, 1</i>

							<?php
							break;
					}

					if ( ! in_array( $active_tab, array( 'shortcodes', 'reset', 'support' ) ) ) {
						submit_button();
					}
					?>

</form>
</div>
			<?php
	}

}
