<?php

namespace Libraries;

class BaseController
{
    protected function renderView(string $view) : void
    {
        require dirname(__DIR__) . "/app/views/$view.php";
    }
}