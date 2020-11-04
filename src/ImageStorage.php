<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

final class ImageStorage
{
	private string $storagePath;

	private string $relativeStoragePath;


	public function __construct(string $storagePath, string $relativeStoragePath)
	{
		$this->storagePath = $storagePath;
		$this->relativeStoragePath = $relativeStoragePath;
	}


	public function save(string $url): void
	{
		if (Validators::isUrl($url) === false) {
			throw new \InvalidArgumentException('Given input is not valid absolute URL, because "' . $url . '" given.');
		}
		if (\is_file($storagePath = $this->getInternalPath($url)) === false) {
			FileSystem::copy($url, $storagePath);
		}
	}


	public function getInternalPath(string $url): string
	{
		return $this->storagePath . '/' . $this->getRelativeInternalUrl($url);
	}


	public function getAbsoluteInternalUrl(string $url): string
	{
		return $this->getBaseUrl() . '/' . $this->relativeStoragePath . '/' . $this->getRelativeInternalUrl($url);
	}


	public function getRelativeInternalUrl(string $url): string
	{
		$originalFileName = (string) preg_replace_callback(
			'/^.*\/([^\/]+)\.([^.]+)$/',
			fn(array $match): string => substr(Strings::webalize($match[1]), 0, 64) . '.' . strtolower($match[2]),
			$url
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


	private function getBaseUrl(): string
	{
		static $return;
		if ($return !== null) {
			return $return;
		}
		if (!isset($_SERVER['REQUEST_URI'], $_SERVER['HTTP_HOST'])) {
			throw new \RuntimeException('Can not resolve current URL in CLI mode.');
		}

		$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
			. '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		if (preg_match('/^(https?:\/\/.+)\/www\//', $currentUrl, $localUrlParser)) {
			$return = $localUrlParser[0];
		} elseif (preg_match('/^(https?:\/\/[^\/]+)/', $currentUrl, $publicUrlParser)) {
			$return = $publicUrlParser[1];
		} else {
			throw new \RuntimeException('Can not parse relative URL from "' . $currentUrl . '".');
		}

		return ($return = rtrim($return, '/'));
	}
}
