<?php

namespace Cronos\Errors;

use Throwable;
use Cronos\Http\Response;

class ExceptionHandler
{
    public function handle(Throwable $e): Response
    {
        if ($e instanceof HttpNotFoundException) {
            try {
                return view('errors.404')->setStatusCode(404);
            } catch (Throwable) {
                return json(['message' => 'Not Found', 'status' => 404], 404);
            }
        }

        if ($e instanceof ValidationException) {
            if ($e->response()) {
                return $e->response();
            }
            return json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof AuthorizationException) {
            return json(['message' => $e->getMessage()], $e->getCode() ?: 403);
        }

        if ($e instanceof RouteException) {
            return json(["message" => $e->getMessage()])->setStatusCode(500);
        }

        // For generic Throwable, check debug mode
        $debug = env('CRONOS_APP_DEBUG', false);
        $isDebug = filter_var($debug, FILTER_VALIDATE_BOOLEAN);

        if ($isDebug) {
            // If request expects JSON / AJAX, return structured debug JSON
            if (\Cronos\Debug\Dumper::isJsonRequest()) {
                return json([
                    "Type error" => get_class($e),
                    "message" => $e->getMessage(),
                    "file" => $e->getFile(),
                    "line" => $e->getLine(),
                    "trace" => $e->getTrace(),
                    "TraceAsString" => $e->getTraceAsString(),
                ])->setStatusCode(500);
            }

            // Render rich interactive dark HTML error page
            $renderer = new \Cronos\Debug\ErrorRenderer();
            return (new Response())
                ->setContentType("text/html; charset=UTF-8")
                ->setStatusCode(500)
                ->setContent($renderer->render($e));
        }

        if (\Cronos\Debug\Dumper::isJsonRequest()) {
            return json(["message" => "An internal server error occurred."])->setStatusCode(500);
        }

        // Generic friendly production 500 error page if view exists, otherwise text
        try {
            return view('errors.500')->setStatusCode(500);
        } catch (Throwable) {
            return (new Response())
                ->setContentType("text/html; charset=UTF-8")
                ->setStatusCode(500)
                ->setContent('<!DOCTYPE html><html><head><title>500 Internal Server Error</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h1>500 Internal Server Error</h1><p>Something went wrong on our servers.</p></body></html>');
        }
    }
}

