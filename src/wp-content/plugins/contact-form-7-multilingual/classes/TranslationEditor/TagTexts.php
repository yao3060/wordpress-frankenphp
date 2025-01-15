<?php

namespace WPML\CF7\TranslationEditor;

use WPML\CF7\Constants;
use WPML\FP\Fns;
use WPML\FP\Lens;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks;
use function WPML\FP\compose;
use function WPML\FP\spreadArgs;

class TagTexts implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	const TAG_PREFIX = 'wpmlcf7-';

	public function add_hooks() {
		Hooks::onFilter( 'wpml_tm_translation_job_data', PHP_INT_MAX, 2 )
			->then( spreadArgs( [ self::class, 'preProcess' ] ) );
		Hooks::onFilter( 'wpml_encode_custom_field', 10, 2 )
			->then( spreadArgs( [ self::class, 'postProcess' ] ) );
	}

	/**
	 * @param array                  $package
	 * @param \WP_Post|\WPML_Package $post
	 *
	 * @return array
	 */
	public static function preProcess( $package, $post ) {
		if ( Constants::POST_TYPE !== Obj::prop( 'post_type', $post ) ) {
			return $package;
		}

		$formContentLens = compose(
			Obj::lensPath( [ 'contents', 'field-_form-0', 'data' ] ),
			Lens::isoBase64Decoded()
		);

		$process = function( string $content ):string {
			$index = 0;

			return preg_replace_callback(
				'#\[(?<tagName>[a-z-]+)(?<beforeTexts>\*?.*?)(?<texts>"[^\]]*)\]#',
				function( array $matches ) use ( &$index ):string {
					$rawTagName = $matches['tagName'];

					if ( 'acceptance' === $rawTagName ) {
						return $matches[0];
					}

					$index++;
					$tagName     = self::TAG_PREFIX . "$index-" . $rawTagName;
					$beforeTexts = $matches['beforeTexts'];

					$texts = wpml_collect( preg_split( '#\s+(?=(?:[^"]*"[^"]*")*[^"]*$)#', $matches['texts'] ) )
						->map( Fns::unary( Str::trim( '"' ) ) )
						->map( self::encodeDoubleQuotes() )
						->implode( PHP_EOL );

					return '[' . $tagName . rtrim( $beforeTexts ) . ']' . PHP_EOL . $texts . PHP_EOL . '[/' . $tagName . ']';
				},
				$content
			);
		};

		return (array) Obj::over( $formContentLens, $process, $package );
	}

	/**
	 * @param mixed  $value
	 * @param string $name
	 *
	 * @return mixed
	 */
	public static function postProcess( $value, $name ) {
		if ( '_form' !== $name || ! is_string( $value ) ) {
			return $value;
		}

		/** @var callable(string):string $removeTagPrefixAndIndex */
		$removeTagPrefixAndIndex = Str::pregReplace( '#^' . self::TAG_PREFIX . '\d+-#', '' );

		return preg_replace_callback(
			'#\[(?<tagName>' . self::TAG_PREFIX . '\d+-[a-z-]+)(?<beforeTexts>[^\]]*)\](?<texts>[^\[]*)\[\/\1\]#',
			function( $matches ) use ( $removeTagPrefixAndIndex ) {
				$tagName     = $removeTagPrefixAndIndex( $matches['tagName'] );
				$beforeTexts = $matches['beforeTexts'];

				$texts = wpml_collect( explode( PHP_EOL, trim( $matches['texts'] ) ) )
					->map( self::encodeDoubleQuotes() )
					->map( Str::wrap( '"', '"' ) )
					->implode( ' ' );

				return '[' . $tagName . $beforeTexts . ' ' . $texts . ']';
			},
			$value
		);
	}

	/**
	 * @return callable(string):string
	 */
	private static function encodeDoubleQuotes() {
		return Str::pregReplace( '/"/', '&quot;' );
	}
}
