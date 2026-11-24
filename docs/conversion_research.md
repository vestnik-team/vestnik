# Исследование конвертации DOCX → LaTeX → PDF

## 1. Цель исследования

Исследовать возможности Pandoc и XeLaTeX для автоматического преобразования научных статей из DOCX в LaTeX и PDF. До разработки микросервиса проверить качество преобразования на тестовых документах с текстом, формулами, таблицами и изображениями.

Отдельная задача — проверить сохранение формул Word (OMML) при преобразовании в LaTeX и оценить возможность создания собственного преобразователя OMML → LaTeX.

## 2. Окружение эксперимента

Работа выполнялась в WSL2 Ubuntu.

- Python 3.12.
- Pandoc 3.1.3 — системная версия.
- Pandoc 3.14 — дополнительная локальная версия.
- XeTeX 0.999995, TeX Live 2023.
- latexmk 4.83.
- Mammoth 1.13.0.
- lxml 6.1.3.

Для Python-экспериментов использовалось виртуальное окружение `.venv`.

## 3. Проведённые эксперименты

### 3.1. Первичная конвертация тестовой статьи

Подготовлен синтетический документ `test_article.docx`, содержащий:

- русский и английский текст;
- заголовки;
- текстовые математические выражения;
- таблицу;
- изображение и подпись;
- список литературы.

Документ преобразовывался Pandoc в LaTeX, после чего результат компилировался XeLaTeX в PDF.

**Результат:** PDF успешно собрался. При визуальной проверке в нём присутствовали текст, таблица, изображение, подписи и библиография. Использовалось стандартное оформление Pandoc, а не шаблон научного журнала.

Полная формальная сверка каждого элемента исходного DOCX с PDF отдельно не проводилась.

### 3.2. Проверка математических формул средствами Pandoc

Подготовлен `test_article_equations.docx` с настоящими формулами Word, сохранёнными в DOCX как OMML.

**Формула среднего значения:**

- Pandoc 3.1.3: часть структуры потеряна — сумма не сохранилась, черта преобразована некорректно.
- Pandoc 3.14: сумма сохранилась, но черта преобразована в `\underline` вместо ожидаемой верхней черты.

**Формула со стандартным отклонением:**

- Pandoc 3.1.3: существенные части формулы потеряны.
- Pandoc 3.14: существенные части формулы потеряны.

Дополнительно проверен минимальный DOCX с формулой `a/b + sqrt(x^2)`. Pandoc 3.14 сформировал неполное математическое выражение: дробь и знак сложения сохранились, корень и степень потерялись.

Проверка Pandoc через формат `native` показала, что неполное представление формулы присутствовало уже во внутреннем представлении Pandoc, до генерации LaTeX.

**Вывод:** в проверенных тестовых документах Pandoc 3.1.3 и 3.14 не обеспечили полного сохранения всех использованных OMML-конструкций. Это вывод только по проведённым тестам, а не утверждение о потере любых формул во всех DOCX.

### 3.3. Проверка Mammoth

Проверен Mammoth 1.13.0 для преобразования DOCX с OMML в HTML.

Mammoth вывел предупреждение о нераспознанном элементе `m:oMath`. Математический элемент был проигнорирован, итоговый HTML не содержал формулу.

**Вывод:** в проведённом эксперименте Mammoth не подошёл как способ сохранения проверенных OMML-формул без дополнительной обработки.

### 3.4. Извлечение OMML и собственный прототип

С помощью `lxml` реализовано извлечение элементов `m:oMath` из `word/document.xml` внутри DOCX. Формулы сохранялись в отдельный XML-файл для дальнейшей обработки.

Затем создан экспериментальный скрипт `omml_to_latex.py`, преобразующий часть структуры OMML в LaTeX.

В прототипе реализована обработка следующих конструкций:

- `m:r` — текстовые элементы формулы;
- `m:f` — дроби;
- `m:rad` — квадратные и индексированные корни;
- `m:sSup` — верхние индексы;
- `m:sSub` — нижние индексы;
- `m:sSubSup` — одновременные нижние и верхние индексы;
- `m:bar` — черта над выражением;
- `m:nary` — операторы, в частности сумма;
- `m:d` — ограничители и скобки;
- `m:m` и `m:mr` — матрица и её строки.

Поддержка проверена не для всех возможных вариантов перечисленных элементов. Например, сумма проверена на тестовой статье, но произведения и интегралы требуют отдельных тестов.

На статье `test_article_equations.docx` прототип преобразовал две формулы:

```latex
\overline{x}=\frac{1}{n}\sum_{i=1}^{n}{x}_{i}
```

```latex
σ=\sqrt{\frac{1}{n}\sum_{i=1}^{n}{({x}_{i}-\overline{x})}^{2}}
```

Обе формулы успешно скомпилировались XeLaTeX в PDF.

Для расширенного теста создан `test_advanced_equations.docx` с тремя OMML-формулами:

