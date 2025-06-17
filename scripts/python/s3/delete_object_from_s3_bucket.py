#!/usr/bin/env python

import argparse
from pathlib import Path
from dotenv import load_dotenv
from get_s3_client import get_s3_client
from botocore.exceptions import ClientError

env_path = Path(__file__).resolve().parents[2] / ".env"
load_dotenv(dotenv_path=env_path)

def delete_file_from_s3_bucket(bucket_name, s3_key):
    """
    Delete a file to from an S3 bucket

    :param bucket_name: Bucket to download from 
    :param s3_key: S3 object name

    :return: True if file was deleted, else False
    """

    if not bucket_name:
        print("ERROR: Bucket name is required.")
        return False

    if not s3_key:
        print("ERROR: S3 key is required.")
        return False

    s3_client = get_s3_client()

    print(f"\nDeleting s3://{bucket_name}/{s3_key}...")

    try:
        s3_client.delete_object(Bucket=bucket_name, Key=s3_key)
        print("File deleted successfully.")
        return True
    except ClientError as e:
        print(f"ERROR: Failed to delete file: {e}")
        return False

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Delete a file from an S3 bucket.")
    parser.add_argument("bucket_name", help="Name of the S3 bucket")
    parser.add_argument("s3_key", help="Key of the object to delete")
    args = parser.parse_args()

    delete_file_from_s3_bucket(args.bucket_name, args.s3_key)
