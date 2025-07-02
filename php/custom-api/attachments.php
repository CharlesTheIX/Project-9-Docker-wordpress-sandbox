<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
  register_rest_route('hyve/v1', '/attachments', [
    'methods'             => 'POST',
    'permission_callback' => '__return_true',
    'callback'            => 'hyve_get_attachments',
  ]);
});
function hyve_get_attachments(WP_REST_Request $request) {
  try {
    $allowed_order = ['ASC', 'DESC'];
    $body_params = $request->get_json_params();
    $allowed_orderby = ['date', 'title', 'modified', 'author', 'name', 'ID', 'rand', 'comment_count'];

    $fields = $body_params['fields'] ?? null;
    $page = intval($body_params['page'] ?? 1);
    $per_page = intval($body_params['per_page'] ?? -1);
    $search = sanitize_text_field($body_params['search'] ?? '');
    $status = sanitize_text_field($body_params['status'] ?? 'inherit');
    $order_by = sanitize_text_field($body_params['order_by'] ?? "date");
    $order = sanitize_text_field(strtoupper($body_params['order'] ?? "DESC"));

    if (!in_array($order, $allowed_order)) $order = "DESC";
    if (!in_array($order_by, $allowed_orderby)) $order_by = "date";

    $query_args = [
      'post_status'    => $status,
      'posts_per_page' => $per_page,
      'post_type'      => 'attachment',
    ];
    
    if ($page < 1) $page = 1;
    if ($search) $query_args['s'] = $search;
    if ($per_page > 0) $query_args['paged'] = $page;

    $cache_ttl_seconds = 600;
    $cache_group = 'hyve_api_attachments';
    $cache_key = 'hyve_attachments_' . md5(json_encode($query_args));

    $data = wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      $query = new WP_Query($query_args);
      $attachments = array_map(function ($attachment) {
        $meta = wp_get_attachment_metadata($attachment->ID);
        return [
          'meta'     => $meta,
          'id'       => $attachment->ID,
          'slug'     => $attachment->post_name,
          'title'    => get_the_title($attachment),
          'date'     => get_the_date('', $attachment),
          'mime'     => get_post_mime_type($attachment),
          'url'      => wp_get_attachment_url($attachment->ID),
          'author'   => get_the_author_meta('display_name', $attachment->post_author),
        ];
      }, $query->posts);

      $data = [
        'attachments' => $attachments,
        'meta' => [
          'current_page' => $page,
          'per_page'     => $per_page,
          'total'        => (int) $query->found_posts,
          'total_pages'  => (int) $query->max_num_pages,
        ],
      ];

      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }

    $filtered_data = filter_fields($data['attachments'], $fields);
    if (count($filtered_data) == 0) return api_no_content_response("No attachments found.", $data['meta']);
    return api_success_response("Attachments retrieved successfully.", $filtered_data, $data['meta']);
  } catch (Exception $err) {
    return api_server_error_response(['exception' => $err->getMessage()]);
  }
} 

add_action('rest_api_init', function () {
  register_rest_route('hyve/v1', '/attachments/by-slug-id', [
    'methods'             => 'POST',
    'permission_callback' => '__return_true',
    'callback'            => 'hyve_get_attachments_by_slug_id',
  ]);
});
function hyve_get_attachments_by_slug_id(WP_REST_Request $request) {
  try {
    $body_params = $request->get_json_params();

    $fields = $body_params['fields'] ?? null;
    $attachment_id = intval($body_params['id'] ?? 0);
    $slug = sanitize_text_field($body_params['slug'] ?? "");

    if (!$attachment_id && !$slug) return api_bad_response('Attachment ID or slug is required.');

    $cache_ttl_seconds = 600;
    $cache_group = 'hyve_api_attachments';
    $cache_key = 'hyve_attachment' . '_' . ($attachment_id ? "id_{$attachment_id}" : "slug_{$slug}");

    $data= wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      if ($attachment_id) {
        $attachment = get_post($attachment_id);
      } else {
        $attachment = get_page_by_path($slug, OBJECT, 'attachment');
      }

      if (!$attachment || $attachment->post_type !== "attachment") return api_bad_response("Attachment not found.");
      $meta = wp_get_attachment_metadata($attachment->ID);
      $data = [
        'meta'     => $meta,
        'id'       => $attachment->ID,
        'slug'     => $attachment->post_name,
        'status'   => $attachment->post_status,
        'title'    => get_the_title($attachment),
        'date'     => get_the_date('', $attachment),
        'mime'     => get_post_mime_type($attachment),
        'url'      => wp_get_attachment_url($attachment->ID),
        'caption'  => wp_get_attachment_caption($attachment->ID),
        'author'   => get_the_author_meta('display_name', $attachment->post_author),
        'alt'      => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
      ];

      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }

    $filtered_data = filter_fields([$data], $fields);
    return api_success_response("Attachment retrieved successfully.", $filtered_data[0]);
  } catch (Exception $err) {
    return api_server_error_response(['exception' => $err->getMessage()]);
  }
}