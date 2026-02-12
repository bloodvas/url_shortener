<?php

namespace App\Services;

use App\Models\Url;

class UrlService
{
    private Url $urlModel;
    private UrlShortener $shortener;

    public function __construct(Url $urlModel, UrlShortener $shortener)
    {
        $this->urlModel = $urlModel;
        $this->shortener = $shortener;
    }

    public function createShortUrl(string $originalUrl): array
    {
        if (!$this->shortener->isValidUrl($originalUrl)) {
            throw new \InvalidArgumentException('Invalid URL provided');
        }

        $existingUrl = $this->urlModel->findByOriginalUrl($originalUrl);
        if ($existingUrl) {
            return $this->formatUrlResponse($existingUrl);
        }

        $shortCode = $this->shortener->generateUniqueShortCode();
        $urlId = $this->urlModel->create($originalUrl, $shortCode);

        if (!$urlId) {
            throw new \RuntimeException('Failed to create short URL');
        }

        return $this->formatUrlResponse([
            'short_code' => $shortCode,
            'original_url' => $originalUrl
        ]);
    }

    public function getUrlByShortCode(string $shortCode): ?array
    {
        return $this->urlModel->findByShortCode($shortCode);
    }

    public function incrementClicks(int $urlId): void
    {
        $this->urlModel->incrementClicks($urlId);
    }

    private function formatUrlResponse(array $urlData): array
    {
        return [
            'short_url' => $this->getBaseUrl() . '/' . $urlData['short_code'],
            'short_code' => $urlData['short_code'],
            'original_url' => $urlData['original_url']
        ];
    }

    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return "$protocol://$host";
    }
}
