<?php

class MuiPageBuilderFilebird
{

    private \wpdb $db;

    private $cache = [];

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function registerRestRoutes()
    {
        $namespace = 'muipagebuilder/v1/media_library';
        register_rest_route($namespace, 'ls', [
            'methods'             => 'GET',
            'callback'            => function (WP_REST_Request $request) {
                $query_params = $request->get_query_params();
                $prefix       = $query_params['prefix'] ?? '';

                return new WP_REST_Response($this->ls($prefix));
            },
            'permission_callback' => fn() => current_user_can('edit_posts')
        ]);
        register_rest_route($namespace, 'objectUrl', [
            'methods'  => 'GET',
            'permission_callback' => fn() => current_user_can('edit_posts'),
            'callback' => function (WP_REST_Request $request) {
                $query_params = $request->get_query_params();
                $key          = $query_params['key'] ?? '';
                if (strlen($key) === 0) {
                    return new WP_Error('bad_request', '"key" parameter is missing or is empty.');
                }

                return new WP_REST_Response($this->objectUrl($key));
            },
        ]);
        register_rest_route($namespace, 'imagePreviewUrl', [
            'methods'  => 'GET',
            'permission_callback' => fn() => current_user_can('edit_posts'),
            'callback' => function (WP_REST_Request $request) {
                $query_params = $request->get_query_params();
                $key          = $query_params['key'] ?? '';
                $width        = intval($query_params['width'] ?? 0);
                if (strlen($key) === 0) {
                    return new WP_Error('bad_request', '"key" parameter is missing or is empty.');
                }
                if ($width < 1 || $width > 4096) {
                    return new WP_Error('bad_request', '"width" parameter is missing or is not between 1 and 4096.');
                }
                $url = $this->imagePreviewUrl($key, $width);
                if ($url instanceof WP_Error) {
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
        ]);
        register_rest_route($namespace, 'mkdir', [
            'methods'             => 'POST',
            'callback'            => function (WP_REST_Request $request) {
                $key    = $request->get_param('key');
                $result = $this->mkdir($key);
                if (!$result) {
                    return new WP_Error('bad_request');
                }

                return new WP_REST_Response('', 204);
            },
            'permission_callback' => fn() => current_user_can('edit_posts')
        ]);
        register_rest_route($namespace, 'upload', [
            'methods'             => 'POST',
            'callback'            => function (WP_REST_Request $request) {
                $params = $request->get_body_params();
                if (empty($params['key'])) {
                    return new WP_Error('bad_request', 'Missing param "key"');
                }
                $key    = $params['key'];
                $result = $this->upload($key);
                if ($result instanceof WP_Error) {
                    return $result;
                }

                return new WP_REST_Response($result);
            },
            'permission_callback' => fn() => current_user_can('edit_posts')
        ]);
    }

    private function normalizePrefix(string $prefix)
    {
        if (!str_starts_with($prefix, '/')) {
            $prefix = "/$prefix";
        }
        if (!str_ends_with($prefix, '/')) {
            $prefix = "$prefix/";
        }
        while (strpos($prefix, '//') !== false) {
            $prefix = str_replace('//', '/', $prefix);
        }

        return $prefix;
    }

    /**
     * @param string $key
     *
     * @return array{'name': string, 'prefix': string}
     */
    private function parseKey(string $key): array
    {
        $key_parts = explode('/', $key);
        $parts     = [];
        foreach ($key_parts as $part) {
            $part = trim($part);
            if (strlen($part) > 0) {
                $parts[] = $part;
            }
        }
        $name   = array_pop($parts);
        $prefix = $this->normalizePrefix(implode('/', $parts));

        return [
            'name'   => $name,
            'prefix' => $prefix,
        ];
    }

    public function objectUrl(string $key): string|WP_Error
    {
        $object = $this->findByKey($key);
        if (empty($object)) {
            return new WP_Error('not_found');
        }

        return $object['url'];
    }

    public function imagePreviewUrl(string $key, int $width): string|WP_Error
    {
        $object = $this->findByKey($key);
        if (empty($object)) {
            return new WP_Error('not_found');
        }
        $sizes            = $object['metadata']['sizes'] ?? [];
        $current_size     = 'thumbnail';
        if (empty($sizes)) {
            return wp_get_attachment_image_url($object['id'], $current_size);
        }
        $current_distance = PHP_INT_MAX;
        foreach ($sizes as $size => $def) {
            $distance = abs($width - $def['width']);
            if ($distance < $current_distance) {
                $current_size     = $size;
                $current_distance = $distance;
            }
        }

        return wp_get_attachment_image_url($object['id'], $current_size);
    }

    private function getFbFolderChildren($folderId)
    {
        $query = $this->db->prepare(
            'SELECT name, id FROM ' . $this->db->prefix . 'fbv WHERE parent = %s',
            [$folderId]
        );
        $results = $this->db->get_results($query, ARRAY_A);
        return $results;
    }

    private function getFbFolderByNameAndParentId($name, $parent_id)
    {
        $query = $this->db->prepare(
            'SELECT name, id FROM ' . $this->db->prefix . 'fbv WHERE name = %s AND parent = %s',
            [$name, $parent_id]
        );
        $results = $this->db->get_results($query, ARRAY_A);
        if (!empty($results)) {
            $folder = $results[0];
            $folder['id'] = intval($folder['id']);
            return $folder;
        }
        return null;
    }

    private function getFolderObjects(string $prefix, $include_postmeta = false)
    {
        $folderId = $this->getFolderId($prefix);
        $post_table      = $this->db->prefix . 'posts';
        $post_meta_table = $this->db->prefix . 'postmeta';
        $fbv_attachment_folder_table      = $this->db->prefix . 'fbv_attachment_folder';
        if ($folderId === 0) {
            // Get all the posts without a parent folder
            $query = $this->db->prepare(
                "
SELECT ID as id
FROM $post_table as posts
LEFT JOIN $fbv_attachment_folder_table as fbv
ON posts.ID = fbv.attachment_id
WHERE post_type = %s
AND fbv.attachment_id IS NULL
",
                ['attachment']
            );
        } else {
            $query = $this->db->prepare(
                'SELECT attachment_id as id FROM ' . $this->db->prefix . 'fbv_attachment_folder WHERE folder_id = %s',
                [$folderId]
            );
        }

        $results = $this->db->get_results($query, ARRAY_A);
        if (empty($results)) {
            return [];
        }
        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result['id'];
        }
        $ids = implode(',', $ids);
        $query           = $this->db->prepare("
SELECT
    posts.ID as post_id,
    posts.post_name as name,
    posts.post_date as created_at,
    posts.guid as url,
    posts.post_mime_type as mime_type,
    postmeta.meta_value as metadata,
    postmeta2.meta_value as attached_file
FROM $post_table as posts
LEFT JOIN $post_meta_table as postmeta
    ON posts.ID = postmeta.post_id
	AND postmeta.meta_key = '_wp_attachment_metadata'
LEFT JOIN $post_meta_table as postmeta2
    ON posts.ID = postmeta2.post_id
	AND postmeta2.meta_key = '_wp_attached_file'
WHERE posts.ID IN ($ids)
");

        $results         = $this->db->get_results($query, ARRAY_A);
        $objects = [];

        foreach ($results as $result) {

            // png
            // array:8 [
            //     "post_id" => "164"
            //     "name" => "aima-2"
            //     "created_at" => "2024-07-09 16:31:14"
            //     "url" => "https://any-staging.oss-cn-hongkong.aliyuncs.com/wp-content/uploads/2024/07/AIMA-2.png"
            //     "mime_type" => "image/png"
            //     "metadata" => "a:6:{s:5:"width";i:1000;s:6:"height";i:1000;s:4:"file";s:18:"2024/07/AIMA-2.png";s:8:"filesize";i:31442;s:5:"sizes";a:3:{s:6:"medium";a:5:{s:4:"file";s:18:"AIMA-2-300x300.png";s:5:"width";i:300;s:6:"height";i:300;s:9:"mime-type";s:9:"image/png";s:8:"filesize";i:2415;}s:9:"thumbnail";a:5:{s:4:"file";s:18:"AIMA-2-150x150.png";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:9:"image/png";s:8:"filesize";i:1043;}s:12:"medium_large";a:5:{s:4:"file";s:18:"AIMA-2-768x768.png";s:5:"width";i:768;s:6:"height";i:768;s:9:"mime-type";s:9:"image/png";s:8:"filesize";i:7104;}}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}"
            //     "attached_file" => "2024/07/AIMA.png"
            //   ]
            // svg
            // array:8 [
            //     "post_id" => "1414"
            //     "name" => "icon-brake"
            //     "created_at" => "2024-07-30 16:21:59"
            //     "url" => "https://any-staging.oss-cn-hongkong.aliyuncs.com/wp-content/uploads/2024/07/icon-brake.svg"
            //     "mime_type" => "image/svg+xml"
            //     "metadata" => "a:1:{s:8:"filesize";i:4007;}"
            //     "attached_file" => "2024/07/icon-brake.svg"
            //   ]

            $is_svg = $result['mime_type'] === 'image/svg+xml';

            $name       = $result['name'];
            $metadata   = $result['metadata'];
            $created_at = $result['created_at'];
            $mime_type  = $result['mime_type'];
            $url        = $result['url'];
            $pathinfo = null;
            if (!empty($metadata)) {
                $metadata = unserialize($metadata);
                $pathinfo  = isset($metadata['file']) ? pathinfo($metadata['file']) : null;
            }
            $name = $is_svg ? $name . '.svg' : ($pathinfo ? $pathinfo['basename'] : $name);
            $key  = $prefix . $name;
            $meta     = [
                'name'         => $name,
                'uri'          => 'wp-uploads://' . $result['attached_file'],
                'url'          => $url,
                'content_type' => $mime_type,
                'size'         => $metadata['filesize'],
            ];
            if (str_starts_with($mime_type, 'image/')) {
                $meta['exif'] = [
                    'width'  => $metadata['width'],
                    'height' => $metadata['height'],
                ];
            }
            $object =  [
                'id'           => $result['post_id'],
                'type'         => 'file',
                'key'          => $key,
                'name'         => $name,
                'size'         => $metadata['filesize'],
                'lastModified' => $created_at,
                'meta'         => $meta,
            ];
            if ($include_postmeta) {
                $object['metadata'] = $metadata;
            }
            $objects[] = $object;
        }
        return $objects;
    }

    private function findByKey(string $key)
    {
        ['prefix' => $prefix, 'name' => $name] = $this->parseKey($key);
        $objects = $this->getFolderObjects($prefix, true);

        // error_log("$prefix $name");
        // error_log(print_r($objects, true));

        foreach ($objects as $object) {
            if ($object['name'] === $name) {
                return $object;
            }
        }
        return null;
    }

    private function getFolderId(string $prefix)
    {
        $cacheKey = __METHOD__ . $prefix;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        $folder = ['id' => 0, 'name' => '/'];
        if ($prefix === '/') {
            return $folder['id'];
        }
        $parts = explode('/', $prefix);
        $parent = $folder;
        $not_found = false;
        while (count($parts) > 0) {
            $folder_name = array_shift($parts);
            if (empty($folder_name)) {
                continue;
            }
            $folder = $this->getFbFolderByNameAndParentId($folder_name, $parent['id']);
            if (empty($folder)) {
                $not_found = true;
                break;
            }
            $parent = $folder;
        }
        if ($not_found) {
            $this->cache[$cacheKey] = null;
            return null;
        }
        $this->cache[$cacheKey] = $folder['id'];
        return $folder['id'];
    }


    /**
     * @param string $key
     *
     * @return array{'id': int, 'post_id': int, 'prefix': string, 'name': string, 'created_at': string} | null
     */
    /**
     * @param string $prefix
     *
     * @return array<>
     */
    public function ls(string $prefix): array
    {
        $folders = [];
        $prefix = $this->normalizePrefix($prefix);
        $folder_id = $this->getFolderId($prefix);
        $filebird_folders = $this->getFbFolderChildren($folder_id);

        foreach ($filebird_folders as $folder) {
            $key = $prefix . $folder['name'] . '/';
            $folders[] = [
                'id' => $key,
                'type' => 'folder',
                'prefix' => $key,
                'name' => $folder['name'],
            ];
        }

        $objects = $this->getFolderObjects($prefix);
        $ls_response = [
            'objects'     => $objects,
            'folders'     => $folders,
            'isTruncated' => false,
            'count'       => count($objects),
        ];

        return $ls_response;
    }

    public function upload(string $key, string $file_id = 'media_library_file'): WP_Error|array
    {
        define('DOING_AJAX', true);
        require_once ABSPATH . 'wp-load.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $post_id = media_handle_upload($file_id, 0);
        if ($post_id instanceof WP_Error) {
            return $post_id;
        }

        $attached_file = get_post_meta($post_id, '_wp_attached_file', true);
        $attached_file_name =  str_replace(date("Y/m/"), '', $attached_file);

        ['prefix' => $prefix] = $this->parseKey($key);
        if (strlen($attached_file_name) === 0) {
            return new WP_Error('bad_request', 'Filename is empty');
        }
        $folder_id = $this->getFolderId($prefix);
        \FileBird\Model\Folder::setFoldersForPosts([$post_id], [$folder_id]);

        return $this->findByKey($prefix . $attached_file_name);
    }

    public function mkdir(string $key): bool
    {
        [
            'name'   => $name,
            'prefix' => $prefix,
        ] = $this->parseKey($key);
        $parent_id = $this->getFolderId($prefix);
        $id = \FileBird\Model\Folder::newOrGet($name, $parent_id);
        return !empty($id);
    }

    public function delete(string $key) {}
}
