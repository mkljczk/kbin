<?php

namespace App\Twig\Components;

use App\Entity\Contracts\ContentInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('favourite')]
final class FavouriteComponent
{
    public string $path;
    public ContentInterface $subject;
}
