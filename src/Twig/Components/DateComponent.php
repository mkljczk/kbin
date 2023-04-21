<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('date')]
final class DateComponent
{
    public \DateTimeInterface $date;
}
