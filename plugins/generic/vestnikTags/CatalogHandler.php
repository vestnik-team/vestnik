<?php

namespace APP\plugins\generic\vestnikTags;

use APP\handler\Handler;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

// Страница секретаря: список тегов, добавление, правка и удаление
class CatalogHandler extends Handler
{
    public VestnikTagsPlugin $plugin;

    public function __construct(VestnikTagsPlugin $plugin)
    {
        parent::__construct();
        $this->plugin = $plugin;
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_SITE_ADMIN],
            ['index', 'save', 'delete']
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function index($args, $request)
    {
        return $this->show($request);
    }

    // Пустой id в форме — новый тег, иначе правим существующий
    public function save($args, $request)
    {
        if (!$request->isPost() || !$request->checkCSRF()) {
            return $this->show($request, 'Неверный запрос, обновите страницу.');
        }

        $contextId = (int) $request->getContext()->getId();
        $tags = $this->plugin->getTags($contextId);

        $id = trim((string) $request->getUserVar('id'));
        $ru = trim((string) $request->getUserVar('nameRu'));
        $en = trim((string) $request->getUserVar('nameEn'));
        $synonyms = array_values(array_unique(array_filter(
            array_map('trim', preg_split('/[,\n]+/u', (string) $request->getUserVar('synonyms')))
        )));

        if ($ru === '' || $en === '') {
            return $this->show($request, 'Заполните оба названия.');
        }

        if ($id === '') {
            $id = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($en)), '_');
            if ($id === '' || in_array($id, array_column($tags, 'id'), true)) {
                return $this->show($request, 'Не удалось создать тег: такой id уже есть или в английском названии нет латиницы.');
            }
            $tags[] = ['id' => $id, 'name' => ['ru' => $ru, 'en' => $en], 'synonyms' => $synonyms];
        } else {
            $tags = array_map(
                fn($tag) => $tag['id'] === $id
                    ? array_merge($tag, ['name' => ['ru' => $ru, 'en' => $en], 'synonyms' => $synonyms])
                    : $tag,
                $tags
            );
        }

        $this->plugin->saveTags($contextId, $tags);
        $request->redirect(null, 'vestnikTagsCatalog');
    }

    public function delete($args, $request)
    {
        if ($request->isPost() && $request->checkCSRF()) {
            $contextId = (int) $request->getContext()->getId();
            $id = (string) $request->getUserVar('id');
            $tags = array_filter($this->plugin->getTags($contextId), fn($tag) => $tag['id'] !== $id);
            $this->plugin->saveTags($contextId, $tags);
        }
        $request->redirect(null, 'vestnikTagsCatalog');
    }

    private function show($request, ?string $error = null)
    {
        $tags = $this->plugin->getTags((int) $request->getContext()->getId());

        $session = $request->getSession();
        $token = method_exists($session, 'token') ? $session->token() : $session->getCSRFToken();
        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $url = fn($op) => $h($request->url(null, 'vestnikTagsCatalog', $op));

        $form = function (array $tag, bool $isNew) use ($h, $url, $token): string {
            $html = '<form method="post" action="' . $url('save') . '" style="margin-bottom:8px">'
                . '<input type="hidden" name="csrfToken" value="' . $h($token) . '">'
                . '<input type="hidden" name="id" value="' . $h($tag['id'] ?? '') . '">'
                . '<input name="nameRu" placeholder="Название (рус.)" value="' . $h($tag['name']['ru'] ?? '') . '"> '
                . '<input name="nameEn" placeholder="Название (англ.)" value="' . $h($tag['name']['en'] ?? '') . '"> '
                . '<input name="synonyms" size="50" placeholder="Синонимы через запятую" value="'
                . $h(implode(', ', $tag['synonyms'] ?? [])) . '"> '
                . '<button type="submit">' . ($isNew ? 'Добавить' : 'Сохранить') . '</button>';
            if (!$isNew) {
                $html .= ' <button type="submit" formaction="' . $url('delete')
                    . '" onclick="return confirm(\'Удалить тег?\')">Удалить</button>';
            }
            return $html . '</form>';
        };

        header('Content-Type: text/html; charset=utf-8');
        echo '<h1>Справочник тегов</h1>';
        if ($error) {
            echo '<p style="color:#d00550">' . $h($error) . '</p>';
        }
        echo '<h2>Новый тег</h2>' . $form([], true);
        echo '<h2>Теги (' . count($tags) . ')</h2>';
        foreach ($tags as $tag) {
            echo $form($tag, false);
        }
    }
}
