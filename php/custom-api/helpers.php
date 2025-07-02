<?php
function api_response($error, $status, $message, $data = null, $meta = null) {
  return new WP_REST_Response([
    'meta'    => $meta,
    'data'    => $data,
    'error'   => $error,
    'status'  => $status,
    'message' => $message,
  ], $status);
}
// 200 - success
function api_success_response($message, $data = null, $meta = null) {
  return api_response(false, 200, $message, $data, $meta);
}
// 204 - no content
function api_no_content_response($message, $meta = null) {
  return new WP_REST_Response([
    'status'  => 204,
    'data'    => null,
    'meta'    => $meta,
    'error'   => false,
    'message' => $message,
  ], 200);
}
// 400 - bad
function api_bad_response($message, $meta = null) {
  return api_response(true, 400, $message, null, $meta);
}
// 500 - server error
function api_server_error_response($data = null) {
  return api_response(true, 500, "An unexpected error occurred.", $data);
}

function filter_fields($items, $fields) {
  if (!$fields || !is_array($fields)) return $items;

  return array_map(function($item) use ($fields) {
    return array_filter($item, function($key) use ($fields) {
      return in_array($key, $fields);
    }, ARRAY_FILTER_USE_KEY);
  }, $items);
}

function hyve_check_basic_auth() {
  if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
      $auth = $_SERVER['HTTP_AUTHORIZATION'];

      if (stripos($auth, 'basic ') === 0) {
        $exploded = explode(':', base64_decode(substr($auth, 6)), 2);
        if (count($exploded) === 2) list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = $exploded;
      }
    }
  }

  $password = $_SERVER['PHP_AUTH_PW'] ?? '';
  $username = $_SERVER['PHP_AUTH_USER'] ?? '';
  if (!$username || !$password) return false;

  $user = wp_authenticate($username, $password);
  if (is_wp_error($user) || !$user || !$user->ID) return false;

  wp_set_current_user($user->ID);
  return true;
}

add_filter('rest_authentication_errors', function ($result) {
  if (!empty($result)) return $result;

  $request_uri = $_SERVER['REQUEST_URI'] ?? '';
  if (strpos($request_uri, '/wp-json/hyve/v1/') === 0) {
    return new WP_Error('rest_forbidden', 'Unauthorized access', ['status' => 401]);
  }

  return $result;
});

add_filter('rest_pre_serve_request', function ($served, $result, $request, $server) {
  if ($result instanceof WP_REST_Response && $result->get_status() === 401) {
    if (strpos($request->get_route(), '/hyve/v1/') === 0) {
      $custom = [
        'data'    => null,
        'status'  => 401,
        'error'   => true,
        'message' => 'Unauthorized',
      ];

      $result->set_data($custom);
      $server->send_header('Content-Type', 'application/json; charset=utf-8');
      echo wp_json_encode($custom);
      return true;
    }
  }

  return $served;
}, 10, 4);