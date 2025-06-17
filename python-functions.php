<?php
$TRANSIENT_TIME = 30;
$BUCKET_NAME = "hyve-lg-01";
$TRANSIENT_TAG = 'python_status';
$S3_META_TAG_EDITS = "hyve_s3_url_edits";
$S3_META_TAG_ORIGINAL = "hyve_s3_url_original";
$S3_META_TAG_EDITS_WEBP = "hyve_s3_url_edits_webp";
$S3_META_TAG_ORIGINAL_WEBP = "hyve_s3_url_original_webp";
$PYTHON_S3_UPLOAD_SCRIPT_PATH = "/opt/scripts/python/s3/upload_file_to_s3_bucket.py";

function python_upload_file_to_s3_bucket($post_id, $update_type, $convert_to_webp, $file_name = null) { 
  if ($convert_to_webp) python_convert_and_save_image_to_webp($post_id, $update_type, $file_name);

  global $BUCKET_NAME;
  global $TRANSIENT_TAG;
  global $TRANSIENT_TIME;
  global $S3_META_TAG_EDITS;
  global $S3_META_TAG_ORIGINAL;
  global $PYTHON_S3_UPLOAD_SCRIPT_PATH;
  $upload_dir = wp_get_upload_dir();
  $base_dir = $upload_dir['basedir'];
  $full_path = $file_name ? $file_name : get_attached_file($post_id);
  $relative_path = str_replace($base_dir, '', $full_path);
  
  $s3_key = ltrim("wordpress" . $relative_path, '/');
  $command = escapeshellcmd("python3 '$PYTHON_S3_UPLOAD_SCRIPT_PATH' '$full_path' '$BUCKET_NAME' '--s3_key=$s3_key'");

  $output = [];
  $return_var = 1;
  exec("$command", $output, $return_var);
  
  if ($return_var === 0) {
    switch ($update_type) {
      case "original":
        update_post_meta($post_id, $S3_META_TAG_ORIGINAL, $s3_key);
        break;
      case "edit":
        $s3_key_edit = get_post_meta($post_id, $S3_META_TAG_EDITS, true);

        if ($s3_key_edit) {
          $value = json_decode($s3_key_edit);
          $value[] = $s3_key;
        } else {
          $value = [$s3_key];
        }

        $value = json_encode($value);
        update_post_meta($post_id, $S3_META_TAG_EDITS, $value);
        break;
    }

    set_transient("$TRANSIENT_TAG", 'upload success', $TRANSIENT_TIME);
  } else {
    set_transient("$TRANSIENT_TAG", 'upload error', $TRANSIENT_TIME);
  }
}

