<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
  register_rest_route('hyve/v1', '/pages', [
    'methods'             => 'POST',
    'permission_callback' => '__return_true',
    'callback'            => 'hyve_get_pages',
  ]);
});
function hyve_get_pages(WP_REST_Request $request) {
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
    $order = sanitize_text_field(strtoupper($body_params['order'] ?? "DESC"));

    if (!in_array($order, $allowed_order)) $order = "DESC";
    if (!in_array($order_by, $allowed_orderby)) $order_by = "date";
    
    $query_args = [
      'post_type'      => 'page',
      'order'          => $order,
      'post_status'    => $status,
      'orderby'        => $orderby,
      'posts_per_page' => $per_page,
    ];
    
    if ($page < 1) $page = 1;
    if ($search) $query_args['s'] = $search;
    if ($per_page > 0) $query_args['paged'] = $page;
    if ($categories) $query_args['category__in'] = array_map('intval', (array) $categories);

    $cache_ttl_seconds = 600;
    $cache_group = 'hyve_api_pages';
    $cache_key = 'hyve_page' . '_' . md5(json_encode($query_args));

    $data = wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      $query = new WP_Query($query_args);
      $pages = array_map(function($page) {
        return [
          'id'      => $page->ID,
          'slug'    => $page->post_name,
          'type'    => $page->post_type,
          'status'  => $page->post_status,
          'title'   => get_the_title($page),
          'excerpt' => get_the_excerpt($page),
          'date'    => get_the_date('', $page),
          'content' => apply_filters('the_content', $page->post_content),
          'author'  => get_the_author_meta('display_name', $page->post_author),
        ];
      }, $query->posts);

      $data = [
        'pages' => $pages,
        'meta' => [
          'current_page' => $page,
          'per_page'     => $per_page,
          'total'        => (int) $query->found_posts,
          'total_pages'  => (int) $query->max_num_pages,
        ]
      ];

      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }
    
    $filtered_data = filter_fields($data['pages'], $fields);
    if (count($filtered_data) == 0) return api_no_content_response("No pages found.", $data['meta']);
    return api_success_response('Pages retrieved successfully.', $filtered_data, $data['meta']);
  } catch (Exception $err) {
    return api_server_error_response(['exception' => $err->getMessage()]);
  }
}

add_action('rest_api_init', function () {
  register_rest_route('hyve/v1', '/pages/by-slug-id', [
    'methods'             => 'POST',
    'permission_callback' => '__return_true',
    'callback'            => 'hyve_get_page_by_slug_id',
  ]);
});
function hyve_get_page_by_slug_id(WP_REST_Request $request) {
  try {
    $body_params = $request->get_json_params();

    $id = intval($body_params['id'] ?? 0);
    $fields = $body_params['fields'] ?? null;
    $slug = sanitize_text_field($body_params['slug'] ?? "");

    if (!$id && !$slug) return api_bad_response('Page ID or slug is required');
    
    $cache_ttl_seconds = 600;
    $cache_group = 'hyve_api_pages';
    $cache_key = 'hyve_page' . '_' . ($id ? "id_{$id}" : "slug_{$slug}");

    $data = wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      if ($id) {
        $page = get_post($id);
      } else {
        $page = get_page_by_path($slug, OBJECT, "page");
      }

      if (!$page || $page->post_type !== 'page') return api_no_content_response("Page not found.");

      $data = [
        'id'         => $page->ID,
        'slug'       => $page->post_name,
        'type'       => $page->post_type,
        'menu_order' => $page->menu_order,
        'status'     => $page->post_status,
        'parent'     => $page->post_parent,
        'title'      => get_the_title($page),
        'excerpt'    => get_the_excerpt($page),
        'date'       => get_the_date('', $page),
        'content'    => apply_filters('the_content', $page->post_content),
        'author'     => get_the_author_meta('display_name', $page->post_author),
      ];

      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }

    $filtered_data = filter_fields([$data], $fields);
    return api_success_response('Page retrieved successfully.', $filtered_data[0]);
  } catch (Exception $err) {
    return api_server_error_response(['exception' => $err->getMessage()]);
  }
}