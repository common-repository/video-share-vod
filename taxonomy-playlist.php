<?php
get_header();

echo '<br style="clear:both">';
$tax = $wp_query->get_queried_object();
echo '<h3>' . esc_html( $tax->name ) . '</h3>';
echo do_shortcode( '[videowhisper_playlist name="' . esc_attr( $tax->name ) . '"]' );
echo '<br style="clear:both">';

get_footer();
