<?php

declare(strict_types = 1);

use Entities\Part;

class PartController extends AbstractActionController
{
    public function duplicateAction()
    {
        /* @var Part $originalPart */
        try {
            $options = $this->getEvent()->getParam('Laminas\ApiTools\ContentValidation\InputFilter')->getValues();

            $originalPart = $this->partService->loadPart($options['original_part_id']);
            if ($originalPart === null) {
                throw new \RuntimeException(sprintf('Part %s not found', $options['original_part_id']));
            }

            $partId = (new \Services\DuplicatePart($originalPart, $options, $this->partService))->create();

            $result = new JsonModel(['result' => true, 'part_id' => $partId ?? 0]);
        } catch (\Throwable $exception) {
            $result = new ApiProblemResponse(new ApiProblem(422, $exception->getMessage()));
        }

        return $result;
    }
}
