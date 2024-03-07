<?php

namespace Tests\Unit;

use App\Support\Masking\PrefixNoteMasker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PrefixNoteMaskerTest extends TestCase
{
    #[Test]
    public function it_keeps_a_four_character_teaser(): void
    {
        $masker = new PrefixNoteMasker();

        $this->assertSame('Tomo****', $masker->mask('Tomorrow you will be proud of this.'));
    }

    #[Test]
    public function it_masks_a_note_no_longer_than_the_preview_entirely(): void
    {
        $masker = new PrefixNoteMasker();

        // "Hi****" leaked the whole note while it was still sealed.
        $this->assertSame('****', $masker->mask('Hi'));
        $this->assertSame('****', $masker->mask('Four'));
    }

    #[Test]
    public function it_counts_characters_not_bytes(): void
    {
        $masker = new PrefixNoteMasker();

        // substr() cut this after four *bytes* ("hél"), and on other notes
        // could leave an invalid UTF-8 sequence that json_encode rejects.
        $this->assertSame('héll****', $masker->mask('héllo wörld secret'));
        $this->assertSame('日本語で****', $masker->mask('日本語でこんにちは'));
    }

    #[Test]
    public function the_preview_length_is_configurable(): void
    {
        $this->assertSame('To****', (new PrefixNoteMasker(previewLength: 2))->mask('Tomorrow'));
        $this->assertSame('****', (new PrefixNoteMasker(previewLength: 0))->mask('Tomorrow'));
    }
}
