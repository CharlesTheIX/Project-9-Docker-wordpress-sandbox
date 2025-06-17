#!/usr/bin/env python

import os
import argparse
from PIL import Image
from pathlib import Path

def convert_image_to_webp(image_path, remove=None):
    """
    Converts a single image (.jpg, .jpeg, or .png) to .webp format.
    Optionally deletes the original file on success.

    :param image_path: Path to the image file.
    :param remove (optional): Removes the original image if set.

    :return void
    """
    print("python test")
    image_path = Path(image_path)
    image_extensions = (".jpg", ".jpeg", ".png")

    if not image_path.is_file() or image_path.suffix.lower() not in image_extensions:
        print(f"ERROR: '{image_path}' is not a valid image file of supported types: {image_extensions}")
        return

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
    parser = argparse.ArgumentParser(description="Convert a single image to WebP format.")
    parser.add_argument("image_path", help="Path to the image file to convert")
    parser.add_argument("--remove", help="Remove the original file after conversion")
    args = parser.parse_args()

    convert_image_to_webp(args.image_path, args.remove)
