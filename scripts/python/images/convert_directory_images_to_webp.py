#!/usr/bin/env python

import os
import argparse
from PIL import Image
from pathlib import Path

def convert_directory_images_to_webp(directory_path, remove=None):
    """
    Converts all .jpg, .jpeg, and .png images in the given directory to .webp format.
    Optionally deletes the original file on success.

    :param directory_path: The path of the directory to iterate through and update child images.
    :param remove (optional): Removes the original image if set.

    :return void
    """

    directory = Path(directory_path)
    image_extensions = (".jpg", ".jpeg", ".png")

    if not directory.is_dir():
        print(f"ERROR: '{directory_path}' is not a valid directory.")
        return

    for image_path in directory.rglob("*"):
        if image_path.suffix.lower() in image_extensions:
            output_path = image_path.with_suffix(".webp")

            try:
                image = Image.open(image_path).convert("RGB")
                image.save(output_path, "webp")
                print(f"Converted: {image_path} -> {output_path}")

                if not remove is None:
                    os.remove(image_path)
                    print(f"Deleted: {image_path}")
            except Exception as e:
                print(f"Failed to convert {image_path}: {e}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Convert images to WebP format.")
    parser.add_argument("directory_path", help="Directory path to scan for images")
    parser.add_argument("--remove", help="Remove the original file (optional)")
    args = parser.parse_args()

    convert_directory_images_to_webp(args.directory_path, args.remove)
