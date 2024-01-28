<?php

namespace App;

class Product
{
    public $capacity;
    public $title;
    public $price;
    public $image;
    public $availability;
    public $shippingText;
    public $colours;

    public function __construct($capacity, $title, $price, $image, $availability, $shippingText, $colours)
    {
        $this->capacity = $capacity;
        $this->title = $title;
        $this->price = $price;
        $this->image = $image;
        $this->availability = $availability;
        $this->shippingText = $shippingText;
        $this->colours = $colours; 
    }

    public function getCapacityInMb()
    {
        if (preg_match('/^(\d+)\s*GB$/i', $this->capacity, $matches)) {
            $size = (int)$matches[1];
            $size *= 1024;
            return $size;
        } elseif (preg_match('/(\d+)\s?mb$/i', $this->capacity, $matches)) {
           return (int)$matches[1];
        } else {
            return '';
        }
    }
    
    public function getavailabilityText()
    {
        return (str_contains($this->availability, 'In Stock')) ? 'In Stock' : (str_contains($this->availability, 'Out of Stock') ? 'Out of Stock' : 'Invalid Data');
    }

    public function getIsAvailable()
    {
        return (str_contains($this->availability, 'In Stock')) ? true : false;
    }

    public function getShippingDate()
    {
        $patternwithDateSuffix = '~\b(\d{1,2}(st|nd|rd|th)\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4})\b~';
        $patternwithoutDateSuffix = '~\b(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4})\b~';

        if (preg_match($patternwithDateSuffix, $this->shippingText, $matches) || preg_match($patternwithoutDateSuffix, $this->shippingText, $matches)) {
            $date = $matches[0];
            $parsedDate = date_create_from_format('jS M Y', $date);
            return $parsedDate->format('d-m-Y');
        } else {
            return '';
        }
    }

    public function getImage()
    {
        $this->image = stripslashes($this->image);
        if (substr($this->image, 0, 3) === '../') {
            $this->image = substr($this->image, 3);
        }
        
        return $_ENV['IMAGE_BASE_URL'] . $this->image;
    }
}
