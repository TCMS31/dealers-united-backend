<?php

namespace App\Support\Masking;

/**
 * How a sealed note is rendered to its owner before its opening time.
 *
 * This is the app's extension seam. The brief asks for a "Tomo****" style
 * teaser, but the rule is the single thing a product owner is most likely to
 * want changed (mask everything, show a word count, show the first line).
 * Bind a different implementation in `config/capsules.php` rather than editing
 * the resource that renders the capsule.
 */
interface NoteMasker
{
    /**
     * @param  string  $note  the full, plaintext note
     * @return string the masked form, safe to send to the client
     */
    public function mask(string $note): string;
}
