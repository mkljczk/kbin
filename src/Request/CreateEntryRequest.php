<?php declare(strict_types=1);

namespace App\Request;

use App\Entity\Image;
use App\Entity\Magazine;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;

class CreateEntryRequest implements RequestDtoInterface
{
    #[Assert\NotBlank]
    public Magazine $magazine;
    public User $user;
    public ?Image $image = null;
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $title;
    #[Assert\Url]
    public ?string $url = null;
    #[Assert\Length(min: 2, max: 15000)]
    public ?string $body = null;

    public function __construct(Request $request)
    {
        // @todo
    }
}