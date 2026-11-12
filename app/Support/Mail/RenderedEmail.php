<?php

namespace App\Support\Mail;

use Illuminate\Support\HtmlString;

final readonly class RenderedEmail
{
    /**
     * @param  list<HtmlString>  $paragraphs  already escaped
     * @param  list<HtmlString>  $footer  already escaped
     */
    public function __construct(
        public string $subject,
        public string $heading,
        public array $paragraphs,
        public array $footer,
    ) {}
}
