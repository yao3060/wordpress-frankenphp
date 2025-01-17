<?php

trait MuiPageBuilderSettingsOptionTrait {

	const OPTIONS_ID = 'acf_mui_page_builder_settings';

	/**
	 * @var array{editor_url: string}
	 */
	private array $options;

	public function getOptions() {
		if  (!empty($this->options)) {
			return $this->options;
		}
		$options = get_option(self::OPTIONS_ID);
		if (empty($options)) {
			$options =  [
				'editor_url' => '',
			];
		}
		$this->options = $options;
		return $this->options;
	}
}