<?php
/*
 * Plugin initialization file.
 *
 * Plugin Name: My WP A/B Testing
 * Plugin URI: https://whodunit.fr/
 * Description: An easy way to set up A/B Testing Campaigns using Gutenberg blocks, and to get the conversion rates for each variation
 * Version: 0.1
 * Requires at least: 5.3
 * Requires PHP: 7.0
 * Tested up to: 5.6
 * Author: Whodunit Agency
 * Author URI: https://whodunit.fr
 * Contributors: whodunitagency, audrasjb, leprincenoir
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: my-wp-ab-testing
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

if ( is_admin() ) {
	/* Polylang compatibility */
	if ( function_exists( 'pll__' ) ) {
		add_action( 'pre_get_posts', 'reblex_reusable_menu_polylang_all_langs', 10, 2 );
	}
	add_filter( 'manage_abtesting_posts_columns', 'reblexab_reusable_screen_add_column' );
	add_action( 'manage_abtesting_posts_custom_column' , 'reblexab_reusable_screen_fill_column', 1000, 2);
}

// Enqueues
function reblexab_enqueue_scripts_public() {
    wp_register_script( 'reblexab-stat', plugins_url( '/js/reblexab-stat.js', __FILE__ ), array( 'jquery' ) );
	$translations = array(
		'reblexab_ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
	);
	wp_localize_script( 'reblexab-stat', 'reblexab_localize', $translations );
}
add_action( 'wp_enqueue_scripts', 'reblexab_enqueue_scripts_public' );

function reblexab_enqueue_scripts_admin( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
	    $screen = get_current_screen();
		if ( is_admin() && 'abtesting' === get_post_type() ) {
			wp_register_style(
				'reblexab-admin',
				plugins_url( 'css/reblexab-admin.css', __FILE__ ),
				array(),
				filemtime( plugin_dir_path( __FILE__ ) . 'css/reblexab-admin.css' ),
				'all'
			);
			wp_enqueue_style( 'reblexab-admin' );

	    	wp_register_script( 'reblexab-chart',
	    		plugins_url( '/vendor/chart/chart.min.js', __FILE__ ),
	    		array( 'jquery' )
	    	);
			wp_enqueue_script( 'reblexab-chart' );

			wp_register_script(
				'reblexab-admin',
				plugins_url( 'js/reblexab-admin.js', __FILE__ ),
				array( 'jquery', 'reblexab-chart' ),
				filemtime( plugin_dir_path( __FILE__ ) . 'js/reblexab-admin.js' ),
				true
			);
			wp_enqueue_script( 'reblexab-admin' );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'reblexab_enqueue_scripts_admin' );

// Create CPT
function reblexab_register_post_type() {
	$labels = array(
		'name'          => esc_html__( 'A/B Testing campaigns', 'my-wp-ab-testing' ),
		'singular_name' => esc_html__( 'A/B Testing campaign', 'my-wp-ab-testing' ),
		'menu_name'     => esc_html__( 'A/B Testing', 'my-wp-ab-testing' ),
		'add_new'       => esc_html__( 'Add New', 'my-wp-ab-testing' ),
		'add_new_item'  => esc_html__( 'Add new A/B testing campaign', 'my-wp-ab-testing' ),
		'new_item'      => esc_html__( 'New A/B testing campaign', 'my-wp-ab-testing' ),
		'edit_item'     => esc_html__( 'Edit campaign', 'my-wp-ab-testing' ),
		'view_item'     => esc_html__( 'View campaign', 'my-wp-ab-testing' ),
		'all_items'     => esc_html__( 'All campaigns', 'my-wp-ab-testing' ),
		'search_items'  => esc_html__( 'Search campaigns', 'my-wp-ab-testing' ),
	);
	$args = array(
		'labels' => $labels,
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => true, 
		'show_in_menu' => true, 
		'query_var' => true,
		'rewrite' => array( 'slug' => 'abtesting' ),
		'capability_type' => 'page',
		'has_archive' => false,
		'exclude_from_search' => true,
		'show_in_rest' => true,
		'hierarchical' => false,
//		'menu_position' => 20,
		'menu_icon' => 'dashicons-chart-pie',
		'supports' => array( 'title' )
  	); 
	register_post_type( 'abtesting', $args );
}
add_action( 'init', 'reblexab_register_post_type' );

function reblexab_reusable_screen_add_column( $columns ) {
	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => esc_html__( 'A/B testing campaigns' ),
		'reblexab-stats' => esc_html__( 'Stats', 'my-wp-ab-testing' ),
		'reblexab-date-modified' => esc_html__( 'Last modified', 'my-wp-ab-testing' )
	);
	return $columns;
}

