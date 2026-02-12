<?php

namespace App\Controllers;

use App\Models\Url;
use App\Services\UrlShortener;

class ApiController
{
    private Url $urlModel;
    private UrlShortener $shortener;

    public function __construct()
    {
        $this->urlModel = new Url();
        $this->shortener = new UrlShortener();
    }

    /**
     * @return void
     * function for shortening url
     */
    public function shorten(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        echo json_encode($input);

        if (!isset($input['url']) || !$this->shortener->isValidUrl($input['url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid URL provided']);
            return;
        }

        $originalUrl = $input['url'];
        
        // Check if URL already exists
        $existingUrl = $this->urlModel->findByOriginalUrl($originalUrl);
        if ($existingUrl) {
            echo json_encode([
                'short_url' => $this->getBaseUrl() . '/' . $existingUrl['short_code'],
                'short_code' => $existingUrl['short_code'],
                'original_url' => $existingUrl['original_url']
            ]);
            return;
        }

        // Create new short URL
        $shortCode = $this->shortener->generateUniqueShortCode();
        $urlId = $this->urlModel->create($originalUrl, $shortCode);

        if ($urlId) {
            echo json_encode([
                'short_url' => $this->getBaseUrl() . '/' . $shortCode,
                'short_code' => $shortCode,
                'original_url' => $originalUrl
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create short URL']);
        }
    }

    /**
     * @param string $shortCode
     * @return void
     * function for redirecting to original url
     */
    public function redirect(string $shortCode): void
    {
        $url = $this->urlModel->findByShortCode($shortCode);
        
        if (!$url) {
            http_response_code(404);
            echo 'URL not found';
            return;
        }

        $this->urlModel->incrementClicks($url['id']);
        header('Location: ' . $url['original_url'], true, 301);
        exit;
    }

    /**
     * @return string
     * function for getting base url
     */
    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return "$protocol://$host";
    }
}
