<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


use Baraja\Url\Url;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

final class ImageStorage
{
	public const IMAGE_MIME_TYPES = ['image/gif', 'image/png', 'image/jpeg', 'image/webp'];

	private string $storagePath;

	private string $relativeStoragePath;


	public function __construct(?string $storagePath = null, string $relativeStoragePath = 'wordpress-post-feed')
	{
		if ($relativeStoragePath === '') {
			trigger_error('Relative storage path can not be empty.');
			$relativeStoragePath = 'wordpress-post-feed';
		}
		if ($storagePath === null && isset($_SERVER['SCRIPT_FILENAME'])) {
			$storagePath = dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR . $relativeStoragePath;
		} elseif ($storagePath === null) {
			throw new \RuntimeException('Script filename is not available. Please define storagePath manually.');
		}
		$this->storagePath = $storagePath;
		$this->relativeStoragePath = $relativeStoragePath;
	}


	/**
	 * The method downloads the image from the physical URL and saves it to the internal storage.
	 * The download is checked to ensure that it is a valid data type for the image.
	 */
	public function save(string $url): void
	{
		if (Validators::isUrl($url) === false) {
			throw new \InvalidArgumentException('Given input is not valid absolute URL, because "' . $url . '" given.');
		}
		$storagePath = $this->getInternalPath($url);
		if (is_file($storagePath) === false) { // image does not exist in local storage
			$content = FileSystem::read($url); // download image
			$contentType = strtolower(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $content));
			if (in_array($contentType, self::IMAGE_MIME_TYPES, true) === false) {
				throw new \RuntimeException(
					'Security issue: Downloaded file "' . $url . '" is not valid image, '
					. 'because content type "' . $contentType . '" has been detected.',
				);
			}
			FileSystem::write($storagePath, $content);
		}
	}


	/**
	 * Returns the absolute path for the internal data store.
	 * Specifies the absolute physical disk path where the image will be written to (or read from) based on the URL.
	 * The disk path is calculated by a deterministic algorithm for future content readability.
	 */
	public function getInternalPath(string $url): string
	{
		return $this->storagePath . '/' . $this->getRelativeInternalUrl($url);
	}


	/**
	 * Returns the physical URL to download the image directly to local disk storage.
	 */
	public function getAbsoluteInternalUrl(string $url): string
	{
		try {
			$baseUrl = Url::get()->getBaseUrl();
		} catch (\Throwable) {
			if (PHP_SAPI === 'cli') {
				throw new \LogicException(
					__METHOD__ . ': Absolute URL is not available in CLI. '
					. 'Did you set context URL to "' . Url::class . '" service?',
				);
			}
			$baseUrl = '';
		}

		return $baseUrl . '/' . $this->relativeStoragePath . '/' . $this->getRelativeInternalUrl($url);
	}


	/**
	 * Returns the relative URL to retrieve the image.
	 */
	public function getRelativeInternalUrl(string $url): string
	{
		$originalFileName = (string) preg_replace_callback(
			'/^.*\/([^\/]+)\.([^.]+)$/',
			fn(array $match): string => substr(Strings::webalize($match[1]), 0, 64) . '.' . strtolower($match[2]),
			$url,
		);
		$relativeName = substr(md5($url), 0, 7) . '-' . $originalFileName;

		return $this->resolvePrefixDir($url) . '/' . $relativeName;
	}


	/**
	 * Generate an automatic unique hash based on the image URL
	 * so that file names from many different sources cannot collide.
	 */
	private function resolvePrefixDir(string $url): string
	{
		if ($url === '') {
			throw new \LogicException('URL can not be empty string.');
		}
		if (preg_match('/wp-content.+(\d{4})\/(\d{2})/', $url, $urlParser)) {
			return $urlParser[1] . '-' . $urlParser[2];
		}

		return substr(md5($url), 0, 7);
	}
}
