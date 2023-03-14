<?php

/**
 * Big Number implementation (BCMath)
 *
 * @author emi
 */

namespace Minds\Core\Util;

use JsonSerializable;

class BigNumber implements JsonSerializable
{
    /** @var string $value */
    protected $value;

    /** @var int $scale */
    protected $scale;

    /**
     * BigNumber constructor.
     * @param $value
     * @param int $scale
     * @throws \Exception
     */
    public function __construct($value, $scale = 0)
    {
        $this->scale = (int) $scale;
        $this->value = $this->normalize($value);
    }

    /**
     * Magic casting to string.
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Returns the value as a string.
     * @return string
     */
    public function toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Returns the value as a double/float.
     * !!!WARNING!!! for really big numbers it might lose some precision
     * @return float
     */
    public function toDouble(): float
    {
        return (double) $this->toString();
    }

    /**
     * Returns the value as an integer.
     * !!!WARNING!!! for really big numbers it might lose some precision
     * @return int
     */
    public function toInt(): int
    {
        return (int) $this->toString();
    }

    /**
     * Sets the current decimal scale.
     * @param int $scale
     * @return BigNumber
     */
    public function setScale(mixed $scale): self
    {
        $this->scale = (int) $scale;
        return $this;
    }

    /**
     * Gets the current decimal scale.
     * @return int
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * Adds value to another.
     * @param mixed $rightOperand
     * @return BigNumber
     * @throws \Exception
     */
    public function add(mixed $rightOperand): self
    {
        return $this->immutable(bcadd($this->value, $this->normalize($rightOperand), $this->scale));
    }

    /**
     * Subtracts value to another.
     * @param mixed $rightOperand
     * @return BigNumber
     * @throws \Exception
     */
    public function sub(mixed $rightOperand): self
    {
        return $this->immutable(bcsub($this->value, $this->normalize($rightOperand), $this->scale));
    }

    /**
     * Multiplies value to another.
     * @param mixed $rightOperand
     * @return BigNumber
     * @throws \Exception
     */
    public function mul(mixed $rightOperand): self
    {
        return $this->immutable(bcmul($this->value, $this->normalize($rightOperand), $this->scale));
    }

    /**
     * Divides value to another.
     * @param mixed $rightOperand
     * @return BigNumber
     * @throws \Exception
     */
    public function div(mixed $rightOperand): self
    {
        return $this->immutable(bcdiv($this->value, $this->normalize($rightOperand), $this->scale));
    }

    /**
     * Raises value to another.
     * @param mixed $rightOperand
     * @return BigNumber
     * @throws \Exception
     */
    public function pow(mixed $rightOperand): self
    {
        return $this->immutable(bcpow($this->value, $this->normalize($rightOperand), $this->scale));
    }

    /**
     * Gets the square root of value.
     * @return BigNumber
     * @throws \Exception
     */
    public function sqrt(): self
    {
        return $this->immutable(bcsqrt($this->value, $this->scale));
    }

    /**
     * Inverts the value's sign.
     * @return BigNumber
     * @throws \Exception
     */
    public function neg(): self
    {
        return $this->mul(-1);
    }

    /**
     * Compares value to another, returning true if equal.
     * Equivalent to ===.
     * @param mixed $rightOperand
     * @return bool
     * @throws \Exception
     */
    public function eq(mixed $rightOperand): bool
    {
        return bccomp($this->value, $this->normalize($rightOperand), $this->scale) === 0;
    }

    /**
     * Compares value to another, returning true if value is less than another.
     * Equivalent to <.
     * @param mixed $rightOperand
     * @return bool
     * @throws \Exception
     */
    public function lt(mixed $rightOperand): bool
    {
        return bccomp($this->value, $this->normalize($rightOperand), $this->scale) === -1;
    }

    /**
     * Compares value to another, returning true if value is less than or equal to another.
     * Equivalent to <=.
     * @param mixed $rightOperand
     * @return bool
     * @throws \Exception
     */
    public function lte(mixed $rightOperand): bool
    {
        return bccomp($this->value, $this->normalize($rightOperand), $this->scale) <= 0;
    }

    /**
     * Compares value to another, returning true if value is greater than another.
     * Equivalent to >.
     * @param mixed $rightOperand
     * @return bool
     * @throws \Exception
     */
    public function gt(mixed $rightOperand): bool
    {
        return bccomp($this->value, $this->normalize($rightOperand), $this->scale) === 1;
    }

