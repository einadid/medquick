<?php
class SEO {
    private $title;
    private $description;
    private $keywords;
    private $image;
    private $url;
    private $type;
    
    public function __construct() {
        $this->url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $this->type = 'website';
        $this->image = 'https://yourwebsite.com/assets/images/logo.png'; // Default image
    }
    
    public function setTitle($title) {
        $this->title = $title . ' - QuickMed';
        return $this;
    }
    
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }
    
    public function setKeywords($keywords) {
        $this->keywords = is_array($keywords) ? implode(', ', $keywords) : $keywords;
        return $this;
    }
    
    public function setImage($image) {
        $this->image = $image;
        return $this;
    }
    
    public function setType($type) {
        $this->type = $type;
        return $this;
    }
    
    public function render() {
        $html = '';
        
        // Basic meta tags
        $html .= '<title>' . htmlspecialchars($this->title) . '</title>' . PHP_EOL;
        $html .= '<meta name="description" content="' . htmlspecialchars($this->description) . '">' . PHP_EOL;
        $html .= '<meta name="keywords" content="' . htmlspecialchars($this->keywords) . '">' . PHP_EOL;
        
        // Open Graph / Facebook
        $html .= '<meta property="og:type" content="' . $this->type . '">' . PHP_EOL;
        $html .= '<meta property="og:url" content="' . $this->url . '">' . PHP_EOL;
        $html .= '<meta property="og:title" content="' . htmlspecialchars($this->title) . '">' . PHP_EOL;
        $html .= '<meta property="og:description" content="' . htmlspecialchars($this->description) . '">' . PHP_EOL;
        $html .= '<meta property="og:image" content="' . $this->image . '">' . PHP_EOL;
        
        // Twitter
        $html .= '<meta name="twitter:card" content="summary_large_image">' . PHP_EOL;
        $html .= '<meta name="twitter:url" content="' . $this->url . '">' . PHP_EOL;
        $html .= '<meta name="twitter:title" content="' . htmlspecialchars($this->title) . '">' . PHP_EOL;
        $html .= '<meta name="twitter:description" content="' . htmlspecialchars($this->description) . '">' . PHP_EOL;
        $html .= '<meta name="twitter:image" content="' . $this->image . '">' . PHP_EOL;
        
        // Canonical URL
        $html .= '<link rel="canonical" href="' . $this->url . '">' . PHP_EOL;
        
        return $html;
    }
}

// Example usage:
// $seo = new SEO();
// $seo->setTitle('Home Page')
//     ->setDescription('Welcome to QuickMed - Your trusted online pharmacy')
//     ->setKeywords(['online pharmacy', 'medicine delivery', 'healthcare'])
//     ->setImage('https://yourwebsite.com/assets/images/og-image.jpg');
// echo $seo->render();
?>