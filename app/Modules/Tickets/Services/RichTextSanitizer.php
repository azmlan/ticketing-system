<?php

namespace App\Modules\Tickets\Services;

class RichTextSanitizer
{
    // Tags allowed per SPEC §3.2
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li><a><blockquote><h1><h2><h3><h4><table><thead><tbody><tr><th><td><span>';

    public function sanitize(string $html): string
    {
        // Strip tags outside the whitelist (including <script>, <style>, <object>, etc.)
        $output = strip_tags($html, self::ALLOWED_TAGS);

        // Strip event handler attributes (onclick=, onload=, onerror=, etc.)
        $output = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $output);
        $output = preg_replace('/\s+on\w+\s*=\s*\'[^\']*\'/i', '', $output);

        // Strip javascript: URIs from href/src/action attributes
        $output = preg_replace(
            '/(\s+(?:href|src|action)\s*=\s*["\']?)\s*javascript:[^"\'>\s]*/i',
            '$1#',
            $output
        );

        return $output;
    }
}
