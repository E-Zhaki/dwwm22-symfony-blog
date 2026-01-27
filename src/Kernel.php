<?php

namespace App;

use App\Trait\TimeZoneTrait;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
    use TimeZoneTrait;

    public function __construct(string $environment, bool $debug)
    {
        $this->changeTimeZone($_ENV['APP_TIME_ZONE']);

        return parent::__construct($environment, $debug);
    }
}
