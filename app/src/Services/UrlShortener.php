<?php

namespace App\Services;

class UrlShortener
{
    private const string CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const int BASE = 62;

    public function generateShortCode(int $id): string
    {
        if ($id === 0) {
            return self::CHARS[0];
        }

        $result = '';
        while ($id > 0) {
            $result = self::CHARS[$id % self::BASE] . $result;
            $id = floor($id / self::BASE);
        }

        return $result;
    }

    public function generateUniqueShortCode(): string
    {
        $timestamp = microtime(true);
        $randomPart = mt_rand(1000, 9999);
        $uniqueId = (int)($timestamp * 1000) + $randomPart;
        
        return $this->generateShortCode($uniqueId);
    }

    public function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
