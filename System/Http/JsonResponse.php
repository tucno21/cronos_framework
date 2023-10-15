<?php

namespace Cronos\Http;

class JsonResponse
{

    protected mixed $dataForJson;

    public function __construct(mixed $dataForJson)
    {
        $this->dataForJson = $dataForJson;
    }

    public function getData()
    {
        $data = match (true) {
            is_object($this->dataForJson) && method_exists($this->dataForJson, 'toArray') => $this->dataForJson->toArray(),
            is_object($this->dataForJson) && !method_exists($this->dataForJson, 'toArray') => (array) $this->dataForJson,
            is_array($this->dataForJson) => $this->dataForJson,
            default => $this->dataForJson
        };

        return $data;
    }
}
