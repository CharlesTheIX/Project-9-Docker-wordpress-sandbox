import os
import boto3
from pathlib import Path
from dotenv import load_dotenv

env_path = Path(__file__).resolve().parents[2] / ".env"
load_dotenv(dotenv_path=env_path)

def get_s3_client():
    return boto3.client(
        's3',
        aws_access_key_id=os.getenv("S3_ACCESS_KEY_ID"),
        aws_secret_access_key=os.getenv("S3_SECRET_ACCESS_KEY"),
        endpoint_url=f"https://{os.getenv('S3_ACCOUNT_ID')}.r2.cloudflarestorage.com",
        region_name="auto",
    )