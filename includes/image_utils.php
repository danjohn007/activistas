<?php
/**
 * Image Utilities
 * Funciones para optimización y compresión de imágenes
 */

/**
 * Compress and optimize an image file
 * 
 * @param string $sourcePath Path to source image
 * @param string $destinationPath Path where to save compressed image
 * @param int $maxWidth Maximum width (default: 1920px)
 * @param int $maxHeight Maximum height (default: 1920px)
 * @param int $quality JPEG quality 0-100 (default: 80)
 * @return array ['success' => bool, 'error' => string|null, 'original_size' => int, 'compressed_size' => int, 'savings' => string]
 */
function compressImage($sourcePath, $destinationPath, $maxWidth = 1920, $maxHeight = 1920, $quality = 80) {
    try {
        // Get original file size
        $originalSize = filesize($sourcePath);
        
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            return ['success' => false, 'error' => 'No es un archivo de imagen válido'];
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Create image resource based on type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                return ['success' => false, 'error' => 'Formato de imagen no soportado'];
        }
        
        if ($sourceImage === false) {
            return ['success' => false, 'error' => 'Error al crear recurso de imagen'];
        }
        
        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        
        // Only resize if image is larger than maximum dimensions
        if ($ratio < 1) {
            $newWidth = intval($width * $ratio);
            $newHeight = intval($height * $ratio);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        
        // Create new image with calculated dimensions
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Copy and resize
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save compressed image
        $success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($resizedImage, $destinationPath, $quality);
                break;
            case IMAGETYPE_PNG:
                // PNG quality is 0-9, convert from 0-100 scale
                $pngQuality = 9 - intval($quality / 11);
                $success = imagepng($resizedImage, $destinationPath, $pngQuality);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($resizedImage, $destinationPath);
                break;
            case IMAGETYPE_WEBP:
                $success = imagewebp($resizedImage, $destinationPath, $quality);
                break;
        }
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        if (!$success) {
            return ['success' => false, 'error' => 'Error al guardar imagen comprimida'];
        }
        
        // Get compressed file size
        $compressedSize = filesize($destinationPath);
        $savings = round((($originalSize - $compressedSize) / $originalSize) * 100, 1);
        
        return [
            'success' => true,
            'error' => null,
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'savings' => $savings,
            'dimensions' => ['width' => $newWidth, 'height' => $newHeight]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error al procesar imagen: ' . $e->getMessage()];
    }
}

/**
 * Validate image dimensions
 * 
 * @param string $filePath Path to image file
 * @param int $maxWidth Maximum allowed width
 * @param int $maxHeight Maximum allowed height
 * @return array ['valid' => bool, 'width' => int, 'height' => int, 'error' => string|null]
 */
function validateImageDimensions($filePath, $maxWidth = 4096, $maxHeight = 4096) {
    $imageInfo = getimagesize($filePath);
    
    if ($imageInfo === false) {
        return ['valid' => false, 'error' => 'No es un archivo de imagen válido'];
    }
    
    list($width, $height) = $imageInfo;
    
    if ($width > $maxWidth || $height > $maxHeight) {
        return [
            'valid' => false,
            'width' => $width,
            'height' => $height,
            'error' => "Dimensiones de imagen exceden el límite ({$maxWidth}x{$maxHeight}px)"
        ];
    }
    
    return [
        'valid' => true,
        'width' => $width,
        'height' => $height,
        'error' => null
    ];
}

/**
 * Get optimal compression quality based on file size
 * 
 * @param int $fileSize File size in bytes
 * @return int Quality value (0-100)
 */
function getOptimalQuality($fileSize) {
    // For files under 500KB, use high quality
    if ($fileSize < 512000) {
        return 85;
    }
    // For files 500KB-2MB, use medium quality
    elseif ($fileSize < 2097152) {
        return 75;
    }
    // For files over 2MB, use lower quality for more compression
    else {
        return 70;
    }
}

/**
 * Check if file is an image based on MIME type
 * 
 * @param string $filePath Path to file
 * @return bool True if file is an image
 */
function isImageFile($filePath) {
    $imageInfo = getimagesize($filePath);
    return $imageInfo !== false;
}

/**
 * Format file size for human reading
 * 
 * @param int $bytes File size in bytes
 * @param int $decimals Number of decimal places
 * @return string Formatted size (e.g., "1.5 MB")
 */
function formatFileSize($bytes, $decimals = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $decimals) . ' ' . $units[$pow];
}
?>
