<?php

namespace App\Controllers;

use App\Services\ApiResponse;
use App\Services\UrlService;

class ApiController
{
    private UrlService $urlService;

    public function __construct(UrlService $urlService)
    {
        $this->urlService = $urlService;
    }

    public function shorten(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['url'])) {
            ApiResponse::error('URL is required');
        }

        try {
            $result = $this->urlService->createShortUrl($input['url']);
            ApiResponse::success($result);
        } catch (\InvalidArgumentException $e) {
            ApiResponse::error($e->getMessage());
        } catch (\RuntimeException $e) {
            ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function redirect(string $shortCode): void
    {
        $url = $this->urlService->getUrlByShortCode($shortCode);
        
        if (!$url) {
            ApiResponse::error('URL not found', 404);
        }

        $this->urlService->incrementClicks($url['id']);
        header('Location: ' . $url['original_url'], true, 301);
        exit;
    }
}
