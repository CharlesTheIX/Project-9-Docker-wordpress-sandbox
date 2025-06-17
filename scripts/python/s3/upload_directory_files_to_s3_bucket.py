#!/usr/bin/env python

import os
import argparse
from pathlib import Path
from dotenv import load_dotenv
from s3.upload_file_to_s3_bucket import upload_file_to_s3_bucket

env_path = Path(__file__).resolve().parents[2] / ".env"
load_dotenv(dotenv_path=env_path)

def upload_directory_files_to_s3_bucket(directory_path, bucket_name, s3_prefix="", remove=None):
    """
    Recursively uploads all files in a directory to an S3 bucket, deleting files on success if the remove flag is included.

    :param directory_path: path name of local directory to run the script on
    :param bucket_name: Bucket to upload to
    :param s3_prefix: Prefix for the s3 object. If not specified then it will remain empty
    :param remove (optional): Removes the original object if set.

    :return void
    """

    if not os.path.isdir(directory_path):
        print(f"ERROR: Directory does not exist: {directory_path}")
        return
    if not bucket_name:
        print("ERROR: S3 bucket name is required.")
        return
    
    print(f"\nBeginning recursive upload:\nTarget directory: {directory_path}\nBucket name: {bucket_name}\nS3 prefix: {s3_prefix}")
    for root, _, files in os.walk(directory_path):
        for file_name in files:
            target_path = os.path.join(root, file_name)
            relative_path = os.path.relpath(target_path, directory_path)
            s3_path = os.path.join(s3_prefix, relative_path).replace(os.sep, "/")
            success = upload_file_to_s3_bucket(target_path, bucket_name, s3_path)

            if success:
                print(f"Successfully uploaded: {target_path}")
                if not remove is None:
                    os.remove(target_path)
                    print(f"Deleted: {target_path}")
            else:
                print(f"WARNING: Failed to upload: {target_path}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Upload the content of a directory to S3 and optionally deletes the uploaded files")
    parser.add_argument("directory_path", help="Path to the target directory")
    parser.add_argument("bucket_name", help="Name of the S3 bucket")
    parser.add_argument("--s3_prefix", default="", help="S3 prefix/path (optional)")
    parser.add_argument("--remove", help="Remove the original file (optional)")
    args = parser.parse_args()

    upload_directory_files_to_s3_bucket(args.directory_path, args.bucket_name, args.s3_prefix, args.remove)

