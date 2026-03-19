<?php
namespace Nelmio\Alice;

use Faker\Provider\Base;

class BarProvider extends Base
{
    public static function bar($str)
    {
        return 'bar' . $str;
    }
}
