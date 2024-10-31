<?php

add_action( 'rest_api_init', 'add_meta_abtesting_to_rest_api' );

function add_meta_abtesting_to_rest_api() {
    register_rest_field( 'abtesting', 'meta_abtesting', array(
            'get_callback'    => function($object){
                $post_id = $object['id'];
                $existing_block_a = absint( get_post_meta( $post_id, 'abtesting_block_a', true ) );
                $existing_block_b = absint( get_post_meta( $post_id, 'abtesting_block_b', true ) );


                $abtesting_block_a_count = 0;
                $abtesting_block_b_count = 0;
                if ( get_post_meta( $post_id, 'abtesting_block_a_count', true ) ) {
                    $abtesting_block_a_count = get_post_meta( $post_id, 'abtesting_block_a_count', true );
                }

                if ( get_post_meta( $post_id, 'abtesting_block_b_count', true ) ) {
                    $abtesting_block_b_count = get_post_meta( $post_id, 'abtesting_block_b_count', true );
                }
                $abtesting_block_a_conversion = 0;
                $abtesting_block_b_conversion = 0;
                if ( get_post_meta( $post_id, 'reblexab_block_conversion_a', true ) ) {
                    $abtesting_block_a_conversion = absint( get_post_meta( $post_id, 'reblexab_block_conversion_a', true ) );
                }
                if ( get_post_meta( $post_id, 'reblexab_block_conversion_b', true ) ) {
                    $abtesting_block_b_conversion = absint( get_post_meta( $post_id, 'reblexab_block_conversion_b', true ) );
                }


                $pourcentage_a = round( $abtesting_block_a_conversion * 100 / $abtesting_block_a_count, 2 ) . '%';
                $pourcentage_b = round( $abtesting_block_a_conversion * 100 / $abtesting_block_b_count, 2 ) . '%';


                return array(
                    'abtesting_block_a' => array(
                        'link' => admin_url() . 'post.php?post='.$existing_block_a.'&action=edit',
                        'title' => get_the_title($existing_block_a),
                        'content' => get_the_content(null, false, $existing_block_a),
                        'count' => $abtesting_block_a_count,
                        'conversion' => $abtesting_block_a_conversion,
                        'percentage' => $pourcentage_a
                    ),
                    'abtesting_block_b' => array(
                        'link' => admin_url() . 'post.php?post='.$existing_block_b.'&action=edit',
                        'title' => get_the_title($existing_block_b),
                        'content' => get_the_content(null, false, $existing_block_b),
                        'count' => $abtesting_block_b_count,
                        'conversion' => $abtesting_block_b_conversion,
                        'percentage' => $pourcentage_b
                    )
                );
            },
            'schema'          => null,
        )
    );
}



add_action('enqueue_block_editor_assets', 'reblexab_block_enqueue', 20);

function reblexab_block_enqueue(){

    wp_register_script( 'reblexab-chart-bo',
        plugins_url( '/vendor/chart/chart.min.js', __FILE__ )
    );
    wp_enqueue_script( 'reblexab-chart-bo' );

    wp_enqueue_script(
        'reblexab-block-script', // Unique handle.
        plugins_url() . '/abtesting/js/block.js',
        array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor', 'wp-plugins', 'wp-edit-post', 'jquery', 'reblexab-chart-bo')
    );

    wp_localize_script('reblexab-block-script', 'REST_API', array(
        'url' => get_rest_url()

    ));

}
/*
add_action('rest_api_init', function() {

    // Surface all Gutenberg blocks in the WordPress REST API
    $post_types = get_post_types_by_support( [ 'editor' ] );

    foreach ( $post_types as $post_type ) {
        //if ( gutenberg_can_edit_post_type( $post_type ) ) {

            register_rest_field( $post_type, 'blocks', [
                'get_callback' => function ( array $post ) {
                    return gutenberg_parse_blocks( $post['content']['raw'] );
                }
            ] );

       // }
    }

}); */