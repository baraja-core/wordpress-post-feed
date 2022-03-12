<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


use Baraja\Url\Url;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

final class ImageStorage
{
	/** image types */
	public const
		JPEG = IMAGETYPE_JPEG,
		PNG = IMAGETYPE_PNG,
		GIF = IMAGETYPE_GIF,
		WEBP = IMAGETYPE_WEBP,
		BMP = IMAGETYPE_BMP;

	public const FORMATS = [
		self::JPEG => 'jpeg',
		self::PNG => 'png',
		self::GIF => 'gif',
		self::WEBP => 'webp',
		self::BMP => 'bmp',
	];

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
			$content = $this->downloadImage($url);
			$imageSize = @getimagesizefromstring($content); // @ - strings smaller than 12 bytes causes read error
			$type = $imageSize !== false ? $imageSize[2] : null;
			if (is_int($type) === false || isset(self::FORMATS[$type]) === false) {
				throw new \RuntimeException(
					'Security issue: Downloaded file "' . $url . '" is not valid image, '
					. 'because image content type has not been detected.',
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


	private function downloadImage(string $url): string
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_ENCODING => 'UTF-8',
		]);
		$haystack = curl_exec($curl);
		curl_close($curl);
		if ($haystack === false) {
			trigger_error('Image URL "' . $url . '" is empty or broken.');
		}

		return (string) $haystack;
	}
}
