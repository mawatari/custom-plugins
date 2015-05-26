<?php
function scc_get_popular_posts($posts_per_page = 10) {
	$post_type = 'post';
	$meta_key       = 'scc_share_count_total';
	$query_args = array(
		'post_type'              => $post_type,
		'post_status'            => 'publish',
		'posts_per_page'         => $posts_per_page,
		'meta_key'               => $meta_key,
		'orderby'                => 'meta_value_num',
		'update_post_term_cache' => false,
		'order'                  => 'DESC'
	);
	$posts_query = new WP_Query($query_args);
	if ($posts_query->have_posts()) {
		echo '<ul>';
		while ($posts_query->have_posts()) {
			$posts_query->the_post();
			$post_url   = esc_url(get_permalink(get_the_ID()));
			$post_title = get_the_title(get_the_ID());
			$title      = preg_replace("/<span class=.*?<\/span>/", "", $post_title);
			echo "<li><a href=\"$post_url\" title=\"$title\">$post_title</a></li>";
		}
		echo '</ul>';
	}
}
?>