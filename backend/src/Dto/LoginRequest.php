<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequest
{
    #[Assert\NotBlank]
    public string $username;
}
