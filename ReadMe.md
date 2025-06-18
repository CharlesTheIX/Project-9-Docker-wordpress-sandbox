# Dockerised WordPress orchestration with Python integration

## Contents

- [Notes](#notes)
- [Introduction](#introduction)
- [Overview](#overview)
- [Requirements](#requirements)
- [Launching the orchestration](#launching-the-orchestration)
- [Integrating Python and scripts into existing Wordpress installation](#integrating-python-and-scripts-into-existing-wordpress-installation)
- [How the scripts work](#how-the-scripts-work)
  - [Uploading new media items](#uploading-new-media-items)
  - [Editing media metadata](#editing-media-metadata)
  - [Editing media items](#editing-media-items)
  - [Deleting media items](#deleting-media-items)
- [Development](#development)

## Notes

- **This document goes through the set up of `WordPress` and `Python` within the `Docker` environment.**
- **If you just want to integrate the `Python` scripts with `PHP` then [skip to here](#integrating-python-and-scripts-into-existing-wordpress-installation).**
- **Do not move the location of the `scripts` directory, unless you update the `Dockerfile` in accordance with the file structure change.**
- **It may or may not be the case that the user will need to change the mode of the following `Python` files so that they are executable: `convert_image_to_webp.py`, `delete_object_from_s3_bucket.py`, and `upload_file_to_s3_bucket.py`.**
- **The bucket name is set within the [python-functions.php](./php/python-functions.php) file, however it is recommended to set this value in a custom `ACF` field within an options page or within the machines environment variables. The code will need to be updated to reflect this.**
- **The locations for the paths the the `Python` scripts are set within the [python-functions.php](./php/python-functions.php) file, however it is recommended to set these values in a custom `ACF` field within an options page or within the machines environment variables. The code will need to be updated to reflect this.**

## Introduction

This document will review how to set up a `WordPress` and `MySQL` orchestration with `Python` integrated so that automation scripts can be run.

In this example, `PHP` is used to hook into the native `WordPress` hooks and actions and run `Python` scripts to sync the media library with an external `Cloudflare R2 (S3) bucket`.

When an attachment is created, updated or deleted, an action is fired within `WordPress`, and `PHP` is used to execute `Python` scripts to manage the `CRUD` operations with regards to the `S3` bucket.

Additionally, there is an option to convert images to webp via the `Pillow` dependency in `Python` when being synced with the `S3` bucket, if the attachment is not already in the webp format.

Finally, this documentation assumes the user has some understanding of the technologies used for this example: `PHP`, `Python`, `Docker`, `WordPress` and `S3`.

## Overview

Within the root of this project you will find a [Dockerfile](./Dockerfile) and a [docker-compose.yml](./docker-compose.yml) file.

The `docker-compose.yml` file is used to manage the orchestration of `MySQL` and `WordPress` containers.

The `MySQL` container is built from a default image (`mysql:5.7`), however the `WordPress` image has been customised to install `python3` (the latest version of the Python programming language), `python-venv` (allows Python to run within it's own virtual environment so that it does not conflict with system Python operations), and `pip` (a package manager for Python).

After the installation of the `Python` on the machine, the [requirements.txt](./scripts/python/requirements.txt) is copied over and used to install the `Python` specific dependencies, and then all scripts are copied over to the `opt` directory within the machine.

The `opt` directory has been chosen here due to the `docker-compose.yml` configuration overwriting the `var/www/html/` directory and it seemed an appropriate directory due to this directory also containing the `pip` binaries.

## Requirements

To use the `Docker` files locally you will need to have `Docker Desktop` installed on your machine ([see here for more info](https://www.docker.com/products/docker-desktop/)).

Additionally you will need a copy of the `.env` file stored in the same directory as the [.env.example](./scripts/.env.example) file - this `.env.example` file contains the names of all the environment variables used in this project, they will need to be assigned values and the file name will need to be changed from `.env.example` to `.env`.

For testing the scripts locally you will need to have `Python` and `pip` installed on your local machine.

This documentation assumes the user understands how to set up, install dependencies, and run `Python` scripts locally.

## Launching the orchestration

This walk through assumes that you are running this docker environment on your local machine, or in a container environment using a provider such as `AWS ECS and ECR`.

Additionally, the `.env.example` has pre-configured variables for the python logging - if this want to be edited then please update the `Dockerfile` and make sure that the permissions of the target directory are set to write for `Python`.

1. Start `Docker Desktop` so that the `Docker Daemon` is running.

2. Open your terminal and navigate to the root of this directory:

```bash
  cd path/to/dir/
```

3. Run the appropriate `Docker command` to 'stand up' you `Docker orchestration`. This will download (pull) the required base images, and create the required volumes (directories) within the project. If errors are encountered, it is suggested you use an AI agent (eg.`ChatGPT`) to assist.

```bash
  docker compose up --build
```

4. Once this command has completed you should see in the terminal the output for the `MySQL` and `WordPress` containers. Additionally, two directories should now be created within the root directory: `mysql` and `wordpress`. These directories act as the volumes for the container.

5. Navigate to [http://localhost:5022](http://localhost:5022), the port set within the `docker-compose.yml` file, to begin set-up of the `WordPress` installation. Follow the steps as you normally would with any new `WordPress` installation. If you wish to clean up the default set-up and install plugins etc. then that can be done now.

6. Within the codebase, move the two `PHP` files [python-actions.php](./php/python-actions.php) and [python-functions.php](./php/python-functions.php) to and appropriate directory within the active `WordPress` theme - by default the active theme for this project is set to `twentytwentfive`. These files contain the code to connect with the `Python` scripts and hooks / actions for the `WordPress` events. This example assumes that the files are copied over to the root of the theme, ie the same location as the `functions.php` file. If the files have been copied to a different location then line 2 of `python-actions.php` will need to be updated to reflect the new location:

```php
  <?php
    require_once get_template_directory() . '/path/to/python-functions.php';

    //...rest of file
```

7. Update the `functions.php` file of the active theme so that line 2 reads thusly - this will allow the custom hooks / actions to be available to `WordPress`:

```php
  <?php
    require_once get_template_directory() . '/path/to/python-actions.php';

    //...rest of file
```

8. The set up should now be complete.

## Integrating Python and scripts into existing Wordpress installation

This walk through assumes you have an active machine that hosts `WordPress` and that the user has admin privileges.

**Be aware** that the `.env.example` file is pre-configured to store logs in the `/var/www/logs` directory, please update this if appropriate and make sure the logging directory has write permissions for `Python`.

1. Within the codebase, move the two `PHP` files [python-actions.php](./php/python-actions.php) and [python-functions.php](./php/python-functions.php) to and appropriate directory within the active `WordPress` theme. These files contain the code to connect with the `Python` scripts and hooks / actions for the `WordPress` events. This example assumes that the files are copied over to the root of the theme, ie the same location as the `functions.php` file. If the files have been copied to a different location then line 2 of `python-actions.php` will need to be updated to reflect the new location:

```php
  <?php
    require_once get_template_directory() . '/path/to/python-functions.php';

    //...rest of file
```

7. Update the `functions.php` file of the active theme so that line 2 reads thusly - this will allow the custom hooks / actions to be available to `WordPress`:

```php
  <?php
    require_once get_template_directory() . '/path/to/python-actions.php';

    //...rest of file
```

8. The set up should now be complete.

## How the scripts work

There are a number of `PHP` scripts that fire on specific `WordPress` events.

Below is a brief description of how each process is expected to work - for greater details, view the files directly.

### Uploading new media items

When uploading a new media items from the `Media Library` page within `WordPress`, the `handle_media_creation` function within the [python-actions.php](./php/python-actions.php) file is fired, triggering the `python_upload_file_to_s3_bucket` function within the [python-functions.php](./php/python-functions.php) file.

The `python_upload_file_to_s3_bucket` function takes four parameters: the `post_id` (the attachment id); the `update_type` (this is 'original' or 'edit' but for new media it is set to 'original'); a boolean flag `convert_to_webp` (optional: default = false); and the file_name (optional: default = null), that is only used if the `update_type` is set to 'edit'.

Once this function is fired, it will connect to the `S3` client and upload the file to the bucket.

If the `convert_to_webp` flag is set to true, the function will also create a webp version of the attachment (if not webp already) and upload that to the `S3` bucket also.

On success, the attachment's metadata is updated with the following tags: `hyve_s3_url_original` (to store the `S3 KEY` of the uploaded item) and `hyve_s3_url_original_webp` (to store the `S3 KEY` of the uploaded webp version of the item if the `convert_to_webp` flag is set to true).

These meta tags can be used when fetching the attachment data and can be used to source the image from the `S3` bucket.

Finally, if the user refreshes the page an admin notification will appear at the top of the page stating if the python function ran successfully or errored - the output can also be viewed in the log files.

### Editing media metadata

Currently this is not active due the image not actually changing when the alt or description etc is changed within the `WordPress` attachment editing screen.

This can be implemented by uncommenting the appropriate line under the `handle_media_update` function with the [python-actions.php](./php/python-actions.php) file.

### Editing media items

When editing a media item from the `Media Library` page within `WordPress`, the `handle_attachment_cropped_or_scaled` function within the [python-actions.php](./php/python-actions.php) file is fired, triggering the `python_upload_file_to_s3_bucket` function within the [python-functions.php](./php/python-functions.php) file.

Details for this function can be viewed in the [upload new media items](#uploading-new-media-items) section, however the `update_type` is set to 'edit' and the `file_name` is passed to the function.

The main difference of this function when the `update_type` is set to 'edit' is the following:

On success, the attachment's metadata is updated with the following tags: `hyve_s3_url_edits` (to store an array of `S3 KEYs` of the edit items) and `hyve_s3_url_edits_webp` (to store the `S3 KEYs` of the webp versions of the edited items if the `convert_to_webp` flag is set to true).

These tags have been added so that both the original and the edits files are stored for access later if desired.

### Deleting media items

Much like the upload function, this function shows the admin messages and logs data within the log files.

When deleting a media item or items from the `Media Library` page within `WordPress`, the `handle_media_deletion` function with the [python-actions.php](./php/python-actions.php) file is fired, triggering the `python_delete_from_s3_bucket` function within the [python-functions.php](./php/python-functions.php) file.

Once this function is fired, it will connect to the `S3` client and delete all the files found under the following tags: `hyve_s3_url_edits`, `hyve_s3_url_original`, `hyve_s3_url_edits_webp`, and `hyve_s3_url_original_webp`.

Finally, if the user refreshes the page an admin notification will appear at the top of the page stating if the python function ran successfully or errored - the output can also be viewed in the log files.

## Development

If updating this repository please keep track of the changes and update this `ReadMe.md` file, the `.env.example` and any other relative files so that documentation can be kept up-to-date for future developers.
