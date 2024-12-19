<?php

namespace App;

trait RedirectTrait
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
