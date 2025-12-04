<?php
/**
 * Directory Initialization System
 * Ensures all required directories exist with proper permissions
 * Cross-platform compatible
 */

class DirectoryManager {
    
    private static $initialized = false;
    private static $baseDir = null;
    
    /**
     * Required directories for the application
     * Optimized structure - removed unused folders
     */
    private static $requiredDirectories = [
        'uploads',
        'uploads/drafts',
        'uploads/drafts/compressed',
        'uploads/drafts/uniform',
        'pdfs',
        'tmp',
        'tmp/mpdf',
        'logs',
        'drafts',
        'drafts/audit'
    ];
    
    /**
     * Initialize all required directories
     */
    public static function init() {
        if (self::$initialized) {
            return true;
        }
        
        self::$baseDir = self::getBaseDir();
        
        $errors = [];
        
        foreach (self::$requiredDirectories as $dir) {
            $fullPath = self::$baseDir . DIRECTORY_SEPARATOR . $dir;
            
            try {
                self::ensureDirectory($fullPath);
            } catch (Exception $e) {
                $errors[] = "Failed to create directory '$dir': " . $e->getMessage();
                error_log($e->getMessage());
            }
        }
        
        if (!empty($errors)) {
            throw new Exception("Directory initialization failed:\n" . implode("\n", $errors));
        }
        
        self::$initialized = true;
        return true;
    }
    
    /**
     * Get base directory of the application
     */
    public static function getBaseDir() {
        if (self::$baseDir !== null) {
            return self::$baseDir;
        }
        
        // Use __DIR__ of this file as base
        self::$baseDir = __DIR__;
        return self::$baseDir;
    }
    
    /**
     * Ensure a directory exists with proper permissions
     */
    private static function ensureDirectory($path) {
        // Normalize path
        $path = self::normalizePath($path);
        
        // Check if already exists
        if (file_exists($path)) {
            if (!is_dir($path)) {
                throw new Exception("Path exists but is not a directory: $path");
            }
            
            // Check if writable
            if (!is_writable($path)) {
                // Try to fix permissions
                if (!@chmod($path, 0755)) {
                    throw new Exception("Directory exists but is not writable: $path");
                }
            }
            
            return true;
        }
        
        // Create directory
        if (!@mkdir($path, 0755, true)) {
            throw new Exception("Failed to create directory: $path");
        }
        
        // Verify it was created
        if (!file_exists($path)) {
            throw new Exception("Directory creation reported success but directory does not exist: $path");
        }
        
        // Create .gitkeep file to preserve directory in git
        $gitkeepFile = $path . DIRECTORY_SEPARATOR . '.gitkeep';
        if (!file_exists($gitkeepFile)) {
            @file_put_contents($gitkeepFile, '');
        }
        
        return true;
    }
    
    /**
     * Normalize path for cross-platform compatibility
     */
    private static function normalizePath($path) {
        // Convert all separators to system separator
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        // Remove duplicate separators
        $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR, '#') . '+#', DIRECTORY_SEPARATOR, $path);
        
        // Remove trailing separator
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        
        return $path;
    }
    
    /**
     * Get absolute path for a relative path
     */
    public static function getAbsolutePath($relativePath) {
        $baseDir = self::getBaseDir();
        
        // If already absolute, return as-is
        if (self::isAbsolutePath($relativePath)) {
            return self::normalizePath($relativePath);
        }
        
        // Combine base dir with relative path
        $absolutePath = $baseDir . DIRECTORY_SEPARATOR . $relativePath;
        return self::normalizePath($absolutePath);
    }
    
    /**
     * Get relative path from absolute path
     */
    public static function getRelativePath($absolutePath) {
        $baseDir = self::getBaseDir();
        
        // Normalize both paths
        $absolutePath = self::normalizePath($absolutePath);
        $baseDir = self::normalizePath($baseDir);
        
        // If path starts with base dir, remove it
        if (strpos($absolutePath, $baseDir) === 0) {
            $relativePath = substr($absolutePath, strlen($baseDir));
            $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);
            return $relativePath;
        }
        
        return $absolutePath;
    }
    
    /**
     * Convert path to web-friendly format (forward slashes)
     */
    public static function toWebPath($path) {
        return str_replace('\\', '/', $path);
    }
    
    /**
     * Check if path is absolute
     */
    private static function isAbsolutePath($path) {
        // Windows: C:\ or \\server\share
        if (preg_match('/^[a-zA-Z]:[\\\\\/]/', $path) || preg_match('/^\\\\\\\\/', $path)) {
            return true;
        }
        // Unix: /path
        if (substr($path, 0, 1) === '/') {
            return true;
        }
        return false;
    }
    
    /**
     * Get directory for compressed images
     * Always use uploads/drafts/compressed for draft images
     */
    public static function getCompressedDir($sourceFile = null) {
        // Always use drafts/compressed directory for all compressed images
        $compressedDir = self::getAbsolutePath('uploads/drafts/compressed');
        self::ensureDirectory($compressedDir);
        return $compressedDir;
    }
    
    /**
     * Get directory for uniform images
     * Always use uploads/drafts/uniform for draft images
     */
    public static function getUniformDir($sourceFile = null) {
        // Always use drafts/uniform directory for all uniform images
        $uniformDir = self::getAbsolutePath('uploads/drafts/uniform');
        self::ensureDirectory($uniformDir);
        return $uniformDir;
    }
    
    /**
     * Check if all directories are properly initialized
     */
    public static function checkHealth() {
        $results = [];
        
        foreach (self::$requiredDirectories as $dir) {
            $fullPath = self::getAbsolutePath($dir);
            
            $results[$dir] = [
                'exists' => file_exists($fullPath),
                'is_dir' => is_dir($fullPath),
                'writable' => is_writable($fullPath),
                'path' => $fullPath
            ];
        }
        
        return $results;
    }
}

// Auto-initialize on include
try {
    DirectoryManager::init();
} catch (Exception $e) {
    error_log('Directory initialization error: ' . $e->getMessage());
    // Don't throw - let the application handle it
}
