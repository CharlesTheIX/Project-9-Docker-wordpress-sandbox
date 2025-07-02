<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
  register_rest_route('hyve/v1', '/menus', [
    'methods' => 'POST',
    'callback' => 'hyve_get_menus',
    'permission_callback' => 'hyve_check_basic_auth',
  ]);
});
function hyve_get_menus(WP_REST_Request $request) {
  try {
    $cache_ttl_seconds = 600;
    $cache_key = 'hyve_menu_types';
    $cache_group = 'hyve_api_menus';
    $data = wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      $data = get_nav_menu_locations();
      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }

    return api_success_response('Menu types retrieved successfully', $data);
  } catch (Exception $e) {
    return api_server_error_response(['exception' => $e->getMessage()]);
  }
}

add_action('rest_api_init', function () {
  register_rest_route('hyve/v1', '/menus/by-type', [
    'methods' => 'POST',
    'callback' => 'hyve_get_menu',
    'permission_callback' => 'hyve_check_basic_auth',
  ]);
});
function hyve_get_menu(WP_REST_Request $request) {
  try {
    $body_params = $request->get_json_params();

    $fields = $body_params['fields'] ?? null;
    $menu_type = sanitize_text_field($body_params['menu_type'] ?? "");
    if (!$menu_type) return api_error(400, 'Menu type required');

    $locations = get_nav_menu_locations();
    if (!isset($locations[$menu_type])) return api_error(404, 'Menu not found for the specified menu type');

    $cache_ttl_seconds = 600;
    $cache_group = 'hyve_api_menus';
    $cache_key = 'hyve_menu_' . $menu_type;

    $data = wp_cache_get($cache_key, $cache_group);

    if ($data === false) {
      $menu_id = $locations[$menu_type];
      $menu_items = wp_get_nav_menu_items($menu_id);
      if (!$menu_items) return api_error(404, 'No menu items found');

      $data = array_map(function($item) {
        return [
          'ID' => $item->ID,
          'url' => $item->url,
          'type' => $item->type,
          'title' => $item->title,
          'object' => $item->object,
          'object_id' => $item->object_id,
          'menu_order' => $item->menu_order,
          'parent' => $item->menu_item_parent,
        ];
      }, $menu_items);

      // usort($menu_data, function($a, $b) {
      //   return $a['menu_order'] <=> $b['menu_order'];
      // });

      wp_cache_set($cache_key, $data, $cache_group, $cache_ttl_seconds);
    }

    $filtered_data = filter_fields($data, $fields);
    return api_success_response("Menu retrieved successfully {$menu_type}.", $filtered_data);
  } catch (Exception $e) {
    return api_server_error_response(['exception' => $e->getMessage()]);
  }
}