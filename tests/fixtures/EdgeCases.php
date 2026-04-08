<?php

namespace Turker\PHPUnitCoverageReporter\Tests\fixtures;
/**
 * Edge case fixture: empty methods, empty classes, special characters in strings.
 * Tests that php-code-coverage handles these correctly and our extractors don't break.
 */
class EmptyClass
{
}

class ClassWithEmptyMethods
{
    public function emptyMethod(): void
    {
    }

    public function anotherEmptyMethod(): void
    {
        // Only a comment, no executable lines
    }

    public function methodWithReturn(): int
    {
        return 0;
    }
}

class SpecialCharacters
{
    public function doubleQuotes(): string
    {
        return "He said \"hello\" to the world";
    }

    public function singleQuotes(): string
    {
        return 'It\'s a test with "double quotes" inside';
    }

    public function backslashes(): string
    {
        return "C:\\Users\\test\\path\\file.php";
    }

    public function unicodeCharacters(): string
    {
        return "Türkçe karakterler: ğüşöçıİĞÜŞÖÇ";
    }

    public function newlinesAndTabs(): string
    {
        return "line1\nline2\ttabbed";
    }

    public function nullBytes(): string
    {
        return "before\0after";
    }

    public function jsonSpecialChars(): string
    {
        return '{"key": "value with \"quotes\" and \\backslash"}';
    }

    public function emojis(): string
    {
        return "🎉 Test 🚀 Coverage 💯";
    }

    public function multilineHeredoc(): string
    {
        return <<<EOT
        This is a "heredoc" string
        with 'special' characters
        and \backslashes\
        EOT;
    }

    public function nowdoc(): string
    {
        return <<<'EOT'
        This is a 'nowdoc' string
        with "quotes" and no $variable interpolation
        EOT;
    }
}

class ClassWithOnlyConstructor
{
    public function __construct(
        private readonly string $value = 'default'
    )
    {
    }
}

class ClassWithMagicMethods
{
    private array $data = [];

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function __toString(): string
    {
        return json_encode($this->data) ?: '{}';
    }
}
