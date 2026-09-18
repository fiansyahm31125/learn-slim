<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exception\ValidationException;

class ProductValidator
{
    public static function validateForCreate(array $data): array
    {
        $errors = [];

        $name = self::validateName($data['name'] ?? null, true, $errors);
        $price = self::validatePrice($data['price'] ?? null, true, $errors);
        $stock = self::validateStock($data['stock'] ?? null, true, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return [
            'name' => $name,
            'price' => $price,
            'stock' => $stock,
        ];
    }

    public static function validateForUpdate(array $data): array
    {
        $errors = [];
        $clean = [];

        $hasName = array_key_exists('name', $data);
        $hasPrice = array_key_exists('price', $data);
        $hasStock = array_key_exists('stock', $data);

        if (!$hasName && !$hasPrice && !$hasStock) {
            throw new ValidationException([
                'general' => ['Setidaknya satu field (name, price, stock) harus diisi untuk update'],
            ]);
        }

        if ($hasName) {
            $clean['name'] = self::validateName($data['name'], false, $errors);
        }
        if ($hasPrice) {
            $clean['price'] = self::validatePrice($data['price'], false, $errors);
        }
        if ($hasStock) {
            $clean['stock'] = self::validateStock($data['stock'], false, $errors);
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $clean;
    }

    private static function validateName(mixed $value, bool $required, array &$errors): ?string
    {
        if ($value === null || $value === '') {
            if ($required) {
                $errors['name'][] = 'Name wajib diisi';
            } else {
                $errors['name'][] = 'Name tidak boleh kosong';
            }
            return null;
        }

        if (!is_string($value) && !is_numeric($value)) {
            $errors['name'][] = 'Name harus berupa teks';
            return null;
        }

        $name = trim((string) $value);

        if ($name === '') {
            $errors['name'][] = 'Name wajib diisi';
            return null;
        }
        if (mb_strlen($name) < 3) {
            $errors['name'][] = 'Name minimal 3 karakter';
        }
        if (mb_strlen($name) > 255) {
            $errors['name'][] = 'Name maksimal 255 karakter';
        }

        return isset($errors['name']) ? null : $name;
    }

    private static function validatePrice(mixed $value, bool $required, array &$errors): ?int
    {
        if ($value === null || $value === '') {
            if ($required) {
                $errors['price'][] = 'Price wajib diisi';
            } else {
                $errors['price'][] = 'Price tidak boleh kosong';
            }
            return null;
        }

        if (!is_numeric($value)) {
            $errors['price'][] = 'Price harus berupa angka';
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            if (!is_int($value) && (string) (int) $value !== trim((string) $value)) {
                $errors['price'][] = 'Price harus berupa bilangan bulat';
                return null;
            }
        }

        $price = (int) $value;

        if ($price < 0) {
            $errors['price'][] = 'Price tidak boleh negatif';
        }
        if ($price > 1_000_000_000) {
            $errors['price'][] = 'Price maksimal 1.000.000.000';
        }

        return isset($errors['price']) ? null : $price;
    }

    private static function validateStock(mixed $value, bool $required, array &$errors): ?int
    {
        if ($value === null || $value === '') {
            if ($required) {
                $errors['stock'][] = 'Stock wajib diisi';
            } else {
                $errors['stock'][] = 'Stock tidak boleh kosong';
            }
            return null;
        }

        if (!is_numeric($value)) {
            $errors['stock'][] = 'Stock harus berupa angka';
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            if (!is_int($value) && (string) (int) $value !== trim((string) $value)) {
                $errors['stock'][] = 'Stock harus berupa bilangan bulat';
                return null;
            }
        }

        $stock = (int) $value;

        if ($stock < 0) {
            $errors['stock'][] = 'Stock tidak boleh negatif';
        }
        if ($stock > 1_000_000_000) {
            $errors['stock'][] = 'Stock maksimal 1.000.000.000';
        }

        return isset($errors['stock']) ? null : $stock;
    }
}
