<?php

declare(strict_types = 1);

namespace Entities;

class Part
{

    public function getId(): int
    {
        return 1;
    }
    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getTechnicalAttributes(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getApplicability(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return [];
    }

    /**
     * @param array $data
     * @return $this|array
     */
    public function addApplicability(array $data): array
    {
        return $this;
    }

    /**
     * @return $this|array
     */
    public function addCategories(array $data): array
    {
        return $this;
    }
}