<div align='center'>
  <picture>
    <source media='(prefers-color-scheme: dark)' srcset='https://cdn.brj.app/images/brj-logo/logo-regular.png'>
    <img src='https://cdn.brj.app/images/brj-logo/logo-dark.png' alt='BRJ logo'>
  </picture>
  <br>
  <a href="https://brj.app">BRJ organisation</a>
</div>
<hr>

# WordPress Post Feed

A lightweight PHP library for fetching, parsing, and caching WordPress RSS feeds with automatic image handling.

The library provides a simple API to download blog posts from any WordPress RSS feed, automatically caches the content to minimize network requests, and handles image downloading and local storage for improved performance and reliability.

## :sparkles: Key Features

- **Automatic RSS feed parsing** - Extracts posts with title, description, link, date, author, and categories
- **Smart caching system** - Feed content is cached with configurable expiration time (default: 2 hours)
- **Image management** - Automatically downloads and stores post images locally
- **Pagination support** - Built-in `limit` and `offset` parameters for easy pagination
- **Zero-config usage** - Works out of the box without any dependencies when used standalone
- **Nette Framework integration** - Full DI container support with configurable extension
- **Security-first image handling** - Validates image types before saving to prevent malicious files

## :building_construction: Architecture Overview

The library consists of four main components that work together to provide a complete feed management solution:

```
┌─────────────────────────────────────────────────────────────────────┐
│                        WordPress RSS Feed                           │
│                    (External WordPress Blog)                        │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                           Feed Service                              │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  • Downloads RSS feed via cURL                              │   │
│  │  • Parses XML using DOMDocument                             │   │
│  │  • Extracts post data (title, description, link, date...)   │   │
│  │  • Manages cache read/write operations                      │   │
│  │  • Extracts images from post descriptions                   │   │
│  └─────────────────────────────────────────────────────────────┘   │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                ┌───────────────┴───────────────┐
                ▼                               ▼
┌───────────────────────────────┐ ┌───────────────────────────────────┐
│         Nette Cache           │ │         ImageStorage              │
│  ┌─────────────────────────┐  │ │  ┌─────────────────────────────┐  │
│  │ • FileStorage (default) │  │ │  │ • Downloads images via cURL │  │
│  │ • Configurable TTL      │  │ │  │ • Validates image types     │  │
│  │ • Auto-invalidation     │  │ │  │ • Stores to local disk      │  │
│  └─────────────────────────┘  │ │  │ • Generates URLs            │  │
└───────────────────────────────┘ │  └─────────────────────────────┘  │
                                  └───────────────────────────────────┘
                                                  │
                                                  ▼
                                  ┌───────────────────────────────────┐
                                  │           Post Entity             │
                                  │  ┌─────────────────────────────┐  │
                                  │  │ • title (string)            │  │
                                  │  │ • description (string)      │  │
                                  │  │ • link (string)             │  │
                                  │  │ • date (DateTimeImmutable)  │  │
                                  │  │ • creator (string|null)     │  │
                                  │  │ • categories (array)        │  │
                                  │  │ • mainImageUrl (string|null)│  │
                                  │  └─────────────────────────────┘  │
                                  └───────────────────────────────────┘
```

### :package: Components

| Component | Description |
|-----------|-------------|
| `Feed` | Main service responsible for downloading, parsing, and caching RSS feeds. Orchestrates the entire feed retrieval process. |
| `Post` | Data entity representing a single blog post with all its properties and image URL helpers. |
| `ImageStorage` | Service for downloading, validating, and storing images locally. Provides URL generation for stored images. |
| `WordpressPostFeedExtension` | Nette DI extension for seamless framework integration with full configuration support. |

## :package: Installation

