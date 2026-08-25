<?php

namespace App\Services;

use App\Models\ArticleSeoMeta;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Str;

class SeoAnalyzerService
{
    /**
     * Daftar kata transisi/penghubung Bahasa Indonesia.
     */
    private const TRANSITION_WORDS = [
        'selain itu', 'namun', 'tetapi', 'akan tetapi', 'meskipun', 'walaupun',
        'karena', 'sebab', 'akibatnya', 'oleh karena itu', 'consequently',
        'di samping itu', 'furthermore', 'moreover', 'additionally',
        'sebaliknya', 'on the other hand', 'however',
        'pertama', 'kedua', 'ketiga', 'selanjutnya', 'kemudian', 'akhirnya',
        'misalnya', 'contohnya', 'seperti', 'yaitu', 'adalah',
        'jika', 'apabila', 'bila', 'seandainya', 'asalkan',
        'justru', 'bahkan', 'terutama', 'khususnya', 'utamanya',
        'tentu', 'pasti', 'jelas', 'nyatanya', 'faktanya',
        'singkatnya', 'intinya', 'pada dasarnya', 'basically',
        'untuk itu', 'dengan demikian', 'thus', 'therefore',
        'sebagai hasilnya', 'as a result',
        'dengan kata lain', 'in other words',
        'alih-alih', 'instead',
        'lalu', 'setelah itu', 'sebelumnya',
        'di sisi lain', 'on the other side',
        'maka', 'sehingga', 'hingga', 'sampai',
    ];

    /**
     * Pola passive voice Bahasa Indonesia (suffix/inflection).
     */
    private const PASSIVE_PATTERNS = [
        '/\bdi\w{2,}/u',
        '/\bter\w{2,}/u',
        '/\b\d+kan\b/u',
        '/\b\d+i\b/u',
    ];

    /**
     * Jalankan analisis lengkap: SEO + Readability.
     *
     * @param  array<string, mixed>  $data
     * @return array{seo_score: int, readability_score: int, word_count: int, estimated_reading_time_minutes: int, seo_breakdown: array, readability_breakdown: array, category: string, can_publish: bool, recommendations: array}
     */
    public function analyze(array $data): array
    {
        $seoBreakdown = $this->analyzeSeo($data);
        $readabilityBreakdown = $this->analyzeReadability($data);

        $seoScore = min(100, max(0, (int) round(array_sum(array_column($seoBreakdown, 'score')))));
        $readabilityScore = min(100, max(0, (int) round(array_sum(array_column($readabilityBreakdown, 'score')))));

        $content = trim((string) ($data['content'] ?? ''));
        $wordCount = $this->wordCount($content);

        $canPublish = $seoScore >= 80 && $readabilityScore >= 80;
        $category = $this->determineCategory($seoScore, $readabilityScore);

        return [
            'seo_score' => $seoScore,
            'readability_score' => $readabilityScore,
            'word_count' => $wordCount,
            'estimated_reading_time_minutes' => max(1, (int) ceil($wordCount / 200)),
            'seo_breakdown' => $seoBreakdown,
            'readability_breakdown' => $readabilityBreakdown,
            'category' => $category,
            'can_publish' => $canPublish,
            'recommendations' => $this->recommendations($seoBreakdown, $readabilityBreakdown),
        ];
    }

    /**
     * Hitung jumlah kata dari konten HTML.
     */
    public function wordCount(string $content): int
    {
        $plain = trim(preg_replace('/<[^>]+>/', ' ', $content) ?? '');

        if ($plain === '') {
            return 0;
        }

        return str_word_count(Str::lower($plain));
    }

    // ===================================================================
    // SEO ANALYSIS (13 indikator, total = 100)
    // ===================================================================

