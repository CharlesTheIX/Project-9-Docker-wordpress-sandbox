#!/usr/bin/env python

import argparse
from pathlib import Path
from dotenv import load_dotenv
from get_s3_client import get_s3_client

env_path = Path(__file__).resolve().parents[2] / ".env"
load_dotenv(dotenv_path=env_path)

def list_s3_buckets():
    """
    Lists the S3 buckets on the configured account

    :return: void
    """

    print("\nFetching S3 Buckets...")
    s3_client = get_s3_client();
    response = s3_client.list_buckets()

    print("\nBuckets:")
    for bucket in response['Buckets']:
        name = bucket['Name']
        creation_date = bucket['CreationDate'].strftime("%d-%m-%Y %H:%M:%S %Z");
        print(f" - Name: {name} | CreationDate: {creation_date}")

    print("\n")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="List the collection of S3 buckets.")
    args = parser.parse_args()

    list_s3_buckets()