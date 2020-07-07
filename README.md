# Simulacrum

> Sim•u•la•crum
> *n.* That which is formed in the likeness of any object; an image.
> *n.* A simple image manipulation platform for the web

## Running

Set the `IMAGES_ROOT` environment variable to the path containing your images. Then, point your web server at `public/index.php`.

Locally:

```
export IMAGES_ROOT=$(pwd)/dev
php -S localhost:9001 public/index.php
```