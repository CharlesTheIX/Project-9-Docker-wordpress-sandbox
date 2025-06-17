#!/usr/bin/env python

import argparse
from pathlib import Path
from dotenv import load_dotenv
from get_s3_client import get_s3_client

env_path = Path(__file__).resolve().parents[2] / ".env"
load_dotenv(dotenv_path=env_path)

def list_objects_in_s3_bucket(bucket_name): 
    """
    List the object in an S3 bucket

    :param bucket_name: Bucket to list objects from 

    :return: void
    """

    if not bucket_name:
        print("ERROR: S3 bucket name is required.")
        return

    print(f"\nFetching Objects from S3 Bucket {bucket_name}...")
    s3_client = get_s3_client();
    response = s3_client.list_objects_v2(Bucket=bucket_name)

    if "Contents" in response:
        print("\nObjects in bucket:")
        for item in response["Contents"]:
            key = item['Key']
            size = item['Size'] 
            etag = item['ETag']
            storage_class = item['StorageClass'] 
            last_modified = item['LastModified'].strftime("%d-%m-%Y %H:%M:%S %Z")
            print(f"- Key: {key} | Size: {size} bytes | Last Modified: {last_modified} | Etag: {etag} | Storage Class: {storage_class}")
    else:
        print("\nBucket is empty or does not exist.")

    print("\n")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="List the objects with an S3 bucket.")
    parser.add_argument("bucket_name", help="Name of the S3 bucket")
    args = parser.parse_args()

    list_objects_in_s3_bucket(args.bucket_name)