1. Одновременный нижний и верхний индекс `a_i^2`.
2. Матрица 2×2 в круглых скобках.
3. Дробь с вложенным квадратным корнем и степенью.

Извлечение подтвердило наличие в документе конструкций `m:sSubSup`, `m:d`, `m:m`, `m:mr`, `m:f`, `m:rad` и `m:sSup`.

До доработки прототип выдавал для первых двух формул `ai2` и `abcd`: математическая структура индексов и матрицы терялась. После добавления обработчиков получены следующие результаты.

Для двойного индекса:

```latex
{a}_{i}^{2}
```

Для матрицы со скобками:

```latex
\left(\begin{matrix}a & b \\ c & d\end{matrix}\right)
```

Для дроби с вложенным корнем:

```latex
\frac{1}{\sqrt{{x}^{2}+1}}
```

Все три формулы успешно скомпилировались XeLaTeX в PDF.

**Вывод:** прототип преобразует проверенное подмножество OMML, включая некоторые вложенные конструкции, двойные индексы и простую матрицу. Он остаётся экспериментальным и не проверен на произвольных формулах научных статей.

## 4. Выявленные проблемы и ограничения

1. **Pandoc.** На конкретных тестовых DOCX часть OMML-структуры потерялась или преобразовалась некорректно. Нельзя обобщать этот результат на все документы и все формулы.
2. **Mammoth.** В проверенном случае игнорировал `m:oMath`, поэтому не подходит для сохранения таких формул без дополнительной обработки.
3. **Собственный OMML → LaTeX-прототип.** Поддерживает только реализованные и проверенные конструкции. Для неизвестных элементов используется общий обход дочерних узлов, который может потерять математическую семантику.
4. **Матрицы и скобки.** Проверена одна матрица 2×2 с круглыми скобками. Другие виды ограничителей, выравнивание, пустые ячейки и более сложные матрицы не проверялись.
5. **Операторы.** Проверена сумма. Произведения, интегралы и различные варианты пределов требуют отдельных тестов.
6. **Специальные символы и греческие буквы.** Полноценная таблица соответствий и обработка всех математических символов не реализованы.
7. **Оформление PDF.** Использовано стандартное оформление LaTeX/Pandoc. Соответствие шаблону журнала не проверялось.
8. **Область проверки.** Эксперименты проведены на синтетических DOCX. Проверка на реальных статьях отложена и не входит в текущий объём работ.

## 5. Итоговая оценка

На синтетических документах подтверждена работоспособность базовой цепочки DOCX → LaTeX → PDF для тестового текста, таблицы и изображения. При этом тестирование выявило проблемы сохранения некоторых математических конструкций при прямом использовании Pandoc.

Отдельный прототип OMML → LaTeX успешно обработал проверенные формулы, в том числе вложенные конструкции, двойные индексы и простую матрицу. Его нельзя считать готовым универсальным конвертером: необходимы расширение набора поддерживаемых элементов и тестирование граничных случаев.

Для текущего этапа исследования проверка на синтетических DOCX считается достаточной. Проверка на реальных статьях отложена. Это ограничение следует учитывать при дальнейшем внедрении микросервиса.

Результаты эксперимента дают основание продолжить проектирование микросервиса конвертации, но не подтверждают качество обработки всех возможных вариантов документов журнала.



## Приложение А. Исходный код экспериментальных скриптов

В этом приложении приведён код скриптов, использованных в эксперименте. Они представлены непосредственно в документе, поэтому отдельные файлы `.py` не требуется хранить в папке с документацией.

### А.1. Извлечение формул OMML из DOCX

Скрипт открывает DOCX как ZIP-архив, извлекает `word/document.xml`, находит элементы `m:oMath` и сохраняет найденные формулы в отдельный XML-файл.

```python
import zipfile
from lxml import etree

DOCX_FILE = "test_advanced_equations.docx"
OUTPUT_FILE = "advanced_omml_formulas.xml"

MATH_NS = "http://schemas.openxmlformats.org/officeDocument/2006/math"

with zipfile.ZipFile(DOCX_FILE) as archive:
    document_xml = archive.read("word/document.xml")

root = etree.fromstring(document_xml)

namespaces = {"m": MATH_NS}
formulas = root.xpath(".//m:oMath", namespaces=namespaces)

output_root = etree.Element("formulas")

for index, formula in enumerate(formulas, start=1):
    wrapper = etree.SubElement(output_root, "formula", index=str(index))
    wrapper.append(formula)

tree = etree.ElementTree(output_root)
tree.write(
    OUTPUT_FILE,
    encoding="utf-8",
    xml_declaration=True,
    pretty_print=True
)

print(f"Extracted formulas: {len(formulas)}")
print(f"Saved to: {OUTPUT_FILE}")
```

### А.2. Преобразование OMML в LaTeX

