<?php

namespace App\Services;

use Illuminate\Support\Str;

class SeoAnalyzerService
{
    public function analyze(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        $seoTitle = trim((string) ($data['yoast_title'] ?? $title));
        $metaDescription = trim((string) ($data['yoast_metadesc'] ?? ''));
        $focusKeyword = trim((string) ($data['yoast_focuskw'] ?? ''));
        $altText = trim((string) ($data['image_alt_text'] ?? ''));

        $wordCount = $this->wordCount($content);
        $sentences = max(1, preg_split('/[.!?]+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ? count(preg_split('/[.!?]+/u', $content, -1, PREG_SPLIT_NO_EMPTY)) : 1);
        $sentenceCount = max(1, $sentences);

        $parts = [
            $this->scoreTitle($seoTitle),
            $this->scoreMetaDescription($metaDescription),
            $this->scoreSlug($slug, $focusKeyword),
            $this->scoreFocusKeyword($focusKeyword),
            $this->scoreKeywordInTitle($seoTitle, $focusKeyword),
            $this->scoreKeywordInHeading($content, $focusKeyword),
            $this->scoreKeywordDensity($content, $focusKeyword),
            $this->scoreInternalLink($content),
            $this->scoreExternalLink($content),
            $this->scoreAltImage($altText),
            $this->scoreContentLength($wordCount),
            $this->scoreReadability($wordCount, $sentenceCount),
        ];

        $score = (int) round(array_sum(array_column($parts, 'score')));

        return [
            'score' => min(100, max(0, $score)),
            'word_count' => $wordCount,
            'estimated_reading_time_minutes' => max(1, (int) ceil($wordCount / 200)),
            'content_score' => $parts[11]['score'],
            'breakdown' => $parts,
            'category' => $score >= 80 ? 'Good' : ($score >= 60 ? 'Needs Improvement' : 'Poor'),
            'can_publish' => $score >= 80,
            'recommendations' => $this->recommendations($parts),
        ];
    }

    public function wordCount(string $content): int
    {
        $plain = trim(preg_replace('/<[^>]+>/', ' ', $content) ?? '');

        if ($plain === '') {
            return 0;
        }

        return str_word_count(Str::lower($plain));
    }

    private function scoreTitle(string $seoTitle): array
    {
        $length = mb_strlen($seoTitle);
        $score = $length >= 50 && $length <= 60 ? 15 : ($length >= 35 && $length <= 75 ? 10 : ($length > 0 ? 5 : 0));

        return ['label' => 'SEO Title', 'score' => $score, 'max' => 15, 'note' => $length > 0 ? "{$length} karakter" : 'Belum diisi'];
    }

    private function scoreMetaDescription(string $metaDescription): array
    {
        $length = mb_strlen($metaDescription);
        $score = $length >= 120 && $length <= 156 ? 10 : ($length >= 90 && $length <= 180 ? 6 : ($length > 0 ? 3 : 0));

        return ['label' => 'Meta Description', 'score' => $score, 'max' => 10, 'note' => $length > 0 ? "{$length} karakter" : 'Belum diisi'];
    }

    private function scoreSlug(string $slug, string $focusKeyword): array
    {
        $normalizedSlug = Str::of($slug)->lower();
        $keyword = Str::of($focusKeyword)->lower();
        $score = 0;

        if ($normalizedSlug->isNotEmpty()) {
            $score += Str::contains($normalizedSlug, $keyword) && $keyword->isNotEmpty() ? 6 : 3;
            $score += preg_match('/^[a-z0-9\-]+$/', $slug) ? 4 : 0;
        }

        return ['label' => 'URL Slug', 'score' => min(10, $score), 'max' => 10, 'note' => $slug ?: 'Belum diisi'];
    }

    private function scoreFocusKeyword(string $focusKeyword): array
    {
        $score = $focusKeyword !== '' ? 10 : 0;

        return ['label' => 'Focus Keyword', 'score' => $score, 'max' => 10, 'note' => $focusKeyword ?: 'Belum diisi'];
    }

    private function scoreKeywordInTitle(string $seoTitle, string $focusKeyword): array
    {
        $score = $focusKeyword !== '' && Str::contains(Str::lower($seoTitle), Str::lower($focusKeyword)) ? 10 : 0;

        return ['label' => 'Keyword di Title', 'score' => $score, 'max' => 10, 'note' => $score ? 'Keyword ditemukan' : 'Keyword belum masuk title'];
    }

    private function scoreKeywordInHeading(string $content, string $focusKeyword): array
    {
        $score = 0;

        if ($focusKeyword !== '' && Str::contains(Str::lower($content), Str::lower($focusKeyword))) {
            $score = 10;
        }

        return ['label' => 'Keyword di Heading', 'score' => $score, 'max' => 10, 'note' => $score ? 'Keyword ditemukan' : 'Tambahkan keyword pada heading'];
    }

    private function scoreKeywordDensity(string $content, string $focusKeyword): array
    {
        $wordCount = max(1, $this->wordCount($content));
        $keywordCount = $focusKeyword !== '' ? substr_count(Str::lower($content), Str::lower($focusKeyword)) : 0;
        $density = ($keywordCount * 100) / $wordCount;
        $score = $density >= 1 && $density <= 2.5 ? 10 : ($density > 0 ? 6 : 0);

        return ['label' => 'Keyword Density', 'score' => $score, 'max' => 10, 'note' => number_format($density, 2).' %'];
    }

    private function scoreInternalLink(string $content): array
    {
        $matches = [];
        preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches);
        $score = collect($matches[1] ?? [])->contains(fn (string $url) => str_starts_with($url, '/') || ! str_starts_with($url, 'http')) ? 10 : 0;

        return ['label' => 'Internal Link', 'score' => $score, 'max' => 10, 'note' => $score ? 'Tautan internal ditemukan' : 'Tambahkan minimal 1 internal link'];
    }

    private function scoreExternalLink(string $content): array
    {
        $matches = [];
        preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches);
        $score = collect($matches[1] ?? [])->contains(fn (string $url) => str_starts_with($url, 'http')) ? 5 : 0;

        return ['label' => 'External Link', 'score' => $score, 'max' => 5, 'note' => $score ? 'Tautan eksternal ditemukan' : 'Tambahkan minimal 1 external link'];
    }

    private function scoreAltImage(string $altText): array
    {
        $score = $altText !== '' ? 5 : 0;

        return ['label' => 'Alt Image', 'score' => $score, 'max' => 5, 'note' => $altText ?: 'Alt text belum ada'];
    }

    private function scoreContentLength(int $wordCount): array
    {
        $score = $wordCount >= 800 ? 10 : ($wordCount >= 500 ? 6 : ($wordCount > 0 ? 3 : 0));

        return ['label' => 'Content Length', 'score' => $score, 'max' => 10, 'note' => $wordCount.' kata'];
    }

    private function scoreReadability(int $wordCount, int $sentenceCount): array
    {
        $averageSentenceLength = $wordCount / max(1, $sentenceCount);
        $score = $averageSentenceLength <= 20 ? 5 : ($averageSentenceLength <= 28 ? 3 : 1);

        return ['label' => 'Readability', 'score' => $score, 'max' => 5, 'note' => number_format($averageSentenceLength, 1).' kata/sentences'];
    }

    private function recommendations(array $parts): array
    {
        return collect($parts)
            ->filter(fn (array $item) => $item['score'] < $item['max'])
            ->map(fn (array $item) => $item['label'].': '.$item['note'])
            ->values()
            ->all();
    }
}