It's best to use [Composer](https://getcomposer.org) for installation, and you can also find the package on
[Packagist](https://packagist.org/packages/baraja-core/wordpress-post-feed) and
[GitHub](https://github.com/baraja-core/wordpress-post-feed).

To install, simply use the command:

```shell
$ composer require baraja-core/wordpress-post-feed
```

You can use the package manually by creating an instance of the internal classes, or register a DIC extension to link the services directly to the Nette Framework.

### Requirements

- PHP 8.0 or higher
- ext-curl extension
- Nette Caching component (installed automatically)

## :gear: Configuration

### Standalone Usage

The library works out of the box without any configuration:

```php
$feed = new \Baraja\WordPressPostFeed\Feed;
```

By default, the `Feed` service will:
- Create a temporary directory for caching in the system temp folder
- Set cache expiration to 2 hours
- Create an `ImageStorage` instance with default settings

### Nette Framework Integration

If you are installing the package into the Nette Framework, register the extension in your configuration:

```yaml
extensions:
    wordpressPostFeed: Baraja\WordPressPostFeed\WordpressPostFeedExtension
```

### Advanced Configuration

The extension supports the following configuration options:

```yaml
wordpressPostFeed:
    # Cache expiration time (supports human-readable format)
    expirationTime: '2 hours'

    # Absolute path for image storage
    imageStoragePath: '%wwwDir%/assets/blog-images'

    # Relative path used in URLs (required when imageStoragePath is set)
    imageRelativeStoragePath: 'assets/blog-images'
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `expirationTime` | string | `'2 hours'` | Cache expiration time in human-readable format |
| `imageStoragePath` | string | `null` | Absolute filesystem path for storing images |
| `imageRelativeStoragePath` | string | `'wordpress-post-feed'` | Relative URL path for accessing stored images |

## :rocket: Basic Usage

### Loading Posts from a Feed

```php
$feed = new \Baraja\WordPressPostFeed\Feed;

$posts = $feed->load('https://blog.example.com/feed/');

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

### Pagination with Limit and Offset

If you only need to retrieve some posts from the feed, use the `limit` and `offset` arguments:

```php
$posts = $feed->load(
    url: 'https://blog.example.com/feed/',
    limit: 3,
    offset: 1,
);
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `url` | string | required | The URL of the WordPress RSS feed |
| `limit` | int\|null | `null` | Maximum number of posts to return (null = all) |
| `offset` | int | `0` | Number of posts to skip from the beginning |

### Post Entity Methods

The `Post` entity provides the following methods:

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getTitle()` | `string` | Returns the post title (HTML tags stripped) |
| `getDescription()` | `string` | Returns the post description/excerpt |
| `getLink()` | `string` | Returns the permalink to the original post |
| `getDate()` | `DateTimeImmutable` | Returns the publication date |
| `getCreator()` | `string\|null` | Returns the author name |
| `getCategories()` | `array<int, string>` | Returns an array of category names |
| `getMainImageUrl()` | `string\|null` | Returns the original external image URL |
| `getAbsoluteInternalUrl()` | `string\|null` | Returns the absolute URL to locally stored image |
| `getRelativeInternalUrl()` | `string\|null` | Returns the relative URL to locally stored image |

## :floppy_disk: Caching System

### How Caching Works

Posts are automatically cached for the set period (default 2 hours). The caching system operates on two levels:

1. **Raw feed caching** - The downloaded XML feed is cached to avoid repeated HTTP requests
2. **Parsed post caching** - The parsed `Post` objects are cached separately for each limit/offset combination

When the cache expires, the next request will:
1. Download the fresh feed
2. Parse all posts
3. Store images locally
4. Update the cache

### Cache Management

#### Manual Cache Update

If you want to refresh the cache before expiration (e.g., via cron job):

```php
$feed = new \Baraja\WordPressPostFeed\Feed;
$feed->updateCache('https://blog.example.com/feed/');
```

This is useful for ensuring all requests are served from cache instantly, even after expiration.

#### Clear All Cache

To completely clear the feed cache:

```php
$feed = new \Baraja\WordPressPostFeed\Feed;
$feed->clearCache();
```

### Recommended Cron Setup

For production environments, it's recommended to set up a cron job that refreshes the cache periodically:

```php
// cron.php - Run every hour
$feed = new \Baraja\WordPressPostFeed\Feed;

$feedUrls = [
    'https://blog.example.com/feed/',
    'https://another-blog.com/feed/',
];

foreach ($feedUrls as $url) {
    $feed->updateCache($url);
}
```

This ensures that user requests are always served from cache and never have to wait for feed downloads.

## :framed_picture: Image Manipulation

When downloading posts from the feed, images are automatically downloaded and copied to disk. The image storage is provided by the `\Baraja\WordPressPostFeed\ImageStorage` service, which is automatically registered by the library.

### Default Storage Location

The default directory for storing images is `wordpress-post-feed` relative to your `index.php` (web root).

### ImageStorage Service Methods

The service provides the following methods:

| Method | Description |
|--------|-------------|
| `save(string $url): void` | Downloads the image from the URL and saves it to disk |
| `getInternalPath(string $url): string` | Returns the absolute filesystem path for the image |
| `getAbsoluteInternalUrl(string $url): string` | Returns the absolute URL to retrieve the image |
| `getRelativeInternalUrl(string $url): string` | Returns the relative URL to retrieve the image |

### Working with Images from Posts

Working with images is also available directly from the `Post` entity:

```php
foreach ($posts as $post) {
    // Original external URL (from WordPress)
    $externalUrl = $post->getMainImageUrl();

    // Local absolute URL (your domain)
    $absoluteUrl = $post->getAbsoluteInternalUrl();

    // Local relative URL (for use in templates)
    $relativeUrl = $post->getRelativeInternalUrl();
}
```

### Supported Image Formats

The library validates downloaded images and supports the following formats:

| Format | Constant |
|--------|----------|
| JPEG | `ImageStorage::JPEG` |
| PNG | `ImageStorage::PNG` |
| GIF | `ImageStorage::GIF` |
| WebP | `ImageStorage::WEBP` |
| BMP | `ImageStorage::BMP` |

If a downloaded file is not a valid image of these types, a `RuntimeException` is thrown to prevent potential security issues.

### Image Naming Strategy

Images are stored with a deterministic naming scheme to prevent collisions and ensure readability:

1. A 7-character MD5 hash prefix is generated from the URL
2. The original filename is sanitized (webalized) and truncated to 64 characters
3. Files are organized into subdirectories based on WordPress date structure (YYYY-MM) or URL hash

Example: `https://blog.example.com/wp-content/uploads/2024/03/my-image.jpg` becomes:
```
wordpress-post-feed/2024-03/a1b2c3d-my-image.jpg
```

## :hammer_and_wrench: Advanced Usage

### Custom Cache Storage

You can provide your own cache storage implementation:

```php
use Nette\Caching\Storages\MemcachedStorage;

$memcached = new Memcached;
$memcached->addServer('localhost', 11211);

$storage = new MemcachedStorage($memcached);
$feed = new \Baraja\WordPressPostFeed\Feed(storage: $storage);
```

### Custom Image Storage Location

```php
$imageStorage = new \Baraja\WordPressPostFeed\ImageStorage(
    storagePath: '/var/www/html/assets/blog-images',
    relativeStoragePath: 'assets/blog-images',
);

$feed = new \Baraja\WordPressPostFeed\Feed(imageStorage: $imageStorage);
```

### Custom Expiration Time

```php
$feed = new \Baraja\WordPressPostFeed\Feed(
    expirationTime: '30 minutes',
);
```

Supported time formats include: `'1 hour'`, `'30 minutes'`, `'2 days'`, etc.

### Dependency Injection in Nette

When using the Nette Framework, you can inject the services directly:

```php
final class BlogPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private \Baraja\WordPressPostFeed\Feed $feed,
    ) {
    }

    public function renderDefault(): void
    {
        $this->template->posts = $this->feed->load(
            url: 'https://blog.example.com/feed/',
            limit: 5,
        );
    }
}
```

## :warning: Error Handling

The library handles errors gracefully:

- **Empty feed response** - Throws `RuntimeException` with descriptive message
- **Invalid image format** - Throws `RuntimeException` to prevent security issues
- **Broken image URLs** - Triggers a warning but continues processing
- **Invalid feed URLs** - Triggers a warning but continues processing
- **Missing absolute URL in CLI** - Throws `LogicException` with guidance

Example of handling errors:

```php
try {
    $posts = $feed->load('https://blog.example.com/feed/');
} catch (\RuntimeException $e) {
    // Handle feed loading errors
    error_log('Feed error: ' . $e->getMessage());
}
```

## :bust_in_silhouette: Author

**Jan Barasek**

- Website: [https://baraja.cz](https://baraja.cz)
- GitHub: [@janbarasek](https://github.com/janbarasek)

## :page_facing_up: License

`baraja-core/wordpress-post-feed` is licensed under the MIT license. See the [LICENSE](https://github.com/baraja-core/wordpress-post-feed/blob/master/LICENSE) file for more details.
