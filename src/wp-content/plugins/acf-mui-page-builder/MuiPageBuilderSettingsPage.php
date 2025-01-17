<?php

class MuiPageBuilderSettingsPage
{
    use MuiPageBuilderSettingsOptionTrait;

    const PAGE_ID = 'acf-mui-page-builder-settings';
    const OPTIONS_GROUP = 'acf_mui_page_builder';


    public function __construct()
    {
        add_action('admin_init', $this->pageInit(...));
        add_action('admin_menu', $this->addOptionsPage(...));
    }

    public function addOptionsPage(): void
    {
        add_options_page(
            __('MUI Page builder'),
            __('MUI Page builder'),
            'manage_options',
            'acf-mui-page-builder-settings',
            $this->renderOptionsPage(...),
            110,
        );
    }

    public function renderOptionsPage(): void
    {
?>
        <form action="options.php" method="post">
            <?php
            settings_fields(self::OPTIONS_GROUP);
            do_settings_sections(self::PAGE_ID);
            submit_button();
            ?>
        </form>
<?php
    }

    public function pageInit(): void
    {
        register_setting(
            self::OPTIONS_GROUP,
            self::OPTIONS_ID,
        );
        $settings_section_id = 'acf_mui_page_builder_settings';
        add_settings_section(
            $settings_section_id,
            __('Settings'),
            function () {
            },
            self::PAGE_ID,
        );
        $languages = apply_filters('wpml_active_languages', NULL);
        if (is_array($languages)) {
            foreach ($languages as $language) {
                add_settings_field(
                    'editor_url_' . $language['code'],
                    __('Editor URL - ') . $language['native_name'],
                    function () use ($language) {
                        $id = 'editor_url_' . $language['code'];
                        printf(
                            '<input class="is-wpml large-text code" type="url" name="%s[%s]" value="%s" />',
                            self::OPTIONS_ID,
                            $id,
                            $this->getOptions()[$id] ?? '',
                        );
                    },
                    self::PAGE_ID,
                    $settings_section_id,
                );
            }
        } else {
            add_settings_field(
                'editor_url',
                __('Editor URL'),
                function () {
                    printf(
                        '<input class="large-text code" type="url" name="%s[editor_url]" value="%s" />',
                        self::OPTIONS_ID,
                        $this->getOptions()['editor_url'],
                    );
                },
                self::PAGE_ID,
                $settings_section_id,
            );
        }
    }
}