Скрипт преобразует поддерживаемые элементы OMML в команды LaTeX. Текущая версия настроена на чтение `advanced_omml_formulas.xml` и запись результата в `article_formulas.tex`.

```python
from lxml import etree

MATH_NS = "http://schemas.openxmlformats.org/officeDocument/2006/math"
NS = {"m": MATH_NS}


def tag_name(element):
    return etree.QName(element).localname


def children_text(element):
    return "".join(convert(child) for child in element)


def convert(element):
    tag = tag_name(element)

    if tag == "r":
        return "".join(element.xpath(".//m:t/text()", namespaces=NS))

    if tag in ("oMath", "e", "num", "den", "sup", "sub"):
        return children_text(element)

    if tag == "f":
        numerator = element.find("m:num", NS)
        denominator = element.find("m:den", NS)
        return (
            r"\frac{" + children_text(numerator) +
            "}{" + children_text(denominator) + "}"
        )

    if tag == "rad":
        expression = element.find("m:e", NS)
        degree = element.find("m:deg", NS)
        degree_hidden = element.find(".//m:degHide", NS)

        value = children_text(expression)

        if degree is not None and degree_hidden is None:
            return (
                r"\sqrt[" + children_text(degree) +
                "]{" + value + "}"
            )

        return r"\sqrt{" + value + "}"

    if tag == "sSup":
        base = element.find("m:e", NS)
        power = element.find("m:sup", NS)
        return (
            "{" + children_text(base) + "}^{" +
            children_text(power) + "}"
        )

    if tag == "sSub":
        base = element.find("m:e", NS)
        index = element.find("m:sub", NS)
        return (
            "{" + children_text(base) + "}_{" +
            children_text(index) + "}"
        )

    if tag == "sSubSup":
        base = element.find("m:e", NS)
        sub = element.find("m:sub", NS)
        sup = element.find("m:sup", NS)
        return (
            "{" + children_text(base) + "}_{" +
            children_text(sub) + "}^{" +
            children_text(sup) + "}"
        )
    
    if tag == "m":
        rows = element.findall("m:mr", NS)
        latex_rows = []

        for row in rows:
            cells = row.findall("m:e", NS)
            latex_cells = [children_text(cell) for cell in cells]
            latex_rows.append(" & ".join(latex_cells))

        return r"\begin{matrix}" + r" \\ ".join(latex_rows) + r"\end{matrix}"

    if tag == "d":
        expression = element.find("m:e", NS)
        properties = element.find("m:dPr", NS)

        begin = "("
        end = ")"

        if properties is not None:
            begin_element = properties.find("m:begChr", NS)
            end_element = properties.find("m:endChr", NS)

            if begin_element is not None:
                begin = begin_element.get(
                    "{http://schemas.openxmlformats.org/officeDocument/2006/math}val",
                    begin
                )

            if end_element is not None:
                end = end_element.get(
                    "{http://schemas.openxmlformats.org/officeDocument/2006/math}val",
                    end
                )

        return (
            r"\left" + begin +
            children_text(expression) +
            r"\right" + end
        )
    
    if tag == "bar":
        expression = element.find("m:e", NS)
        return r"\overline{" + children_text(expression) + "}"

    if tag == "nary":
        properties = element.find("m:naryPr", NS)
        operator = "∑"

        if properties is not None:
            char = properties.find("m:chr", NS)
            if char is not None:
                operator = char.get(
                    "{http://schemas.openxmlformats.org/officeDocument/2006/math}val",
                    operator
                )

        operator_map = {
            "∑": r"\sum",
            "∏": r"\prod",
            "∫": r"\int",
        }
        latex_operator = operator_map.get(operator, operator)

        sub = element.find("m:sub", NS)
        sup = element.find("m:sup", NS)
        expression = element.find("m:e", NS)

        result = latex_operator

        if sub is not None:
            result += "_{" + children_text(sub) + "}"

        if sup is not None:
            result += "^{" + children_text(sup) + "}"

        if expression is not None:
            result += " " + children_text(expression)

        return result

    # Игнорируем служебные свойства OMML.
    if tag.endswith("Pr") or tag in ("ctrlPr", "degHide"):
        return ""

    return children_text(element)


tree = etree.parse("advanced_omml_formulas.xml")
formulas = tree.xpath("//m:oMath", namespaces=NS)

with open("article_formulas.tex", "w", encoding="utf-8") as output_file:
    for index, formula in enumerate(formulas, start=1):
        latex = convert(formula)

        output_file.write(f"% Formula {index}\n")
        output_file.write(latex + "\n\n")

        print(f"Formula {index}:")
        print(latex)
        print()

print("Saved to: article_formulas.tex")
```

**Примечание.** Это экспериментальные скрипты, а не готовая часть микросервиса. Имена входных и выходных файлов заданы непосредственно в коде. Для повторного запуска на других документах их необходимо изменить в соответствующих константах или строках.
