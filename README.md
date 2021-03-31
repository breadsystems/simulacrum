# Simulacrum

> Sim•u•la•crum
> *n.* That which is formed in the likeness of any object; an image.
> *n.* A simple image manipulation platform for the web

## Goals

* A minimalist image CDN with on-demand resizing
* Radical simplicity
* Speed

## Usage

### Requesting an image

Request images from your Simulacrum server like so:

```
img.yourdomain.com/img/c,300,300/cat.jpg
```

This will serve a version of `cat.jpg` cropped down to 300 x 300 pixels.

`img.yourdomain.com` can, of course, be anything you want and will depend on your DNS setup.

### Example from the CLI

**NOTE:** these examples use `jq`, a [nice](https://stedolan.github.io/jq/) little command-line utility for working with JSON.

```sh
$ curl --silent --upload-file ~/Pictures/cat.png -H 'X-Simulacrum-Key: [YOUR API KEY]' \
  'your-cdn.xyz/api?file=my-subdir/cat.png'|jq
{
  "success": true,
  "path": "my-subdir/cat.png",
  "mime_type": "image/png",
  "width": 500,
  "height": 400,
  "bytes": 85547,
  "new_dir": true
}
# ^ new_dir means `my-subdir` was created for you.
# To replace an image, upload it again:
$ curl --silent --upload-file ~/Pictures/cat.png -H 'X-Simulacrum-Key: [YOUR API KEY]' \
  'your-cdn.xyz/api?file=my-subdir/cat.png'|jq
{
  "success": true,
  "path": "my-subdir/cat.png",
  "mime_type": "image/png",
  "width": 500,
  "height": 400,
  "bytes": 85547,
  "new_dir": false
}
# ^ Same request, but this time new_dir is false.
$
# NOTE: Subdirectories cannot contain dots:
$ curl --silent -H 'X-Simulacrum-Key: [YOUR API KEY]' -XDELETE \
  'your-cdn.xyz/api?file=a.b.c/cat.png'|jq
{
  "success": false,
  "error": "Directory name cannot contain dots (\".\")"
}
# Now try deleting an image:
$ curl --silent -H 'X-Simulacrum-Key: [YOUR API KEY]' -XDELETE \
  'your-cdn.xyz/api?file=my/subdir/cat.png'|jq
{
  "success": false,
  "error": "file path must be exactly two levels deep (dir/file.ext)"
}
# Whoops! You typed a / instead of a -.
# That's OK, mistakes are part of life. Let's try again:
$ curl --silent -H 'X-Simulacrum-Key: [YOUR API KEY]' -XDELETE \
  'your-cdn.xyz/api?file=my-subdir/cat.png'|jq
{
  "success": true,
  "path": "my-subdir/cat.png"
}
# It worked! The file was deleted and its path returned.
# What happens if we try to delete it again?
{
  "success": false,
  "error": "File does not exist or is not writeable."
}
```

The `X-Simulacrum-Key` header should contain your API key. See **Setup**, below, for details.

### Upload API

Simulacrum offers a simple REST(-ish) API for uploading and deleting images on your server. Uploading a single file and deleting a single file are the only two operations.

API responses are in JSON format. The `success` property in the returned JSON object indicates whether the request succeeded or failed. On error, an `error` message will describe what went wrong.

All requests require an `X-Simulacrum-Key` header containing your API key.

#### Uploading a new image

```
PUT /api?file=dir/file.ext
```

* `dir` must be _exactly one_ level deep (no subdirectories) and cannot contain dots (`.`).
* `file.ext` is the name of the filename you want on disk.
* The binary contents of your file go in the request body.

##### CLI example

```
curl --silent -H 'x-simulacrum-key: password' --upload-file ./cat.jpg localhost:9002/api?file=img/cat.jpg
```

##### Example result

```json
{
  "success": true,
  "path": "img/cat.jpg",
  "mime_type": "image/png",
  "width": 500,
  "height": 400,
  "bytes": 123456,
  "new_dir": false
}
```

##### Result schema

| Key         | Type    | Description                                                  |
| ----------- | ------- | ------------------------------------------------------------ |
| `success`   | Boolean | Whether or not the upload was successful.                    |
| `path`      | String  | The new (relative) image path.                               |
| `mime_type` | String  | The MIME type of the uploaded image.                         |
| `width`     | Integer | The width of the uploaded image, in pixels.                  |
| `height`    | Integer | The height of the uploaded image, in pixels.                 |
| `bytes`     | Integer | The number of bytes written to the file system.              |
| `new_dir`   | Boolean | Whether or not a new subdirectory was created as a result of this request. |

#### Deleting an image

```
DELETE /api?file=dir/file.ext
```

* If `dir/file.ext` does not exist, request will fail with a **404 Not Found** error.
* Request body should be blank.

##### CLI example

```
curl --silent -H 'x-simulacrum-key: password' -XDELETE ./cat.jpg localhost:9002/api?file=img/cat.jpg
```

##### Example result

```json
{
  "success": true,
  "path": "img/cat.jpg"
}
```

##### Result schema

| Key       | Type    | Description                               |
| --------- | ------- | ----------------------------------------- |
| `success` | Boolean | Whether or not the upload was successful. |
| `path`    | String  | The (relative) path of the deleted file.  |

## Setup

### Install the code

Download the source code to your server. Make sure the `public` directory is internet-accessible.

Install dependencies:

```
composer install --no-dev
```

### Create an API key

Set up an API key at the _root_ of the Simulacrum repo (alongside composer.json):

```
php -r 'echo password_hash("your API key", PASSWORD_DEFAULT);' > /path/to/simulacrum/api.key
```

Expose the `IMAGES_ROOT` environment variable from your frontend (e.g. Nginx). This should be the absolute path to the directory where your images will be uploaded.

Now test it out:

```
curl --silent -H 'X-Simulacrum-Key: (your API key)' --upload-file cat.gif simulacrum.yourdomain.xyz/api?file=cat.gif
```

Distribute your key to developers you trust. You're done.

### IMPORTANT RULES about directories

##### No user accounts

**There are no user accounts** and write access to all subdirectories is assumed for all users: in other words, it is up to you and your comrades to coordinate who writes to where. **This is a feature, not a bug,** because it affords much greater simplicity. Once an API key is set up, there is **exactly one** API key at all times. To revoke all API access, just do `rm api.key`.

A good baseline strategy for coordinating users and subdirectories is "one user-facing application == one subdirectory." A coarser grain than this increases the risk of one application accidentally overwriting the files of another. For example, if `alice.com` and `bob.com` both upload to the `images` subdirectory, `alice.com` might overwrite `images/bobs-pic.jpg` by mistake. Instead, Alice and Bob should simply coordinate: in this case they could choose to upload to the `alicedotcom` and `bobdotcom` directories, respectively. By that token, **Simulacrum is optimal for small teams with tight communication.**

You can, of course, choose a finer grain than this, too, e.g. if you have a WordPress site you might give each WordPress user their own subdirectory like `wpsitename_username`.

##### Subdirectories are exactly one level deep

**Paths are only allowed to go one subdirectory deep** from the `IMAGES_ROOT` environment variable: images in `$IMAGES_ROOT/a/b`, for example, will be inaccessible. This is by design to keep the parsing rules for URLs as simple as possible. With exactly one subdirectory, URLs look like `/dir/[operations]+` where `[operations]+` means one or more image transforms, such as crops or scales.

The API will create subdirectories in response to PUT requests to subdirectories that do not exist yet.

## Running locally

Set the `IMAGES_ROOT` environment variable to the path containing your images. Then, point your web server at `public/index.php`.

Locally:

```
export IMAGES_ROOT=$(pwd)/dev
php -S localhost:9001 public/index.php
```