    /**
     * Compares value to another, returning true if value is greater than or equal to another.
     * Equivalent to >=.
     * @param mixed $rightOperand
     * @return bool
     * @throws \Exception
     */
    public function gte(mixed $rightOperand): bool
    {
        return bccomp($this->value, $this->normalize($rightOperand), $this->scale) >= 0;
    }

    /**
     * Converts value to another base.
     * @param int $base
     * @return string
     * @throws \Exception
     */
    public function toBase(int $base): string
    {
        if ($base < 2 || $base > 36) {
            throw new \Exception('Invalid base');
        }

        $base = (string) $base;
        $sign = bccomp($this->value, '0') === -1 ? '-' : '';
        $dec = ltrim($this->value, '-');
        $based = '';

        do {
            $based = base_convert(bcmod($dec, $base), 10, (int) $base) . $based;
            $dec = bcdiv($dec, $base, '0');
        } while (bccomp($dec, 0) === 1);

        return $sign . $based;
    }

    /**
     * Converts the value to hexadecimal.
     * @param bool $prefix
     * @return string
     * @throws \Exception
     */
    public function toHex(bool $prefix = false): string
    {
        return ($prefix ? '0x' : '') . $this->toBase(16);
    }

    /**
     * Creates a new instance using a value represented in another base.
     * @param string $based
     * @param int $base
     * @return self
     * @throws \Exception
     */
    public static function fromBase(string $based, int $base): self
    {
        if ($base < 2 || $base > 36) {
            throw new \Exception('Invalid base');
        }

        $base = (string) $base;

        if (!$based) {
            return new BigNumber(0);
        }

        $digits = array_reverse(str_split($based));
        $dec = '0';

        for ($i = 0; $i < count($digits); $i++) {
            $mul = bcpow($base, (string) $i, '0');
            $part = bcmul(base_convert($digits[$i], (int) $base, 10), $mul, '0');

            $dec = bcadd($dec, $part, '0');
        }

        return new BigNumber($dec, 0);
    }

    /**
     * Creates a new instance using an hexadecimal value.
     * @param string $value
     * @return BigNumber
     * @throws \Exception
     */
    public static function fromHex(string $value): self
    {
        if (stripos($value, '0x') === 0) {
            $value = substr($value, 2);
        }

        return static::fromBase($value, 16);
    }

    /**
     * Creates a new instance with the value converted to plain decimals (used by Eth). (x)
     * @param mixed $value
     * @param int $decimalPlaces
     * @return static
     * @throws \Exception
     */
    public static function toPlain(mixed $value, int $decimalPlaces): self
    {
        $decimal = (new BigNumber(10))->pow((int) $decimalPlaces);
        return (new BigNumber($value))->mul($decimal);
    }

    /**
     * Creates a new instance with the value converted from plain decimals (used by Eth). (/)
     * @param mixed $value
     * @param int $decimalPlaces
     * @return static
     * @throws \Exception
     */
    public static function fromPlain(mixed $value, int $decimalPlaces): self
    {
        $decimal = (new BigNumber(10))->pow((int) $decimalPlaces);
        return (new BigNumber($value, $decimalPlaces))->div($decimal);
    }

    /**
     * Factory method.
     * @param mixed $value
     * @param int $base
     * @return self
     * @throws \Exception
     */
    public static function _(mixed $value, int $base = 0): self
    {
        return new BigNumber($value, $base);
    }

    /**
     * Creates a new instance of BigNumber with the provided value.
     * @param mixed $value
     * @return static
     * @throws \Exception
     */
    protected function immutable(mixed $value): self
    {
        return new BigNumber($value, $this->scale);
    }

    /**
     * Normalizes a value.
     * Accepts Cassandra types, exp notation and any numeric value.
     * @param mixed $value
     * @return string
     * @throws \Exception
     */
    protected function normalize(mixed $value): string
    {
        if (
            is_object($value) &&
            strpos(trim(get_class($value), '\\'), 'Cassandra') === 0
        ) {
            $value = $value->value();
        }

        try { // Avoid exceptions
            $stringValue = @strtolower((string) $value);

            if (preg_match("/^-?[0-9]+(\.[0-9]+)?e[-+]?[0-9]+$/", $stringValue)) {
                $parts = explode('e', $stringValue, 2);
                $value = bcmul($parts[0], bcpow('10', $parts[1], $this->scale), $this->scale);
            }
        } catch (\Exception $e) {
        }

        $value = (string) $value;

        if ($value === '' || !is_numeric($value)) {
            throw new \Exception('Expected a numeric value');
        }

        return $value;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return string data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
