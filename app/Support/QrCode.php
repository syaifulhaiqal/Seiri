<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR codes as inline SVG (office clock-in screen, shared clock-in links). The SVG is built here from our own
 * URLs, so it is safe to print unescaped.
 */
class QrCode
{
    public static function svg(string $text, int $size = 360): string
    {
        return (new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd)))->writeString($text);
    }
}
