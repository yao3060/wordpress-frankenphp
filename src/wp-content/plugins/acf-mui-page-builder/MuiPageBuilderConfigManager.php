<?php

class MuiPageBuilderConfigManager {

	private $db;

	const VERSION_KEY = self::class . ':schemaVersion';

	public function __construct( \wpdb $wpdb ) {
		$this->db = $wpdb;
	}

	public function getTableName() {
		return $this->db->prefix . 'muipagebuilder_config';
	}

	public function getSchemaVersion() {
		return intval( get_option( self::VERSION_KEY, - 1 ) );
	}

	public function setSchemaVersion( int $version ) {
		add_option( self::VERSION_KEY, $version );
	}

	public function installSchema() {
		$current_version = $this->getSchemaVersion();
		$table_name      = $this->getTableName();
		$queries         = [
			"
CREATE TABLE $table_name (
	id VARCHAR(255) NOT NULL,
    data JSON NOT NULL,
    revision_id INT NOT NULL DEFAULT 1,
    PRIMARY KEY(id)
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
			}
		}
		$this->setSchemaVersion( $latest_version );
	}

	public function registerRestRoutes() {
		$route_namespace = 'muipagebuilder/v1';
		register_rest_route( $route_namespace, '/config', [
			[
				'methods'             => 'GET',
				'callback'            => function () {
					$data = $this->list();

					return new WP_REST_Response( $data );
				},
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			],
			[
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$data = $request->get_json_params();
					if ( $data instanceof WP_Error ) {
						return $data;
					}
					$result = $this->save( $data );
					if ( $result instanceof WP_Error ) {
						return $result;
					}
					$config = $this->load( $data['id'] );

					return new WP_REST_Response( $config );
				},
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		] );
		register_rest_route( $route_namespace, '/config/(?P<id>[a-z0-9-_.]+)', [
				[
					'methods'             => 'GET',
					'callback'            => function ( WP_REST_Request $request ) {
						$url_params = $request->get_url_params( 'id' );
						$id         = $url_params['id'];
						$config     = $this->load( $id );
						if ( empty( $config ) ) {
							return new WP_REST_Response( [
								'errors' => [
									[
										'id'     => 'not_found',
										'status' => 404,
									]
								],
							], 404 );
						}

						return new WP_REST_Response( $config );
					},
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],
				[
					'methods'             => 'DELETE',
					'callback'            => function ( WP_REST_Request $request ) {
						$url_params = $request->get_url_params( 'id' );
						$id         = $url_params['id'];
						$config     = $this->load( $id );
						if ( empty( $config ) ) {
							return new WP_REST_Response( [
								'errors' => [
									[
										'id'     => 'not_found',
										'status' => 404,
									]
								],
							], 404 );
						}
						$this->delete( $id );

						return new WP_REST_Response( null, 204 );
					},
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],
			]
		);
	}

	/**
	 * @param array{'id': string, "data": array, "revision_id": string} $data
	 *
	 * @return boolean | WP_Error
	 */
	public function save(
		array $config
	) {
		$table_name = $this->getTableName();
		$json_data  = json_encode( $config['data'] );
		$query      = $this->db->prepare(
			"
INSERT INTO $table_name (id, data)
VALUES (%s, %s)
ON DUPLICATE KEY UPDATE data = %s, revision_id = revision_id + 1
",
			[
				$config['id'],
				$json_data,
				$json_data,
			] );
		$result     = $this->db->query( $query );
		if ( ! $result ) {
			return $this->db->error;
		}

		return true;
	}

	public function delete(
		string $id
	) {
		$result = $this->db->delete( $this->getTableName(), [
			'id' => $id,
		] );
		if ( $result === false ) {
			return $this->db->error;
		}

		return true;
	}

	/**
	 * @param int $id
	 *
	 * @return array{"id": string, "data": array, "revision_id": string}
	 */
	public function load(
		string $id
	): ?array {
		$table_name = $this->getTableName();
		$query      = $this->db->prepare( "SELECT * FROM $table_name WHERE id = %s", [
			$id
		] );
		$rows       = $this->db->get_results( $query, ARRAY_A );
		if ( empty( $rows ) ) {
			return null;
		}
		$row         = $rows[0];
		$row['data'] = json_decode( $row['data'], true );

		return $row;
	}

	/**
	 * @return array<array{"id": string, "data": array, "revision_id": string}>
	 */
	public function list(): array {
		$table_name = $this->getTableName();
		$rows       = $this->db->get_results( "SELECT id, data FROM $table_name", ARRAY_A );
		foreach ( $rows as $i => $row ) {
			$decoded            = json_decode( $row['data'], true );
			$rows[ $i ]['data'] = $decoded;
		}

		return $rows;
	}
}