<?php

namespace APP\plugins\generic\vestnikTags\classes;

class TagMatcher
{
    // строка (синоним, название, id) => id тега
    private array $map = [];

    public function __construct(array $tags = [])
    {
        foreach ($tags as $tag) {
            $words = array_merge(
                [$tag['id'], $tag['name']['ru'] ?? '', $tag['name']['en'] ?? ''],
                $tag['synonyms'] ?? []
            );
            foreach ($words as $word) {
                $key = self::clean($word);
                if ($key !== '') {
                    $this->map[$key] = $tag['id'];
                }
            }
        }
    }
    /**
     * Сопоставляет теги статьи с тегами людей.
     * 
     * @param array $articleTags Теги статьи.
     * @param array $people Люди с полями userId, name, tags и currentReviews.
     * 
     * @return array Отсортированный список людей с совпавшими тегами,
     *               количеством совпадений и текущей загрузкой.
     */
    public function matchTags(array $articleTags, array $people): array
    {
        $articleIds = $this->toIds($articleTags);
        $result = [];

        foreach ($people as $person) {
            $personIds = $this->toIds($person['tags'] ?? []);
            $matchedTags = array_values(array_intersect($articleIds, $personIds));

            $result[] = [
                'userId' => $person['userId'],
                'name' => $person['name'] ?? '',
                'matchedTags' => $matchedTags,
                'score' => count($matchedTags),
                'currentReviews' => (int) ($person['currentReviews'] ?? 0),
            ];
        }

        usort($result, function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            return $a['currentReviews'] <=> $b['currentReviews'];
        });

        return $result;
    }

     private function toIds(array $tags): array
    {
        $ids = [];
        foreach ($tags as $tag) {
            $key = self::clean((string) $tag);
            if ($key === '') {
                continue;
            }
            $ids[] = $this->map[$key] ?? $key;
        }
        return array_values(array_unique($ids));
    }

    // регистр, ё/е, кавычки, дефисы и пробелы
    private static function clean(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(['ё', 'é'], ['е', 'e'], $s);
        $s = preg_replace('/[«»"\']/u', '', $s);
        return trim(preg_replace('/[\s\-_]+/u', ' ', $s));
    }
}
