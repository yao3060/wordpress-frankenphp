<?php

if (!defined('ABSPATH')) {
    exit();
}

class AcfFieldMuiPageBuilder extends \acf_field
{
    use MuiPageBuilderSettingsOptionTrait;

    /**
     * Controls field type visibilty in REST requests.
     *
     * @var bool
     */
    public $show_in_rest = true;


    private JWTAuth\Auth $jwt;


    /**
     * Constructor.
     */
    public function __construct()
    {
        /**
         * Field type reference used in PHP and JS code.
         *
         * No spaces. Underscores allowed.
         */
        $this->name = 'pageBuilder';

        /**
         * Field type label.
         *
         * For public-facing UI. May contain spaces.
         */
        $this->label = __('Page builder');

        /**
         * The category the field appears within in the field type picker.
         */
        $this->category = 'content'; // basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME

        /**
         * Defaults for your custom user-facing settings for this field type.
         */
        $this->defaults = array();

        /**
         * Strings used in JavaScript code.
         *
         * Allows JS strings to be translated in PHP and loaded in JS via:
         *
         * ```js
         * const errorMessage = acf._e("FIELD_NAME", "error");
         * ```
         */
        $this->l10n = array( // 'error'	=> __( 'Error! Please enter a higher value', 'TEXTDOMAIN' ),
        );

        parent::__construct();
    }

    public function initialize()
    {
        if (is_admin()) {
            $this->jwt = new JWTAuth\Auth('acf-mui-page-builder', 1);
            $this->add_action('admin_enqueue_scripts', function () {
                $path = __DIR__ . '/dist/main.js';
                $src = plugins_url('/acf-mui-page-builder/dist/main.js');
                $handle = 'acf-mui-page-builder';
                wp_register_script(
                    $handle,
                    $src,
                    ['acf-input'],
                    md5_file($path),
                    true,
                );
                wp_enqueue_script($handle);
            });
        }
    }

    public function getCurrentUserJWTToken(): string
    {
        $get_current_user = wp_get_current_user();
        add_filter('jwt_auth_iss', fn() => site_url(), 10);
        add_filter('jwt_auth_expire', function ($expire, $issued_at) {
            return $issued_at + WEEK_IN_SECONDS;
        }, 10, 2);
        $token = $this->jwt->generate_token($get_current_user);

        if (empty($token) || $token instanceof WP_Error) {
            return '';
        }
        return $token;
    }

    /**
     * Settings to display when users configure a field of this type.
     *
     * These settings appear on the ACF “Edit Field Group” admin page when
     * setting up the field.
     *
     * @param array $field
     *
     * @return void
     */
    public function render_field_settings($field): void
    {
        /*
         * Repeat for each setting you wish to display for this field type.
         */
        // acf_render_field_setting(
        //     $field,
        //     array(
        //         'label'			=> __( 'Font Size','TEXTDOMAIN' ),
        //         'instructions'	=> __( 'Customise the input font size','TEXTDOMAIN' ),
        //         'type'			=> 'number',
        //         'name'			=> 'font_size',
        //         'append'		=> 'px',
        //     )
        // );

        // To render field settings on other tabs in ACF 6.0+:
        // https://www.advancedcustomfields.com/resources/adding-custom-settings-fields/#moving-field-setting
    }

    /**
     * HTML content to show when a publisher edits the field on the edit screen.
     *
     * @param array $field The field settings and values.
     *
     * @return void
     */
    public function render_field($field): void
    {
        $options = $this->getOptions();
        $editor_url = $options['editor_url'] ?? '';
        if (function_exists('wpml_get_current_language')) {
            $current_language = wpml_get_current_language();
            $editor_url = $options['editor_url_' . $current_language] ?? $editor_url;
        }
        $token = $this->getCurrentUserJWTToken();

        if (!empty($editor_url)) {

?>
            <div class='acf-field-mui-page-builder' style="display: flex;justify-content: end;margin-top: -32px;">
                <button type="button" class="button button-large"
                    onclick="document.getElementById(`wordpress-preview-iframe`).requestFullscreen()">Fullscreen</button>
                <script type="application/json" class="settings">
                    <?php
                    echo json_encode([
                        'editor_url' => $editor_url,
                        'rest_api_url' => rest_url(),
                        'jwt_token' => $token,
                    ])
                    ?>
                </script>
                <input type="hidden" name="<?php echo esc_attr($field['name']) ?>" value="<?php echo esc_attr($field['value']) ?>" />
            </div>
<?php
        } else {
            printf('Editor URL is not set.');
        }
    }
}
