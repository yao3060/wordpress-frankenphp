<?php

require_once __DIR__ . '/utils.php';

class MeiliSearch
{
    private $meilisearch_options;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'meilisearch_add_plugin_page'));
        add_action('admin_init', array($this, 'meilisearch_page_init'));
    }

    function getClient()
    {
        $this->meilisearch_options = get_option('meilisearch_option_name');
        $meilisearch_url = $this->meilisearch_options['meilisearch_url_0'];
        $meilisearch_key = $this->meilisearch_options['meilisearch_private_key_1'];
        if ($meilisearch_url && $meilisearch_key) {
            $client = new \Meilisearch\Client($meilisearch_url, $meilisearch_key);
            return $client;
        }
        return null;
    }

    public function meilisearch_add_plugin_page()
    {
        add_menu_page(
            'MeiliSearch', // page_title
            'MeiliSearch', // menu_title
            'manage_options', // capability
            'meilisearch', // menu_slug
            array($this, 'meilisearch_create_admin_page'), // function
            'dashicons-admin-generic', // icon_url
            // position (int)
        );

        add_submenu_page(
            'meilisearch', // parent_slug
            'MeiliSearch Settings', // page_title
            'Index content', // menu_title
            'manage_options', // capability
            'meilisearch_index_content', // menu_slug
            array($this, 'meilisearch_create_admin_page_index_content'), // function
        );
    }

    public function meilisearch_create_admin_page()
    {

        $this->meilisearch_options = get_option('meilisearch_option_name');
?>

        <div class="wrap">
            <h2>Meilisearch</h2>
            <p>Set up Meilisearch for your Wordpress</p>

            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('meilisearch_option_group');
                do_settings_sections('meilisearch-admin');
                submit_button();
                ?>
            </form>

            <?php
            $client = $this->getClient();
            if ($client) {
                echo '<p><strong>Status:</strong> ' . $client->health()['status'] . '</p>';
                echo $this->meilisearch_options['meilisearch_index_names'] ?? "";
            }
            ?>

        </div>
    <?php }

    public function meilisearch_create_admin_page_index_content()
    {

        if (isset($_GET['indexAll']) && $_GET['indexAll'] == 1 && $_GET['postType']) {
            index_all_posts($_GET['postType']);
        }
        if (isset($_GET['deleteIndex']) && $_GET['deleteIndex'] == 1 && $_GET['postType']) {
            delete_index($_GET['postType']);
        }

        $this->meilisearch_options = get_option('meilisearch_option_name');

        $client = $this->getClient();
    ?>

        <div class="wrap">
            <h2>Index your existing content to Meilisearch</h2>
            <p>In this page you can index all of your currently existing content in your Wordpress site</p>
            <?php settings_errors(); ?>

            <style>
                .indexes-flex {
                    display: flex;
                    gap: 20px;

                    .single-index {
                        width: 250px;
                        border: 2px solid #ccc;
                        padding: 20px;
                        display: flex;
                        flex-direction: column;
                        justify-content: space-between;
                        gap: 20px;
                        box-shadow: 0 0 2px #efefef;
                    }
                }
            </style>

            <div class="indexes-flex">

                <?php foreach ($client->stats()['indexes'] as $key => $index) : ?>
                    <div class="single-index">
                        <span>Indexed <?php echo $key; ?> documents: <?php echo $index['numberOfDocuments']; ?></span>

                        <form method="post" action="admin.php?page=meilisearch_index_content&deleteIndex=1&postType=<?php echo $key; ?>">
                            <span id="index-all-button">
                                <input id="index-all" type="submit" value="Delete Index" class="button">
                            </span>
                        </form>
                        <form method="post" action="admin.php?page=meilisearch_index_content&indexAll=1&postType=<?php echo $key; ?>">
                            <span id="delete-index-button">
                                <input id="delete-index" type="submit" value="Index my site content" class="button">
                            </span>
                        </form>
                    </div>
                <?php endforeach; ?>


            </div>
        </div>
<?php }

    public function meilisearch_page_init()
    {

        register_setting(
            'meilisearch_option_group', // option_group
            'meilisearch_option_name', // option_name
            array($this, 'meilisearch_sanitize') // sanitize_callback
        );

        add_settings_section(
            'meilisearch_setting_section', // id
            'Settings', // title
            array($this, 'meilisearch_section_info'), // callback
            'meilisearch-admin' // page
        );

        add_settings_field(
            'meilisearch_url_0', // id
            'MeiliSearch URL', // title
            array($this, 'meilisearch_url_0_callback'), // callback
            'meilisearch-admin', // page
            'meilisearch_setting_section' // section
        );

        add_settings_field(
            'meilisearch_search_url_4', // id
            'MeiliSearch Search URL (if different)', // title
            array($this, 'meilisearch_search_url_4_callback'), // callback
            'meilisearch-admin', // page
            'meilisearch_setting_section' // section
        );

        add_settings_field(
            'meilisearch_private_key_1', // id
            'MeiliSearch Private Key', // title
            array($this, 'meilisearch_private_key_1_callback'), // callback
            'meilisearch-admin', // page
            'meilisearch_setting_section' // section
        );

        add_settings_field(
            'meilisearch_public_key_2', // id
            'MeiliSearch Public Key', // title
            array($this, 'meilisearch_public_key_2_callback'), // callback
            'meilisearch-admin', // page
            'meilisearch_setting_section' // section
        );

        add_settings_field(
            'meilisearch_index_names', // id
            'MeiliSearch Index Name', // title
            array($this, 'meilisearch_index_name_callback'), // callback
            'meilisearch-admin', // page
            'meilisearch_setting_section' // section
        );
    }

    public function meilisearch_sanitize($input)
    {
        $sanitary_values = array();
        if (isset($input['meilisearch_url_0'])) {
            $sanitary_values['meilisearch_url_0'] = sanitize_text_field($input['meilisearch_url_0']);
        }

        if (isset($input['meilisearch_search_url_4'])) {
            $sanitary_values['meilisearch_search_url_4'] = sanitize_text_field($input['meilisearch_search_url_4']);
        }

        if (isset($input['meilisearch_private_key_1'])) {
            $sanitary_values['meilisearch_private_key_1'] = sanitize_text_field($input['meilisearch_private_key_1']);
        }

        if (isset($input['meilisearch_public_key_2'])) {
            $sanitary_values['meilisearch_public_key_2'] = sanitize_text_field($input['meilisearch_public_key_2']);
        }

        if (isset($input['meilisearch_index_names'])) {
            $sanitary_values['meilisearch_index_names'] = sanitize_text_field($input['meilisearch_index_names']);

            $indexes = $sanitary_values['meilisearch_index_names'] ? explode(',', $sanitary_values['meilisearch_index_names']) : null;
            if ($indexes) {
                foreach ($indexes as $index) {
                    $this->getClient()->createIndex(trim($index));
                }
            }
        }

        return $sanitary_values;
    }

    public function meilisearch_section_info() {}

    public function meilisearch_url_0_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="meilisearch_option_name[meilisearch_url_0]" id="meilisearch_url_0" value="%s">',
            isset($this->meilisearch_options['meilisearch_url_0']) ? esc_attr($this->meilisearch_options['meilisearch_url_0']) : ''
        );
    }

    public function meilisearch_search_url_4_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="meilisearch_option_name[meilisearch_search_url_4]" id="meilisearch_search_url_4" value="%s">',
            isset($this->meilisearch_options['meilisearch_search_url_4']) ? esc_attr($this->meilisearch_options['meilisearch_search_url_4']) : ''
        );
    }

    public function meilisearch_private_key_1_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="meilisearch_option_name[meilisearch_private_key_1]" id="meilisearch_private_key_1" value="%s">',
            isset($this->meilisearch_options['meilisearch_private_key_1']) ? esc_attr($this->meilisearch_options['meilisearch_private_key_1']) : ''
        );
    }

    public function meilisearch_public_key_2_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="meilisearch_option_name[meilisearch_public_key_2]" id="meilisearch_public_key_2" value="%s">',
            isset($this->meilisearch_options['meilisearch_public_key_2']) ? esc_attr($this->meilisearch_options['meilisearch_public_key_2']) : ''
        );
    }

    public function meilisearch_index_name_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="meilisearch_option_name[meilisearch_index_names]" id="meilisearch_index_names" value="%s">',
            isset($this->meilisearch_options['meilisearch_index_names']) ? esc_attr($this->meilisearch_options['meilisearch_index_names']) : ''
        );
    }
}
