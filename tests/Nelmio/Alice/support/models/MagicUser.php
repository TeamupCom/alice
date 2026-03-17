<?php

namespace Nelmio\Alice\support\models;

class MagicUser
{
    public function __call($method, $args)
    {
        if (str_starts_with($method, 'set')) {
            $property = lcfirst(substr($method, 3));
            $this->$property = $args[0] . ' set by __call';

            return;
        }

        if (str_starts_with($method, 'get')) {
            $property = lcfirst(substr($method, 3));

            return $this->$property;
        }
    }
}
