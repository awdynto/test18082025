<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class PatientController
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $patients = [];

    private int $nextId = 1;

    /**
     * @param array<int, array<string, mixed>> $seedData
     */
    public function __construct(array $seedData = [])
    {
        foreach ($seedData as $patient) {
            $this->store($patient);
        }
    }

    /**
     * Return all stored patients.
     *
     * @return array<int, array<string, mixed>>
     */
    public function index(): array
    {
        return array_values($this->patients);
    }

    /**
     * Display a single patient by id.
     *
     * @return array<string, mixed>
     */
    public function show(int $id): array
    {
        return $this->patients[$id] ?? throw new RuntimeException("Patient with id {$id} not found");
    }

    /**
     * Store a new patient record.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function store(array $data): array
    {
        $patient = $this->normalizePatientData($data, true);
        $patient['id'] = $this->nextId++;

        $this->patients[$patient['id']] = $patient;

        return $patient;
    }

    /**
     * Update an existing patient record.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        if (!isset($this->patients[$id])) {
            throw new RuntimeException("Patient with id {$id} not found");
        }

        $existing = $this->patients[$id];
        $updated = array_merge($existing, $this->normalizePatientData($data, false));
        $updated['id'] = $id;

        $this->patients[$id] = $updated;

        return $updated;
    }

    /**
     * Remove a patient record.
     */
    public function destroy(int $id): void
    {
        if (!isset($this->patients[$id])) {
            throw new RuntimeException("Patient with id {$id} not found");
        }

        unset($this->patients[$id]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalizePatientData(array $data, bool $requireAllFields): array
    {
        $normalized = [];

        if ($requireAllFields || array_key_exists('name', $data)) {
            $normalized['name'] = $this->sanitizeRequiredString($data['name'] ?? null, 'name');
        }

        if ($requireAllFields || array_key_exists('date_of_birth', $data)) {
            $normalized['date_of_birth'] = $this->sanitizeDate($data['date_of_birth'] ?? null);
        }

        if ($requireAllFields || array_key_exists('gender', $data)) {
            $normalized['gender'] = $this->sanitizeGender($data['gender'] ?? null);
        }

        if (array_key_exists('address', $data)) {
            $address = $data['address'];
            $normalized['address'] = $address === null ? null : $this->sanitizeOptionalString($address, 'address');
        } elseif ($requireAllFields) {
            $normalized['address'] = null;
        }

        if (array_key_exists('phone', $data)) {
            $phone = $data['phone'];
            $normalized['phone'] = $phone === null ? null : $this->sanitizeOptionalString($phone, 'phone');
        } elseif ($requireAllFields) {
            $normalized['phone'] = null;
        }

        return $normalized;
    }

    private function sanitizeRequiredString(mixed $value, string $field): string
    {
        $string = $this->sanitizeOptionalString($value, $field);

        if ($string === '') {
            throw new InvalidArgumentException(ucfirst($field) . ' is required.');
        }

        return $string;
    }

    private function sanitizeOptionalString(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(ucfirst($field) . ' must be a string.');
        }

        return trim($value);
    }

    private function sanitizeDate(mixed $value): string
    {
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException('Date of birth is required and must be a non-empty string.');
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date === false) {
            throw new InvalidArgumentException('Date of birth must follow the format YYYY-MM-DD.');
        }

        return $date->format('Y-m-d');
    }

    private function sanitizeGender(mixed $value): string
    {
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException('Gender is required and must be a non-empty string.');
        }

        $normalized = strtolower(trim($value));
        $allowed = ['male', 'female', 'other'];

        if (!in_array($normalized, $allowed, true)) {
            throw new InvalidArgumentException('Gender must be one of: ' . implode(', ', $allowed) . '.');
        }

        return $normalized;
    }
}
