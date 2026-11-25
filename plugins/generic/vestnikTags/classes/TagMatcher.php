<?php

namespace APP\plugins\generic\vestnikTags\classes;

class TagMatcher
{
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
        $articleTags = array_values(array_unique($articleTags));

        $result = [];

        foreach ($people as $person) {
            $personTags = array_values(
                array_unique($person['tags'] ?? [])
            );

            // Находим совпавшие теги
            $matchedTags = array_values(
                array_intersect($articleTags, $personTags)
            );

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
}
