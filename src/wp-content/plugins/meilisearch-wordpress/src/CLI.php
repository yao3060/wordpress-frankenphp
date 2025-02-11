<?php

class Meilisearch_Command {

    /**
     * Prints a greeting.
     *
     * ## OPTIONS
     *
     * <post_type>
     * : The `post_type` is the name of the post type.
     *
     *
     * ## EXAMPLES
     *
     *     wp meili sync all
     *
     * @when after_wp_load
     */
    function sync( $args ) {

        list( $post_type ) = $args;

        WP_CLI::log( "Hello, $post_type!" );

        $post_types = get_post_types(['public'   => true, 'show_ui' => true]);

        if($post_type === 'attachment' || !$post_types[$post_type]) {
            WP_CLI::error("Cannot sync `$post_type`");
        } else {
            index_all_posts( trim($post_type) );
        }

        WP_CLI::success( "Synced `$post_type`" );
    }
}

// Only register the command if WP-CLI is available
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'meili', 'Meilisearch_Command' );
}
