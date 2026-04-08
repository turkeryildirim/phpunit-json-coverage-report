<?php

declare(strict_types=1);

// Enum
namespace Turker\PHPUnitCoverageReporter\Tests\fixtures;
use Countable;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use TypeError;

enum HttpStatus: int
{
    case OK = 200;
    case NotFound = 404;
    case InternalServerError = 500;

    public function isSuccessful(): bool
    {
        return $this->value >= 200 && $this->value < 300;
    }
}

// Class with readonly properties (PHP 8.1+)
class Point
{
    public function __construct(
        public readonly float $x,
        public readonly float $y,
    )
    {
    }

    public function distanceTo(self $other): float
    {
        return sqrt(($this->x - $other->x) ** 2 + ($this->y - $other->y) ** 2);
    }
}

// Class with various PHP features
class PhpFeatures
{
    // Union types in property
    private int|string $id;

    // Constructor property promotion
    public function __construct(
        int|string              $id,
        private readonly string $name,
        private ?string         $description = null,
    )
    {
        $this->id = $id;
    }

    // Match expression
    public function classify(int $code): string
    {
        return match (true) {
            $code >= 200 && $code < 300 => 'success',
            $code >= 300 && $code < 400 => 'redirect',
            $code >= 400 && $code < 500 => 'client error',
            $code >= 500 => 'server error',
            default => 'unknown',
        };
    }

    // Try/catch/finally
    public function safeDivide(int $a, int $b): float|string
    {
        try {
            if ($b === 0) {
                throw new InvalidArgumentException('Division by zero');
            }
            return $a / $b;
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        } finally {
            // Always executes
        }
    }

    // Named arguments
    public function createPoint(float $x, float $y): Point
    {
        return new Point(x: $x, y: $y);
    }

    // First-class callable syntax
    public function getTransformer(): callable
    {
        return $this->transform(...);
    }

    private function transform(string $value): string
    {
        return strtoupper(trim($value));
    }

    // Arrow function
    public function getMultiplier(int $factor): callable
    {
        return static fn(int $x): int => $x * $factor;
    }

    // Anonymous class
    public function createLogger(): object
    {
        return new class {
            public function log(string $message): void
            {
                // Logging implementation
            }

            public function error(string $message): void
            {
                // Error logging implementation
            }
        };
    }

    // Nullsafe operator
    public function getLength(?string $value): int
    {
        return $value?->length ?? 0;
    }

    // Spread operator
    public function sum(int ...$numbers): int
    {
        return array_sum($numbers);
    }

    // Destructuring with keys
    public function getCoordinates(): array
    {
        $data = ['x' => 1.0, 'y' => 2.0, 'z' => 3.0];
        ['x' => $x, 'y' => $y] = $data;

        return ['x' => $x, 'y' => $y];
    }

    /**
     * @param object $items
     * @return int
     */
    public function countItems(object $items): int
    {
        if ($items instanceof Countable) {
            return count($items);
        }
        return 0;
    }
}

// Trait with abstract method
trait Timestampable
{
    private ?DateTimeImmutable $createdAt = null;
    private ?DateTimeImmutable $updatedAt = null;

    public function setCreatedAt(?DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

// Class using trait
class BlogPost
{
    use Timestampable;

    public function __construct(
        private readonly string $title,
        private readonly string $content,
    )
    {
    }
}

// Nested try/catch
class NestedExceptionHandling
{
    public function process(mixed $data): string
    {
        try {
            return $this->validate($data);
        } catch (TypeError $e) {
            try {
                return $this->fallback($data);
            } catch (Exception $e2) {
                return 'error';
            }
        } catch (Exception $e) {
            return 'error';
        }
    }

    private function validate(mixed $data): string
    {
        if (!is_string($data)) {
            throw new TypeError('Expected string');
        }
        return $data;
    }

    private function fallback(mixed $data): string
    {
        return (string)$data;
    }
}
