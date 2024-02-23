<?php

declare(strict_types=1);

namespace App\Traits;

trait CommandInputValidator
{
    public function getValidInput(
        string $askMessage,
        \Closure $callback,
        string $inputType = 'ask'
    ): string
    {
        $validInput = false;
        while($validInput === false) {
            $input = $this->$inputType($askMessage);

            try {
                $validInput = $callback($input);
            } catch(\Exception $e) {
                $this->info($e->getMessage());
            }
        }
        return $input;
    }
}
