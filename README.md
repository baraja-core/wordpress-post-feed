<div align='center'>
  <picture>
    <source media='(prefers-color-scheme: dark)' srcset='https://cdn.brj.app/images/brj-logo/logo-regular.png'>
    <img src='https://cdn.brj.app/images/brj-logo/logo-dark.png' alt='BRJ logo'>
  </picture>
  <br>
  <a href="https://brj.app">BRJ organisation</a>
</div>
<hr>

Wordpress post feed
===================

API service for downloading a list of new blog posts.

The library downloads the feed of posts from Wordpress and provides a simple API for reading them.

Posts from the feed are automatically cached for a set period of time, so they don't have to be downloaded again in each request.

📦 Installation
---------------

It's best to use [Composer](https://getcomposer.org) for installation, and you can also find the package on
[Packagist](https://packagist.org/packages/baraja-core/wordpress-post-feed) and
[GitHub](https://github.com/baraja-core/wordpress-post-feed).

To install, simply use the command:

```
$ composer require baraja-core/wordpress-post-feed
```

You can use the package manually by creating an instance of the internal classes, or register a DIC extension to link the services directly to the Nette Framework.

Configuration
-------------

If you are installing the package into the Nette Framework, you can simply register the extension:

```yaml
extensions:
    wordpressPostFeed: Baraja\WordPressPostFeed\WordpressPostFeedExtension
```

However, the extension is not required, as you can create an instance manually:

```php
$feed = new \Baraja\WordPressPostFeed\Feed;
```

By default, no dependencies are required. Feed will take care of all dependencies by itself.

How to use
----------

Basic usage is simple:

```php
$feed = new \Baraja\WordPressPostFeed\Feed;

$posts = $feed->load('https://blog.mycleverminds.cz/feed/');

foreach ($posts as $post) {
    echo $post->getTitle();
    echo $post->getDescription();
    echo $post->getLink();
    echo $post->getDate()->format('Y-m-d');
    echo $post->getCreator();
    echo json_encode($post->getCategories());
    echo $post->getMainImageUrl();
}
```

The feed will be downloaded automatically, then cached and further requests will be handled instantly straight from the cache.

If you only need to retrieve some posts from the feed, you can use the `limit` and `offset` arguments:

```php
$posts = $feed->load(
    url: 'https://blog.mycleverminds.cz/feed/',
    limit: 3,
    offset: 1,
);
```

Posts are automatically cached for the set period (default 2 hours). If the period expires, the cache is automatically invalidated and the next request will have to retrieve the data and rebuild the cache. This retrieval may slow down this request. If you want to handle all requests instantly, the cache for the feed needs to be automatically refreshed by cron.

Cron will call the following method to refresh the cache:

```php
$feed = new \Baraja\WordPressPostFeed\Feed;
$feed->updateCache('https://blog.mycleverminds.cz/feed/');
```

Image manipulation
------------------

When downloading posts from the feed, images are also automatically downloaded and copied to disk. The image storage is provided by the `\Baraja\WordPressPostFeed\ImageStorage` service, which is automatically registered by the library. The default directory for storing images is `wordpress-post-feed` relative to your `index.php`.

The service provides the following methods:

| Method | Description |
|--------|-------------|
| `save(string $url): void` | Downloads the image from the URL and saves it to disk |
| `getInternalPath(string $url): string` | Returns the absolute path for the internal data store. |
| `getAbsoluteInternalUrl(string $url): string` | Returns the absolute URL to retrieve the image |
| `getRelativeInternalUrl(string $url): string` | Returns the relative URL to retrieve the image |

Working with images is also available directly from the `Post` entity via methods:

- `getAbsoluteInternalUrl()`
- `getRelativeInternalUrl()`
- `getMainImageUrl()`

📄 License
-----------

`baraja-core/wordpress-post-feed` is licensed under the MIT license. See the [LICENSE](https://github.com/baraja-core/template/blob/master/LICENSE) file for more details.
