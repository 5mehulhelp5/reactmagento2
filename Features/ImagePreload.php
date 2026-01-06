<?php

namespace React\React\Features;

class ImagePreload
{
    /**
     * Preload image using HTTP Link header
     * 
     * @param string $imageUrl The URL of the image to preload
     * @param bool $isBase64 Whether the image is base64 encoded (skip preload if true)
     * @param string $fetchpriority Priority level: 'high', 'low', or 'auto' (default: 'high')
     * @return void
     */
    public function preloadImage($imageUrl, $isBase64 = false, $fetchpriority = 'high')
    {
        if (!$isBase64 && !empty($imageUrl)) {
            // Use @header() with false parameter to allow setting header even after headers started
            // This matches the pattern used in ReactInjectPlugin.php
            @header("Link: <" . $imageUrl . ">; rel=preload; as=image; fetchpriority=" . $fetchpriority, false);
        }
    }

    /**
     * Preload multiple images using HTTP Link header
     * 
     * @param array $imageUrls Array of image URLs to preload
     * @param bool $isBase64 Whether the images are base64 encoded (skip preload if true)
     * @param string $fetchpriority Priority level: 'high', 'low', or 'auto' (default: 'high')
     * @return void
     */
    public function preloadImages(array $imageUrls, $isBase64 = false, $fetchpriority = 'high')
    {
        if ($isBase64 || headers_sent() || empty($imageUrls)) {
            return;
        }

        $links = [];
        foreach ($imageUrls as $imageUrl) {
            if (!empty($imageUrl)) {
                $links[] = "<" . $imageUrl . ">; rel=preload; as=image; fetchpriority=" . $fetchpriority;
            }
        }

        if (!empty($links)) {
            header("Link: " . implode(", ", $links));
        }
    }
}
