<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
  register_rest_route('hyve/v1', '/posts', [
    'methods'             => 'POST',
    'callback'            => 'hyve_get_posts',
    'permission_callback' => 'hyve_check_basic_auth',
  ]);
});
function hyve_get_posts(WP_REST_Request $request) {
  try {
    $allowed_order = ['ASC', 'DESC'];
    $body_params = $request->get_json_params();
    $allowed_orderby = ['date', 'title', 'modified', 'author', 'name', 'ID', 'rand', 'comment_count'];

    $fields = $body_params['fields'] ?? null;
    $page = intval($body_params['page'] ?? 1);
    $categories = $body_params['categories'] ?? null;
    $per_page = intval($body_params['per_page'] ?? -1);
    $search = sanitize_text_field($body_params['search'] ?? '');
    $status = sanitize_text_field($body_params['status'] ?? 'publish');
    $order_by = sanitize_text_field($body_params['order_by'] ?? "date");
    $post_type = sanitize_text_field($body_params['post_type' ?? "post"]);
    $order = sanitize_text_field(strtoupper($body_params['order'] ?? "DESC"));

    if (!post_type_exists($post_type)) return api_bad_response("Invalid post type: {$post_type}");

    if (!in_array($order, $allowed_order)) $order = "DESC";
    if (!in_array($order_by, $allowed_orderby)) $order_by = "date";
    
    $query_args = [
      'order'          => $order,
      'post_status'    => $status,
      'orderby'        => $orderby,
      'posts_per_page' => $per_page,
      'post_type'      => $post_type,
    ];
    
    if ($page < 1) $page = 1;
    if ($search) $query_args['s'] = $search;
    if ($per_page > 0) $query_args['paged'] = $page;
    if ($categories) $query_args['category__in'] = array_map('intval', (array) $categories);
    
    $cache_ttl_seconds = 600;
    $cache_group = 'hyve_api_posts';
    $cache_key = 'hyve_posts' . '_' . md5(json_encode($query_args));

    $data = wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      $query = new WP_Query($query_args);
      $posts = array_map(function($post) {
        return [
          'id'         => $post->ID,
          'slug'       => $post->post_name,
          'type'       => $post->post_type,
          'status'     => $post->post_status,
          'title'      => get_the_title($post),
          'excerpt'    => get_the_excerpt($post),
          'date'       => get_the_date('', $post),
          'categories' => wp_get_post_categories($post->ID),
          'content'    => apply_filters('the_content', $post->post_content),
          'tags'       => wp_get_post_tags($post->ID, ['fields' => 'names']),
          'author'     => get_the_author_meta('display_name', $post->post_author),
        ];
      }, $query->posts);
           
      $data = [
        'posts' => $posts,
        'meta' => [
          'current_page' => $page,
          'per_page'     => $per_page,
          'total'        => (int) $query->found_posts,
          'total_pages'  => (int) $query->max_num_pages,
        ]
        ];

      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }

    $filtered_data = filter_fields($data['posts'], $fields);
    if (count($filtered_data) == 0) return api_no_content_response("No posts found: {$post_type}.", $data['meta']);
    return api_success_response("Posts retrieved successfully: {$post_type}.", $filtered_data, $data['meta']);
  } catch (Exception $err) {
    return api_server_error_response(['exception' => $err->getMessage()]);
  }
}

add_action('rest_api_init', function () {
  register_rest_route('hyve/v1', '/posts/by-slug-id', [
    'methods'             => 'POST',
    'permission_callback' => 'hyve_check_basic_auth',
    'callback'            => 'hyve_get_post_by_slug_id',
  ]);
});
function hyve_get_post_by_slug_id(WP_REST_Request $request) {
  try {
    $body_params = $request->get_json_params();

    $fields = $body_params['fields'] ?? null;
    $post_id = intval($body_params['id'] ?? 0);
    $slug = sanitize_text_field($body_params['slug'] ?? "");
    $post_type = sanitize_text_field($body_params['post_type'] ?? "post");

    if (!$post_id && !$slug) return api_bad_response('Post ID or slug is required.');
    if (!post_type_exists($post_type)) return api_bad_response("Invalid post type: {$post_type}");

    $cache_ttl_seconds = 600;
    $cache_group = 'hyve_api_posts';
    $cache_key = 'hyve_' . $post_type . '_' . ($post_id ? "id_{$post_id}" : "slug_{$slug}");

    $data= wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      if ($post_id) {
        $post = get_post($post_id);
      } else {
        $post = get_page_by_path($slug, OBJECT, $post_type);
      }

      if (!$post || $post->post_type !== $post_type) return api_no_content_response("Post not found: {$post_type}.");

      $data = [
        'id'         => $post->ID,
        'slug'       => $post->post_name,
        'type'       => $post->post_type,
        'status'     => $post->post_status,
        'title'      => get_the_title($post),
        'excerpt'    => get_the_excerpt($post),
        'date'       => get_the_date('', $post),
        'categories' => wp_get_post_categories($post->ID),
        'content'    => apply_filters('the_content', $post->post_content),
        'tags'       => wp_get_post_tags($post->ID, ['fields' => 'names']),
        'author'     => get_the_author_meta('display_name', $post->post_author),
      ];

      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }

    $filtered_data = filter_fields([$data], $fields);
    return api_success_response("Post retrieved successfully: {$post_type}.", $filtered_data[0]);
  } catch (Exception $err) {
    return api_server_error_response(['exception' => $err->getMessage()]);
  }
}

add_action('rest_api_init', function () {
  register_rest_route('hyve/v1', '/post-types', [
    'methods'             => 'POST',
    'callback'            => 'hyve_get_post_types',
    'permission_callback' => 'hyve_check_basic_auth',
  ]);
});
function hyve_get_post_types(WP_REST_Request $request) {
  try { 
    $body_params = $request->get_json_params();

    $fields = $body_params['fields'] ?? null;

    $cache_ttl_seconds = 600;
    $cache_key = 'hyve_post_types';
    $cache_group = 'hyve_api_posts';
    $data = wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      $post_types = get_post_types(['public' => true], 'objects');
      $data = [];

      foreach ($post_types as $post_type) {
        $data[] = [
          'name'         => $post_type->name,
          'label'        => $post_type->label,
          'public'       => $post_type->public,
          'description'  => $post_type->description,
          'hierarchical' => $post_type->hierarchical,
          'taxonomies'   => get_object_taxonomies($post_type->name),
          'supports'     => get_all_post_type_supports($post_type->name),
        ];
      }

      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }

    $filtered_data = filter_fields($data, $fields);
    return api_success_response("Post types retrieved successfully.", $filtered_data);
  } catch (Exception $err) {
    return api_server_error_response(['exception' => $err->getMessage()]);
  }
}