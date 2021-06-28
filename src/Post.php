<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


class Post
{
	private ImageStorage $imageStorage;

	private string $title;

	private string $description;

	private string $link;

	private \DateTimeImmutable $date;

	private ?string $creator = null;

	/** @var string[] */
	private array $categories = [];

	private ?string $mainImageUrl = null;


	public function __construct(
		string $title,
		string $description,
		string $link,
		\DateTimeImmutable $date,
		ImageStorage $imageStorage
	) {
		$this->title = $title;
		$this->description = $description;
		$this->link = $link;
		$this->date = $date;
		$this->imageStorage = $imageStorage;
	}


	public function setImageStorage(ImageStorage $imageStorage): void
	{
		$this->imageStorage = $imageStorage;
	}


	public function getTitle(): string
	{
		return $this->title;
	}


	public function getDescription(): string
	{
		return $this->description;
	}


	public function getLink(): string
	{
		return $this->link;
	}


	public function getDate(): \DateTimeImmutable
	{
		return $this->date;
	}


	public function getCreator(): ?string
	{
		return $this->creator;
	}


	public function setCreator(?string $creator): self
	{
		$this->creator = $creator;

		return $this;
	}


	/**
	 * @return string[]
	 */
	public function getCategories(): array
	{
		return $this->categories;
	}


	/**
	 * @param string[] $categories
	 */
	public function setCategories(array $categories): self
	{
		$this->categories = $categories;

		return $this;
	}


	public function getAbsoluteInternalUrl(): ?string
	{
		return $this->mainImageUrl !== null
			? $this->imageStorage->getAbsoluteInternalUrl($this->mainImageUrl)
			: null;
	}


	public function getRelativeInternalUrl(): ?string
	{
		return $this->mainImageUrl !== null
			? $this->imageStorage->getRelativeInternalUrl($this->mainImageUrl)
			: null;
	}


	public function getMainImageUrl(): ?string
	{
		return $this->mainImageUrl;
	}


	public function setMainImageUrl(?string $mainImageUrl): self
	{
		$this->mainImageUrl = $mainImageUrl;

		return $this;
	}
}
