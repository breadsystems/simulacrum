# Simulacrum

> Sim•u•la•crum
> *n.* That which is formed in the likeness of any object; an image.
> *n.* A simple image manipulation platform for the web

## Goals

* Radical simplicity
* Speed
* A minimalist image CDN with on-demand resizing

## Usage

Request images from your Simulacrum server like so:

```
/img/c,300,300/cat.jpg
```

This will serve a version of `cat.jpg` cropped down to 300 x 300 pixels.

## Setup

Download the source code to your server. Make sure the `public` directory is internet-accessible.

Install dependencies:

```
composer install --no-dev
```

Set up an API key at the _root_ of the Simulacrum repo (alongside composer.json):

```
php -r 'echo password_hash("your API key", PASSWORD_DEFAULT);' > /path/to/simulacrum/api.key
```

Expose the `IMAGES_ROOT` environment variable from your frontend (e.g. Nginx). This should be the absolute path to the directory where your images will be uploaded.

Test it out:

```
curl --silent -H 'x-simulacrum-key: (your API key)' --upload-file cat.gif simulacrum.yourdomain.xyz/api?file=cat.gif
```

The API should respond with something like:

```json
{
  "success": true,
  "path": "x/hacker.png",
  "mime_type": "image/png",
  "dimensions": [
    500,
    400
  ],
  "new_dir": false
}
```

Distribute your key to developers you trust.

### Set up directories (optional)

Set up at least one subdirectory of `IMAGES_ROOT`. Subdirectory names cannot contain dots (`.`).

You can only go one subdirectory deep: images in `$IMAGES_ROOT/a/b`, for example, will be inaccessible. This is by design to keep the parsing rules for URLs as simple as possible. With exactly one subdirectory, URLs look like `/dir/[operations]+` where `[operations]+` means one or more image transforms, such as crops or scales.

This step is optional since the API will create subdirectories in response to PUT requests to subdirectories that do not exist yet.

## Running locally

Set the `IMAGES_ROOT` environment variable to the path containing your images. Then, point your web server at `public/index.php`.

Locally:

```
export IMAGES_ROOT=$(pwd)/dev
php -S localhost:9001 public/index.php
```
