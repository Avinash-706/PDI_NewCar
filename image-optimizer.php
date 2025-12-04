<?php
/**
 * Fast Image Optimizer
 * Optimizes images for PDF generation with minimal processing time
 * Cross-platform compatible with dynamic path handling
 */

require_once __DIR__ . '/init-directories.php';

class ImageOptimizer {
    
    private static $cache = [];
    private static $gdAvailable = null;
    
    /**
     * Check if GD extension is available
     */
    private static function checkGDAvailability() {
        if (self::$gdAvailable !== null) {
            return self::$gdAvailable;
        }
        
        if (!extension_loaded('gd')) {
            self::$gdAvailable = false;
            error_log('ERROR: GD extension is not loaded. Please install php-gd extension.');
            return false;
        }
        
        // Check for required functions
        $requiredFunctions = [
            'imagecreatefromjpeg',
            'imagecreatefrompng',
            'imagecreatefromgif',
            'imagecreatetruecolor',
            'imagecopyresampled',
            'imagejpeg',
            'imagedestroy',
            'getimagesize'
        ];
        
        foreach ($requiredFunctions as $func) {
            if (!function_exists($func)) {
                self::$gdAvailable = false;
                error_log("ERROR: Required GD function '$func' is not available.");
                return false;
            }
        }
        
        self::$gdAvailable = true;
        return true;
    }
    
    /**
     * Normalize path to work cross-platform
     */
    private static function normalizePath($path) {
        // Convert to absolute path if relative
        if (!self::isAbsolutePath($path)) {
            $path = __DIR__ . DIRECTORY_SEPARATOR . $path;
        }
        
        // Normalize directory separators
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        // Resolve real path
        $realPath = realpath($path);
        if ($realPath !== false) {
            return $realPath;
        }
        
        return $path;
    }
    
    /**
     * Check if path is absolute
     */
    private static function isAbsolutePath($path) {
        // Windows: C:\ or \\server\share
        if (preg_match('/^[a-zA-Z]:\\\\/', $path) || preg_match('/^\\\\\\\\/', $path)) {
            return true;
        }
        // Unix: /path
        if (substr($path, 0, 1) === '/') {
            return true;
        }
        return false;
    }
    
    /**
     * Ensure directory exists with proper permissions
     */
    private static function ensureDirectory($dirPath) {
        $normalizedPath = self::normalizePath($dirPath);
        
        if (!file_exists($normalizedPath)) {
            if (!@mkdir($normalizedPath, 0755, true)) {
                throw new Exception("Failed to create directory: $normalizedPath");
            }
        }
        
        if (!is_writable($normalizedPath)) {
            throw new Exception("Directory is not writable: $normalizedPath");
        }
        
        return $normalizedPath;
    }
    
    /**
     * Fast image compression and encoding
     * Uses caching to avoid reprocessing same images
     * Optimized for mPDF memory efficiency
     */
    public static function optimizeForPDF($imagePath, $maxWidth = 1200, $quality = 65) {
        // Check GD availability
        if (!self::checkGDAvailability()) {
            error_log('GD extension not available for image optimization');
            return '';
        }
        
        // Normalize path
        $imagePath = self::normalizePath($imagePath);
        
        if (empty($imagePath) || !file_exists($imagePath)) {
            error_log("Image file not found: $imagePath");
            return '';
        }
        
        // Check cache
        $cacheKey = md5($imagePath . $maxWidth . $quality);
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        
        try {
            // Get image info
            $imageInfo = @getimagesize($imagePath);
            if (!$imageInfo) {
                return '';
            }
            
            list($width, $height) = $imageInfo;
            $mimeType = $imageInfo['mime'];
            
            // Always compress for mPDF - don't use base64 for large images
            // Return file path instead of base64 for memory efficiency
            if ($width <= $maxWidth && filesize($imagePath) < 200000) { // < 200KB
                // Small images can use base64
                $result = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($imagePath));
                self::$cache[$cacheKey] = $result;
                return $result;
            }
            
            // Create image resource
            $image = self::createImageResource($imagePath, $mimeType);
            if (!$image) {
                return '';
            }
            
            // Calculate new dimensions
            if ($width > $maxWidth) {
                $ratio = $maxWidth / $width;
                $newWidth = $maxWidth;
                $newHeight = (int)($height * $ratio);
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }
            
            // Create new image
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
                imagefill($newImage, 0, 0, $transparent);
            }
            
