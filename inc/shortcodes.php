<?php
namespace VideoWhisper\VideoShareVOD;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

ini_set( 'display_errors', 1 ); // debug only

trait Shortcodes {


		// ! Videos AJAX handler

		static function vwvs_videos() {
			$options = get_option( 'VWvideoShareOptions' );

			$perPage = (int) $_GET['pp'];
			if ( ! $perPage ) {
				$perPage = $options['perPage'];
			}

			$playlist = sanitize_file_name( $_GET['playlist'] );

			$id = sanitize_file_name( $_GET['id'] );

			$category = intval( $_GET['cat'] ?? 0);

			$page   = intval( $_GET['p'] ?? 0 );
			$offset = $page * $perPage;

			$perRow = (int) $_GET['pr'];
			if ( ! $perRow ) {
				$perRow = $options['perRow'];
			}

			$menu             = boolval( $_GET['menu'] );

			// order
			$order_by = sanitize_file_name( $_GET['ob'] );
			if ( ! $order_by ) {
				$order_by = $options['order_by'];
			}
			if ( ! $order_by ) {
				$order_by = 'post_date';
			}

			// options
			$selectCategory = (int) $_GET['sc'];
			$selectOrder    = (int) $_GET['so'];
			$selectPage     = (int) $_GET['sp'];

			$selectName = (int) $_GET['sn'];
			$selectTags = (int) $_GET['sg'];

			// tags,name search
			$tags = sanitize_text_field( $_GET['tags'] );
			$name = sanitize_file_name( $_GET['name'] );
			if ( $name == 'undefined' ) {
				$name = '';
			}
			if ( $tags == 'undefined' ) {
				$tags = '';
			}

			// user_id
			$user_id = intval( $_GET['user_id'] );

			// query
			$args = array(
				'post_type'      => $options['custom_post'],
				'post_status'    => 'publish',
				'posts_per_page' => $perPage,
				'offset'         => $offset,
				'order'          => 'DESC',
			);

			switch ( $order_by ) {
				case 'post_date':
					$args['orderby'] = 'post_date';
					break;

				case 'rand':
					$args['orderby'] = 'rand';
					break;

				default:
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = $order_by;
					break;
			}

			if ( $playlist ) {
				$args['playlist'] = $playlist;
			}
			if ( $category ) {
				$args['category'] = $category;
			}

			if ( $tags ) {
				$tagList = explode( ',', $tags );
				foreach ( $tagList as $key => $value ) {
					$tagList[ $key ] = trim( $tagList[ $key ] );
				}

				$args['tax_query'] = array(
					array(
						'taxonomy' => 'post_tag',
						'field'    => 'slug',
						'operator' => 'AND',
						'terms'    => $tagList,
					),
				);
			}

			if ( $name ) {
				$args['s'] = $name;
			}

			if ( $user_id ) {
				$args['author'] = $user_id;
			}

			$isAdministrator = 0;
			$isID = 0;

			// user permissions
			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				if ( in_array( 'administrator', $current_user->roles ) ) {
					$isAdministrator = 1;
				}
				$isID = $current_user->ID;

				if ( is_plugin_active( 'paid-membership/paid-membership.php' ) ) {
					$pmEnabled = 1;
				}

				if ( $user_id == -1 ) {
					$args['author'] = $isID;
				}
			}

			$isMobile = (bool) preg_match( '#\b(ip(hone|od|ad)|android|opera m(ob|in)i|windows (phone|ce)|blackberry|tablet|s(ymbian|eries60|amsung)|p(laybook|alm|rofile/midp|laystation portable)|nokia|fennec|htc[\-_]|mobile|up\.browser|[1-4][0-9]{2}x[1-4][0-9]{2})\b#i', $_SERVER['HTTP_USER_AGENT'] );

			$isSafari = (bool) ( strpos( $_SERVER['HTTP_USER_AGENT'], 'AppleWebKit' ) && strpos( $_SERVER['HTTP_USER_AGENT'], 'Safari' ) );
			if ( $isSafari ) {
				$previewMuted = 'muted';
			} else {
				$previewMuted = '';
			}

			$postslist = get_posts( $args );

			ob_clean();
			// output

			$ajaxurl = admin_url() . 'admin-ajax.php?action=vwvs_videos&menu=' . $menu . '&pp=' . $perPage . '&pr=' . $perRow . '&playlist=' . urlencode( $playlist ) . '&sc=' . $selectCategory . '&sn=' . $selectName . '&sg=' . $selectTags . '&so=' . $selectOrder . '&sp=' . $selectPage . '&id=' . esc_attr( $id ) . '&user_id=' . $user_id;

			// reset on change, selections persist

