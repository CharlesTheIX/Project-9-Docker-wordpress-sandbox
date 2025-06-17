#!/usr/bin/env python

import argparse
from pathlib import Path
from dotenv import load_dotenv
from get_s3_client import get_s3_client
from botocore.exceptions import ClientError

env_path = Path(__file__).resolve().parents[2] / ".env"
load_dotenv(dotenv_path=env_path)

def copy_object_from_s3_bucket(source_bucket_name, source_key, destination_bucket_name, destination_key):
    """
    Copy an object from one location in S3 to another.

    :param source_bucket_name: Name of the source bucket
    :param source_key: Key of the source object
    :param destination_bucket_name: Name of the destination bucket
    :param destination_key: Key of the destination object

    :return: void
    """

    if not source_bucket_name:
        print("ERROR: Source bucket name is required.")
        return False

    if not source_key:
        print("ERROR: Source key is required.")
        return False

    if not destination_bucket_name:
        print("ERROR: Destination bucket name is required.")
        return False

    if not destination_key:
        print("ERROR: Destination key is required.")
        return False

    s3_client = get_s3_client()
    source_details = {
        'Bucket': source_bucket_name,
        'Key': source_key
    }

    try:
        print(f"Copying s3://{source_bucket_name}/{source_key} to s3://{destination_bucket_name}/{destination_key}...")
        s3_client.copy_object(
            CopySource=source_details,
            Bucket=destination_bucket_name,
            Key=destination_key
        )
        print("Copy completed successfully.")
    except ClientError as e:
        print(f"ERROR: Failed to copy object: {e}")

# Optional CLI interface
if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Copy an object in S3.")
    parser.add_argument("source_bucket_name", help="Source S3 bucket name")
    parser.add_argument("source_key", help="Source S3 object key")
    parser.add_argument("destination_bucket_name", help="Destination S3 bucket name")
    parser.add_argument("destination_key", help="Destination S3 object key")
    args = parser.parse_args()

    copy_object_from_s3_bucket(args.source_bucket_name, args.source_key, args.destination_bucket_name, args.destination_key)
