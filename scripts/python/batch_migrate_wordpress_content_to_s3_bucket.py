#!/usr/bin/env python

import os
import shutil
import argparse
from pathlib import Path
from datetime import datetime
from dotenv import load_dotenv
from logger.log_content_to_file import log_content_to_file
from images.convert_directory_images_to_webp import convert_directory_images_to_webp
from s3.upload_directory_files_to_s3_bucket import upload_directory_files_to_s3_bucket

env_path = Path(__file__).resolve().parents[1] / ".env"
load_dotenv(dotenv_path=env_path)

def batch_migrate_wordpress_content_to_s3_bucket(convert_to_webp=None):
    """
    Scans through the WordPress content folder and copies a defined set of files to a temp folder.
    The files are then uploaded to an S3 bucket.
    A log is created for successful and non-successful uploads and saved at a defined location.
    The temp folder is then deleted.

    :param convert_to_webp (optional): Converts the images of a defined format to webp format is this flag is True.

    :return void
    """
    
    log_file_name = os.getenv("LOG_FILE_NAME")
    error_log_file_name = os.getenv("ERROR_LOG_FILE_NAME")
    log_file_directory_path = os.getenv("LOG_FILE_DIR_PATH")
    directory_path = os.getenv("WORDPRESS_CONTENT_DIR_PATH")
    log_content_to_file(log_file_directory_path, log_file_name, datetime.now().strftime("%d-%m-%Y_%H-%M-%S"))
    log_content_to_file(
        log_file_directory_path,
        log_file_name,
        f"Batch migrate WordPress content to s3 bucket started | convert_to_webp: {convert_to_webp} | Directory path: {directory_path}"
    )

    if not os.path.exists(directory_path) or not os.path.isdir(directory_path):
        log_content_to_file(log_file_directory_path, error_log_file_name, f"Error: Directory does not exist: {directory_path}")
        return

    target_files = []
    allowed_extensions = os.getenv("WORDPRESS_IMAGE_FORMATS")

    for file_name in os.listdir(directory_path):
        file_path = os.path.join(directory_path, file_name)

        if os.path.isfile(file_path) and os.path.splitext(file_name)[1].lower() in allowed_extensions:
            target_files.append(file_path.replace(os.sep, "/"))

    if len(target_files) == 0:
        log_content_to_file(log_file_directory_path, log_file_name, "No files to upload.")
        return

    temp_directory_path = os.getenv("WORDPRESS_TEMP_CONTENT_DIR_PATH")

    if os.path.isdir(temp_directory_path):
        log_content_to_file(log_file_directory_path, error_log_file_name, f"The directory already exists: {temp_directory_path}")
        return
  
    os.makedirs(temp_directory_path)
    log_content_to_file(log_file_directory_path, log_file_name, f"Temp directory created at {temp_directory_path}.")
    log_content_to_file(log_file_directory_path, log_file_name, "Copying files to temp directory.")

    for target_file in target_files:
        file_name = os.path.basename(target_file)
        target_path = os.path.join(temp_directory_path, file_name)
        shutil.copy2(target_file, target_path)
        log_content_to_file(log_file_directory_path, log_file_name, f"Copied {target_file} to {target_path}")

    if not convert_to_webp == False:
        log_content_to_file(log_file_directory_path, log_file_name, "Converting files to webp.")
        convert_directory_images_to_webp(temp_directory_path, True)

    log_content_to_file(log_file_directory_path, log_file_name, "Uploading files to S3.")
    bucket_name = os.getenv("S3_BUCKET_NAME")
    s3_prefix = os.getenv("WORDPRESS_S3_PREFIX")
    upload_directory_files_to_s3_bucket(temp_directory_path, bucket_name, s3_prefix, True) 

    shutil.rmtree(temp_directory_path)
    log_content_to_file(log_file_directory_path, log_file_name, f"Temp directory removed.")
    log_content_to_file(log_file_directory_path, log_file_name, f"Bath migrate WordPress content to s3 bucket complete.")
    print(f"\nView the log files for more details: {log_file_directory_path}\n")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Upload the content of the WordPress content directory to S3 bucket")
    parser.add_argument("--convert_to_webp", help="Convert image files to webp format (optional)")
    args = parser.parse_args()

    batch_migrate_wordpress_content_to_s3_bucket(args.convert_to_webp)
