<?php

class MuiPageBuilderRevisions {

    const BLOCKS_META_KEY = 'blocks';

    public $revision_prefix = "_" . self::BLOCKS_META_KEY . ':revisions:';

    public function __construct() {
        add_action('acf/save_post', $this->add_revisions_on_save(...), 5);
        add_action('add_meta_boxes', [$this, 'add_revisions_meta_box']);
        add_action('admin_footer', [$this, 'add_dialog_html']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_delete_mui_revision', [$this, 'handle_delete_revision']);
    }

    function add_revisions_on_save($post_id) {
        $prev_blocks = get_field( self::BLOCKS_META_KEY, $post_id );
            if($prev_blocks) {
                $user = wp_get_current_user();
                update_post_meta($post_id, "{$this->revision_prefix}{$user->user_login}:{$user->ID}:" . time(), $prev_blocks);
            }
    }

    public function handle_delete_revision() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $post_id = intval($_POST['post_id']);
        $meta_key = sanitize_text_field($_POST['meta_key']);

        if (!$post_id || !$meta_key) {
            wp_send_json_error('Invalid parameters');
        }

        // Verify the meta key starts with our prefix
        if (strpos($meta_key, $this->revision_prefix) !== 0) {
            wp_send_json_error('Invalid revision key');
        }

        $deleted = delete_post_meta($post_id, $meta_key);
        if ($deleted) {
            wp_send_json_success('Revision deleted successfully');
        } else {
            wp_send_json_error('Failed to delete revision');
        }
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-dialog');

        // Add our custom script
        wp_add_inline_script('jquery-ui-dialog', '
            jQuery(document).ready(function($) {
                $("#mui-revision-dialog").dialog({
                    autoOpen: false,
                    modal: true,
                    width: "80%",
                    height: 500,
                    title: "Revision Content"
                });

                $(".mui-revision-item").on("click", function(e) {
                    e.preventDefault();
                    var content = e.currentTarget.dataset.content;
                    console.log(e.currentTarget, content);

                    $("#mui-revision-content").val(content);
                    $("#mui-revision-dialog").dialog("open");
                });

                $(".mui-revision-delete").on("click", function(e) {
                    e.preventDefault();
                    var $row = $(this).closest("tr");
                    var metaKey = $(this).data("meta-key");

                    if (confirm("Are you sure you want to delete this revision?")) {
                        $.ajax({
                            url: ajaxurl,
                            type: "POST",
                            data: {
                                action: "delete_mui_revision",
                                post_id: $("#post_ID").val(),
                                meta_key: metaKey
                            },
                            success: function(response) {
                                if (response.success) {
                                    $row.fadeOut(400, function() {
                                        $(this).remove();
                                        // If no more rows, show "No revisions" message
                                        if ($(".widefat tr").length === 0) {
                                            $(".widefat").replaceWith("<p>No revisions found.</p>");
                                        }
                                    });
                                } else {
                                    alert("Error: " + response.data);
                                }
                            },
                            error: function() {
                                alert("Failed to delete revision. Please try again.");
                            }
                        });
                    }
                });
            });
        ');
    }

    public function add_dialog_html() {
        ?>
        <div id="mui-revision-dialog" style="display:none;">
            <textarea id="mui-revision-content" style="width: 100%;height: 100%;"></textarea>
        </div>
        <?php
    }

    public function add_revisions_meta_box() {
        add_meta_box(
            'mui-page-builder-revisions',
            'Page Builder Revisions',
            [$this, 'render_revisions_meta_box'],
            'page',
            'advanced',
            'default'
        );
    }

    public function render_revisions_meta_box($post) {
        global $wpdb;


        $revisions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_key, meta_value
                FROM {$wpdb->postmeta}
                WHERE post_id = %d
                AND meta_key LIKE %s
                ORDER BY meta_key DESC",
                $post->ID,
                $this->revision_prefix . '%'
            )
        );

        if (empty($revisions)) {
            echo '<p>No revisions found.</p>';
            return;
        }

        echo '<table class="widefat fixed striped">';
        foreach ($revisions as $revision) {
            $parsed_key = explode(':', $revision->meta_key);
            $display_name = $parsed_key[2] ?? "display_name";
            $timestamp = last($parsed_key);
            $date = wp_date('Y-m-d H:i:s', $timestamp);
            echo sprintf(
                '<tr>
                    <td>
                        <span class="display-name">%s</span>
                    </td>
                    <td width="200px"><span class="date">%s</span></td>
                    <td width="200px" >
                        <a href="#" class="button mui-revision-item" data-content="%s" >View</a>
                        <a href="#" class="button mui-revision-delete" data-meta-key="%s">Delete</a>
                    </td>
                </tr>',
                esc_html($display_name),
                esc_html($date),
                esc_attr($revision->meta_value),
                esc_attr($revision->meta_key)
            );
        }
        echo '</table>';
    }
}
