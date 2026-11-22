# Как написать плагин OJS с нуля

Вестник использует OJS **3.5.0-5**. Плагины пишем на PHP и храним в `plugins/`. Каждый плагин живёт в своём каталоге, имеет собственное имя, версию и набор переводов. Для редакторского интерфейса плагин может подключать JavaScript и CSS на выбранных страницах.

Ниже сначала запускаем готовый пример из [задачи № 44](https://github.com/vestnik-team/vestnik/issues/44), потом создаём свой плагин с нуля. Для разработки достаточно работающего [локального стенда](../README.md) и редактора кода. PHP из контейнера можно использовать и для запуска OJS, и для проверки синтаксиса.

## 1. Запустите пример из № 44

Плагин **«Вестник — тестовое уведомление»** показывает сообщение в редакторском интерфейсе. Для проверки достаточно журнала и учётной записи с доступом к панели редактора.

### Подключение к локальному стенду

1. Выполните `sh start.sh` по [корневому README](../README.md) и дождитесь сообщения **«OJS готов»**. `generic/vestnikExample` уже есть в списке в [docker/entrypoint.sh](../docker/entrypoint.sh). Скрипт подключает исходники, а bootstrap регистрирует плагин при первом запуске с этой базой. [Утилита регистрации в 3.5.0-5](https://github.com/pkp/pkp-lib/blob/3_5_0-5/tools/installPluginVersion.php)
2. Откройте **Администрирование → Журналы на сайте**. Если список пуст, создайте тестовый журнал. Откройте его настройки под управляющим журналом.
3. Перейдите в **Настройки → Веб-сайт → Модули → Установленные модули**. Найдите **«Вестник — тестовое уведомление»** в разделе **«Модули общего назначения»** и включите его для журнала. [Включение плагина в OJS](https://github.com/pkp/pkp-docs/blob/main/dev/plugin-guide/en/getting-started.md)

### Как проверить

1. В меню нужного журнала откройте **Панель редактора → Назначено мне**. Справа вверху должно появиться уведомление **«Плагин „Вестник“ работает»**. Оно исчезает через 3,5 секунды и появляется снова каждые 5 секунд. Если открыть рабочий процесс рукописи отдельной страницей, уведомление появится и там.
2. Нажмите **«Остановить показ»**. Уведомление исчезнет и больше не появится на открытой странице. Обновите страницу, чтобы запустить показ снова.
3. Снимите галочку рядом с плагином и заново откройте панель редактора. Уведомление исчезнет для этого журнала. Включите плагин снова, если хотите оставить демонстрацию на стенде.

### Как устроен пример

| Файл | За что отвечает |
| --- | --- |
| [`version.xml`](../plugins/generic/vestnikExample/version.xml) | Имя, категория, версия и PHP-класс, по которым OJS регистрирует плагин |
| [`VestnikExamplePlugin.php`](../plugins/generic/vestnikExample/VestnikExamplePlugin.php) | Проверка, что модуль включён для журнала, и подключение переводов, JavaScript и CSS на нужных страницах |
| [`js/notice.js`](../plugins/generic/vestnikExample/js/notice.js) | Показ уведомления, повтор по таймеру и остановка по кнопке |
| [`css/notice.css`](../plugins/generic/vestnikExample/css/notice.css) | Положение и оформление уведомления |
| [`locale/ru/locale.po`](../plugins/generic/vestnikExample/locale/ru/locale.po) и [`locale/en/locale.po`](../plugins/generic/vestnikExample/locale/en/locale.po) | Переводы названия, описания, уведомления и кнопки |

## 2. Создайте новый плагин

### Выберите тип и имя

Для редакторского действия, серверного хука или фоновой задачи берите `GenericPlugin`. У тематического оформления, блока боковой панели, импорта и экспорта, отчётов свои категории и базовые классы. [Категории плагинов](https://github.com/pkp/pkp-docs/blob/main/dev/plugin-guide/en/categories.md)

В этом шаблоне имя каталога — `editorTools`. Выберите новое имя из латинских букв и цифр. Оно должно совпадать в каталоге, пространстве имён и поле `<application>`. Класс плагина начинается с заглавной буквы и заканчивается на `Plugin`. [Getting Started](https://github.com/pkp/pkp-docs/blob/main/dev/plugin-guide/en/getting-started.md)

```text
plugins/generic/editorTools/
    EditorToolsPlugin.php
    version.xml
    locale/ru/locale.po
    locale/en/locale.po
```

### Добавьте метаданные

В `plugins/generic/editorTools/version.xml` укажите имя класса, категорию и первую версию. Для своего плагина подставьте фактическую дату выпуска.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE version SYSTEM "../../../lib/pkp/dtd/pluginVersion.dtd">
<version>
    <application>editorTools</application>
    <type>plugins.generic</type>
    <release>1.0.0.0</release>
    <date>2026-11-22</date>
    <lazy-load>1</lazy-load>
    <class>EditorToolsPlugin</class>
</version>
```

### Добавьте класс

В `plugins/generic/editorTools/EditorToolsPlugin.php` вызовите `parent::register()` перед регистрацией обработчиков. Условие `getEnabled($mainContextId)` позволяет подключать обработчики только там, где включён плагин. Если при регистрации нужен доступ к базе, проверяйте, что OJS установлена и не обновляется, как в примере Вестника.

```php
<?php

namespace APP\plugins\generic\editorTools;

use APP\core\Application;
use PKP\plugins\GenericPlugin;

class EditorToolsPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        if (Application::isInstalled() && !Application::isUpgrading() && $this->getEnabled($mainContextId)) {
            // Подключите выбранные точки расширения здесь.
        }

        return true;
    }

    public function getDisplayName(): string
    {
        return __('plugins.generic.editorTools.displayName');
    }

    public function getDescription(): string
    {
        return __('plugins.generic.editorTools.description');
    }
}
```

Используйте отдельный префикс для строк каждого плагина. В `locale/ru/locale.po` поместите русские переводы. В `locale/en/locale.po` добавьте те же `msgid` и английские `msgstr` с заголовком `Language: en`. [Переводы плагинов](https://github.com/pkp/pkp-docs/blob/main/dev/plugin-guide/en/translation.md)

```po
msgid ""
msgstr ""
"Language: ru\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"

msgid "plugins.generic.editorTools.displayName"
msgstr "Инструменты редактора"

msgid "plugins.generic.editorTools.description"
msgstr "Добавляет действия редактора в карточку рукописи."
```

### Подключите каталог и зарегистрируйте плагин

Добавьте `generic/editorTools` в список после `set --` в [docker/entrypoint.sh](../docker/entrypoint.sh). Для других категорий укажите путь вида `категория/каталог` относительно `plugins/`.

```sh
set -- \
    generic/vestnikExample \
    generic/editorTools
```

Выполните `sh start.sh`, чтобы применить конфигурацию Compose. Если контейнер уже работает с `entrypoint.sh` и поменялся только список плагинов, достаточно `docker compose restart ojs`. Скрипт подключит каталог и зарегистрирует плагин, если в базе ещё нет его текущей версии. Когда сайт откроется, проверьте синтаксис.

```sh
docker compose exec -T --user www-data --workdir /var/www/html ojs \
  php -l plugins/generic/editorTools/EditorToolsPlugin.php
```

После регистрации включите плагин из настроек сайта нужного журнала. Минимальный каркас готов, если в списке плагинов появилось заданное имя и переключатель включения работает.

## 3. Добавьте нужную возможность

| Задача | Что взять за шаблон | Документация PKP |
| --- | --- | --- |
| Уведомление на странице редактора | Возьмите подключение скрипта и CSS из `VestnikExamplePlugin.php`. В `register()` проверяйте `PKPPageRouter` перед вызовом `getRequestedPage()`. Для показа внутри карточки рукописи используйте точку расширения редакторского интерфейса. | [Backend UI](https://github.com/pkp/pkp-docs/blob/main/dev/plugin-guide/en/examples-backend-ui.md) · [Каталог Vue-хуков 3.5](https://stable-3_5_0--6555d3db80418bb1681b8b17.chromatic.com/?path=/docs/guide-plugins--docs) |
| Маршрут для действия с конкретной рукописью | Объявите в API путь, имя маршрута, обработчик и роли. Для доступа к рукописи используйте `SubmissionAccessPolicy` и читайте объект, который авторизовала OJS. | [API Endpoints](https://github.com/pkp/pkp-docs/blob/main/dev/plugin-guide/en/api-endpoints.md) |
| Свой ресурс, например справочник тегов | Создайте контроллер под собственным префиксом через `APIHandler::endpoints::plugin`. Для чтения и записи определите операции и роли. Для изменения данных из браузера используйте штатный REST-маршрут с CSRF-защитой. | [Контроллер плагина](https://github.com/pkp/pkp-docs/blob/main/dev/plugin-guide/en/api-endpoints.md#create-plugin-api-endpoints) |
| Событие в редакционном процессе | Зарегистрируйте `Hook::add()` после проверки `getEnabled()`. Найдите место вызова в исходнике и проверьте, какие аргументы получает обработчик и в какой момент он срабатывает. | [Hooks](https://github.com/pkp/pkp-docs/blob/main/dev/documentation/en/utilities-hooks.md) |
| Фоновая задача | Добавьте класс от `ScheduledTask`, реализуйте `executeActions()`, подключите `HasTaskScheduler` и зарегистрируйте задачу в `registerSchedules()`. Для задачи с обходом журналов проверяйте `getEnabled($journalId)` в каждом журнале. | [Scheduled Tasks](https://github.com/pkp/pkp-docs/blob/main/dev/plugin-guide/en/scheduled-tasks.md) |
| Форма настроек плагина | Используйте `PluginSettingsController` и стандартное модальное окно. Сохраняйте настройки с ID журнала через API плагина. | [Plugin Settings](https://github.com/pkp/pkp-docs/blob/main/dev/plugin-guide/en/settings.md) |
| Блок или шаблон на публичной странице | Выберите тему, блок или Smarty-хук нужной страницы. Держите `.tpl` в каталоге своего плагина. | [Templates](https://github.com/pkp/pkp-docs/blob/main/dev/plugin-guide/en/templates.md) |

### Шаблон PHP-обработчика события

Например, подписка на публикацию использует `Publication::publish`. Этот хук подходит для реакции на уже выполненную публикацию. Сигнатуру и порядок аргументов сверяйте с вызовом в закреплённой версии исходников. [Репозиторий публикаций 3.5.0-5](https://github.com/pkp/pkp-lib/blob/3_5_0-5/classes/publication/Repository.php)

```php
// Импорт в EditorToolsPlugin.php
use PKP\plugins\Hook;

// В register(), внутри условия getEnabled(...)
Hook::add('Publication::publish', $this->onPublicationPublished(...));

// Метод класса EditorToolsPlugin
public function onPublicationPublished(string $hookName, array $args): bool
{
    $publication = $args[0];
    // Вызовите свою логику, используя $publication.
    return Hook::CONTINUE;
}
```

В исходниках OJS и `lib/pkp` точки вызова PHP- и Smarty-хуков можно найти так:

```sh
rg -n 'Hook::(call|run)\s*\(|\{call_hook\b|@hook' classes pages api templates lib/pkp
```

Для кода Вестника сверяйте примеры с тегом `3_5_0-5`. При переносе плагинов из более ранних версий полезен перечень удалённых хуков и изменений REST API. [Исходники OJS 3.5.0-5](https://github.com/pkp/ojs/tree/3_5_0-5) · [Заметки разработчикам о 3.5](https://github.com/pkp/pkp-docs/blob/main/dev/release-notebooks/en/3.5-release-notebook.md)

### Когда плагину нужны данные OJS

В PHP используйте `APP\facades\Repo` и коллектора соответствующей сущности. Например, у задачи по расписанию нет открытого журнала в браузере, поэтому ID журнала передаётся явно через `filterByContextIds()`. В редакторском запросе берите рукопись из контекста авторизации маршрута. Имена своих маршрутов, хуков, классов и ключей перевода привязывайте к имени плагина, чтобы два расширения могли работать одновременно. [Репозитории OJS](https://github.com/pkp/pkp-docs/blob/main/dev/documentation/en/architecture-repositories.md) · [Спецификация REST API 3.5.0-5](https://github.com/pkp/ojs/blob/3_5_0-5/docs/dev/swagger-source.json)

## 4. Проверьте плагин перед передачей команде

1. Запустите `php -l` для каждого PHP-файла. Проверьте имя каталога, пространство имён и `version.xml`.
2. Включите плагин в одном журнале. Пройдите основной сценарий с ожидаемой ролью и проверьте отказ пользователю без доступа. Если есть второй журнал, проверьте работу при отключённом плагине там.
3. Если есть расписание, проверьте наличие задачи через `scheduler.php list`, выполните через `scheduler.php test` и прочитайте журнал. Для записывающей задачи повторите запуск с теми же данными и проверьте результат.
4. Если элемент интерфейса не появился, проверьте, включён ли плагин для журнала и загружается ли его JavaScript. Посмотрите консоль браузера и `docker compose logs ojs`. Если изменился JS, обновите страницу с очисткой кэша для этой вкладки. При `403` проверьте роль и назначение на рукопись, при `404` — путь запроса и настройки журнала.
5. Оставьте в репозитории шаги включения, проверочную роль, тестовые данные и ожидаемый результат. Следующему разработчику должно хватить этой инструкции и исходников плагина.
