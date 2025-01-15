<?php

namespace WPML\CF7\TranslationEditor;

use WPML\FP\Str;
use WPML\FP\Obj;

class JobFilter implements \IWPML_Backend_Action, \IWPML_Frontend_Action {
	const CONTACT_FORM_7_TOP_LEVEL_GROUP_ID = 'contact-form-7';

	const CONTACT_FORM_7_TOP_LEVEL_GROUP_TITLE = 'Contact Form 7';

	const JOB_ORIGINAL_POST_TYPE_CONTACT_FORM_7 = 'post_wpcf7_contact_form';

	/**
	 * @var array<string,string>
	 */
	const MESSAGES_LABELS = [
		'mail_sent_ok'             => "Sender's message was sent successfully",
		'mail_sent_ng'             => "Sender's message failed to send",
		'validation_error'         => 'Validation errors occurred',
		'spam'                     => 'Submission was referred to as spam',
		'accept_terms'             => 'There are terms that the sender must accept',
		'invalid_required'         => 'There is a field that the sender must fill in',
		'invalid_too_long'         => 'There is a field with input that is longer than the maximum allowed length',
		'invalid_too_short'        => 'There is a field with input that is shorter than the minimum allowed length',
		'upload_failed'            => 'Uploading a file fails for any reason',
		'upload_file_type_invalid' => 'Uploaded file is not allowed for file type',
		'upload_file_too_large'    => 'Uploaded file is too large',
		'upload_failed_php_error'  => 'Uploading a file fails for PHP error',
		'invalid_date'             => 'Date format that the sender entered is invalid',
		'date_too_early'           => 'Date is earlier than minimum limit',
		'date_too_late'            => 'Date is later than maximum limit',
		'invalid_number'           => 'Number format that the sender entered is invalid',
		'number_too_small'         => 'Number is smaller than minimum limit',
		'number_too_large'         => 'Number is larger than maximum limit',
		'quiz_answer_not_correct'  => 'Sender does not enter the correct answer to the quiz',
		'invalid_email'            => 'Email address that the sender entered is invalid',
		'invalid_url'              => 'URL that the sender entered is invalid',
		'invalid_tel'              => 'Telephone number that the sender entered is invalid',
	];

	/**
	 * @var array<string,string>
	 */
	const REPLACE_LABELS = [
		'Body' => 'Message',
	];

	const TYPE_FIELD = 'field';

	public function add_hooks() {
		add_filter( 'wpml_tm_adjust_translation_fields', [ $this, 'addTitleAndGroupInfo' ], 10, 2 );
		add_filter( 'wpml_tm_adjust_translation_job', [ $this, 'reorderFields' ], 10, 2 );
	}

	private function isContactForm7Job( \stdClass $job ): bool {
		return self::JOB_ORIGINAL_POST_TYPE_CONTACT_FORM_7 === Obj::prop( 'original_post_type', $job );
	}

	public function addTitleAndGroupInfo( array $fields, \stdClass $job ): array {
		if ( ! $this->isContactForm7Job( $job ) ) {
			return $fields;
		}

		foreach ( $fields as $key => $field ) {
			$fields[ $key ] = $this->processField( $field );
		}

		return $fields;
	}

	public function reorderFields( array $fields, \stdClass $job ): array {
		if ( ! $this->isContactForm7Job( $job ) ) {
			return $fields;
		}

		$fields = $this->prepareFieldsToSort( $fields );

		$title = [];
		$forms = [];
		$mails = [];
		$left  = [];

		foreach ( $fields as $field ) {
			if ( 'title' === $field['__sort_helper']['group_lv1'] ) {
				$title[] = $field;
				continue;
			}

			if ( 'form' === $field['__sort_helper']['group_lv2'] ) {
				$forms[] = $field;
				continue;
			}

			if ( 'mail' === $field['__sort_helper']['group_lv2'] ) {
				$mails[] = $field;
				continue;
			}

			$left[] = $field;
		}

		$mailSorter = function ( array $rowA, array $rowB ): int {
			$a = $rowA['__sort_helper']['mail'];
			$b = $rowB['__sort_helper']['mail'];

			if ( $a['id'] === $b['id'] ) {

				if ( $a['is_sub_field_is_subject'] ) {
					return - 1;
				}

				if ( $b['is_sub_field_is_subject'] ) {
					return + 1;
				}
			}

			return $a['id'] <=> $b['id'];
		};

		usort( $mails, $mailSorter );

		$fields = array_merge( $title, $forms, $mails, $left );

		return \WPML\FP\Fns::map( \WPML\FP\Obj::without( '__sort_helper' ), $fields );
	}