function reblexab_reusable_screen_fill_column( $column, $ID ) {
	global $post;
	// Existing data
	$existing_block_a = '';
	$existing_block_b = '';
	$existing_distribution_a = 50;
	$existing_distribution_b = 50;
	$reblexab_block_a_target_selector = '';
	$reblexab_block_b_target_selector = '';
	if ( 
		get_post_meta( $ID, 'abtesting_block_a', true ) && 
		get_post_meta( $ID, 'abtesting_block_b', true ) &&
		get_post_meta( $ID, 'abtesting_distribution_a', true ) &&
		get_post_meta( $ID, 'abtesting_distribution_b', true )
	) {
		$existing_block_a = absint( get_post_meta( $ID, 'abtesting_block_a', true ) );
		$existing_block_b = absint( get_post_meta( $ID, 'abtesting_block_b', true ) );
		$existing_distribution_a = absint( get_post_meta( $ID, 'abtesting_distribution_a', true ) );
		$existing_distribution_b = absint( get_post_meta( $ID, 'abtesting_distribution_b', true ) );
		if ( get_post_meta( $ID, 'reblexab_block_a_target_selector', true ) ) {
			$reblexab_block_a_target_selector = esc_html( get_post_meta( $ID, 'reblexab_block_a_target_selector', true ) );
		}
		if ( get_post_meta( $ID, 'reblexab_block_b_target_selector', true ) ) {
			$reblexab_block_b_target_selector = esc_html( get_post_meta( $ID, 'reblexab_block_b_target_selector', true ) );
		}
		// Conversions
		$abtesting_block_a_conversion = 0;
		$abtesting_block_b_conversion = 0;
		if ( get_post_meta( $ID, 'reblexab_block_conversion_a', true ) ) {
			$abtesting_block_a_conversion = absint( get_post_meta( $ID, 'reblexab_block_conversion_a', true ) );
		}
		if ( get_post_meta( $ID, 'reblexab_block_conversion_b', true ) ) {
			$abtesting_block_b_conversion = absint( get_post_meta( $ID, 'reblexab_block_conversion_b', true ) );
		}
		$abtesting_block_a_count = 0;
		$abtesting_block_b_count = 0;
		if ( get_post_meta( $ID, 'abtesting_block_a_count', true ) ) {
			$abtesting_block_a_count = get_post_meta( $ID, 'abtesting_block_a_count', true );
		}
		if ( get_post_meta( $ID, 'abtesting_block_b_count', true ) ) {
			$abtesting_block_b_count = get_post_meta( $ID, 'abtesting_block_b_count', true );
		}
	}
	switch( $column ) {
		case 'reblexab-stats' :
			if (
				get_post_meta( $ID, 'reblexab_block_conversion_a', true ) &&
				get_post_meta( $ID, 'reblexab_block_conversion_b', true ) &&
				get_post_meta( $ID, 'abtesting_block_a_count', true ) &&
				get_post_meta( $ID, 'abtesting_block_b_count', true )
			) :
			?>
				<?php if ( $existing_block_a ) : ?>
					<p>
						<strong><?php esc_html_e( 'Block A', 'my-wp-ab-testing' ); ?></strong> (<?php echo absint( $existing_distribution_a ); ?>%) – 
						<a href="<?php echo get_edit_post_link( $existing_block_a ); ?>" target="_blank">
							<?php echo get_the_title( $existing_block_a ); ?>
							<span aria-hidden="true"> ↗</span><span class="screen-reader-text">
							<?php esc_html_e( 'Opens in a new tab', 'my-wp-ab-testing' ); ?></span>
						</a>
						<br />
						<?php
						echo sprintf(
							/* Translators: 1: Number of times the block was displayed. 2: Number of times the block was converted. */
							esc_html__( 'Displayed %1$d times and converted %2$s times.', 'my-wp-ab-testing' ),
							$abtesting_block_a_count,
							$abtesting_block_a_conversion
						);
						?>
						<br />
						<?php
						echo sprintf(
							/* Translators: Percentage of conversions. */
							esc_html__( 'Conversions rate: %d', 'my-wp-ab-testing' ),
							round( $abtesting_block_a_conversion * 100 / $abtesting_block_a_count, 2 )
						) . '%';
						?>
					</p>
				<?php endif; ?>
				<?php if ( $existing_block_b ) : ?>
					<p>
						<strong><?php esc_html_e( 'Block B', 'my-wp-ab-testing' ); ?></strong> (<?php echo absint( $existing_distribution_b ); ?>%) – 
						<a href="<?php echo get_edit_post_link( $existing_block_b ); ?>" target="_blank">
							<?php echo get_the_title( $existing_block_b ); ?>
							<span aria-hidden="true"> ↗</span><span class="screen-reader-text">
							<?php esc_html_e( 'Opens in a new tab', 'my-wp-ab-testing' ); ?></span>
						</a>
						<br />
						<?php
						echo sprintf(
							/* Translators: 1: Number of times the block was displayed. 2: Number of times the block was converted. */
							esc_html__( 'Displayed %1$d times and converted %2$s times.', 'my-wp-ab-testing' ),
							$abtesting_block_b_count,
							$abtesting_block_b_conversion
						);
						?>
						<br />
						<?php
						echo sprintf(
							/* Translators: Percentage of conversions. */
							esc_html__( 'Conversions rate: %d', 'my-wp-ab-testing' ),
							round( $abtesting_block_b_conversion * 100 / $abtesting_block_b_count, 2 )
						) . '%';
						?>
					</p>
				<?php endif; ?>
			<?php
			endif;
			break;
		case 'reblexab-date-modified' :
			$d = get_date_from_gmt( $post->post_modified, 'Y-m-d H:i:s' );
			echo sprintf(
				/* translators: %1$s: Date the block was last modified %2$s Time the block was last modified %3$s Author */
				esc_html__( '%1$s at %2$s', 'my-wp-ab-testing' ),
				date_i18n( get_option('date_format'), strtotime( $d ) ),
				date_i18n( get_option('time_format'), strtotime( $d ) )
			);
			if ( get_post_meta( $ID, '_edit_last', true ) ) {
				$last_user = get_userdata( get_post_meta( $ID, '_edit_last', true ) );
				echo ' ' . esc_html__( 'by', 'my-wp-ab-testing' ) . ' ' . $last_user->display_name;
			}
			break;
		default :
			break;
	}
}