    /**
     * @return array<int, array{label: string, score: int, max: int, note: string}>
     */
    private function analyzeSeo(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        $seoTitle = trim((string) ($data['yoast_title'] ?? $title));
        $metaDescription = trim((string) ($data['yoast_metadesc'] ?? ''));
        $focusKeyword = trim((string) ($data['yoast_focuskw'] ?? ''));
        $altText = trim((string) ($data['image_alt_text'] ?? ''));
        $firstParagraph = $this->extractFirstParagraph($content, 100);

        return [
            $this->scoreKeyphraseInTitle($seoTitle, $focusKeyword),
            $this->scoreSeoTitleLength($seoTitle),
            $this->scoreKeyphraseInSlug($slug, $focusKeyword),
            $this->scoreKeyphraseInIntroduction($firstParagraph, $focusKeyword),
            $this->scoreKeyphraseDensity($content, $focusKeyword),
            $this->scoreKeyphraseInMetaDescription($metaDescription, $focusKeyword),
            $this->scoreMetaDescriptionLength($metaDescription),
            $this->scoreKeyphraseInSubheading($content, $focusKeyword),
            $this->scoreKeyphraseInAltAttributes($altText, $focusKeyword),
            $this->scoreInternalLinks($content),
            $this->scoreOutboundLinks($content),
            $this->scoreContentWordCount($content),
            $this->scorePreviouslyUsedKeyphrase($focusKeyword, $data['article_id'] ?? null),
        ];
    }

    private function scoreKeyphraseInTitle(string $seoTitle, string $focusKeyword): array
    {
        if ($focusKeyword === '') {
            return ['label' => 'Keyphrase in Title', 'score' => 0, 'max' => 12, 'note' => 'Belum diisi'];
        }

        $lowerTitle = Str::lower($seoTitle);
        $lowerKw = Str::lower($focusKeyword);

        if (! Str::contains($lowerTitle, $lowerKw)) {
            return ['label' => 'Keyphrase in Title', 'score' => 0, 'max' => 12, 'note' => 'Keyword tidak ditemukan di judul'];
        }

        // Bonus jika keyword di posisi awal judul
        $isAtStart = Str::startsWith($lowerTitle, $lowerKw);

        return [
            'label' => 'Keyphrase in Title',
            'score' => $isAtStart ? 12 : 8,
            'max' => 12,
            'note' => $isAtStart ? 'Keyword di awal judul' : 'Keyword ditemukan di judul',
        ];
    }

    private function scoreSeoTitleLength(string $seoTitle): array
    {
        $length = mb_strlen($seoTitle);

        $score = match (true) {
            $length >= 50 && $length <= 60 => 8,
            $length >= 35 && $length <= 75 => 5,
            $length > 0 => 3,
            default => 0,
        };

        $note = $length > 0 ? "{$length} karakter" : 'Belum diisi';

        return ['label' => 'SEO Title Length', 'score' => $score, 'max' => 8, 'note' => $note];
    }

    private function scoreKeyphraseInSlug(string $slug, string $focusKeyword): array
    {
        $normalizedSlug = Str::of($slug)->lower();
        $keyword = Str::of($focusKeyword)->lower();
        $score = 0;

        if ($normalizedSlug->isNotEmpty()) {
            $score += ($keyword->isNotEmpty() && $normalizedSlug->contains($keyword)) ? 5 : 2;
            $score += preg_match('/^[a-z0-9\-]+$/', $slug) ? 3 : 0;
        }

        return ['label' => 'Keyphrase in Slug', 'score' => min(8, $score), 'max' => 8, 'note' => $slug ?: 'Belum diisi'];
    }

    private function scoreKeyphraseInIntroduction(string $firstParagraph, string $focusKeyword): array
    {
        if ($focusKeyword === '') {
            return ['label' => 'Keyphrase in Introduction', 'score' => 0, 'max' => 10, 'note' => 'Belum diisi'];
        }

        $found = Str::contains(Str::lower($firstParagraph), Str::lower($focusKeyword));

        return [
            'label' => 'Keyphrase in Introduction',
            'score' => $found ? 10 : 0,
            'max' => 10,
            'note' => $found ? 'Keyword ditemukan di paragraf pertama' : 'Tambahkan keyword di 100 kata pertama',
        ];
    }

    private function scoreKeyphraseDensity(string $content, string $focusKeyword): array
    {
        $wordCount = max(1, $this->wordCount($content));
        $keywordCount = $focusKeyword !== '' ? substr_count(Str::lower($content), Str::lower($focusKeyword)) : 0;
        $density = ($keywordCount * 100) / $wordCount;

        $score = match (true) {
            $density >= 0.5 && $density <= 2.5 => 8,
            $density > 0 => 4,
            default => 0,
        };

        return ['label' => 'Keyphrase Density', 'score' => $score, 'max' => 8, 'note' => number_format($density, 2).' %'];
    }

