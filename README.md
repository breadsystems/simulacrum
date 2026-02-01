# Simulacrum

> Sim•u•la•crum
>
> *n.* That which is formed in the likeness of any object; an image.
>
> *n.* A simple image manipulation service for the web.

## Goals

* A minimalist image CDN with on-demand resizing
* Radical simplicity
* Speed

## Usage

### Requesting an image

Image URLs look like this:

```
yourdomain.xyz/subdir/width,1000/crop,300,300/cat.jpg
```

In this example, the original image `subdir/cat.jpg` is read from the filesystem (if it exists), then is scaled down to a width of 1000 pixels while preserving aspect ratio, and finally is cropped to 300 x 300 pixels.

(The domain can, of course, be anything you want and will depend on your DNS setup.)

Formally, a valid request path takes the following form:

```
/<dir>/<op>+/<img>
```

- `<dir>` is a subdirectory exactly one level deep, such as `img`
- `<op>+` is one or more image transform operations, such as `crop` or `scale`, delimited by `/`
- `<img>` is the filename of your image, such as `cat.jpg`

Each `op` takes the form of `<opname>[,<arg>+]`, where `<arg>+` is one or more comma-separated arguments for the operation.

#### Examples

Serve a version of `img/cat.jpg` cropped down to 300 x 300 pixels:

```
/img/c,300,300/cat.jpg
```

Scale `img/cat.jpg` by 50%, then crop it down to 150 x 150 pixels:

```
/img/s,50/c,150,150/cat.jpg
```

#### Resize operations

Available image resize operations are shown below in the form:

```
<op>,<arg:type>
```

Most `op`s have aliases, e.g. `w` for `resize_to_width`. These are shown below as multiple lines.

##### Resize to width

```
resize_to_width,<width:int>
w,<width:int>
```

Scale to `width`, preserving aspect ratio.

##### Resize to height

```
resize_to_height,<height:int>
h,<height:int>
```

Scale to `height`, preserving aspect ratio.

##### Resize to long side

```
resize_to_long_side,length
long,length
```

Resize longer dimension (original width or height) to `length`, preserving aspect ratio.

##### Resize to short side

```
resize_to_short_side,<length:int>
short,<length:int>
```

Resize shorter dimension (original width or height) to `length`, preserving aspect ratio.

##### Crop

```
crop,<x:int>,<y:int>
c,<x:int>,<y:int>
```

Scale as close as possible to `x` by `y` pixels, then crop to `x` by `y` pixels, centered.

##### Scale

```
s,<percent:int>
scale,<percent:int>
```

Scale by `min(percent, MAX_RESIZE_PERCENT)`, preserving aspect ratio. Set `MAX_RESIZE_PERCENT` in the environment to configure; default is 100.

**NOTE:** The `min()` is performed so that an accidentally or maliciously huge `percent` does not take down the server by hogging resources.

### Example from the CLI

```sh
$ curl --silent --upload-file ~/Pictures/cat.png -H 'X-Simulacrum-Key: [YOUR API KEY]' \
  'your-cdn.xyz/api?file=my-subdir/cat.png'
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
  'your-cdn.xyz/api?file=my-subdir/cat.png'
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
  'your-cdn.xyz/api?file=a.b.c/cat.png'
{
  "success": false,
  "error": "Directory name cannot contain dots (\".\")"
}
# Now try deleting an image:
$ curl --silent -H 'X-Simulacrum-Key: [YOUR API KEY]' -XDELETE \
  'your-cdn.xyz/api?file=my/subdir/cat.png'
{
  "success": false,
  "error": "file path must be exactly two levels deep (dir/file.ext)"
}
# Whoops! You typed a / instead of a -.
# That's OK, mistakes are part of life. Let's try again:
$ curl --silent -H 'X-Simulacrum-Key: [YOUR API KEY]' -XDELETE \
  'your-cdn.xyz/api?file=my-subdir/cat.png'
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

Simulacrum offers a simple REST API for uploading and deleting images on your server, as well as some barebones user management.

API responses are in JSON format. The `success` property in the returned JSON object indicates whether the request succeeded or failed. On error, an `error` message will describe what went wrong.

All requests require Basic authentication. The directory name doubles as username.

#### Uploading a new image

```
PUT /api?file=dir/file.ext
```

* `dir` must be _exactly one_ level deep (no subdirectories) and cannot contain dots (`.`).
* `file.ext` is the name of the filename you want on disk.
* The binary contents of your file go in the request body.

##### CLI example

```
curl --silent -H "Authorization: Basic $(echo img:$KEY | base64)" --upload-file ./cat.jpg localhost:9002/api?file=cat.jpg
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
curl --silent -H "Authorization: Basic $(echo img:$KEY | base64)" -XDELETE ./cat.jpg localhost:9002/api?file=cat.jpg
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

**Paths are only allowed to go one subdirectory deep** from the `IMAGES_ROOT` environment variable: images in `$IMAGES_ROOT/a/b`, for example, will be inaccessible. This is by design to keep the parsing rules for URLs as simple as possible.

The API will create subdirectories in response to PUT requests to subdirectories that do not exist yet.

## Running locally

Set the `IMAGES_ROOT` environment variable to the path containing your images. Then, point your web server at `public/index.php`.

Locally:

```
export IMAGES_ROOT=$(pwd)/dev
php -S localhost:9001 public/index.php
```

Run `bin/download-samples` to download a few large public-domain images from WikiMedia Commons (about 41 MB total) into `$IMAGES_ROOT/dev` (defaults to `./img/dev`).

You can now request e.g. `http://localhost:9001/dev/w,300/bobbins.jpg` (scales the image to 300px wide).

## Running tests

```
composer test
```