// Swith to one-column only on post edit screen
function reblexab_cpt_just_one_column( $result, $option, $user ) {
	return 1;
}
add_filter( 'get_user_option_screen_layout_abtesting', 'reblexab_cpt_just_one_column', 10, 3 );

// Declare reblexab CPT metaboxes
function reblexab_abtesting_metaboxes( $object ) {

	// Nonce
	wp_nonce_field( basename(__FILE__), 'abtesting-nonce' );

	// Existing data
	$existing_block_a = '';
	$existing_block_b = '';
	$existing_distribution_a = 50;
	$existing_distribution_b = 50;
	$reblexab_block_a_target_selector = '';
	$reblexab_block_b_target_selector = '';
	$second_step = false;
	$campaign_locked = false;
	if ( get_post_meta( $object->ID, 'abtesting_block_a', true ) && get_post_meta( $object->ID, 'abtesting_block_b', true ) ) {
		$existing_block_a = absint( get_post_meta( $object->ID, 'abtesting_block_a', true ) );
		$existing_block_b = absint( get_post_meta( $object->ID, 'abtesting_block_b', true ) );
		$second_step = true;
	}
	if ( get_post_meta( $object->ID, 'abtesting_distribution_a', true ) && get_post_meta( $object->ID, 'abtesting_distribution_b', true ) ) {
		$second_step = false;
		$campaign_locked = true;
		$existing_distribution_a = absint( get_post_meta( $object->ID, 'abtesting_distribution_a', true ) );
		$existing_distribution_b = absint( get_post_meta( $object->ID, 'abtesting_distribution_b', true ) );
		if ( get_post_meta( $object->ID, 'reblexab_block_a_target_selector', true ) ) {
			$reblexab_block_a_target_selector = esc_html( get_post_meta( $object->ID, 'reblexab_block_a_target_selector', true ) );
		}
		if ( get_post_meta( $object->ID, 'reblexab_block_b_target_selector', true ) ) {
			$reblexab_block_b_target_selector = esc_html( get_post_meta( $object->ID, 'reblexab_block_b_target_selector', true ) );
		}
		// Conversions
		$abtesting_block_a_conversion = 0;
		$abtesting_block_b_conversion = 0;
		if ( get_post_meta( $object->ID, 'reblexab_block_conversion_a', true ) ) {
			$abtesting_block_a_conversion = absint( get_post_meta( $object->ID, 'reblexab_block_conversion_a', true ) );
		}
		if ( get_post_meta( $object->ID, 'reblexab_block_conversion_b', true ) ) {
			$abtesting_block_b_conversion = absint( get_post_meta( $object->ID, 'reblexab_block_conversion_b', true ) );
		}	
	}

	$abtesting_block_a_count = 0;
	$abtesting_block_b_count = 0;
	if ( get_post_meta( $object->ID, 'abtesting_block_a_count', true ) ) {
		$abtesting_block_a_count = get_post_meta( $object->ID, 'abtesting_block_a_count', true );
	}
	if ( get_post_meta( $object->ID, 'abtesting_block_b_count', true ) ) {
		$abtesting_block_b_count = get_post_meta( $object->ID, 'abtesting_block_b_count', true );
	}

	// WP_Query
	$args = array(
		'post_type' => 'wp_block',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	);
	$error = '';
	$reusable_blocks = array();
	$query_reusable = new WP_Query( $args );
	if ( $query_reusable->found_posts > 1 ) {
		while ( $query_reusable->have_posts() ) {
			$query_reusable->the_post();
			$reusable_blocks[] = array(
				'id' => get_the_ID(),
				'title' => get_the_title(),
			);
		}
	} else {
		$error = '<p>' . esc_html__( 'Please create at least two reusable blocks first!', 'my-wp-ab-testing' ) . '</p>';
	}
	wp_reset_postdata();

	if ( ! empty( $error ) ) {
		echo $error;
	} else {
		?>

		<?php if ( $second_step && ! $campaign_locked ) : ?>
			<h2><?php esc_html_e( 'Step 2: Choose a target for each reusable block', 'my-wp-ab-testing' ); ?></h2>
		<?php endif; ?>

		<?php if ( ! $second_step && ! $campaign_locked ) : ?>
			<h2><?php esc_html_e( 'Step 1: Give your campaign a title and choose two reusable blocks to A/B test', 'my-wp-ab-testing' ); ?></h2>
			<script>jQuery(window).on('load', function(e) { jQuery(window).off('beforeunload'); } );</script>
		<?php endif; ?>

		<?php if ( $campaign_locked ) : ?>
			<h2><?php esc_html_e( 'Campaign settings', 'my-wp-ab-testing' ); ?></h2>
		<?php endif; ?>

		<table class="form-table reblexab_block_select_wrapper reblexab_block_a_wrapper">
			<tr>
				<th>
					<label for="reblexab_block_a_selector"><?php esc_html_e( 'Block A', 'my-wp-ab-testing' ); ?></label>
				</th>
				<td>
					<?php
					$disabled = '';
					if ( $existing_block_a ) {
						$disabled = ' disabled';	
					}
					?>

					<select name="reblexab_block_a_selector" <?php echo $disabled; ?>>
						<option value=""><?php esc_html_e( '— Select a reusable block —', 'my-wp-ab-testing' ); ?></option>
						<?php foreach ( $reusable_blocks as $block ) : ?>
							<?php
							$selected = '';
							if ( $block['id'] === $existing_block_a ) {
								$selected = ' selected';
							}
							?>
							<option value="<?php echo $block['id']; ?>" <?php echo $selected; ?> /><?php echo $block['title']; ?></option>
						<?php endforeach; ?>
					</select>

					<?php if ( ! $campaign_locked && ! $second_step ) : ?>
						<a class="reblexab_external" href="<?php echo admin_url( '/post-new.php?post_type=wp_block' ); ?>" target="_blank"><?php esc_html_e( 'or create a new reusable block first', 'my-wp-ab-testing' ); ?> <span class="dashicons dashicons-external"></span></a>
					<?php endif; ?>

					<?php if ( $campaign_locked ) : ?>
						<a class="reblexab_external" href="<?php echo get_edit_post_link( $existing_block_a ); ?>" target="_blank"><?php esc_html_e( 'edit this block', 'my-wp-ab-testing' ); ?> <span class="dashicons dashicons-external"></span></a>
					<?php endif; ?>
					
					<?php if ( $second_step || $campaign_locked ) : ?>
						<p>
							<label for="reblexab_block_a_target_selector"><?php esc_html_e( 'Conversion target for Block A:', 'my-wp-ab-testing' ); ?></label>
						</p>
						<?php
						$content = apply_filters( 'the_content', get_post_field( 'post_content', $existing_block_a ) );
						$dom = new DOMDocument;
						@$dom->loadHTML( $content );
						$links = $dom->getElementsByTagName( 'a' );
						if ( $links ) : ?>
							<p>
								<?php
								$disabled = '';
								if ( $reblexab_block_a_target_selector ) {
									$disabled = ' disabled';	
								}
								?>
								<select name="reblexab_block_a_target_selector" <?php echo $disabled; ?>>
									<option value=""><?php esc_html_e( '— Select a target URL to track conversion —', 'my-wp-ab-testing' ); ?></option>
									<?php foreach ( $links as $link ) : ?>
										<?php
										$target = $link->getAttribute( 'href' );
										if ( ! empty( $target ) ) :
											$selected = '';
											if ( $target === $reblexab_block_a_target_selector ) :
												$selected = ' selected';
											endif;
										?>
										<option value="<?php echo $target; ?>"<?php echo $selected; ?>><?php echo $target; ?></option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
							</p>
						<?php
						endif;
						?>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<table class="form-table reblexab_block_select_wrapper reblexab_block_b_wrapper">
			<tr>
				<th>
					<label for="reblexab_block_a_selector"><?php esc_html_e( 'Block B', 'my-wp-ab-testing' ); ?></label>
				</th>
				<td>
					<?php
					$disabled = '';
					if ( $existing_block_b ) {
						$disabled = ' disabled';	
					}
					?>

					<select name="reblexab_block_b_selector" <?php echo $disabled; ?>>
						<option value=""><?php esc_html_e( '— Select a reusable block —', 'my-wp-ab-testing' ); ?></option>
						<?php foreach ( $reusable_blocks as $block ) : ?>
							<?php
							$selected = '';
							if ( $block['id'] === $existing_block_b ) {
								$selected = ' selected';
							}
							?>
							<option value="<?php echo $block['id']; ?>" <?php echo $selected; ?> /><?php echo $block['title']; ?></option>
						<?php endforeach; ?>
					</select>

					<?php if ( ! $campaign_locked && ! $second_step ) : ?>
						<a class="reblexab_external" href="<?php echo admin_url( '/post-new.php?post_type=wp_block' ); ?>" target="_blank"><?php esc_html_e( 'or create a new reusable block first', 'my-wp-ab-testing' ); ?> <span class="dashicons dashicons-external"></span></a>
					<?php endif; ?>

					<?php if ( $campaign_locked ) : ?>
						<a class="reblexab_external" href="<?php echo get_edit_post_link( $existing_block_a ); ?>" target="_blank"><?php esc_html_e( 'edit this block', 'my-wp-ab-testing' ); ?> <span class="dashicons dashicons-external"></span></a>
					<?php endif; ?>

					<?php if ( $second_step || $campaign_locked ) : ?>
						<p>
							<label for="reblexab_block_b_target_selector"><?php esc_html_e( 'Conversion target for Block B:', 'my-wp-ab-testing' ); ?></label>
						</p>
						<?php
						$content = apply_filters( 'the_content', get_post_field( 'post_content', $existing_block_b ) );
						$dom = new DOMDocument;
						@$dom->loadHTML( $content );
						$links = $dom->getElementsByTagName( 'a' );
						if ( $links ) : ?>
							<p>
								<?php
								$disabled = '';
								if ( $reblexab_block_b_target_selector ) {
									$disabled = ' disabled';	
								}
								?>
								<select name="reblexab_block_b_target_selector" <?php echo $disabled; ?>>
									<option value=""><?php esc_html_e( '— Select a target URL to track conversion —', 'my-wp-ab-testing' ); ?></option>
									<?php foreach ( $links as $link ) : ?>
										<?php
										$target = $link->getAttribute( 'href' );
										if ( ! empty( $target ) ) :
											$selected = '';
											if ( $target === $reblexab_block_b_target_selector ) :
												$selected = ' selected';
											endif;
										?>
										<option value="<?php echo esc_attr( $target ); ?>"<?php echo $selected; ?>><?php echo $target; ?></option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
							</p>
						<?php
						endif;
						?>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php if ( $second_step || $campaign_locked ) : ?>
			<table class="form-table reblexab_percentage_wrapper">
				<tr>
					<th>
						<label for="reblexab_percentage_selector"><?php esc_html_e( 'Distribution', 'my-wp-ab-testing' ); ?></label>
					</th>
					<td>
						<table>
							<tr>
								<td>
									<?php esc_html_e( 'Block A', 'my-wp-ab-testing' ); ?><br />
									<input type="number" min="0" max="100" name="abtesting_distribution_a" id="abtesting_distribution_a" class="small-text" value="<?php echo esc_attr( $existing_distribution_a ); ?>" />
								</td>
								<td>
									<input type="range" name="reblexab_percentage_selector" id="reblexab_percentage_selector" min="0" max="100" value="<?php echo esc_attr( $existing_distribution_a ); ?>" />
								</td>
								<td>
									<?php esc_html_e( 'Block B', 'my-wp-ab-testing' ); ?><br />
									<input type="number" min="0" max="100" name="abtesting_distribution_b" id="abtesting_distribution_b" class="small-text" value="<?php echo esc_attr( $existing_distribution_b ); ?>" />
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<p>
				<input type="submit" class="button button-primary button-hero" value="<?php esc_html_e( 'Save changes', 'my-wp-ab-testing' ); ?>" />
			</p>
		<?php endif; ?>

		<?php if ( ! $campaign_locked && ! $second_step ) : ?>
			<p>
				<input type="submit" class="button button-primary button-hero" value="<?php esc_html_e( 'Build your campaign', 'my-wp-ab-testing' ); ?>" />
			</p>
		<?php endif; ?>
		<?php if ( ! $campaign_locked && $second_step ) : ?>
			<p>
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Go to campaign settings', 'my-wp-ab-testing' ); ?></button>
			</p>
		<?php endif; ?>
			
		<?php if ( $campaign_locked ) : ?>
			<div class="reblexab_stats_wrapper">
				<?php
				$we_have_stats = true;
				if ( $abtesting_block_a_count === 0 || $abtesting_block_a_count === 0 ) {
					$we_have_stats = false;
				}
				?>
				<?php if ( $we_have_stats ) : ?>
					<h2><?php esc_html_e( 'A/B Testing live results', 'my-wp-ab-testing' ); ?></h2>
					<p class="reblexab_stats_winner">
						<?php
						$conversion_rate_block_a = round( $abtesting_block_a_conversion * 100 / $abtesting_block_a_count, 2 );
						$conversion_rate_block_b = round( $abtesting_block_b_conversion * 100 / $abtesting_block_b_count, 2 );
						if ( $conversion_rate_block_a > $conversion_rate_block_b ) {
							$winner = '<strong class="winner_block_a">' . esc_html__( 'Block A', 'my-wp-ab-testing' ) . '</strong>';
							$winner_result = '<strong class="winner_block_a">' . $conversion_rate_block_a . '%</strong>';
						} else {
							$winner = '<strong class="winner_block_b">' . esc_html__( 'Block B', 'my-wp-ab-testing' ) . '</strong>';
							$winner_result = '<strong class="winner_block_b">' . $conversion_rate_block_b . '%</strong>';
						}
						echo sprintf(
							esc_html__( '%1$s wins with a conversion rate of %2$s', 'my-wp-ab-testing' ),
							$winner,
							$winner_result
						);
						?>
					</p>
				<?php endif; ?>

				<?php if ( $we_have_stats ) : ?>
				<table class="wp-list-table widefat reblexab_stats_wrapper_charts">
					<tr>
						<td>
							<canvas
								id="reblexab_chart_displayed" 
								class="reblexab_chart" 
								data-title="Displays"
								data-a="<?php echo esc_attr( $abtesting_block_a_count ); ?>" 
								data-b="<?php echo esc_attr( $abtesting_block_b_count ); ?>"
							></canvas>
						</td>
						<td>
							<canvas
								id="reblexab_chart_converted" 
								class="reblexab_chart" 
								data-title="Conversions"
								data-a="<?php echo esc_attr( $abtesting_block_a_conversion ); ?>" 
								data-b="<?php echo esc_attr( $abtesting_block_b_conversion ); ?>"
							></canvas>
						</td>
					</tr>
				</table>
				<table class="wp-list-table widefat fixed striped reblexab_stats_wrapper_table">
					<tr>
						<th><?php esc_html_e( 'Block', 'my-wp-ab-testing' ); ?></th>
						<th><?php esc_html_e( 'Displayed', 'my-wp-ab-testing' ); ?></th>
						<th><?php esc_html_e( 'Converted', 'my-wp-ab-testing' ); ?></th>
						<th><?php esc_html_e( 'Conversion rate', 'my-wp-ab-testing' ); ?></th>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Block A', 'my-wp-ab-testing' ); ?></th>
						<td><?php echo $abtesting_block_a_count; ?></td>
						<td><?php echo $abtesting_block_a_conversion; ?></td>
						<td><strong><?php echo round( $abtesting_block_a_conversion * 100 / $abtesting_block_a_count, 2 ); ?>%</strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Block B', 'my-wp-ab-testing' ); ?></th>
						<td><?php echo $abtesting_block_b_count; ?></td>
						<td><?php echo $abtesting_block_b_conversion; ?></td>
						<td><strong><?php echo round( $abtesting_block_b_conversion * 100 / $abtesting_block_b_count, 2 ); ?>%</strong></td>
					</tr>
				</table>
				<?php else : ?>
					<p><?php esc_html_e( 'Stats: No figure available for now', 'my-wp-ab-testing' ); ?>
				<?php endif; ?>
		<?php endif; ?>

		<?php if ( $campaign_locked ) : ?>
			<h2><?php esc_html_e( 'Implement your campaign', 'my-wp-ab-testing' ); ?></h2>
			<p><?php esc_html_e( 'It’s pretty easy. Just edit the posts of your choice and either use our "A/B Testing" Gutenberg block or our shortcode.', 'my-wp-ab-testing' ); ?></p>
			<p><?php esc_html_e( 'Shortcode:', 'my-wp-ab-testing' ); ?> <code><?php echo '[my-wp-abtesting id="' . $object->ID . '"]'; ?></code></p>
		<?php endif; ?>

		<?php
	}
}