    private function scoreKeyphraseInMetaDescription(string $metaDescription, string $focusKeyword): array
    {
        if ($focusKeyword === '') {
            return ['label' => 'Keyphrase in Meta Description', 'score' => 0, 'max' => 10, 'note' => 'Belum diisi'];
        }

        $found = Str::contains(Str::lower($metaDescription), Str::lower($focusKeyword));

        return [
            'label' => 'Keyphrase in Meta Description',
            'score' => $found ? 10 : 0,
            'max' => 10,
            'note' => $found ? 'Keyword ditemukan di meta description' : 'Tambahkan keyword di meta description',
        ];
    }

    private function scoreMetaDescriptionLength(string $metaDescription): array
    {
        $length = mb_strlen($metaDescription);

        $score = match (true) {
            $length >= 120 && $length <= 156 => 7,
            $length >= 90 && $length <= 180 => 4,
            $length > 0 => 2,
            default => 0,
        };

        $note = $length > 0 ? "{$length} karakter" : 'Belum diisi';

        return ['label' => 'Meta Description Length', 'score' => $score, 'max' => 7, 'note' => $note];
    }

    private function scoreKeyphraseInSubheading(string $content, string $focusKeyword): array
    {
        if ($focusKeyword === '') {
            return ['label' => 'Keyphrase in Subheading', 'score' => 0, 'max' => 9, 'note' => 'Belum diisi'];
        }

        $headings = $this->extractHeadings($content);
        $found = false;

        foreach ($headings as $heading) {
            if (Str::contains(Str::lower($heading), Str::lower($focusKeyword))) {
                $found = true;
                break;
            }
        }

        return [
            'label' => 'Keyphrase in Subheading',
            'score' => $found ? 9 : 0,
            'max' => 9,
            'note' => $found ? 'Keyword ditemukan di subheading' : 'Tambahkan keyword di H2/H3',
        ];
    }

    private function scoreKeyphraseInAltAttributes(string $altText, string $focusKeyword): array
    {
        if ($focusKeyword === '') {
            return ['label' => 'Keyphrase in Image Alt', 'score' => 0, 'max' => 6, 'note' => 'Belum diisi'];
        }

        $found = Str::contains(Str::lower($altText), Str::lower($focusKeyword));

        return [
            'label' => 'Keyphrase in Image Alt',
            'score' => $found ? 6 : ($altText !== '' ? 3 : 0),
            'max' => 6,
            'note' => $found ? 'Keyword ditemukan di alt text' : ($altText !== '' ? 'Alt text ada tanpa keyword' : 'Alt text belum ada'),
        ];
    }

    private function scoreInternalLinks(string $content): array
    {
        $matches = [];
        preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches);
        $found = collect($matches[1] ?? [])->contains(fn (string $url) => str_starts_with($url, '/') || ! str_starts_with($url, 'http'));

