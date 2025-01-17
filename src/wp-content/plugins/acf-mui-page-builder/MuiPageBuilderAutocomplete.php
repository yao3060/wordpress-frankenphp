<?php

class MuiPageBuilderAutocomplete {

	private $db;

	public function __construct( \wpdb $wpdb ) {
		$this->db = $wpdb;
	}

	public function getPosts( $params = [] ) {
		$q           = $params['q'] ?? '';
		$bundle      = $params['bundle'] ?? '';
		$posts_table = $this->db->prefix . 'posts';
		$args        = [];
		$sql         = "
SELECT  
    ID as id,
    post_date as date,
    post_title as label,
    post_status as status,
    post_type as type,
    post_name as name
FROM $posts_table 
WHERE post_type NOT IN ('attachment', 'acf-field', 'acf-field-group') 
";
		if ( ! empty( $q ) ) {
			$sql    = "$sql AND post_name LIKE %s";
			$args[] = $this->db->esc_like( $q ) . '%';
		}
		if ( ! empty( $bundle ) && is_string( $bundle ) ) {
			$sql    = "$sql AND post_type = %s";
			$args[] = $bundle;
		}
		$sql   = "$sql ORDER BY post_type ASC, post_date ASC LIMIT 200";
		$query = $this->db->prepare( $sql, $args );
		$data  = $this->db->get_results( $query, ARRAY_A );
		foreach ( $data as $i => $datum ) {
			$url          = parse_url( get_permalink( $datum['id'] ) );
			$datum['url'] = $url['path'];
			$data[ $i ]   = $datum;
		}

		return $data;
	}

	public function getTerms( $params = [] ) {
		$q                   = $params['q'] ?? '';
		$bundle              = $params['bundle'] ?? '';
		$term_table          = $this->db->prefix . 'terms';
		$term_taxonomy_table = $this->db->prefix . 'term_taxonomy';
		$args                = [];
		$sql                 = "
SELECT  
    terms.term_id as id,
    terms.name as label,
    term_taxonomy.taxonomy as type,
    terms.slug as name
FROM $term_table as terms
INNER JOIN $term_taxonomy_table as term_taxonomy
ON terms.term_id = term_taxonomy.term_id
WHERE 
";
		if ( ! empty( $q ) ) {
			$sql    = "$sql AND terms.name LIKE %s";
			$args[] = $this->db->esc_like( $q ) . '%';
		}
		if ( ! empty( $bundle ) && is_string( $bundle ) ) {
			$sql    = "$sql AND term_taxonomy.taxonomy = %s";
			$args[] = $bundle;
		}
		$sql   = "$sql ORDER BY terms.name ASC LIMIT 100";
		$query = $this->db->prepare( $sql, $args );
		$data  = $this->db->get_results( $query, ARRAY_A );

		return $data;
	}

	public function registerRestRoutes() {
		$route_namespace = 'muipagebuilder/v1';
		register_rest_route( $route_namespace, '/autocomplete', [
			[
				'methods'             => 'GET',
				'callback'            => function ( WP_REST_Request $request ) {
					$query_params = $request->get_query_params();
					$q            = $query_params['q'] ?? '';
					$q            = strtolower( trim( $q ) );
					$bundle       = $query_params['bundle'] ?? '';
					$params       = [ 'q' => $q, 'bundle' => $bundle ];
					$type         = $query_params['type'] ?? 'post';
					if ( ! is_string( $q ) ) {
						return new WP_REST_Response( [
							'errors' => [
								[
									'message' => '"q" param is not a string.'
								]
							]
						], 400 );
					}
					if ( $type === 'post' ) {
						$data = $this->getPosts( $params );
						return new WP_REST_Response( $data );
					}
					if ( $type === 'term' ) {
						if ( empty( $bundle ) || ! is_string( $bundle ) ) {
							return new WP_REST_Response( [
								'errors' => [
									[
										'message' => 'Parameter "bundle" is missing or is empty.'
									]
								]
							], 400 );
						}
						$data = $this->getTerms( $params );
						return new WP_REST_Response( $data );
					}
					return new WP_REST_Response( [
						'errors' => [
							[
								'message' => 'Invalid "type" parameter. Allowed values: post, term'
							]
						]
					], 400 );
				},
				'permission_callback' => function () {
					return true;
				},
			],
		] );
	}

}