            // Resize
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Output to buffer
            ob_start();
            imagejpeg($newImage, null, $quality);
            $imageData = ob_get_clean();
            
            // Clean up
            imagedestroy($image);
            imagedestroy($newImage);
            
            // Encode
            $result = 'data:image/jpeg;base64,' . base64_encode($imageData);
            self::$cache[$cacheKey] = $result;
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Image optimization error: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Create image resource from file
     */
    private static function createImageResource($imagePath, $mimeType) {
        if (!self::checkGDAvailability()) {
            throw new Exception('GD extension is not available');
        }
        
        $imagePath = self::normalizePath($imagePath);
        
        if (!file_exists($imagePath)) {
            throw new Exception("Image file not found: $imagePath");
        }
        
        try {
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/jpg':
                    if (!function_exists('imagecreatefromjpeg')) {
                        throw new Exception('imagecreatefromjpeg function not available');
                    }
                    $resource = @imagecreatefromjpeg($imagePath);
                    break;
                    
                case 'image/png':
                    if (!function_exists('imagecreatefrompng')) {
                        throw new Exception('imagecreatefrompng function not available');
                    }
                    $resource = @imagecreatefrompng($imagePath);
                    break;
                    
                case 'image/gif':
                    if (!function_exists('imagecreatefromgif')) {
                        throw new Exception('imagecreatefromgif function not available');
                    }
                    $resource = @imagecreatefromgif($imagePath);
                    break;
                    
                default:
                    throw new Exception("Unsupported image type: $mimeType");
            }
            
            if ($resource === false) {
                throw new Exception("Failed to create image resource from: $imagePath");
            }
            
            return $resource;
            
        } catch (Exception $e) {
            error_log('Image resource creation error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Batch optimize multiple images (parallel processing simulation)
     */
    public static function batchOptimize($imagePaths, $maxWidth = 600, $quality = 70) {
        $results = [];
        foreach ($imagePaths as $key => $path) {
            $results[$key] = self::optimizeForPDF($path, $maxWidth, $quality);
        }
        return $results;
    }
    
    /**
     * Clear cache to free memory
     */
    public static function clearCache() {
        self::$cache = [];
    }
    
    /**
     * Resize image to uniform dimensions for PDF
     * All images will be exactly the same size in the PDF
     * 
     * @param string $imagePath Path to the image file
     * @param int $uniformWidth Target width (default 400px)
     * @param int $uniformHeight Target height (default 300px)
     * @param int $quality JPEG quality (default 75)
     * @return string Path to resized image or original if error
     */
    public static function resizeToUniform($imagePath, $uniformWidth = 400, $uniformHeight = 300, $quality = 75) {
        // Check GD availability
        if (!self::checkGDAvailability()) {
            error_log('GD extension not available for uniform resize');
            return $imagePath;
        }
        
        // Normalize path
        $imagePath = self::normalizePath($imagePath);
        
        if (empty($imagePath) || !file_exists($imagePath)) {
            error_log("Image file not found for uniform resize: $imagePath");
            return $imagePath;
        }
        
        try {
            // Get uniform directory using DirectoryManager
            $uniformDir = DirectoryManager::getUniformDir($imagePath);
            
            $filename = basename($imagePath);
            $uniformPath = $uniformDir . DIRECTORY_SEPARATOR . 'uniform_' . $uniformWidth . 'x' . $uniformHeight . '_' . $filename;
            
            // If uniform version exists and is newer, use it
            if (file_exists($uniformPath) && filemtime($uniformPath) >= filemtime($imagePath)) {
                return $uniformPath;
            }
            
            // Get image info
            $imageInfo = @getimagesize($imagePath);
            if (!$imageInfo) {
                return $imagePath;
            }
            
            list($origWidth, $origHeight) = $imageInfo;
            $mimeType = $imageInfo['mime'];
            
            // Create image resource
            $image = self::createImageResource($imagePath, $mimeType);
            if (!$image) {
                return $imagePath;
            }
            
            // Create canvas with uniform dimensions
            $canvas = imagecreatetruecolor($uniformWidth, $uniformHeight);
            
            // Fill with white background
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
            
            // Calculate aspect ratio to fit image within uniform dimensions
            $origRatio = $origWidth / $origHeight;
            $targetRatio = $uniformWidth / $uniformHeight;
            
            if ($origRatio > $targetRatio) {
                // Image is wider - fit to width
                $newWidth = $uniformWidth;
                $newHeight = (int)($uniformWidth / $origRatio);
                $x = 0;
                $y = (int)(($uniformHeight - $newHeight) / 2);
            } else {
                // Image is taller - fit to height
                $newHeight = $uniformHeight;
                $newWidth = (int)($uniformHeight * $origRatio);
                $x = (int)(($uniformWidth - $newWidth) / 2);
                $y = 0;
            }
            
            // Resize and center image on canvas
            imagecopyresampled($canvas, $image, $x, $y, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            
            // Save as JPEG
            imagejpeg($canvas, $uniformPath, $quality);
            
            // Clean up
            imagedestroy($image);
            imagedestroy($canvas);
            
            return $uniformPath;
            
        } catch (Exception $e) {
            error_log('Uniform resize error: ' . $e->getMessage());
            return $imagePath;
        }
    }
    
    /**
     * Compress image to file (for mPDF memory efficiency)
     * Returns path to compressed image
     */
    public static function compressToFile($imagePath, $maxWidth = 1200, $quality = 65) {
        // Check GD availability
        if (!self::checkGDAvailability()) {
            error_log('GD extension not available for image compression');
            return $imagePath;
        }
        
        // Normalize path
        $imagePath = self::normalizePath($imagePath);
        
        if (empty($imagePath) || !file_exists($imagePath)) {
            error_log("Image file not found for compression: $imagePath");
            return $imagePath;
        }
        
        try {
            // Get compressed directory using DirectoryManager
            $compressedDir = DirectoryManager::getCompressedDir($imagePath);
            
            $filename = basename($imagePath);
            $compressedPath = $compressedDir . DIRECTORY_SEPARATOR . 'compressed_' . $filename;
            
            // If compressed version exists and is newer, use it
            if (file_exists($compressedPath) && filemtime($compressedPath) >= filemtime($imagePath)) {
                return $compressedPath;
            }
            
            // Get image info
            $imageInfo = @getimagesize($imagePath);
            if (!$imageInfo) {
                return $imagePath;
            }
            
            list($width, $height) = $imageInfo;
            $mimeType = $imageInfo['mime'];
            
            // If already small, just copy
            if ($width <= $maxWidth && filesize($imagePath) < 300000) {
                copy($imagePath, $compressedPath);
                return $compressedPath;
            }
            
            // Create image resource
            $image = self::createImageResource($imagePath, $mimeType);
            if (!$image) {
                return $imagePath;
            }
            
            // Calculate new dimensions
            if ($width > $maxWidth) {
                $ratio = $maxWidth / $width;
                $newWidth = $maxWidth;
                $newHeight = (int)($height * $ratio);
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }
            
            // Create new image
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG (but convert to JPG for PDF)
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            
            // Resize
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Save as JPEG (smaller than PNG)
            imagejpeg($newImage, $compressedPath, $quality);
            
            // Clean up
            imagedestroy($image);
            imagedestroy($newImage);
            
            return $compressedPath;
            
        } catch (Exception $e) {
            error_log('Image compression error: ' . $e->getMessage());
            return $imagePath;
        }
    }
}
