# WSM Prod Picture on Local Env WordPress Plugin

## Description

WSM Prod Picture on Local Env is a WordPress plugin that helps developers and site managers redirect image requests for locally non-existent images to a remote domain. This is particularly useful when working with local development environments and wanting to display images from a production site.

## Features

- Automatically redirects missing images from `/wp-content/uploads/` to a remote domain
- Configurable remote domain through WordPress admin settings
- Supports both direct image requests and WordPress attachment URLs
- Lightweight and performant
- Easy to configure

## Installation

1. Download the plugin folder
2. Upload the `wsm-prod-picture-on-local-env` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the remote domain in Settings > WSM Prod Picture

## Configuration

After installation, go to Settings > WSM Prod Picture to set your remote domain. By default, it uses `https://www.thetransmitter.org`, but you can change this to any domain hosting your production images.

## How It Works

The plugin intercepts requests to files in the `/wp-content/uploads/` folder. If a requested file doesn't exist locally, it redirects to the specified remote domain, serving the image from the production environment.

## Global Function

The plugin also provides a global function `wsm_prod_picture_url()` that can be used directly in theme files:

```php
$image_url = wsm_prod_picture_url($original_url);
```

## Recommended PHP Configuration

For best results, enable `output_buffering` in your PHP configuration to ensure proper request interception.

## License

This plugin is licensed under the GPL-2.0+ license.

## Author

WiSiM - [GitHub Profile](https://github.com/wsmvin/)

## Support

For support, please open an issue on the GitHub repository.
