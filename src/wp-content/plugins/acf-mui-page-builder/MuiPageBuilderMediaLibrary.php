<?php

class MuiPageBuilderMediaLibrary {

	private \wpdb $db;

	const VERSION_KEY = self::class . ':schemaVersion';

	public function install() {

	}

	public function __construct( \wpdb $wpdb ) {
		$this->db = $wpdb;
	}

	public function getTableName() {
		return $this->db->prefix . 'muipagebuilder_mediaobjects';
	}

	public function getSchemaVersion() {
		return intval( get_option( self::VERSION_KEY, - 1 ) );
	}

	public function setSchemaVersion( int $version ) {
		add_option( self::VERSION_KEY, $version );
	}

	public function uninstallSchema() {
		$table_name = $this->getTableName();
		$query      = "DROP TABLE IF EXISTS $table_name";
		$this->db->query( $query );
		delete_option( self::VERSION_KEY );
	}

	public function installSchema() {
		$current_version = $this->getSchemaVersion();
		$table_name      = $this->getTableName();
		$queries         = [
			"
CREATE TABLE $table_name (
    post_id BIGINT UNSIGNED NOT NULL,
	prefix VARCHAR(255),
	name VARCHAR(255),
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (prefix, name),
	INDEX (post_id)
)",
		];
		$latest_version  = count( $queries ) - 1;
		if ( $current_version >= $latest_version ) {
			return;
		}
		if ( $current_version === - 1 ) {
			$current_version = 0;
		}
		for ( $i = $current_version; $i <= $latest_version; $i ++ ) {
			$result = $this->db->query( $queries[ $i ] );
			if ( $result === false ) {
				$this->setSchemaVersion( $i );
				throw new Error("Failed to install schema");
			}
		}
		$this->setSchemaVersion( $latest_version );
	}

	public function registerRestRoutes() {
		$namespace = 'muipagebuilder/v1/media_library';
		register_rest_route( $namespace, 'ls', [
			'methods'             => 'GET',
			'callback'            => function ( WP_REST_Request $request ) {
				$query_params = $request->get_query_params();
				$prefix       = $query_params['prefix'] ?? '';

				return new WP_REST_Response( $this->ls( $prefix ) );
			},
			'permission_callback' => fn() => current_user_can( 'edit_posts' )
		] );
		register_rest_route( $namespace, 'objectUrl', [
			'methods'  => 'GET',
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			'callback' => function ( WP_REST_Request $request ) {
				$query_params = $request->get_query_params();
				$key          = $query_params['key'] ?? '';
				if ( strlen( $key ) === 0 ) {
					return new WP_Error( 'bad_request', '"key" parameter is missing or is empty.' );
				}

				return new WP_REST_Response( $this->objectUrl( $key ) );
			},
		] );
		register_rest_route( $namespace, 'imagePreviewUrl', [
			'methods'  => 'GET',
			'permission_callback' => fn() => current_user_can( 'edit_posts' ),
			'callback' => function ( WP_REST_Request $request ) {
				$query_params = $request->get_query_params();
				$key          = $query_params['key'] ?? '';
				$width        = intval( $query_params['width'] ?? 0 );
				if ( strlen( $key ) === 0 ) {
					return new WP_Error( 'bad_request', '"key" parameter is missing or is empty.' );
				}
				if ( $width < 1 || $width > 4096 ) {
					return new WP_Error( 'bad_request', '"width" parameter is missing or is not between 1 and 4096.' );
				}
				$url = $this->imagePreviewUrl( $key, $width );
				if ( $url instanceof WP_Error ) {
					return $url;
				}

				return new WP_REST_Response(
					$url,
					200,
					[
						"Cache-control" => 'public, max-age=31536000'
					],
				);
			},
		] );
		register_rest_route( $namespace, 'mkdir', [
			'methods'             => 'POST',
			'callback'            => function ( WP_REST_Request $request ) {
				$key    = $request->get_param( 'key' );
				$result = $this->mkdir( $key );
				if ( ! $result ) {
					return new WP_Error( 'bad_request' );
				}

				return new WP_REST_Response( '', 204 );
			},
			'permission_callback' => fn() => current_user_can( 'edit_posts' )
		] );
		register_rest_route( $namespace, 'upload', [
			'methods'             => 'POST',
			'callback'            => function ( WP_REST_Request $request ) {
				$params = $request->get_body_params();
				if ( empty( $params['key'] ) ) {
					return new WP_Error( 'bad_request', 'Missing param "key"' );
				}
				$key    = $params['key'];
				$result = $this->upload( $key );
				if ( $result instanceof WP_Error ) {
					return $result;
				}

				return new WP_REST_Response( $result );
			},
			'permission_callback' => fn() => current_user_can( 'edit_posts' )
		] );
	}

