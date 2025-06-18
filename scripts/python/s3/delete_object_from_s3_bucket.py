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
from logger.log_content_to_file import log_content_to_file

env_path = Path(__file__).resolve().parents[2] / ".env"
load_dotenv(dotenv_path=env_path)

def delete_file_from_s3_bucket(bucket_name, s3_key):
    """
    Delete a file to from an S3 bucket

    :param bucket_name: Bucket to download from 
    :param s3_key: S3 object name

    :return: True if file was deleted, else False
    """ 
    
    log_file_name = os.getenv("LOG_FILE_NAME")
    error_log_file_name = os.getenv("ERROR_LOG_FILE_NAME")
    log_file_directory_path = os.getenv("LOG_FILE_DIR_PATH")
    log_content_to_file(log_file_directory_path, log_file_name, datetime.now().strftime("%d-%m-%Y_%H-%M-%S"))
    log_content_to_file(
        log_file_directory_path,
        log_file_name,
        f"Preparing to delete: {s3_key} from s3://{bucket_name}/{s3_key}"
    )

    if not bucket_name:
        log_content_to_file(log_file_directory_path, error_log_file_name, "ERROR: Bucket name is required.")
        return False

    if not s3_key:
        log_content_to_file(log_file_directory_path, error_log_file_name, "ERROR: S3 key is required.")
        return False

    s3_client = get_s3_client()
    log_content_to_file(log_file_directory_path, log_file_name, f"Deleting s3://{bucket_name}/{s3_key}...")

    try:
        s3_client.delete_object(Bucket=bucket_name, Key=s3_key)
        log_content_to_file(log_file_directory_path, log_file_name, "File deleted successfully.\n")
        return True
    except ClientError as e:
        log_content_to_file(log_file_directory_path, error_log_file_name, f"ERROR: Failed to delete file: {e}\n")
        return False

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Delete a file from an S3 bucket.")
    parser.add_argument("bucket_name", help="Name of the S3 bucket")
    parser.add_argument("s3_key", help="Key of the object to delete")
    args = parser.parse_args()

    delete_file_from_s3_bucket(args.bucket_name, args.s3_key)
