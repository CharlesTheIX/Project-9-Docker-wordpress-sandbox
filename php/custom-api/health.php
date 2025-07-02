<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
  register_rest_route('hyve/v1', '/health', [
    'methods' => 'GET',
    'callback' => 'hyve_health',
    'permission_callback' => '__return_true',
  ]);
});

function hyve_health(WP_REST_Request $request) {
  try {
    return api_response(false, 200, 'API healthy.');
  } catch (Exception $e) {
    return api_error(500, 'An unexpected error occurred.', ['exception' => $e->getMessage()]);
  }
}
