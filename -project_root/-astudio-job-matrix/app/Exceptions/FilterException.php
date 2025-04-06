<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Custom exception for handling filter-related errors
 *
 * This exception is used to handle errors that occur during the filtering
 * process and returns appropriate JSON responses with error messages.
 */
class FilterException extends Exception
{
    /**
     * The HTTP status code to be returned in the response
     *
     * @var int
     */
    protected int $httpStatusCode = 400;

    /**
     * Get the HTTP status code for this exception
     *
     * @return int The HTTP status code
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Set the HTTP status code for this exception
     *
     * @param int $code The HTTP status code to set
     * @return self Returns this instance for method chaining
     */
    public function setHttpStatusCode(int $code): self
    {
        $this->httpStatusCode = $code;
        return $this;
    }

    /**
     * Render the exception as a JSON response
     *
     * @return JsonResponse The JSON response with error details
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $this->getMessage()
        ], $this->httpStatusCode);
    }
}