	private function processField( array $field ): array {
		$fieldTitle = (string) Obj::prop( 'title', $field );
		$parts      = explode( '-', $fieldTitle );

		$type        = $parts[0] ?? null;
		$sectionName = $parts[1] ?? null;
		$sectionId   = (int) ( $parts[2] ?? 0 );
		$fieldName   = $parts[3] ?? null;

		if ( strtolower( $type ) === self::TYPE_FIELD ) {
			if ( Str::startsWith( '_mail', $sectionName ) ) {
				$groupName  = $this->convertSlugToHuman( $sectionName );
				$labelTitle = $this->convertSlugToHuman( $fieldName );
			} elseif ( Str::startsWith( '_messages', $sectionName ) ) {
				$groupId    = $this->createGroupId( [ $sectionName, $sectionId, $fieldName ] );
				$groupName  = $this->convertSlugToHumanMessages( $fieldName );
				$labelTitle = 'Text';
			} else {
				$groupName  = 'Form Elements';
				$labelTitle = '';
			}
		}

		$groupId    = $groupId ?? $this->createGroupId( [ $sectionName, $sectionId ] );
		$groupName  = $groupName ?? $this->convertSlugToHuman( $sectionName ?? $type );
		$labelTitle = $labelTitle ?? $this->convertSlugToHuman( end( $parts ) );

		return $this->handleField( $field, $labelTitle, $groupId, $groupName );
	}

	private function createGroupId( array $args ): string {
		array_unshift( $args, 'group_id' );

		$args = array_filter(
			$args,
			function ( $arg ) {
				return null !== $arg;
			}
		);

		return join( '-', $args );
	}

	private function convertSlugToHuman( string $slug ): string {
		$label = apply_filters( 'wpml_labelize_string', $slug, 'TranslationEditorLabel' );

		return trim( $label );
	}

	private function convertSlugToHumanMessages( string $slug ): string {
		return 'Messages: ' . ( self::MESSAGES_LABELS[ $slug ] ?? $this->convertSlugToHuman( $slug ) );
	}

	private function handleField( array $field, string $title, string $groupId, string $groupName ): array {
		$field['title'] = self::REPLACE_LABELS[ $title ] ?? $title;
		$field['group'] = [
			self::CONTACT_FORM_7_TOP_LEVEL_GROUP_ID => self::CONTACT_FORM_7_TOP_LEVEL_GROUP_TITLE,
		];

		if ( $groupName ) {
			$field['group'][ $groupId ] = $groupName;
		}

		return $field;
	}

	private function prepareFieldsToSort( array $fields ): array {
		$callbackPrepareGroup = function ( string $label ): string {
			$label  = trim( $label, '_' );
			$groups = explode( '_', $label, 2 );

			return $groups[0] ?? '';
		};

		return array_map(
			function ( array $field ) use ( $callbackPrepareGroup ): array {
				$fieldSlug = $field['attributes']['id'];
				$parts     = explode( '-', $fieldSlug, 4 );

				$field['__sort_helper'] = [
					'group_lv1' => $callbackPrepareGroup( $parts[0] ?? '' ),
					'group_lv2' => $callbackPrepareGroup( $parts[1] ?? '' ),
					'mail'      => [
						'id'                      => (int) ( Str::match( '@[0-9]+@', $fieldSlug )[0] ?? 0 ),

						'is_sub_field_is_subject' => 'subject' === ( $parts[3] ?? '' ),
					],
				];

				return $field;

			},
			$fields
		);
	}
}
