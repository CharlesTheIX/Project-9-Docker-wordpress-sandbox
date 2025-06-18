#!/usr/bin/env python

import os
import sys
import argparse
from pathlib import Path
from datetime import datetime
from dotenv import load_dotenv
from botocore.exceptions import ClientError

sys.path.append(str(Path(__file__).resolve().parents[1]))
from s3.get_s3_client import get_s3_client;
from s3.ProgressPercentage import ProgressPercentage
from logger.log_content_to_file import log_content_to_file

env_path = Path(__file__).resolve().parents[2] / ".env"
load_dotenv(dotenv_path=env_path)

def upload_file_to_s3_bucket(file_path, bucket_name, s3_key=None):
    """
    Upload a file to an S3 bucket

    :param file_path: File path of the file to upload
    :param bucket_name: Bucket to upload to
    :param s3_key (optional): S3 object name. If not specified then file_name is used

    :return: True if file was uploaded, else False
    """
    
    log_file_name = os.getenv("LOG_FILE_NAME")
    error_log_file_name = os.getenv("ERROR_LOG_FILE_NAME")
    log_file_directory_path = os.getenv("LOG_FILE_DIR_PATH")
    log_content_to_file(log_file_directory_path, log_file_name, datetime.now().strftime("%d-%m-%Y_%H-%M-%S"))
    log_content_to_file(
        log_file_directory_path,
        log_file_name,
        f"Preparing to upload: {file_path} to s3://{bucket_name}/{s3_key or os.path.basename(file_path)}"
    )

    if not os.path.isfile(file_path):
        log_content_to_file(log_file_directory_path, error_log_file_name, f"Error: File does not exist: {file_path}")
        return False

    if not bucket_name:
        log_content_to_file(log_file_directory_path, error_log_file_name, f"ERROR: Bucket name must not be empty.")
        return False

    if s3_key is None:
        s3_key = os.path.basename(file_path)

    s3_client = get_s3_client()

    try:
        s3_client.upload_file(
            file_path,
            bucket_name,
            s3_key,
            Callback=ProgressPercentage(file_path),
        )
    except ClientError as e:
        log_content_to_file(log_file_directory_path, error_log_file_name, f"ERROR: Upload failed: {file_path} -> {e}\n")
        return False
    
    try:
        s3_client.head_object(Bucket=bucket_name, Key=s3_key)
        log_content_to_file(log_file_directory_path, log_file_name, f"{s3_key} successfully uploaded to {bucket_name}.\n")
        return True
    except ClientError:
        log_content_to_file(log_file_directory_path, error_log_file_name, f"ERROR: Upload reported success but object not found in S3: {s3_key}\n")
        return False

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Upload an object to an S3 bucket.")
    parser.add_argument("file_path", help="File path the file")
    parser.add_argument("bucket_name", help="Name of the S3 bucket")
    parser.add_argument("--s3_key", help="S3 key (optional)")
    args = parser.parse_args()

    upload_file_to_s3_bucket(args.file_path, args.bucket_name, args.s3_key)