// Add reblexab CPT metaboxes
function reblexab_add_abtesting_metaboxes() {
	add_meta_box( 'abtesting-metaboxes', __( 'A/B Testing Settings' , 'my-wp-ab-testing' ), 'reblexab_abtesting_metaboxes', 'abtesting', 'normal', 'core', null);
}
add_action('add_meta_boxes', 'reblexab_add_abtesting_metaboxes');


// Save reblexab CPT metaboxes
function reblexab_save_abtesting_metaboxes( $post_id, $post, $update ) {
	if ( ! isset( $_POST['abtesting-nonce'] ) || ! wp_verify_nonce( $_POST['abtesting-nonce'], basename(__FILE__) ) ) {
		return $post_id;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	if ( 'abtesting' !== $post->post_type ) {
		return $post_id;
	}
	
	if ( wp_is_post_revision( $post_id ) ) {
		return $post_id;
	}

	if ( isset( $_POST['reblexab_block_a_selector'] ) ) {
		update_post_meta( $post_id, 'abtesting_block_a', intval( $_POST['reblexab_block_a_selector'] ) );		
	}
	if ( isset( $_POST['reblexab_block_b_selector'] ) ) {
		update_post_meta( $post_id, 'abtesting_block_b', intval( $_POST['reblexab_block_b_selector'] ) );		
	}
	if ( isset( $_POST['abtesting_distribution_a'] ) && isset( $_POST['abtesting_distribution_b'] ) ) {
		update_post_meta( $post_id, 'abtesting_distribution_a', intval( $_POST['abtesting_distribution_a'] ) );		
		update_post_meta( $post_id, 'abtesting_distribution_b', intval( $_POST['abtesting_distribution_b'] ) );		
	}
	if ( isset( $_POST['reblexab_block_a_target_selector'] ) ) {
		update_post_meta( $post_id, 'reblexab_block_a_target_selector', esc_html( $_POST['reblexab_block_a_target_selector'] ) );
	}
	if ( isset( $_POST['reblexab_block_b_target_selector'] ) ) {
		update_post_meta( $post_id, 'reblexab_block_b_target_selector', esc_html( $_POST['reblexab_block_b_target_selector'] ) );
	}

	if (
		get_post_meta( $post_id, 'abtesting_block_a', true ) && 
		get_post_meta( $post_id, 'abtesting_block_b', true ) &&
		! empty( get_the_title( $post_id ) )
	) {
		// Remove save_post action to avoid infinite loop
		remove_action( 'save_post_abtesting', 'reblexab_save_abtesting_metaboxes' );
		
		$existing_block_a = absint( get_post_meta( $post_id, 'abtesting_block_a', true ) );
		$existing_block_b = absint( get_post_meta( $post_id, 'abtesting_block_b', true ) );
		$existing_distribution_a = absint( get_post_meta( $post_id, 'abtesting_distribution_a', true ) );
		$existing_distribution_b = absint( get_post_meta( $post_id, 'abtesting_distribution_b', true ) );
		$post_content = '[reblexab id="' . $post_id . '"]';
		$update_block = array(
			'ID' => $post_id,
			'post_title' => get_the_title( $post_id ),
			'post_content' => $post_content,
			'post_status' => 'publish',
		);
		wp_update_post( $update_block, true );

		// Re-hook save_post action
		add_action( 'save_post_abtesting', 'reblexab_save_abtesting_metaboxes' );
	}
}
add_action( 'save_post_abtesting', 'reblexab_save_abtesting_metaboxes', 10, 3 );

function reblexab_shortcode( $atts ){
	extract( shortcode_atts(
		array(
			'id' => ''
	), $atts ) );
	$content = '';
	$reblexab_id = intval( $id );
	if ( ! empty( $reblexab_id ) && $reblexab_id > 0 ) {
		$existing_block_a = absint( get_post_meta( $reblexab_id, 'abtesting_block_a', true ) );
		$existing_block_b = absint( get_post_meta( $reblexab_id, 'abtesting_block_b', true ) );
		$existing_distribution_a = absint( get_post_meta( $reblexab_id, 'abtesting_distribution_a', true ) );
		$existing_distribution_b = absint( get_post_meta( $reblexab_id, 'abtesting_distribution_b', true ) );
		$reblexab_block_a_target_selector = esc_attr( get_post_meta( $reblexab_id, 'reblexab_block_a_target_selector', true ) );
		$reblexab_block_b_target_selector = esc_attr( get_post_meta( $reblexab_id, 'reblexab_block_b_target_selector', true ) );
		if ( $existing_block_a && $existing_block_b && $existing_distribution_a && $existing_distribution_b ) {
			$randomize = random_int( 0, 100 );
			if ( $randomize <= $existing_distribution_a ) {
				$before_content = '<div class="reblexab-wrapper" data-id="' . $reblexab_id . '" data-block="a" data-url="' . $reblexab_block_a_target_selector . '">';
				$after_content = '</div>';
				$content = $before_content . apply_filters( 'the_content', get_post_field( 'post_content', $existing_block_a ) ) . $after_content;
				$abtesting_block_a_count = intval( get_post_meta( $reblexab_id, 'abtesting_block_a_count', true ) );
				update_post_meta( $reblexab_id, 'abtesting_block_a_count', $abtesting_block_a_count + 1 );
				wp_enqueue_script( 'reblexab-stat' );
			} else {
				$before_content = '<div class="reblexab-wrapper" data-id="' . $reblexab_id . '" data-block="b" data-url="' . $reblexab_block_b_target_selector . '">';
				$after_content = '</div>';
				$content = $before_content . apply_filters( 'the_content', get_post_field( 'post_content', $existing_block_b ) ) . $after_content;
				$abtesting_block_b_count = intval( get_post_meta( $reblexab_id, 'abtesting_block_b_count', true ) );
				update_post_meta( $reblexab_id, 'abtesting_block_b_count', $abtesting_block_b_count + 1 );
				wp_enqueue_script( 'reblexab-stat' );
			}
		}
	}
	
	return $content;
}
add_shortcode( 'my-wp-abtesting', 'reblexab_shortcode' );

// AJAX
function reblexab_ajax_update_stat() {
	$button_target  = esc_html( filter_input( INPUT_POST, 'target', FILTER_SANITIZE_STRING ) );
	$reblexab_id    = esc_html( filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT ) );
	$reblexab_block = esc_html( filter_input( INPUT_POST, 'block', FILTER_SANITIZE_STRING ) );
	$reblexab_url   = esc_html( filter_input( INPUT_POST, 'url', FILTER_SANITIZE_STRING ) );

	if ( ! empty( $button_target ) && ! empty( $reblexab_id ) && ! empty( $reblexab_block ) && ! empty( $reblexab_url ) ) {
		if ( 'a' === $reblexab_block ) {
			$count = absint( get_post_meta( $reblexab_id, 'reblexab_block_conversion_a', true ) );
			update_post_meta( $reblexab_id, 'reblexab_block_conversion_a', $count + 1 );
		} else {
			$count = absint( get_post_meta( $reblexab_id, 'reblexab_block_conversion_b', true ) );
			update_post_meta( $reblexab_id, 'reblexab_block_conversion_b', $count + 1 );
		}
		wp_send_json_success( 'success' );
	} else {
		wp_send_json_success( 'error' );
	}
	die();
}

add_action( 'wp_ajax_reblexab_stat', 'reblexab_ajax_update_stat' );
add_action( 'wp_ajax_nopriv_reblexab_stat', 'reblexab_ajax_update_stat' );

require_once plugin_dir_path( __FILE__ ) . 'gut.php';