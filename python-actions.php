<?php
require_once get_template_directory() . '/python-functions.php';

// Fires when an attachment is created
add_action('add_attachment', 'handle_media_creation');
function handle_media_creation($post_id) {
  $mime_type = get_post_mime_type($post_id);
  if (strpos($mime_type, 'image/') !== 0) return;
  python_upload_file_to_s3_bucket($post_id, "original", true);
}

// Fired when attachment details are updated
// Not sure if this is needed as the actual image does not get updated, just the meta data within wordpress -- DavidC
add_action('attachment_updated', 'handle_media_update');
function handle_media_update($post_id) {
  $mime_type = get_post_mime_type($post_id);
  if (strpos($mime_type, 'image/') !== 0) return;
  // uncomment if you want to activate -- DavidC
  // python_upload_file_to_s3_bucket($post_id, "edit", true);
}

// Fires when the attachment image is edited (cropped, rotated rotated rtc)
add_filter('wp_save_image_editor_file', 'handle_attachment_cropped_or_scaled', 10, 5);
function handle_attachment_cropped_or_scaled($override, $filename, $image, $mime_type, $post_id) {
  if (strpos($mime_type, 'image/') !== 0) return $override;
  add_action('shutdown', function() use ($post_id, $filename) {
    if (file_exists($filename)) python_upload_file_to_s3_bucket($post_id, 'edit', true, $filename);
  });
  return $override;
}

// Fired on attachment deletion
add_action('delete_attachment', 'handle_media_deletion');
function handle_media_deletion($post_id) {
  $mime_type = get_post_mime_type($post_id);
  if (strpos($mime_type, 'image/') !== 0) return;
  python_delete_from_s3_bucket($post_id);
}

// Shows upload notice
add_action('admin_notices', 'python_admin_notice');
function python_admin_notice() {
  $screen = get_current_screen();
  if ($screen && $screen->id === 'upload' && $status = get_transient('python_status')) {
    switch ($status) {
      case "upload success": 
        echo '<div class="notice notice-success is-dismissible"><p>Python script executed successfully, file uploaded to S3.</p></div>';
        break;
      case "upload error":
        echo '<div class="notice notice-error is-dismissible"><p>Python script failed to upload the file to S3.</p></div>';
        break;
      case "deletion success":
        echo '<div class="notice notice-success is-dismissible"><p>Python script executed successfully, file sdeleted from S3.</p></div>';
        break;
      case "deletion error":
        echo '<div class="notice notice-error is-dismissible"><p>Python script failed to delete the file from S3.</p></div>';
        break;
    }

    delete_transient('python_status');
  }
}