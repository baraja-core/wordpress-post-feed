<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


use Baraja\Url\Url;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

final class ImageStorage
{
	private string $storagePath;

	private string $relativeStoragePath;


	public function __construct(?string $storagePath = null, string $relativeStoragePath = 'wordpress-post-feed')
	{
		if ($storagePath === null && isset($_SERVER['SCRIPT_FILENAME'])) {
			$storagePath = dirname((string) $_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR . $relativeStoragePath;
		} else {
			throw new \RuntimeException('Script filename is not available. Please define storagePath manually.');
		}
		$this->storagePath = $storagePath;
		$this->relativeStoragePath = $relativeStoragePath;
	}


	public function save(string $url): void
	{
		if (Validators::isUrl($url) === false) {
			throw new \InvalidArgumentException('Given input is not valid absolute URL, because "' . $url . '" given.');
		}
		$storagePath = $this->getInternalPath($url);
		if (\is_file($storagePath) === false) {
			FileSystem::copy($url, $storagePath);
		}
	}


	public function getInternalPath(string $url): string
	{
		return $this->storagePath . '/' . $this->getRelativeInternalUrl($url);
	}


	public function getAbsoluteInternalUrl(string $url): string
	{
		try {
			$baseUrl = Url::get()->getBaseUrl();
		} catch (\Throwable) {
			$baseUrl = '';
		}

		return $baseUrl . '/' . $this->relativeStoragePath . '/' . $this->getRelativeInternalUrl($url);
	}


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


	private function resolvePrefixDir(string $url): string
	{
		if ($url === '') {
			throw new \InvalidArgumentException('URL can not be empty string.');
		}
		if (preg_match('/wp-content.+(\d{4})\/(\d{2})/', $url, $urlParser)) {
			return $urlParser[1] . '-' . $urlParser[2];
		}

		return substr(md5($url), 0, 7);
	}
}
