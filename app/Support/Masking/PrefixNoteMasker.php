<?php

namespace App\Support\Masking;

/**
 * The default rule: keep the first few characters as a teaser, replace the
 * rest with a fixed run of asterisks.
 *
 * Two details the naive `substr($note, 0, 4).'****'` gets wrong:
 *
 *  - `substr` counts bytes, so a multi-byte note ("héllo…") is cut mid
 *    character and can produce an invalid UTF-8 sequence that `json_encode`
 *    refuses to serialise. This counts characters.
 *  - A note no longer than the preview is revealed in full while still
 *    sealed ("Hi" became "Hi****"). Such notes are masked entirely.
 */
final class PrefixNoteMasker implements NoteMasker
{
    public function __construct(
        private readonly int $previewLength = 4,
        private readonly string $mask = '****',
    ) {
    }

    public function mask(string $note): string
    {
        if (mb_strlen($note) <= $this->previewLength) {
            return $this->mask;
        }

        return mb_substr($note, 0, $this->previewLength).$this->mask;
    }
}
