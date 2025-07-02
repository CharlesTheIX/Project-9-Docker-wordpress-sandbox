<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
  register_rest_route(CUSTOM_API_NAMESPACE . '/v1', '/taxonomies', [
    'methods'             => 'POST',
    'callback'            => 'hyve_get_taxonomies',
    'permission_callback' => 'hyve_check_basic_auth',
  ]);
});
function hyve_get_taxonomies(WP_REST_Request $request) {
  try {
    $allowed_order = ['ASC', 'DESC'];
    $body_params = $request->get_json_params();
    $allowed_orderby = ['name', 'slug', 'count', 'term_id', 'id'];

    $parent = $body_params['parent'] ?? null;
    $fields = $body_params['fields'] ?? null;
    $search = sanitize_text_field($body_params['search'] ?? "");
    $order_by = sanitize_text_field($body_params['order_by'] ?? "name");
    $order = sanitize_text_field(strtoupper($body_params['order'] ?? "ASC"));
    $taxonomy = sanitize_text_field($request->get_param('taxonomy') ?? 'category');

    if (!taxonomy_exists($taxonomy)) return api_bad_response("Taxonomy {$taxonomy} does not exist.");

    if (!in_array($order, $allowed_order)) $order = "ASC";
    if (!in_array($order_by, $allowed_orderby)) $order_by = "name";
    $query_args = [
      'hide_empty' => false,
      'order'      => $order,
      'taxonomy'   => $taxonomy,
      'orderby'    => $order_by,
    ];

    if ($search) $query_args['search'] = $search;
    if ($parent) $query_args['parent'] = intval($parent);

    $cache_ttl_seconds = 600;
    $cache_group = 'hyve_api_taxonomies';
    $cache_key = 'hyve_taxonomies' . '_' . md5(json_encode($query_args));

    $data= wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      $terms = get_terms($query_args);
      if (is_wp_error($terms) || empty($terms)) return api_no_content_response("No terms found in taxonomy {$taxonomy}.");

      $data = array_map(function($term) {
        return [
          'name'        => $term->name,
          'slug'        => $term->slug,
          'count'       => $term->count,
          'parent'      => $term->parent,
          'id'          => $term->term_id,
          'description' => $term->description,
        ];
      }, $terms);

      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }
    
    $filtered_data = filter_fields($data, $fields);
    return api_success_response('Taxonomies retrieved successfully.', $filtered_data);
  } catch (Exception $e) {
    return api_server_error_response(['exception' => $e->getMessage()]);
  }
}

add_action('rest_api_init', function () {
  register_rest_route(CUSTOM_API_NAMESPACE . '/v1', '/taxonomies/by-slug-id', [
    'methods'             => 'POST',
    'permission_callback' => 'hyve_check_basic_auth',
    'callback'            => 'hyve_get_taxonomy_by_slug_id',
  ]);
});
function hyve_get_taxonomy_by_slug_id(WP_REST_Request $request) {
  try {
    $body_params = $request->get_json_params();

    $fields = $body_params['fields'] ?? null;
    $taxonomy = sanitize_text_field($request->get_param('taxonomy') ?? 'category');

    if (!taxonomy_exists($taxonomy)) return api_bad_response("Taxonomy {$taxonomy} does not exist.");

    $id = isset($body_params['id']) ? intval($body_params['id']) : null;
    $slug = isset($body_params['slug']) ? sanitize_text_field($body_params['slug']) : null;

    if (!$id && !$slug) return api_bad_response('You must provide a id or slug.');

    $cache_ttl_seconds = 600;
    $cache_group = 'hyve_api_taxonomies';
    $cache_key = 'hyve_taxonomies' . '_' . md5(json_encode($query_args));

    $data = wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      if ($id) {
        $term = get_term($id, $taxonomy);
      } else {
        $term = get_term_by('slug', $slug, $taxonomy);
      }

      if (!$term || is_wp_error($term)) return api_no_content_response("Term not found in taxonomy {$taxonomy}.");

      $data = [
        'name'        => $term->name,
        'slug'        => $term->slug,
        'count'       => $term->count,
        'parent'      => $term->parent,
        'id'          => $term->term_id,
        'description' => $term->description,
      ];

      wp_cache_set($cache_key, $taxonomy_data, $cache_group, $cache_ttl_seconds);
    }

    $filtered_data = filter_fields([$data], $fields);
    return api_success_response('Taxonomy retrieved successfully.', $filtered_data[0]);
  } catch (Exception $err) {
    return api_server_error_response(['exception' => $err->getMessage()]);
  }
}

add_action('rest_api_init', function () {
  register_rest_route(CUSTOM_API_NAMESPACE . '/v1', '/taxonomy-types', [
    'methods'             => 'POST',
    'permission_callback' => 'hyve_check_basic_auth',
    'callback'            => 'hyve_get_taxonomy_types',
  ]);
});
function hyve_get_taxonomy_types(WP_REST_Request $request) {
  try { 
    $body_params = $request->get_json_params();

    $fields = $body_params['fields'] ?? null;

    $cache_ttl_seconds = 600;
    $cache_key = 'hyve_taxonomy_types';
    $cache_group = 'hyve_api_taxonomies';
    $data = wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      $taxonomies = get_taxonomies(['public' => true], 'objects');
      $data = [];

      foreach ($taxonomies as $taxonomy) {
        $data[] = [
          'name'         => $taxonomy->name,
          'label'        => $taxonomy->label,
          'public'       => $taxonomy->public,
          'description'  => $taxonomy->description,
          'object_type'  => $taxonomy->object_type,
          'hierarchical' => $taxonomy->hierarchical,
        ];
      }

      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }

    $filtered_data = filter_fields($data, $fields);
    return api_success_response("Taxonomy types retrieved successfully.", $filtered_data);
  } catch (Exception $err) {
    return api_server_error_response(['exception' => $err->getMessage()]);
  }
}