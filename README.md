# First Image as Featured

A simple WordPress plugin that scans all posts and sets the first image from the content as the featured image if one is not already set.

## Description

This plugin provides a utility for WordPress sites where featured images were not consistently set. It adds a tool page in the WordPress admin area that allows an administrator to run a one-time process. The script intelligently finds posts without a featured image, downloads the first image from the post's content, and sets it as the post's featured image.

## Features

- ✅ **Bulk Processing:** Scans all published posts on your site in one click.
- ✅ **Smart Detection:** Automatically skips posts that already have a featured image.
- ✅ **Image Conversion:** Converts non-JPEG images (like PNG, WebP, GIF) to the more compatible JPEG format.
- ✅ **Browser Mimicking:** Downloads images using a browser user-agent to bypass hotlink protections and "Too Many Requests" errors.
- ✅ **Safe & Polite:** Includes a 1-second delay between processing posts to avoid rate-limiting issues.
- ✅ **Live Logging:** Shows a detailed log of its actions directly on the admin page as it runs.

## Installation

1.  Download the latest release from the [Releases](https://github.com/your-username/first-image-as-featured/releases) page.
2.  In your WordPress dashboard, navigate to **Plugins > Add New**.
3.  Click on **Upload Plugin** and select the `.zip` file you downloaded.
4.  Activate the plugin through the 'Plugins' menu in WordPress.

## How to Use

1.  After activating, navigate to **Tools > First Image as Featured** in your WordPress admin panel.
2.  Read the instructions on the page.
3.  Click the **"Scan Posts and Set Featured Images"** button to start the process.
4.  Do not navigate away from the page while the process is running. You will see a live log of the progress.
5.  Once the process is complete, a "Process complete!" message will appear at the end of the log.

## Changelog

### 1.3.0
- **Feature:** Replaced `download_url` with `wp_remote_get` to mimic a browser request.
- **Fix:** Resolves errors from sites with hotlink protection by sending a common User-Agent header.

### 1.2.0
- **Feature:** Added a 1-second `sleep()` delay between each post to prevent rate-limiting errors.
- **Fix:** Resolves "Too Many Requests" errors from external image hosts.

### 1.1.0
- **Feature:** Added automatic image conversion of non-JPEG formats to JPEG for better compatibility.
- **Feature:** Added a check to ensure the server has the required GD library.

### 1.0.0
- Initial release.

## License

This plugin is licensed under the GPL-2.0+. See the `LICENSE` file for more details.
