#!/usr/bin/env python

import os
import argparse
from pathlib import Path
from dotenv import load_dotenv
from get_s3_client import get_s3_client
from botocore.exceptions import ClientError

env_path = Path(__file__).resolve().parents[2] / ".env"
load_dotenv(dotenv_path=env_path)

def download_file_from_s3_bucket(bucket_name, s3_key, destination_path=None):
    """
    Download a file to from an S3 bucket

    :param bucket_name: Bucket to download from 
    :param s3_key: S3 object name
    :param destination_path (optional): the path to the directory to store the downloaded file

    :return: True if file was downloaded, else False
    """

    print(f"\nPreparing to download: {s3_key} from s3://{bucket_name}/{s3_key}")

    if not bucket_name:
        print("ERROR: Bucket name is required.")
        return False

    if not s3_key:
        print("ERROR: S3 key is required.")
        return False

    s3_client = get_s3_client()

    if not destination_path:
        destination_path = os.path.basename(s3_key)

    print(f"\nDownloading s3://{bucket_name}/{s3_key} -> {destination_path}")

    try:
        s3_client.download_file(bucket_name, s3_key, destination_path)
        print("Download successful.")
        return True
    except ClientError as e:
        print(f"ERROR: Failed to download object: {e}")
        return False

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Download a file from an S3 bucket.")
    parser.add_argument("bucket_name", help="S3 bucket name")
    parser.add_argument("s3_key", help="Key of the s3 object to download")
    parser.add_argument("--destination_path", help="Local destination path (optional)")
    args = parser.parse_args()

    download_file_from_s3_bucket(args.bucket_name, args.s3_key, args.destination_path)
