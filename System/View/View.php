<?php

namespace Cronos\View;

interface View
{
    public function render(string $view, array $params = []): string;
}
