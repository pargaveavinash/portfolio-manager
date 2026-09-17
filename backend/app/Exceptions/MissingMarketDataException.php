<?php

namespace App\Exceptions;

use Exception;

class MissingMarketDataException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'message' => 'Market data is temporarily unavailable for one or more holdings.',
            'error'   => $this->getMessage(),
        ], 409);
    }
}