	private function normalizePrefix( string $prefix ) {
		if ( ! str_starts_with( $prefix, '/' ) ) {
			$prefix = "/$prefix";
		}
		if ( ! str_ends_with( $prefix, '/' ) ) {
			$prefix = "$prefix/";
		}
		while ( strpos( $prefix, '//' ) !== false ) {
			$prefix = str_replace( '//', '/', $prefix );
		}

		return $prefix;
	}

	/**
	 * @param string $key
	 *
	 * @return array{'name': string, 'prefix': string}
	 */
	private function parseKey( string $key ): array {
		$key_parts = explode( '/', $key );
		$parts     = [];
		foreach ( $key_parts as $part ) {
			$part = trim( $part );
			if ( strlen( $part ) > 0 ) {
				$parts[] = $part;
			}
		}
		$name   = array_pop( $parts );
		$prefix = $this->normalizePrefix( implode( '/', $parts ) );

		return [
			'name'   => $name,
			'prefix' => $prefix,
		];
	}

	/**
	 * @param string $key
	 *
	 * @return array{'id': int, 'post_id': int, 'prefix': string, 'name': string, 'created_at': string} | null
	 */
	private function findByKey( string $key ): ?array {
		[ 'prefix' => $prefix, 'name' => $name ] = $this->parseKey( $key );
		$table_name      = $this->getTableName();
		$post_table      = $this->db->prefix . 'posts';
		$post_meta_table = $this->db->prefix . 'postmeta';
		$query           = $this->db->prepare( "
SELECT  
    media.post_id as post_id,
    media.prefix as prefix,
    media.name as name,
    media.created_at as created_at,
    posts.guid as url,
    posts.post_mime_type as mime_type,
    postmeta.meta_value as metadata
FROM $table_name as media
LEFT JOIN $post_table as posts 
    ON media.post_id = posts.ID
LEFT JOIN $post_meta_table as postmeta 
    ON media.post_id = postmeta.post_id 
	AND postmeta.meta_key = '_wp_attachment_metadata'
WHERE prefix = %s and name = %s
", [
			$prefix,
			$name,
		] );
		$results         = $this->db->get_results( $query, ARRAY_A );
		if ( empty( $results ) ) {
			return null;
		}
		$result = $results[0];
		if ( ! empty( $result['metadata'] ) ) {
			$result['metadata'] = unserialize( $result['metadata'] );
		}