			$ajaxurlC  = $ajaxurl . '&cat=' . $category . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ); // select order
			$ajaxurlO  = $ajaxurl . '&ob=' . $order_by . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ); // select cat
			$ajaxurlCO = $ajaxurl . '&cat=' . $category . '&ob=' . $order_by; // select name tag

			$ajaxurlA = $ajaxurl . '&cat=' . $category . '&ob=' . $order_by . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ); // all persist: reload/page


//start menu 
if ( $menu ) {
			echo '
<style>
	.vwItemsSidebar {
    grid-area: sidebar;
  }

  .vwItemsContent {
    grid-area: content;
  }

.vwItemsWrapper {
    display: grid;
    grid-gap: 4px;
    grid-template-columns: 120px  auto;
    grid-template-areas: "sidebar content";
    color: #444;
  }

  .ui .title { height: auto !important; background-color: inherit !important}
  .ui .content {margin: 0 !important; }
  .vwItemsSidebar .menu { max-width: 120px !important;}

 </style>
 <div class="vwItemsWrapper">
 <div class="vwItemsSidebar">';

			if ( $selectCategory ) {
				echo '
<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' accordion small">

  <div class="active title">
    <i class="dropdown icon"></i>
    ' . __( 'Category', 'video-share-vod' ) . ' ' . ( esc_html( $category ) ? '<i class="check icon small"></i>' : '' ) . '
  </div>
  <div class="active content">
  <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' vertical menu small">
  ';
				echo '  <a class="' . ( $category == 0 ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlO ) . '&cat=0\'; loadVideos' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading category', 'video-share-vod' ) . '...</div>\')">' . __( 'All Categories', 'video-share-vod' ) . '</a> ';

				$categories = get_categories( array( 'taxonomy' => 'category' ) );
				foreach ( $categories as $cat ) {
					echo '  <a class="' . ( $category == $cat->term_id ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_html( $ajaxurlO ) . '&cat=' . esc_attr( $cat->term_id ) . '\'; loadVideos' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading category', 'video-share-vod' ) . '...</div>\')">' . esc_html( $cat->name ) . '</a> ';
				}

				echo '</div>

  </div>
</div>';

			}
			
			if ( $selectOrder ) {

				$optionsOrders = array(
					'post_date'  => __( 'Added Recently', 'video-share-vod' ),
					'video-views' => __( 'Views', 'video-share-vod' ),
					'video-lastview' => __( 'Watched Recently', 'video-share-vod' ),
					'rand'       => __( 'Random', 'ppv-live-webcams' ),									
				);

				if ( $options['rateStarReview'] ) {
					$optionsOrders['rateStarReview_rating']       = __( 'Rating', 'video-share-vod' );
					$optionsOrders['rateStarReview_ratingNumber'] = __( 'Ratings Number', 'video-share-vod' );
					$optionsOrders['rateStarReview_ratingPoints'] = __( 'Rate Popularity', 'video-share-vod' );

					if ( $category ) {
						$optionsOrders[ 'rateStarReview_rating_category' . $category ]       = __( 'Rating', 'video-share-vod' ) . ' ' . __( 'in Category', 'video-share-vod' );
						$optionsOrders[ 'rateStarReview_ratingNumber_category' . $category ] = __( 'Ratings Number', 'video-share-vod' ) . ' ' . __( 'in Category', 'video-share-vod' );
						$optionsOrders[ 'rateStarReview_ratingPoints_category' . $category ] = __( 'Rate Popularity', 'video-share-vod' ) . ' ' . __( 'in Category', 'video-share-vod' );
					}
				}

				echo '
<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' accordion small">

  <div class="title">
    <i class="dropdown icon"></i>
    ' . __( 'Order By', 'video-share-vod' ) . ' ' . ( $order_by != 'default' ? '<i class="check icon small"></i>' : '' ) . '
  </div>
  <div class="' . ( $order_by != 'default' ? 'active' : '' ) . ' content">
  <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' vertical menu small">
  ';

				foreach ( $optionsOrders as $key => $value ) {
					echo '  <a class="' . ( $order_by == $key ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlC ) . '&ob=' . esc_attr( $key ) . '\'; loadVideos' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Ordering Videos', 'video-share-vod' ) . '...</div>\')">' . esc_html( $value ) . '</a> ';
				}

				echo '</div>

  </div>
</div>';

			}

			echo '
<PRE style="display: none"><SCRIPT language="JavaScript">
jQuery(document).ready(function()
{
jQuery(".ui.accordion").accordion({exclusive:false});
});
</SCRIPT></PRE>
';
			echo '</div><div class="vwItemsContent">';
		}
		
		//end menu
		
			// options

			// echo '<div class="videowhisperListOptions">';
			// echo '<div class="ui ' . esc_attr( $options['interfaceClass'] ) .' form"><div class="inline fields">';
			echo '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' equal width form tiny" style="z-index: 20;"><div class="inline fields">';

			if ( $selectCategory && ! $menu ) {
				echo '<div class="field">' . wp_dropdown_categories( 'show_count=0&echo=0&name=category' . esc_attr( $id ) . '&hide_empty=1&class=ui+dropdown+v-select&show_option_all=' . __( 'All', 'video-share-vod' ) . '&selected=' . $category ) . '</div>';
				echo '<script>var category' . esc_attr( $id ) . ' = document.getElementById("category' . esc_attr( $id ) . '"); 			category' . esc_attr( $id ) . '.onchange = function(){aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlO ) . '&cat=\'+ this.value; loadVideos' . esc_attr( $id ) . '(\'<div class="ui active inline text large loader">Loading category...</div>\')}
			</script>';
			}

			if ( $selectOrder && ! $menu ) {
				echo '<div class="field"><select class="ui dropdown v-select" id="order_by' . esc_attr( $id ) . '" name="order_by' . esc_attr( $id ) . '" onchange="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlC ) . '&ob=\'+ this.value; loadVideos' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Ordering videos...</div>\')">';
				echo '<option value="">' . __( 'Order By', 'video-share-vod' ) . ':</option>';
				echo '<option value="post_date"' . ( $order_by == 'post_date' ? ' selected' : '' ) . '>' . __( 'Added Recently', 'video-share-vod' ) . '</option>';
				echo '<option value="video-views"' . ( $order_by == 'video-views' ? ' selected' : '' ) . '>' . __( 'Views', 'video-share-vod' ) . '</option>';
				echo '<option value="video-lastview"' . ( $order_by == 'video-lastview' ? ' selected' : '' ) . '>' . __( 'Watched Recently', 'video-share-vod' ) . '</option>';

				if ( $options['rateStarReview'] ) {
					echo '<option value="rateStarReview_rating"' . ( $order_by == 'rateStarReview_rating' ? ' selected' : '' ) . '>' . __( 'Rating', 'video-share-vod' ) . '</option>';
					echo '<option value="rateStarReview_ratingNumber"' . ( $order_by == 'rateStarReview_ratingNumber' ? ' selected' : '' ) . '>' . __( 'Most Rated', 'video-share-vod' ) . '</option>';
					echo '<option value="rateStarReview_ratingPoints"' . ( $order_by == 'rateStarReview_ratingPoints' ? ' selected' : '' ) . '>' . __( 'Rate Popularity', 'video-share-vod' ) . '</option>';
				}

				echo '<option value="rand"' . ( $order_by == 'rand' ? ' selected' : '' ) . '>' . __( 'Random', 'video-share-vod' ) . '</option>';
				echo '</select></div>';
			}

			if ( $selectTags || $selectName ) {

				echo '<div class="field"></div>'; // separator

				if ( $selectTags ) {
					echo '<div class="field" data-tooltip="Tags, Comma Separated"><div class="ui left icon input"><i class="tags icon"></i><INPUT class="videowhisperInput" type="text" size="12" name="tags" id="tags" placeholder="' . __( 'Tags', 'video-share-vod' ) . '" value="' . esc_attr( htmlspecialchars( $tags ) ) . '">
					</div></div>';
				}

				if ( $selectName ) {
					echo '<div class="field"><div class="ui left corner labeled input"><INPUT class="videowhisperInput" type="text" size="12" name="name" id="name" placeholder="' . __( 'Name', 'video-share-vod' ) . '" value="' . esc_attr( htmlspecialchars( $name ) ) . '">
  <div class="ui left corner label">
    <i class="asterisk icon"></i>
  </div>
					</div></div>';
				}

				// search button
				echo '<div class="field" data-tooltip="Search by Tags and/or Name"><button class="ui icon button" type="submit" name="submit" id="submit" value="' . __( 'Search', 'video-share-vod' ) . '" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlCO ) . '&tags=\' + document.getElementById(\'tags\').value +\'&name=\' + document.getElementById(\'name\').value; loadVideos' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Searching Videos...</div>\')"><i class="search icon"></i></button></div>';

			}

			// reload button
			if ( $selectCategory || $selectOrder || $selectTags || $selectName ) {
				echo '<div class="field"></div> <div class="field" data-tooltip="Reload"><button class="ui icon button" type="submit" name="reload" id="reload" value="' . __( 'Reload', 'picture-gallery' ) . '" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlA ) . '\'; loadVideos' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Reloading Videos List...</div>\')"><i class="sync icon"></i></button></div>';
			}

			// echo '</div>';
			echo '</div></div>';

			// list
			if ( count( $postslist ) > 0 ) {
				echo '<div class="videowhisperVideos">';
				$k = 0;

				foreach ( $postslist as $item ) {
					if ( $perRow ) {
						if ( $k ) {
							if ( $k % $perRow == 0 ) {
														echo '<br>';
							}
						}
					}

							$videoDuration = get_post_meta( $item->ID, 'video-duration', true );
						$imagePath         = get_post_meta( $item->ID, 'video-thumbnail', true );

					$views = get_post_meta( $item->ID, 'video-views', true );
					if ( ! $views ) {
						$views = 0;
					}

					// get preview video
					$previewVideo  = '';
					$videoAdaptive = get_post_meta( $item->ID, 'video-adaptive', true );

					if ( is_array( $videoAdaptive ) ) {
						if ( array_key_exists( 'preview', $videoAdaptive ) ) {
							if ( $videoAdaptive['preview'] ) {
								if ( $videoAdaptive['preview']['file'] ) {
									if ( file_exists( $videoAdaptive['preview']['file'] ) ) {
										$previewVideo = $videoAdaptive['preview']['file'];
									} else {
									}
								} else {
								}
							} elseif ( $options['convertPreview'] ) {
								self::convertVideo( $item->ID ); // add preview if enabled and missing (older)
							}
						}
					}

										$duration = self::humanDuration( $videoDuration );
									$age          = self::humanAge( time() - strtotime( $item->post_date ) );

								$height = get_post_meta( $item->ID, 'video-height', true );

							$canEdit = 0;
					if ( $options['editContent'] ) {
						if ( $isAdministrator || $item->post_author == $isID ) {
							$canEdit = 1;
						}
					}

							$info = '' . __( 'Title', 'video-share-vod' ) . ': ' . esc_html( $item->post_title ) . "\r\n" . __( 'Duration', 'video-share-vod' ) . ': ' . esc_html( $duration ) . "\r\n" . __( 'Added', 'video-share-vod' ) . ': ' . esc_html( $age ) . "\r\n" . __( 'Views', 'video-share-vod' ) . ': ' . esc_html( $views );
						$views   .= ' ' . __( 'views', 'video-share-vod' );

						echo '<div class="videowhisperVideo">';
						echo '<a href="' . get_permalink( $item->ID ) . '" title="' . esc_attr( $info ) . '"><div class="videowhisperVideoTitle">' . esc_html( $item->post_title ) . '</div></a>';
						echo '<div class="videowhisperVideoDuration">' . esc_html( $duration ) . '</div>';
						echo '<div class="videowhisperVideoDate">' . esc_html( $age ) . '</div>';
						echo '<div class="videowhisperVideoViews">' . esc_html( $views ) . '</div>';
						echo '<div class="videowhisperVideoResolution">' . esc_html( $height ) . 'p</div>';

						$ratingCode = '';
					if ( $options['rateStarReview'] ) {
						$rating = floatval( get_post_meta( $item->ID, 'rateStarReview_rating', true ) );
						$max    = 5;
						if ( $rating > 0 ) {
							echo '<div class="videowhisperVideoRating"><div class="ui yellow star rating readonly" data-rating="' . round( $rating * $max ) . '" data-max-rating="' . esc_html( $max ) . '"></div></div>';
						}
					}

					if ( $pmEnabled && $canEdit ) {
						echo '<a style="z-index:10" href="' . esc_url( $options['editURL'] ) . esc_attr( $item->ID ) . '"><span class="videowhisperVideoEdit">' . __( 'EDIT', 'video-share-vod' ) . ' </span></a>';
					}

					if ( ! $imagePath || ! file_exists( $imagePath ) ) {
						$imagePath = plugin_dir_path( __FILE__ ) . 'no_video.png';
						self::updatePostThumbnail( $item->ID );
					} else // what about featured image?
						{
						$post_thumbnail_id = get_post_thumbnail_id( $item->ID );
						if ( $post_thumbnail_id ) {
							$post_featured_image = wp_get_attachment_image_src( $post_thumbnail_id, 'featured_preview' );
						}

						if ( ! $post_featured_image ) {
							self::updatePostThumbnail( $item->ID );
						}
					}

						$thumbURL    = self::path2url( $imagePath );
						$previewCode = '<IMG src="' . esc_url( $thumbURL ) . '" width="' . intval( $options['thumbWidth'] ) . 'px" height="' . intval( $options['thumbHeight'] ) . 'px" ALT="' . esc_attr( $info ) . '">';

					if ( $previewVideo && ! $isMobile ) {
						$previewCode = '<video class="videowhisperPreviewVideo" ' . esc_attr( $previewMuted ) . ' poster="' . esc_url( $thumbURL ) . '" preload="none" width="' . intval( $options['thumbWidth'] ) . '" height="' . intval( $options['thumbHeight'] ) . '"><source src="' . self::path2url( $previewVideo ) . '" type="video/mp4">' . $previewCode . '</video>'; //$previewCode image escaped above

					}

						echo '<a href="' . get_permalink( $item->ID ) . '" title="' . esc_attr( $info ) . '">' . $previewCode . '</a>'; //$previewCode escaped above

						// <div class="videowhisperPreview" style="background-image: url(\'' . $thumbURL . '\'); width: ' . $options['thumbWidth'] . 'px; height: ' . $options['thumbHeight'] . 'px; padding: 0px; margin: 0px; overflow: hidden; display: block;"> </div>

						echo '</div>
					';

						$k++;
				}

				echo '</div>';

			} else {
				echo __( 'No videos.', 'video-share-vod' );					
			}

			if ( !$options['enable_exec'] ) echo '<div class="ui segment">Warning: Server Command Execution is disabled from plugin settings and FFmpeg can NOT run to generate snapshots (required to list videos). Generating snapshots and converting videos requires <a href="https://videosharevod.com/hosting/">web hosting with FFmpeg support</a>.</div>';

			if ( ! $isMobile ) {
				echo '
<SCRIPT language="JavaScript">

jQuery(document).ready(function()
{

var hHandlers = jQuery(".videowhisperVideo").hover( hoverVideoWhisper, outVideoWhisper );

function hoverVideoWhisper(e) {
   var vid = jQuery(\'video\', this).get(0);
   if (vid) vid.play();
}

function outVideoWhisper(e) {
     var vid = jQuery(\'video\', this).get(0);
     if (vid) vid.pause();
}
});
</SCRIPT>
';

			}
			// pagination
			if ( $selectPage ) {
				echo '<BR>';
				echo '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' form"><div class="inline fields">';

				if ( $page > 0 ) {
					echo ' <a class="ui labeled icon button black" href="JavaScript: void()" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlA ) . '&p=' . intval( $page - 1 ) . '\'; loadVideos' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading previous page...</div>\');"><i class="left arrow icon"></i> ' . __( 'Previous', 'video-share-vod' ) . '</a> ';
				}

				echo '<a class="ui button black" href="#"> ' . __( 'Page', 'video-share-vod' ) . ' ' . intval( $page + 1 ) . ' </a>';

				if ( count( $postslist ) >= $perPage ) {
					echo ' <a class="ui right labeled icon button black" href="JavaScript: void()" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlA ) . '&p=' . intval( $page + 1 ) . '\'; loadVideos' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading next page...</div>\');">' . __( 'Next', 'video-share-vod' ) . ' <i class="right arrow icon"></i></a> ';
				}

				echo '</div></div>';

			}

			//close layout with menu
			if ( $menu ) echo '</div></div>';

			echo self::scriptThemeMode($options);

			// output end
			die;

		}

		static function scriptThemeMode($options)
		{
			$theme_mode = '';
			
			//check if using the FansPaysite theme and apply the dynamic theme mode
			if (function_exists('fanspaysite_get_current_theme_mode')) $theme_mode = fanspaysite_get_current_theme_mode();
			else $theme_mode = '';
		
			if (!$theme_mode) $theme_mode = $options['themeMode'] ?? '';
		
			if (!$theme_mode) return '<!-- No theme mode -->';
		
			// JavaScript function to apply the theme mode
			return '<script>
			if (typeof setConfiguredTheme !== "function")  // Check if the function is already defined
			{ 
		
				function setConfiguredTheme(theme) {
					if (theme === "auto") {
						if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
							document.body.dataset.theme = "dark";
						} else {
							document.body.dataset.theme = "";
						}
					} else {
						document.body.dataset.theme = theme;
					}
		
					if (document.body.dataset.theme == "dark")
					{
					jQuery("body").find(".ui").addClass("inverted");
					jQuery("body").addClass("inverted");
					}else
					{
						jQuery("body").find(".ui").removeClass("inverted");
						jQuery("body").removeClass("inverted");
					}
		
					console.log("VideoShareVOD/setConfiguredTheme:", theme);
				}
			}	
		
			setConfiguredTheme("' . esc_js($theme_mode) . '");
		
			</script>';
		}

		
		static function enqueueUI() {
			wp_enqueue_script( 'jquery' );

			wp_enqueue_style( 'semantic', dirname( plugin_dir_url( __FILE__ ) ) . '/interface/semantic/semantic.min.css' );
			wp_enqueue_script( 'semantic', dirname( plugin_dir_url( __FILE__ ) ) . '/interface/semantic/semantic.min.js', array( 'jquery' ) );
		}


		// !Shortcodes

		static function videowhisper_videos( $atts ) {

			$options = get_option( 'VWvideoShareOptions' );

			$atts = shortcode_atts(
				array(
					'menu'            => sanitize_text_field( $options['listingsMenu'] ),				
					'perpage'         => $options['perPage'],
					'perrow'          => '',
					'playlist'        => '',
					'order_by'        => '',
					'category_id'     => '',
					'select_category' => '1',
					'select_tags'     => '1',
					'select_name'     => '1',
					'select_order'    => '1',
					'select_page'     => '1',
					'include_css'     => '1',
					'tags'            => '',
					'name'            => '',
					'user_id'         => '0',
					'id'              => '',
				),
				$atts,
				'videowhisper_videos'
			);

			$id = $atts['id'];
			if ( ! $id ) {
				$id = uniqid();
			}

			self::enqueueUI();

			$ajaxurl = admin_url() . 'admin-ajax.php?action=vwvs_videos&menu=' . $atts['menu'] . '&pp=' . $atts['perpage'] . '&pr=' . $atts['perrow'] . '&playlist=' . urlencode( $atts['playlist'] ) . '&ob=' . $atts['order_by'] . '&cat=' . $atts['category_id'] . '&sc=' . $atts['select_category'] . '&sn=' . $atts['select_name'] . '&sg=' . $atts['select_tags'] . '&so=' . $atts['select_order'] . '&sp=' . $atts['select_page'] . '&id=' . $id . '&tags=' . urlencode( $atts['tags'] ) . '&name=' . urlencode( $atts['name'] ) . '&user_id=' . intval( $atts['user_id'] );

			$htmlCode  = <<<HTMLCODE
<!-- VideoShareVOD: videowhisper_videos -->
<script type="text/javascript">
var aurl$id = '$ajaxurl';
var loader$id;

	function loadVideos$id(message){

	if (message)
	if (message.length > 0)
	{
	  jQuery("#videowhisperVideos$id").html(message);
	}

		if (loader$id) loader$id.abort();

		loader$id = jQuery.ajax({
			url: aurl$id,
			success: function(data) {
				jQuery("#videowhisperVideos$id").html(data);
				try{
				jQuery(".ui.dropdown:not(.multi,.fpsDropdown)").dropdown();
				jQuery(".ui.rating.readonly").rating("disable");
				} catch (error)
				{
					console.log("interface error loadVideos", error);
					}
			}
		});
	}


	jQuery(document).ready(function(){
			loadVideos$id();
			setInterval("loadVideos$id('')", 60000);
	});

</script>

<div id="videowhisperVideos$id">
	<div class="ui active inline text large loader">Loading videos...</div>
</div>

HTMLCODE;
			$htmlCode .= self::poweredBy();

			if ( $atts['include_css'] ) {
				$htmlCode .= '<STYLE>' . html_entity_decode( stripslashes( $options['customCSS'] ) ) . '</STYLE>';
			}

			return $htmlCode;
		}


	static function videowhisper_plupload( $atts ) {

		$options = get_option( 'VWvideoShareOptions' );

		self::enqueueUI();

		if ( ! is_user_logged_in() ) {
			return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . __( 'Login is required to upload videos!', 'video-share-vod' ) . '<br/><br/> <a class="ui button qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'video-share-vod' ) . '</a>  <a class="ui button qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'video-share-vod' ) . '</a></div>';
		}

		$current_user = wp_get_current_user();
		$userName     = sanitize_text_field( $options['userName'] );
		if ( ! $userName ) {
			$userName = 'user_nicename';
		}
		$username = $current_user->$userName;

		if ( ! self::hasPriviledge( $options['shareList'] ) ) {
			return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . __( 'You do not have permissions to share videos!', 'video-share-vod' ) . '</div>';
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
			'videowhisper_plupload'
		);

		self::enqueueUI();

		// plupload + jquery ui  widget
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-button' );
		wp_enqueue_script( 'jquery-ui-progressbar' );
		wp_enqueue_script( 'jquery-ui-widget' );
		wp_enqueue_script( 'plupload' );

		wp_enqueue_style( 'jquery-ui-css', dirname( plugin_dir_url( __FILE__ ) ) . '/interface/jquery.ui/jquery-ui.min.css' );
		wp_enqueue_style( 'plupload2-widget', dirname( plugin_dir_url( __FILE__ ) ) . '/interface/jquery.ui.plupload/css/jquery.ui.plupload.css' );
		wp_enqueue_script( 'plupload2-widget', dirname( plugin_dir_url( __FILE__ ) ) . '/interface/jquery.ui.plupload/jquery.ui.plupload.min.js', array( 'plupload' ) );

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

			$htmlCode       = '';
			$interfaceClass = sanitize_text_field( $options['interfaceClass'] );

		if ( isset($_POST['upload']) && $_POST['upload'] == 'VideoWhisper' ) {

			$htmlCode .= '<div class ="ui message"> ' . __( 'Submission Results', 'video-share-vod' ) . ':';

			$category_id = intval( $_POST['category'] );
			$owner_id    = intval( $_POST['owner'] );
			$description = wp_encode_emoji( sanitize_text_field( $_POST['description'] ) );

			// if csv sanitize as array
			$playlist = sanitize_text_field( $_POST['playlist'] );
			if ( strpos( $playlist, ',' ) !== false ) {
				$playlists = explode( ',', $playlist );
				foreach ( $playlists as $key => $value ) {
					$playlists[ $key ] = sanitize_file_name( trim( $value ) );
				}
				$playlist = $playlists;
			}

			$tag = sanitize_text_field( $_POST['tag'] );
			if ( strpos( $tag, ',' ) !== false ) {
				$tags = explode( ',', $tag );
				foreach ( $tags as $key => $value ) {
					$tags[ $key ] = sanitize_file_name( trim( $value ) );
				}
				$tag = $tags;
			} else {
				$tag = sanitize_file_name( trim( $tag ) );
			}

			// checks

			if ( $owner_id && ! current_user_can( 'edit_users' ) && $owner_id != $current_user->ID ) {
				return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . __( 'Only admin can upload for other users!', 'video-share-vod' ) . '</div>';
			}
			if ( ! $owner_id ) {
				$owner = $current_user->ID;
			}

			if ( ! $playlist ) {
				return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . __( 'Playlist is required!', 'video-share-vod' ) . '</div>';
			}

			$uploader_count = intval( $_POST['uploader_count'] );
			// $htmlCode .= '<br>Files: ' . $uploader_count;

			$targetDir = $options['uploadsPath'] . '/plupload/';
			if ( ! file_exists( $options['uploadsPath'] ) ) {
				mkdir( $options['uploadsPath'] );
			}
			if ( ! file_exists( $targetDir ) ) {
				mkdir( $targetDir );
			}

			if ( $uploader_count > 0 ) {
				for ( $i = 0; $i < $uploader_count; $i++ ) {

					$name   = sanitize_file_name( $_POST[ 'uploader_' . $i . '_name' ] );
					$status = sanitize_text_field( $_POST[ 'uploader_' . $i . '_status' ] );

					$el    = array_shift( explode( '.', $name ) );
					$title = ucwords( str_replace( '-', ' ', $el ) );

					if ( file_exists( $targetDir . $name ) ) {
						$htmlCode .= '<div class="ui segment ' . $interfaceClass . '">' . self::importFile( $targetDir . $name, $title, $owner_id, $playlist, $category_id, $tag, $description ) . '</div>';
					} else {
						$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . 'Missing upload: ' . $targetDir . $name . ' Upload can fail due to restricted filename (like extra dots or special characters), unsupported extension, web hosting upload restriction or upload timeout.</div>';
					}
				}
			} else {
				$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . 'Form submitted with no uploads, uploader_count=0! You could try a different browser.' . '</div>';
			}

				$htmlCode .= '</div><h4 class="ui header">Add more:</H4>';
		}

		$ajaxurl = admin_url() . 'admin-ajax.php?action=vwvs_plupload';

		$extensions = implode( ',', self::extensions_video() );

		$thisPage = self::getCurrentURLfull();

		$t_files   = __( 'Add Files', 'video-share-vod' );
		$t_details = __( 'Add Details', 'video-share-vod' );
		$t_submit  = __( 'Submit', 'video-share-vod' );

		$htmlCode .= <<<HTMLCODE
<form class="ui $interfaceClass form" id="uploaderForm" method="post" action="$thisPage">
<h4 class="ui header"><i class="file video icon"></i> 1. $t_files</H4>

	<div id="uploader">
		<p>Your browser doesn't have JavaScript & HTML5 upload support or there's some error preventing JS uploader (check developer console).</p>
	</div>

<h4 class="ui header"><i class="list icon"></i> 2. $t_details</H4>

<fieldset>
$categories
$playlists
$tags
$descriptions
$owners
</fieldset>

<h4 class="ui header"><i class="save icon"></i> 3. $t_submit</H4>
<input type="hidden" id="upload" name="upload" value="VideoWhisper" />

<button class="ui button">
<i class="save icon"></i>
  Submit
</button>

</form>

<script type="text/javascript">
// Initialize the widget when the DOM is ready
jQuery(function() {
	jQuery("#uploader").plupload({

		// General settings
		runtimes : 'html5,flash,silverlight,html4',
		url : '$ajaxurl',

		// User can upload no more then 20 files in one go (sets multiple_queues to false)
		max_file_count: 20,

		chunk_size: '1mb',
		max_retries: 5,

		filters : {
			// Maximum file size
			max_file_size : '6000mb',

			// Specify what files to browse for
			mime_types: [
				{title : "Video files", extensions : "$extensions"}
			],
			prevent_duplicates: true
		},

		// Rename files by clicking on their titles
		rename: true,

		// Sort files
		sortable: true,

		// Enable ability to drag'n'drop files onto the widget (currently only HTML5 supports that)
		dragdrop: true,

		// Views to activate
		views: {
			list: true,
			thumbs: true, // Show thumbs
			active: 'thumbs'
		},

	});


//	$('#uploader').plupload('notify', 'info', "This might be obvious, but you need to click 'Add Files' to add some files.");


jQuery('#uploader').on('error', function(event, args) {
			jQuery('#uploader').plupload('notify', 'error', args.error.message);
			//console.log(args, event);
							});


//	$('#uploader').plupload.ua = navigator.userAgent;

	// Handle the case when form was submitted before uploading has finished
	jQuery('#uploaderForm').submit(function(e) {
		// Files in queue upload them first
		if (jQuery('#uploader').plupload('getFiles').length > 0) {

			// When all files are uploaded submit form
			jQuery('#uploader').on('complete', function() {
				jQuery('#uploaderForm')[0].submit();
			});

			jQuery('#uploader').plupload('start');
		} else {
			alert("You must have at least one file in the queue.");
		}
		return false; // Keep the form from submitting
	});
});
</script>

HTMLCODE;
		return $htmlCode;
	}


	// ajax handler for plupload
	static function vwvs_plupload() {
		$options = get_option( 'VWvideoShareOptions' );

		// Make sure file is not cached (as it happens for example on iOS devices)
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );

		/*
		 s
		// Support CORS
		header("Access-Control-Allow-Origin: *");
		// other CORS headers if any...
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
		exit; // finish preflight CORS requests here
		}
		*/

		if ( ! is_user_logged_in() ) {
			die( '{"jsonrpc" : "2.0", "error" : {"code": 201, "message": "User login required for upload."}, "id" : "id"}' );
			exit;
		}

		// Settings
		$targetDir = $options['uploadsPath'] . '/plupload';
		// $targetDir = 'uploads';
		$cleanupTargetDir = true; // Remove old files
		$maxFileAge       = 6 * 3600; // Temp file age in seconds 6h

		// Create target dir
		if ( ! file_exists( $options['uploadsPath'] ) ) {
			mkdir( $options['uploadsPath'] );
		}
		if ( ! file_exists( $targetDir ) ) {
			mkdir( $targetDir );
		}

		// Get a file name
		if ( isset( $_REQUEST['name'] ) ) {
			$fileName = sanitize_file_name( $_REQUEST['name'] );
		} elseif ( ! empty( $_FILES ) ) {
			$fileName = sanitize_file_name( $_FILES['file']['name'] );
		} else {
			$fileName = uniqid( 'file_' );
		}

		// double check extension server side
		$ext = strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) );

		if ( ! in_array( $ext, self::extensions_video() ) ) {
			die( '{"jsonrpc" : "2.0", "error" : {"code": 203, "message": "File extension is not supported."}, "id" : "id"}' );
			exit;
		}

		$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

		// Chunking might be enabled
		$chunk  = isset( $_REQUEST['chunk'] ) ? intval( $_REQUEST['chunk'] ) : 0;
		$chunks = isset( $_REQUEST['chunks'] ) ? intval( $_REQUEST['chunks'] ) : 0;

		// Remove old temp files
		if ( $cleanupTargetDir ) {
			if ( ! is_dir( $targetDir ) || ! $dir = opendir( $targetDir ) ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}' );
			}

			while ( ( $file = readdir( $dir ) ) !== false ) {
				$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

				// If temp file is current file proceed to the next
				if ( $tmpfilePath == "{$filePath}.part" ) {
					continue;
				}

				// Remove temp file if it is older than the max age and is not the current file
				if ( preg_match( '/\.part$/', $file ) && ( filemtime( $tmpfilePath ) < time() - $maxFileAge ) ) {
					@unlink( $tmpfilePath );
				}
			}
			closedir( $dir );
		}

		// Open temp file
		if ( ! $out = @fopen( "{$filePath}.part", $chunks ? 'ab' : 'wb' ) ) {
			die( '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}' );
		}

		if ( ! empty( $_FILES ) ) {
			if ( $_FILES['file']['error'] || ! is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}' );
			}

			// Read binary input stream and append it to temp file
			if ( ! $in = @fopen( $_FILES['file']['tmp_name'], 'rb' ) ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}' );
			}
		} else {
			if ( ! $in = @fopen( 'php://input', 'rb' ) ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}' );
			}
		}

		while ( $buff = fread( $in, 4096 ) ) {
			fwrite( $out, $buff );
		}

		@fclose( $out );
		@fclose( $in );

		// Check if file has been uploaded
		if ( ! $chunks || $chunk == $chunks - 1 ) {
			// Strip the temp .part suffix off
			rename( "{$filePath}.part", $filePath );
			// completed
		}

		// Return Success JSON-RPC response
		die( '{"jsonrpc" : "2.0", "result" : null, "id" : "id"}' );

	}



}
