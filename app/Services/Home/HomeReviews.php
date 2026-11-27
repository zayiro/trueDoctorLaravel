<?php

declare(strict_types=1);

namespace App\Services\Home;

use App\Models\Review;
use Illuminate\Support\Facades\Log;

/**
 * Opiniones para la portada: reseñas reales publicadas + testimonios con permiso de config/testimonials.php.
 * Devuelve una lista ya mezclada al azar: [['name', 'text', 'rating', 'label'], ...]
 */
final class HomeReviews
{
    /** @return array<int, array{name:string, text:string, rating:int, label:string}> */
    public static function pick(?int $limit = null): array
    {
        $limit = $limit ?? max(1, (int) config('testimonials.limit', 15));
        $items = [];

        try {
            $rows = Review::query()
                ->published()
                ->where('rating', '>=', (int) config('testimonials.min_rating', 4))
                ->whereNotNull('comment')
                ->whereRaw('CHAR_LENGTH(TRIM(comment)) >= ?', [(int) config('testimonials.min_chars', 25)])
                ->with('user:id,name')
                ->inRandomOrder()
                ->limit($limit)
                ->get();

            foreach ($rows as $r) {
                $items[] = [
                    'name' => (string) $r->author_name,
                    'text' => trim((string) $r->comment),
                    'rating' => (int) $r->rating,
                    'label' => 'Paciente de OpenDoctor',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('HomeReviews: no se pudieron leer las reseñas: ' . $e->getMessage());
        }

        $items = array_merge($items, self::fromConfig((array) config('testimonials.items', [])));
        shuffle($items);

        return array_slice($items, 0, $limit);
    }

    /**
     * Solo pasan los testimonios completos y con consentimiento explícito.
     *
     * @return array<int, array{name:string, text:string, rating:int, label:string}>
     */
    public static function fromConfig(array $list): array
    {
        $out = [];

        foreach ($list as $t) {
            if (!is_array($t) || ($t['consent'] ?? false) !== true) {
                continue;
            }

            $name = trim((string) ($t['name'] ?? ''));
            $text = trim((string) ($t['text'] ?? ''));
            $rating = (int) ($t['rating'] ?? 0);

            if ($name === '' || $text === '' || $rating < 1 || $rating > 5) {
                continue;
            }

            $out[] = ['name' => $name, 'text' => $text, 'rating' => $rating, 'label' => trim((string) ($t['label'] ?? '')) ?: 'Paciente de OpenDoctor'];
        }

        return $out;
    }
}
