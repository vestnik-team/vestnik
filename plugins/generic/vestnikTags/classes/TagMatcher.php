<?php

namespace APP\plugins\generic\vestnikTags\classes;

class TagMatcher
{
    /**
     * Сопоставляет теги статьи с тегами людей.
    
     * Возвращает людей, отсортированных по score.
     */
    public function matchTags(array $articleTags, array $people): array
    {
        $articleTags = array_values(array_unique($articleTags));

        $result = [];

        foreach ($people as $person) {
            $personTags = array_values(
                array_unique($person['tags'] ?? [])
            );

            $matchedTags = array_values(
                array_intersect($articleTags, $personTags)
            );

            $result[] = [
                'userId' => $person['userId'],
                'name' => $person['name'] ?? '',
                'matchedTags' => $matchedTags,
                'score' => count($matchedTags),
            ];
        }

        usort($result, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $result;
    }
}
