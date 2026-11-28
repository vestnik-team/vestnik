<?php

use APP\plugins\generic\vestnikTags\classes\TagMatcher;
use PHPUnit\Framework\TestCase;

class TagMatcherTest extends TestCase
{
    public function testSortsPeopleByNumberOfMatches(): void
    {
        $matcher = new TagMatcher();

        $result = $matcher->matchTags(
            ['ml', 'nlp', 'data'],
            [
                ['userId' => 1, 'tags' => ['ml', 'nlp']],
                ['userId' => 2, 'tags' => ['ml']],
                ['userId' => 3, 'tags' => ['biology']],
            ]
        );

        self::assertSame(1, $result[0]['userId']);
        self::assertSame(2, $result[0]['score']);

        self::assertSame(2, $result[1]['userId']);
        self::assertSame(1, $result[1]['score']);

        self::assertSame(3, $result[2]['userId']);
        self::assertSame(0, $result[2]['score']);
    }

    public function testDoesNotCountDuplicateTagsTwice(): void
    {
        $matcher = new TagMatcher();

        $result = $matcher->matchTags(
            ['ml', 'ml'],
            [
                ['userId' => 1, 'tags' => ['ml', 'ml']],
            ]
        );

        self::assertSame(1, $result[0]['score']);
        self::assertSame(['ml'], $result[0]['matchedTags']);
    }

    public function testReturnsNoMatchesWhenTagsAreDifferent(): void
    {
        $matcher = new TagMatcher();

        $result = $matcher->matchTags(
            ['ml'],
            [
                ['userId' => 1, 'tags' => ['biology']],
            ]
        );

        self::assertSame([], $result[0]['matchedTags']);
        self::assertSame(0, $result[0]['score']);
    }

    public function testHandlesEmptyArticleTags(): void
    {
        $matcher = new TagMatcher();

        $result = $matcher->matchTags(
            [],
            [
                ['userId' => 1, 'tags' => ['ml']],
            ]
        );

        self::assertSame(0, $result[0]['score']);
    }

    public function testMatchesSynonymsAndTranslations(): void
    {
        $matcher = new TagMatcher([
            [
                'id' => 'machine_learning',
                'name' => ['ru' => 'машинное обучение', 'en' => 'machine learning'],
                'synonyms' => ['ML', 'МО'],
            ],
        ]);

        $result = $matcher->matchTags(
            ['ML'],
            [['userId' => 1, 'tags' => ['машинное обучение']]]
        );

        self::assertSame(1, $result[0]['score']);
        self::assertSame(['machine_learning'], $result[0]['matchedTags']);
    }
}
