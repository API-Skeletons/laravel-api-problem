<?php

declare(strict_types=1);

namespace ApiSkeletons\Laravel\ApiProblem;

use ApiSkeletons\Laravel\ApiProblem\Exception\InvalidArgumentException;
use ApiSkeletons\Laravel\ApiProblem\Exception\ProblemExceptionInterface;
use Exception;
use Throwable;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function assert;
use function count;
use function get_class;
use function in_array;
use function is_numeric;
use function sprintf;
use function strtolower;
use function trim;

/**
 * Object describing an API-Problem payload.
 */
class ApiProblem
{
    /**
     * Content type for api problem response
     */
    public const CONTENT_TYPE = 'application/problem+json';

    /**
     * Additional details to include in report.
     *
     * @var string[]
     */
    protected array $additionalDetails = [];

    /**
     * URL describing the problem type; defaults to HTTP status codes.
     */
    protected string $type = 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html';

    /**
     * Description of the specific problem.
     */
    protected string|Throwable|Throwable $detail = '';

    /**
     * Whether or not to include a stack trace and previous
     * exceptions when an exception is provided for the detail.
     */
    protected bool $detailIncludesStackTrace = false;

    /**
     * HTTP status for the error.
     */
    protected int $status;

    /**
     * Normalized property names for overloading.
     *
     * @var string[]
     */
    protected array $normalizedProperties = [
        'type'   => 'type',
        'status' => 'status',
        'title'  => 'title',
        'detail' => 'detail',
    ];

    /**
     * Status titles for common problems.
     *
     * @var string[]
     */
    protected array $problemStatusTitles = [
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * Title of the error.
     */
    protected string $title;

    /**
     * Create an instance using the provided information. If nothing is
     * provided for the type field, the class default will be used;
     * if the status matches any known, the title field will be selected
     * from $problemStatusTitles as a result.
     *
     * @param string[] $additional
     */
    public function __construct(int|string $status, string|Throwable|Throwable $detail, ?string $type = null, ?string $title = null, array $additional = [])
    {
        if ($detail instanceof ProblemExceptionInterface) {
            if ($type === null) {
                $type = $detail->getType();
            }

            if ($title === null) {
                $title = $detail->getTitle();
            }

            if (empty($additional)) {
                $additional = $detail->getAdditionalDetails();
            }
        }

        // Ensure a valid HTTP status
        if (
            ! is_numeric($status)
            || ($status < 100)
            || ($status > 599)
        ) {
            $status = 500;
        }

        $this->status = (int) $status;
        $this->detail = $detail;
        $this->title  = $title;

        if ($type !== null) {
            $this->type = $type;
        }

        $this->additionalDetails = $additional;
    }

    /**
     * Retrieve properties.
     *
     * @throws InvalidArgumentException
     */
    public function __get(string $name): mixed
    {
        $normalized = strtolower($name);
        if (in_array($normalized, array_keys($this->normalizedProperties))) {
            $prop = $this->normalizedProperties[$normalized];

            return $this->{$prop};
        }

        if (isset($this->additionalDetails[$name])) {
            return $this->additionalDetails[$name];
        }

        if (isset($this->additionalDetails[$normalized])) {
            return $this->additionalDetails[$normalized];
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid property name "%s"',
            $name
        ));
    }

    /**
     * Cast to an array.
     *
     * @return string[]
     */
    public function toArray(): array
    {
        $problem = [
            'type'   => $this->type,
            'title'  => $this->getTitle(),
            'status' => $this->getStatus(),
            'detail' => $this->getDetail(),
        ];

        // Required fields should always overwrite additional fields
        return array_merge($this->additionalDetails, $problem);
    }

    /**
     * Set the flag indicating whether an exception detail should include a
     * stack trace and previous exception information.
     */
    public function setDetailIncludesStackTrace(bool $flag): ApiProblem
    {
        $this->detailIncludesStackTrace = (bool) $flag;

        return $this;
    }

    /**
     * Retrieve the API-Problem detail.
     *
     * If an exception was provided, creates the detail message from it;
     * otherwise, detail as provided is used.
     */
    protected function getDetail(): string
    {
        if ($this->detail instanceof Throwable || $this->detail instanceof Exception) {
            return $this->createDetailFromException();
        }

        return $this->detail;
    }

    /**
     * Retrieve the API-Problem HTTP status code.
     *
     * If an exception was provided, creates the status code from it;
     * otherwise, code as provided is used.
     */
    protected function getStatus(): int
    {
        if ($this->detail instanceof Throwable || $this->detail instanceof Exception) {
            $this->status = (int) $this->createStatusFromException();
        }

        return $this->status;
    }

    /**
     * Retrieve the title.
     *
     * If the default $type is used, and the $status is found in
     * $problemStatusTitles, then use the matching title.
     *
     * If no title was provided, and the above conditions are not met, use the
     * string 'Unknown'.
     *
     * Otherwise, use the title provided.
     */
    protected function getTitle(): string
    {
        if ($this->title !== null) {
            return $this->title;
        }

        if (
            $this->title === null
            && $this->type === 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html'
            && array_key_exists($this->getStatus(), $this->problemStatusTitles)
        ) {
            return $this->problemStatusTitles[$this->status];
        }

        if ($this->detail instanceof Throwable) {
            return get_class($this->detail);
        }

        if ($this->title === null) {
            return 'Unknown';
        }

        return $this->title;
    }

    /**
     * Create detail message from an exception.
     */
    protected function createDetailFromException(): string
    {
        $e = $this->detail;
        assert($e instanceof Exception || $e instanceof Throwable);

        if (! $this->detailIncludesStackTrace) {
            return $e->getMessage();
        }

        $message                          = trim($e->getMessage());
        $this->additionalDetails['trace'] = $e->getTrace();

        $previous = [];
        $e        = $e->getPrevious();
        while ($e) {
            $previous[] = [
                'code'    => (int) $e->getCode(),
                'message' => trim($e->getMessage()),
                'trace'   => $e->getTrace(),
            ];
            $e          = $e->getPrevious();
        }

        if (count($previous)) {
            $this->additionalDetails['exception_stack'] = $previous;
        }

        return $message;
    }

    /**
     * Create HTTP status from an exception.
     */
    protected function createStatusFromException(): int|string
    {
        $e = $this->detail;
        assert($e instanceof Exception || $e instanceof Throwable);
        $status = $e->getCode();

        if ($status) {
            return $status;
        }

        return 500;
    }
}
