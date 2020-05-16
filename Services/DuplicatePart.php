<?php

declare(strict_types = 1);

namespace Services;

use Entities\Part;

final class DuplicatePart
{
    private Part         $originalPart;

    private PartService  $partService;

    private array        $options;

    public function __construct(Part $originalPart, array $options, PartService $partService)
    {
        $this->originalPart = $originalPart;
        $this->options      = $options;
        $this->partService  = $partService;
    }

    /**
     * @return int
     */
    public function create(): int
    {
        $options = $this->formalizeOptions($this->options);

        $newPart = new Part();
        $newPart->setName($this->options['name'])
            ->setBrand($options['brand'])
            ->setNumber($options['number'])
            ->setSlug($options['slug'])
            ->setSku($options['sku'])
            ->setEnabled($this->options['enabled'])
            ->setAttributes($this->options['attributes'])
            ->setFamily($this->originalPart->getFamily());

        $newPart = $this->addApplicability($newPart);
        $newPart = $this->addCategories($newPart);
        $newPart = $this->addAttributes($newPart);

        $this->entityManager->persist($newPart);
        $this->entityManager->flush();

        $newPart = $this->addReplacementPart($newPart);

        $this->connectParts($newPart);

        $this->entityManager->flush();

        return $newPart->getId();
    }

    /**
     * @param Part $part
     * @return Part
     */
    private function addApplicability(Part $part): Part
    {
        foreach ($this->originalPart->getApplicability() as $vehicle) {
            $part->addApplicability($vehicle);
        }

        return $part;
    }

    /**
     * @param Part $part
     * @return Part
     */
    private function addCategories(Part $part): Part
    {
        foreach ($this->originalPart->getCategories() as $category) {
            $part->addCategories($category);
        }

        return $part;
    }

    /**
     * @param Part $newPart
     * @return mixed
     */
    private function addAttributes(Part $newPart)
    {
        //Process technical attributes
        $partAttributes = array_merge($newPart->getAttributes(), $this->originalPart->getTechnicalAttributes());

        //Work with images
        $mediaAttributes = array_merge(
            $partAttributes['media_gallery'] ?? [],
            $this->originalPart->getAttributes()['media_gallery'] ?? []
        );

        // Process main image -> move to main image or to media gallery
        $mainImage = $this->originalPart->getAttributes()['main_image'] ?? '';
        if (!empty($mainImage) && !isset($partAttributes['main_image'])) {
            $partAttributes['main_image'] = $mainImage;
        }
        if (!empty($mainImage) && $partAttributes['main_image'] !== $mainImage) {
            $mediaAttributes = array_merge($mediaAttributes, [$mainImage]);
        }

        $mediaAttributes = array_unique($mediaAttributes);
        if (!empty($mediaAttributes)) {
            $partAttributes['media_gallery'] = $mediaAttributes;
        }

        return $newPart->setAttributes($partAttributes);
    }

    /**
     * @param Part $part
     * @return Part
     */
    private function addReplacementPart(Part $part): Part
    {
        foreach ($this->originalPart->getReplacements() as $cross) {
            $part->addReplacementPart($cross->getTo());
        }

        return $part;
    }

    /**
     * @param Part $newPart
     */
    private function connectParts(Part $newPart): void
    {
        $newPart->addReplacementPart($this->originalPart);
        $this->originalPart->addReplacementPart($newPart);
    }

    /**
     * @param $options
     * @return array
     */
    private function formalizeOptions($options): array
    {
        $brand = $this->partService->loadBrand($options['brand']);
        if ($brand === null) {
            throw new \RuntimeException(sprintf('Brand %s not found', $options['brand']));
        }

        $number    = $this->partService->normalizePartNumber($options['number']);
        $sku       = $this->partService->normalizeSku($brand->getCode(), $options['number']);
        $partBySku = $this->partService->loadPartBySku($options['sku']);
        if ($partBySku !== null) {
            throw new \RuntimeException(sprintf('Sku %s already exist!', $options['sku']));
        }

        return compact('brand', 'number', 'sku', 'partBySku');
    }
}