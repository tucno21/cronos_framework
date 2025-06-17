<?php

namespace Cronos\Errors;

use Throwable;
use Cronos\Http\Response;

class ExceptionHandler
{
    public function handle(Throwable $e): Response
    {
        if ($e instanceof HttpNotFoundException) {
            return view('error/404')->setStatusCode(404);
        }

        if ($e instanceof RouteException) {
            return json(["message" => $e->getMessage()])->setStatusCode(500);
        }

        // For generic Throwable, check debug mode
        if (env('CRONOS_APP_DEBUG', false)) {
            return json([
                "Type error" => get_class($e),
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTrace(),
                "TraceAsString" => $e->getTraceAsString(),
            ])->setStatusCode(500);
        }

        return json(["message" => "An internal server error occurred."])->setStatusCode(500);
    }
}