        return [
            'label' => 'Internal Links',
            'score' => $found ? 8 : 0,
            'max' => 8,
            'note' => $found ? 'Tautan internal ditemukan' : 'Tambahkan minimal 1 internal link',
        ];
    }

    private function scoreOutboundLinks(string $content): array
    {
        $matches = [];
        preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches);
        $found = collect($matches[1] ?? [])->contains(fn (string $url) => str_starts_with($url, 'http'));

        return [
            'label' => 'Outbound Links',
            'score' => $found ? 5 : 0,
            'max' => 5,
            'note' => $found ? 'Tautan eksternal ditemukan' : 'Tambahkan minimal 1 external link',
        ];
    }

    private function scoreContentWordCount(string $content): array
    {
        $wordCount = $this->wordCount($content);

        $score = match (true) {
            $wordCount >= 900 => 9,
            $wordCount >= 300 => 5,
            $wordCount > 0 => 2,
            default => 0,
        };

        return ['label' => 'Content Word Count', 'score' => $score, 'max' => 9, 'note' => $wordCount.' kata'];
    }

    private function scorePreviouslyUsedKeyphrase(?string $focusKeyword, ?int $articleId): array
    {
        if ($focusKeyword === '' || $focusKeyword === null) {
            return ['label' => 'Previously Used Keyphrase', 'score' => 0, 'max' => 8, 'note' => 'Belum diisi'];
        }

        $query = ArticleSeoMeta::withoutGlobalScope(TenantScope::class)
            ->where('yoast_focuskw', $focusKeyword);

        if ($articleId !== null) {
            $query->where('article_id', '!=', $articleId);
        }

        $exists = $query->exists();

        return [
            'label' => 'Previously Used Keyphrase',
            'score' => $exists ? 0 : 8,
            'max' => 8,
            'note' => $exists ? 'Keyword sudah dipakai artikel lain' : 'Keyword unik',
        ];
    }

    // ===================================================================
    // READABILITY ANALYSIS (7 indikator, total = 100)
    // ===================================================================

    /**
     * @return array<int, array{label: string, score: int, max: int, note: string}>
     */
    private function analyzeReadability(array $data): array
    {
        $content = trim((string) ($data['content'] ?? ''));
        $plainContent = preg_replace('/<[^>]+>/', ' ', $content);
        $plainContent = preg_replace('/\s+/', ' ', $plainContent);

        $sentences = $this->splitSentences($plainContent);
        $paragraphs = $this->splitParagraphs($content);
        $subheadings = $this->extractSubheadingPositions($content);
        $wordCount = max(1, $this->wordCount($content));

        return [
            $this->scoreParagraphLength($paragraphs),
            $this->scoreSentenceLengthRatio($sentences, $wordCount),
            $this->scoreSubheadingDistribution($paragraphs, $subheadings, $wordCount),
            $this->scoreTransitionWords($sentences),
            $this->scorePassiveVoice($sentences),
            $this->scoreConsecutiveSentences($sentences),
            $this->scoreFleschReadingEase($wordCount, $sentences),
        ];
    }

    private function scoreParagraphLength(array $paragraphs): array
    {
        if (empty($paragraphs)) {
            return ['label' => 'Paragraph Length', 'score' => 15, 'max' => 15, 'note' => 'Tidak ada paragraf'];
        }

        $longParagraphs = 0;
        foreach ($paragraphs as $paragraph) {
            $wordCount = str_word_count($paragraph);
            if ($wordCount > 150) {
                $longParagraphs++;
            }
        }

        $ratio = ($longParagraphs * 100) / max(1, count($paragraphs));
        $score = $ratio <= 10 ? 15 : ($ratio <= 25 ? 10 : ($ratio <= 50 ? 5 : 0));

        return [
            'label' => 'Paragraph Length',
            'score' => $score,
            'max' => 15,
            'note' => "{$longParagraphs} paragraf panjang (>{150} kata)",
        ];
    }

    private function scoreSentenceLengthRatio(array $sentences, int $wordCount): array
    {
        if (empty($sentences) || $wordCount === 0) {
            return ['label' => 'Sentence Length', 'score' => 18, 'max' => 18, 'note' => 'Tidak ada kalimat'];
        }

        $longSentences = 0;
        foreach ($sentences as $sentence) {
            $sentenceWords = str_word_count(trim($sentence));
            if ($sentenceWords > 20) {
                $longSentences++;
            }
        }

        $ratio = ($longSentences * 100) / max(1, count($sentences));
        $score = $ratio <= 25 ? 18 : ($ratio <= 40 ? 12 : ($ratio <= 60 ? 6 : 0));

        return [
            'label' => 'Sentence Length',
            'score' => $score,
            'max' => 18,
            'note' => number_format($ratio, 1).'% kalimat panjang (>20 kata)',
        ];
    }

    private function scoreSubheadingDistribution(array $paragraphs, array $subheadingPositions, int $wordCount): array
    {
        if ($wordCount <= 300) {
            return ['label' => 'Subheading Distribution', 'score' => 15, 'max' => 15, 'note' => 'Konten pendek, tidak perlu subheading'];
        }

        if (empty($subheadingPositions)) {
            return ['label' => 'Subheading Distribution', 'score' => 0, 'max' => 15, 'note' => 'Tambahkan subheading (H2/H3)'];
        }

        // Hitung rata-rata kata antar subheading
        $totalGaps = count($subheadingPositions);
        $avgWordsBetween = $wordCount / max(1, $totalGaps);

        $score = $avgWordsBetween <= 300 ? 15 : ($avgWordsBetween <= 450 ? 10 : ($avgWordsBetween <= 600 ? 5 : 0));

        return [
            'label' => 'Subheading Distribution',
            'score' => $score,
            'max' => 15,
            'note' => number_format($avgWordsBetween, 0).' kata rata-rata antar subheading',
        ];
    }

    private function scoreTransitionWords(array $sentences): array
    {
        $totalSentences = count($sentences);

        if ($totalSentences === 0) {
            return ['label' => 'Transition Words', 'score' => 15, 'max' => 15, 'note' => 'Tidak ada kalimat'];
        }

        $withTransition = 0;
        foreach ($sentences as $sentence) {
            $lowerSentence = Str::lower(trim($sentence));
            foreach (self::TRANSITION_WORDS as $word) {
                if (Str::contains($lowerSentence, $word)) {
                    $withTransition++;
                    break;
                }
            }
        }

        $ratio = ($withTransition * 100) / $totalSentences;
        $score = $ratio >= 30 ? 15 : ($ratio >= 20 ? 10 : ($ratio >= 10 ? 5 : 0));

        return [
            'label' => 'Transition Words',
            'score' => $score,
            'max' => 15,
            'note' => number_format($ratio, 1).'% kalimat menggunakan kata penghubung',
        ];
    }

    private function scorePassiveVoice(array $sentences): array
    {
        $totalSentences = count($sentences);

        if ($totalSentences === 0) {
            return ['label' => 'Passive Voice', 'score' => 12, 'max' => 12, 'note' => 'Tidak ada kalimat'];
        }

        $passiveCount = 0;
        foreach ($sentences as $sentence) {
            $lowerSentence = Str::lower(trim($sentence));
            foreach (self::PASSIVE_PATTERNS as $pattern) {
                if (preg_match($pattern, $lowerSentence)) {
                    $passiveCount++;
                    break;
                }
            }
        }

        $ratio = ($passiveCount * 100) / $totalSentences;
        $score = $ratio <= 10 ? 12 : ($ratio <= 20 ? 8 : ($ratio <= 30 ? 4 : 0));

        return [
            'label' => 'Passive Voice',
            'score' => $score,
            'max' => 12,
            'note' => number_format($ratio, 1).'% kalimat pasif',
        ];
    }

    private function scoreConsecutiveSentences(array $sentences): array
    {
        if (count($sentences) < 3) {
            return ['label' => 'Consecutive Sentences', 'score' => 12, 'max' => 12, 'note' => 'Terlalu sedikit kalimat'];
        }

        $maxConsecutive = 0;
        $currentConsecutive = 1;

        for ($i = 1; $i < count($sentences); $i++) {
            $prevFirstWord = $this->getFirstWord($sentences[$i - 1]);
            $currFirstWord = $this->getFirstWord($sentences[$i]);

            if ($prevFirstWord !== '' && $currFirstWord !== '' && $prevFirstWord === $currFirstWord) {
                $currentConsecutive++;
                $maxConsecutive = max($maxConsecutive, $currentConsecutive);
            } else {
                $currentConsecutive = 1;
            }
        }

        $score = $maxConsecutive <= 2 ? 12 : ($maxConsecutive <= 3 ? 8 : ($maxConsecutive <= 4 ? 4 : 0));

        return [
            'label' => 'Consecutive Sentences',
            'score' => $score,
            'max' => 12,
            'note' => "Max {$maxConsecutive} kalimat berturut awal sama",
        ];
    }

    private function scoreFleschReadingEase(int $wordCount, array $sentences): array
    {
        $sentenceCount = max(1, count($sentences));
        $totalSyllables = $this->countTotalSyllables($sentences);

        // Flesch Reading Ease formula (Bahasa Indonesia adapted)
        $flesch = 206.835 - (1.015 * ($wordCount / $sentenceCount)) - (84.6 * ($totalSyllables / max(1, $wordCount)));

        // Clamp to 0-100 range
        $flesch = max(0, min(100, $flesch));

        // Skor: target 60.0
        $diff = abs($flesch - 60.0);
        $score = match (true) {
            $diff <= 10 => 13,
            $diff <= 20 => 10,
            $diff <= 30 => 7,
            default => 3,
        };

        return [
            'label' => 'Flesch Reading Ease',
            'score' => $score,
            'max' => 13,
            'note' => number_format($flesch, 1).' (target: 60.0)',
        ];
    }

    // ===================================================================
    // HELPER METHODS
    // ===================================================================

    private function extractFirstParagraph(string $content, int $maxWords = 100): string
    {
        $plain = preg_replace('/<[^>]+>/', ' ', $content);
        $plain = preg_replace('/\s+/', ' ', trim($plain));
        $words = explode(' ', $plain);

        return implode(' ', array_slice($words, 0, $maxWords));
    }

    /**
     * Ekstrak semua heading (H2, H3) dari konten HTML.
     */
    private function extractHeadings(string $content): array
    {
        $headings = [];
        preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/i', $content, $matches);

        foreach ($matches[1] as $match) {
            $clean = trim(strip_tags($match));
            if ($clean !== '') {
                $headings[] = $clean;
            }
        }

        return $headings;
    }

    /**
     * Ekstrak posisi subheading dalam konten (untuk distribusi).
     */
    private function extractSubheadingPositions(string $content): array
    {
        $positions = [];
        preg_match_all('/<h[23][^>]*>.*?<\/h[23]>/is', $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
            $positions[] = $match[1];
        }

        return $positions;
    }

    private function splitSentences(string $content): array
    {
        $sentences = preg_split('/[.!?]+/u', $content, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter(array_map('trim', $sentences ?? []));
    }

    private function splitParagraphs(string $content): array
    {
        // Split by double newlines or paragraph tags
        $paragraphs = preg_split('/(<\/p>|<br\s*\/?>|\n{2,})/i', $content, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter(array_map(function (string $p): string {
            return trim(strip_tags($p));
        }, $paragraphs ?? []));
    }

    private function getFirstWord(string $sentence): string
    {
        $sentence = trim($sentence);
        $sentence = preg_replace('/[^\w\s]/u', '', $sentence);
        $words = preg_split('/\s+/u', $sentence, -1, PREG_SPLIT_NO_EMPTY);

        return strtolower($words[0] ?? '');
    }

    /**
     * Estimasi jumlah suku kata total dari semua kalimat.
     */
    private function countTotalSyllables(array $sentences): int
    {
        $total = 0;

        foreach ($sentences as $sentence) {
            $words = explode(' ', trim($sentence));
            foreach ($words as $word) {
                $total += $this->countSyllables($word);
            }
        }

        return $total;
    }

    /**
     * Estimasi jumlah suku kata satu kata (heuristik Bahasa Indonesia).
     */
    private function countSyllables(string $word): int
    {
        $word = strtolower(trim($word));

        if (mb_strlen($word) <= 3) {
            return 1;
        }

        // Hitung vokal sebagai proksi suku kata
        $vowels = preg_match_all('/[aiueo]/u', $word);

        return max(1, $vowels);
    }

    private function determineCategory(int $seoScore, int $readabilityScore): string
    {
        $avg = ($seoScore + $readabilityScore) / 2;

        return match (true) {
            $avg >= 80 => 'Good',
            $avg >= 60 => 'Needs Improvement',
            default => 'Poor',
        };
    }

    /**
     * @param  array<int, array{label: string, score: int, max: int}>  $seoBreakdown
     * @param  array<int, array{label: string, score: int, max: int}>  $readabilityBreakdown
     * @return array<int, string>
     */
    private function recommendations(array $seoBreakdown, array $readabilityBreakdown): array
    {
        $all = array_merge($seoBreakdown, $readabilityBreakdown);

        return collect($all)
            ->filter(fn (array $item) => $item['score'] < $item['max'])
            ->map(fn (array $item) => $item['label'].': '.$item['score'].'/'.$item['max'])
            ->values()
            ->all();
    }
}
