<?php

namespace App\Services;

class MemorialNameService
{
    public function prefix(bool $isAlive, ?string $gender, ?string $religion): string
    {
        if ($isAlive) {
            return '';
        }

        return match ($religion) {
            'islam' => match ($gender) {
                'male' => 'Alm. ',
                'female' => 'Almh. ',
                default => 'Almarhum/Almarhumah ',
            },
            'christian', 'catholic' => '† ',
            default => 'Mendiang ',
        };
    }

    public function displayName(bool $isAlive, ?string $gender, ?string $religion, string $name): string
    {
        return $this->prefix($isAlive, $gender, $religion).$name;
    }
}
