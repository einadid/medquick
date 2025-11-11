<?php
function optimizeAndResizeImage($source_path, $destination_path, $max_width = 800, $quality = 75) {
    // Get image info
    $info = getimagesize($source_path);
    $mime = $info['mime'];
    
    // Create image from source
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    // Get original dimensions
    $original_width = imagesx($image);
    $original_height = imagesy($image);
    
    // Calculate new dimensions
    $ratio = $original_width / $original_height;
    $new_width = $max_width;
    $new_height = $max_width / $ratio;
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG
    if ($mime == 'image/png') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize image
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, 
                      $new_width, $new_height, $original_width, $original_height);
    
    // Save optimized image
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($new_image, $destination_path, $quality);
            break;
        case 'image/png':
            imagepng($new_image, $destination_path, 9); // Compression level 0-9
            break;
        case 'image/gif':
            imagegif($new_image, $destination_path);
            break;
    }
    
    // Free up memory
    imagedestroy($image);
    imagedestroy($new_image);
    
    return true;
}

// Example usage:
// optimizeAndResizeImage('path/to/source.jpg', 'path/to/destination.jpg', 800, 75);
?>