		return $result;
	}

	public function objectUrl( string $key ): string|WP_Error {
		$object = $this->findByKey( $key );
		if ( empty( $object ) ) {
			return new WP_Error( 'not_found' );
		}

		return $object['url'];
	}

	public function imagePreviewUrl( string $key, int $width ): string|WP_Error {
		$object = $this->findByKey( $key );
		if ( empty( $object ) ) {
			return new WP_Error( 'not_found' );
		}
		$sizes            = $object['metadata']['sizes'];
		$current_size     = 'thumbnail';
		$current_distance = PHP_INT_MAX;
		foreach ( $sizes as $size => $def ) {
			$distance = abs( $width - $def['width'] );
			if ( $distance < $current_distance ) {
				$current_size     = $size;
				$current_distance = $distance;
			}
		}

		return wp_get_attachment_image_url( $object['post_id'], $current_size );
	}

	/**
	 * @param string $prefix
	 *
	 * @return array<>
	 */
	public function ls( string $prefix ): array {
		$prefix          = $this->normalizePrefix( $prefix );
		$table_name      = $this->getTableName();
		$post_table      = $this->db->prefix . 'posts';
		$post_meta_table = $this->db->prefix . 'postmeta';
		$query           = $this->db->prepare( "
SELECT  
    media.post_id as post_id,
    media.prefix as prefix,
    media.name as name,
    media.created_at as created_at,
    posts.guid as url,
    posts.post_mime_type as mime_type,
    postmeta.meta_value as metadata
FROM $table_name as media
LEFT JOIN $post_table as posts 
    ON media.post_id = posts.ID
LEFT JOIN $post_meta_table as postmeta 
    ON media.post_id = postmeta.post_id 
	AND postmeta.meta_key = '_wp_attachment_metadata'
WHERE media.prefix = %s
", [
			$prefix,
		] );
		$results         = $this->db->get_results( $query, ARRAY_A );
		$folders         = [];
		$objects         = [];
		foreach ( $results as $result ) {
			$name       = $result['name'];
			$prefix     = $result['prefix'];
			$key        = $prefix . $name;
			$post_id    = intval( $result['post_id'] );
			$metadata   = $result['metadata'];
			$created_at = $result['created_at'];
			$mime_type  = $result['mime_type'];
			$url        = $result['url'];
			$is_folder  = str_ends_with( $name, '/' ) && $post_id === 0;
			$is_object  = ! $is_folder;
			if ( $is_folder ) {
				$folders[] = [
					'id'     => $key,
					'type'   => 'folder',
					'prefix' => $key,
					'name'   => substr( $name, 0, - 1 ),
				];
			}
			if ( $is_object ) {
				$metadata = unserialize( $metadata );
				$meta     = [
					'name'         => $name,
					'uri'          => 'wp-uploads://' . $metadata['file'],
					'url'          => $url,
					'content_type' => $mime_type,
					'size'         => $metadata['filesize'],
				];
				if ( str_starts_with( $mime_type, 'image/' ) ) {
					$meta['exif'] = [
						'width'  => $metadata['width'],
						'height' => $metadata['height'],
					];
				}
				$objects[] = [
					'id'           => $key,
					'type'         => 'file',
					'key'          => $key,
					'name'         => $name,
					'size'         => $metadata['filesize'],
					'lastModified' => $created_at,
					'meta'         => $meta,
				];
			}
		}
		$ls_response = [
			'objects'     => $objects,
			'folders'     => $folders,
			'isTruncated' => false,
			'count'       => count( $results ),
		];

		return $ls_response;
	}

	public function upload( string $key, string $file_id = 'media_library_file' ): WP_Error|array {
		define( 'DOING_AJAX', true );
		require_once ABSPATH . 'wp-load.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$post_id = media_handle_upload( $file_id, 0 );
		if ( $post_id instanceof WP_Error ) {
			return $post_id;
		}
		[
			'prefix' => $prefix,
			'name'   => $name,
		] = $this->parseKey( $key );
		if ( strlen( $name ) === 0 ) {
			return new WP_Error( 'bad_request', 'Filename is empty' );
		}
		$result = $this->db->insert( $this->getTableName(), [
			'post_id' => $post_id,
			'prefix'  => $prefix,
			'name'    => $name,
		] );
		if ( $result === false ) {
			return new WP_Error( $this->db->last_error );
		}

		return $this->findByKey( $prefix . $name );
	}

	public function mkdir( string $key ): bool {
		[
			'name'   => $name,
			'prefix' => $prefix,
		] = $this->parseKey( $key );
		$result = $this->db->insert( $this->getTableName(), [
			'post_id' => 0,
			'prefix'  => $prefix,
			'name'    => $name . '/',
		] );

		return $result !== false;
	}

	public function delete( string $key ) {
		[ $prefix, $name ] = $this->parseKey( $key );
		if ( strlen( $name ) === 0 ) {
			return new WP_Error( 'bad_request' );
		}
		$this->db->delete( $this->getTableName(), [
			'prefix' => $prefix,
			'name'   => $name,
		] );
	}
}
