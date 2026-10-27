<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Aceita somente e-mails da UNIPAR: o domínio (depois do @) precisa ter "unipar"
 * como uma de suas partes. Ex.: nome@unipar.br e nome@edu.unipar.br passam;
 * nome@gmail.com e nome@naounipar.com não.
 */
class UniparEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = Str::lower(Str::afterLast((string) $value, '@'));

        if (! in_array('unipar', explode('.', $domain), true)) {
            $fail('Use o seu e-mail da UNIPAR (com "unipar" depois do @, como nome@unipar.br).');
        }
    }
}
