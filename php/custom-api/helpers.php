<?php
  add_filter('rest_post_dispatch', function($response, $server, $request) {
    if ($response instanceof WP_Error) {
      $code = $response->get_error_code();
      $data = $response->get_error_data();
      $message = $response->get_error_message();
      $status = isset($data['status']) ? $data['status'] : 500;

      $custom_response = api_response(true, $status, $message);
      return new WP_REST_Response($custom_response->get_data(), $status);
    }
    return $response;
  }, 10, 3);

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
