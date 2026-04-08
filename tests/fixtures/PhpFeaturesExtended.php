<?php

declare(strict_types=1);

// Interface
namespace Turker\PHPUnitCoverageReporter\Tests\fixtures;
use Closure;
use Countable;
use Generator;
use InvalidArgumentException;
use Iterator;
use JsonException;
use Traversable;
use TypeError;
use ValueError;

interface Loggable
{
    public function getLogEntry(): string;

    public function getLogLevel(): string;
}

// Abstract class
abstract class Shape
{
    abstract public function area(): float;

    public function describe(): string
    {
        return sprintf('%s with area %.2f', static::class, $this->area());
    }
}

// Inheritance + method override
class Circle extends Shape
{
    public function __construct(
        private readonly float $radius
    )
    {
    }

    public function area(): float
    {
        return M_PI * $this->radius ** 2;
    }

    public function describe(): string
    {
        return sprintf('Circle(r=%.2f) area=%.2f', $this->radius, $this->area());
    }
}

class Rectangle extends Shape implements Loggable
{
    public function __construct(
        private readonly float $width,
        private readonly float $height
    )
    {
    }

    public function area(): float
    {
        return $this->width * $this->height;
    }

    public function getLogEntry(): string
    {
        return sprintf('Rectangle(%sx%s)', $this->width, $this->height);
    }

    public function getLogLevel(): string
    {
        return 'info';
    }
}

// Static methods and properties
class Counter
{
    private static int $count = 0;

    public static function increment(): void
    {
        self::$count++;
    }

    public static function getCount(): int
    {
        return self::$count;
    }

    public static function reset(): void
    {
        self::$count = 0;
    }
}

// Generator
class NumberGenerator
{
    public function fibonacci(int $limit): Generator
    {
        $a = 0;
        $b = 1;
        while ($a < $limit) {
            yield $a;
            [$a, $b] = [$b, $a + $b];
        }
    }

    public function range(int $start, int $end, int $step = 1): Generator
    {
        for ($i = $start; $i <= $end; $i += $step) {
            yield $i;
        }
    }
}

// Readonly property on non-readonly class
class Config
{
    public readonly string $name;
    private array $values = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }
}

// Closures
class EventEmitter
{
    /** @var array<string, list<Closure>> */
    private array $listeners = [];

    public function on(string $event, Closure $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function emit(string $event, mixed $data = null): array
    {
        $results = [];
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $results[] = $listener($data);
        }
        return $results;
    }

    public function createGreeter(string $prefix): Closure
    {
        return function (string $name) use ($prefix): string {
            return "$prefix, $name!";
        };
    }
}

// Heredoc and string interpolation
class TemplateEngine
{
    public function render(string $name, int $age): string
    {
        $greeting = "Hello, $name!";
        $info = "You are $age years old.";

        $template = <<<EOT
        Dear $name,
        {$info}
        Welcome!
        EOT;

        return $greeting . "\n" . $template;
    }
}

// Class constants
class HttpMethod
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';

    public const ALL = [self::GET, self::POST, self::PUT, self::DELETE];

    public static function isValid(string $method): bool
    {
        return in_array(strtoupper($method), self::ALL, true);
    }
}

// Multiple catch types
class DataProcessor
{
    public function parse(string $input): mixed
    {
        try {
            return json_decode($input, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException|InvalidArgumentException $e) {
            return null;
        }
    }

    public function convert(mixed $value): string
    {
        try {
            if (is_array($value)) {
                return implode(',', $value);
            }
            return (string)$value;
        } catch (TypeError|ValueError $e) {
            return '';
        }
    }
}

// Intersection type (PHP 8.1+)
class CollectionProcessor
{
    public function countTraversable(Traversable&Countable $items): int
    {
        return count($items);
    }

    public function firstItem(Iterator&Countable $items): mixed
    {
        if (count($items) === 0) {
            return null;
        }
        $items->rewind();
        return $items->current();
    }
}

// Standalone functions (to test FunctionExtractor)
function calculateDiscount(float $price, float $percentage): float
{
    return $price * (1 - $percentage / 100);
}

function formatCurrency(float $amount, string $currency = 'USD'): string
{
    return sprintf('%s %.2f', $currency, $amount);
}

function isEven(int $n): bool
{
    return $n % 2 === 0;
}