function python_delete_from_s3_bucket($post_id) {
  global $BUCKET_NAME;
  global $TRANSIENT_TAG;
  global $S3_ACCOUNT_ID;
  global $TRANSIENT_TIME;
  global $S3_META_TAG_EDITS;
  global $S3_META_TAG_ORIGINAL;
  global $S3_META_TAG_EDITS_WEBP;
  global $S3_META_TAG_ORIGINAL_WEBP;
  $s3_key_edit = get_post_meta($post_id, $S3_META_TAG_EDITS, true);
  $s3_key_original = get_post_meta($post_id, $S3_META_TAG_ORIGINAL, true);
  $s3_key_edit_webp = get_post_meta($post_id, $S3_META_TAG_EDITS_WEBP, true);
  $s3_key_original_webp = get_post_meta($post_id, $S3_META_TAG_ORIGINAL_WEBP, true);
  $PYTHON_S3_DELETION_SCRIPT_PATH = "/opt/scripts/python/s3/delete_object_from_s3_bucket.py";

  if ($s3_key_original) {
    $output = [];
    $return_var_original = 1;
    $command_original = escapeshellcmd("python3 '$PYTHON_S3_DELETION_SCRIPT_PATH' '$BUCKET_NAME' '$s3_key_original'");
    exec("$command_original", $output, $return_var_original);

    if ($return_var_original === 0) {
      set_transient("$TRANSIENT_TAG", 'deletion success', $TRANSIENT_TIME);
    } else {
      set_transient("$TRANSIENT_TAG", 'deletion error', $TRANSIENT_TIME);
    }
  }

  if ($s3_key_original_webp) {
    $output = [];
    $return_var_original_webp = 1;
    $command_original_webp = escapeshellcmd("python3 '$PYTHON_S3_DELETION_SCRIPT_PATH' '$BUCKET_NAME' '$s3_key_original_webp'");
    exec("$command_original_webp", $output, $return_var_original_webp);

    if ($return_var_original_webp === 0) {
      set_transient("$TRANSIENT_TAG", 'deletion success', $TRANSIENT_TIME);
    } else {
      set_transient("$TRANSIENT_TAG", 'deletion error', $TRANSIENT_TIME);
    }
  }
  
  if ($s3_key_edit) {
    $output = [];
    $return_var_edit = 1;
    $s3_key_edit = json_decode($s3_key_edit);

    foreach($s3_key_edit as $value) {
      $command_edit = escapeshellcmd("python3 '$PYTHON_S3_DELETION_SCRIPT_PATH' '$BUCKET_NAME' '$value'");
      exec("$command_edit", $output, $return_var_edit);

      if ($return_var_edit === 0) {
        set_transient("$TRANSIENT_TAG", 'deletion success', $TRANSIENT_TIME);
      } else {
        set_transient("$TRANSIENT_TAG", 'deletion error', $TRANSIENT_TIME);
      }
    }
  }
  
  if ($s3_key_edit_webp) {
    $output = [];
    $return_var_edit_webp = 1;
    $s3_key_edit_webp = json_decode($s3_key_edit_webp);

    foreach($s3_key_edit_webp as $value) {
      $command_edit_webp = escapeshellcmd("python3 '$PYTHON_S3_DELETION_SCRIPT_PATH' '$BUCKET_NAME' '$value'");
      exec("$command_edit_webp", $output, $return_var_edit_webp);

      if ($return_var_edit_webp === 0) {
        set_transient("$TRANSIENT_TAG", 'deletion success', $TRANSIENT_TIME);
      } else {
        set_transient("$TRANSIENT_TAG", 'deletion error', $TRANSIENT_TIME);
      }
    }
  }
}

function python_convert_and_save_image_to_webp($post_id, $update_type, $file_name = null) { 
  $upload_dir = wp_get_upload_dir();
  $base_dir = $upload_dir['basedir'];
  $full_path = $file_name ? $file_name : get_attached_file($post_id);
  $relative_path = str_replace($base_dir, '', $full_path);
  $extension = pathinfo($full_path, PATHINFO_EXTENSION);
  
  if (!$extension || $extension === "webp") return;
  
  $PYTHON_IMAGE_CONVERSION_SCRIPT_PATH = "/opt/scripts/python/images/convert_image_to_webp.py";
  $command = escapeshellcmd("python3 '$PYTHON_IMAGE_CONVERSION_SCRIPT_PATH' '$full_path'");
  
  $output = [];
  $return_var = 1;
  exec("$command", $output, $return_var);
  
  if ($return_var !== 0) return;
  
  global $BUCKET_NAME;
  global $S3_META_TAG_EDITS_WEBP;
  global $S3_META_TAG_ORIGINAL_WEBP;
  global $PYTHON_S3_UPLOAD_SCRIPT_PATH;
  $full_path_info = pathinfo($full_path);
  $relative_path_info = pathinfo($relative_path);
  $webp_full_path = $full_path_info['dirname'] . '/' . $full_path_info['filename'] . '.webp';
  $webp_relative_path = $relative_path_info['dirname'] . '/' . $relative_path_info['filename'] . '.webp';
  
  $s3_key = ltrim("wordpress" . $webp_relative_path, '/');
  $command = escapeshellcmd("python3 '$PYTHON_S3_UPLOAD_SCRIPT_PATH' '$full_path' '$BUCKET_NAME' '--s3_key=$s3_key'");

  $output = [];
  $return_var = 1;
  exec("$command", $output, $return_var);

  if ($return_var !== 0) return;
  
  switch ($update_type) {
    case "original":
      update_post_meta($post_id, $S3_META_TAG_ORIGINAL_WEBP, $s3_key);
      break;
    case "edit":
      $s3_key_edit = get_post_meta($post_id, $S3_META_TAG_EDITS_WEBP, true);

      if ($s3_key_edit) {
        $value = json_decode($s3_key_edit);
        $value[] = $s3_key;
      } else {
        $value = [$s3_key];
      }

      $value = json_encode($value);
      update_post_meta($post_id, $S3_META_TAG_EDITS_WEBP, $value);
      break;
  }
}