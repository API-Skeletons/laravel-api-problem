<?php

declare(strict_types=1);

namespace ApiSkeletonsTest\Laravel\ApiProblem;

use ApiSkeletons\Laravel\ApiProblem\ApiProblem;
use ApiSkeletons\Laravel\ApiProblem\Exception;
use ApiSkeletons\Laravel\ApiProblem\Facades\ApiProblem as ApiProblemFacade;
use http\Exception\InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use ReflectionObject;
use TypeError;

final class ApiProblemTest extends TestCase
{
    /** @psalm-return array<string, array{0: int}> */
    public function statusCodes(): array
    {
        return [
            '200' => [200],
            '201' => [201],
            '300' => [300],
            '301' => [301],
            '302' => [302],
            '400' => [400],
            '401' => [401],
            '404' => [404],
            '500' => [500],
        ];
    }

    public function testResponseWithObject(): void
    {
        $apiProblem = new ApiProblem(500, 'Testing');

        $this->assertInstanceOf(JsonResponse::class, $apiProblem->response());
    }

    public function testResponseWithFacade(): void
    {
        $this->assertInstanceOf(JsonResponse::class, ApiProblemFacade::response('Testing', 500));
    }

    /**
     * @dataProvider statusCodes
     */
    public function testStatusIsUsedVerbatim(int $status): void
    {
        $apiProblem = new ApiProblem($status, 'foo');
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('status', $payload);
        $this->assertEquals($status, $payload['status']);
    }

    /**
     * @requires PHP 7.0
     */
    public function testErrorAsDetails(): void
    {
        $error = new TypeError('error message', 705);
        $apiProblem = new ApiProblem(500, $error);
        $payload = $apiProblem->toArray();

        $this->assertArrayHasKey('title', $payload);
        $this->assertEquals('TypeError', $payload['title']);
        $this->assertArrayHasKey('status', $payload);
        $this->assertEquals(705, $payload['status']);
        $this->assertArrayHasKey('detail', $payload);
        $this->assertEquals('error message', $payload['detail']);
    }

    public function testExceptionCodeIsUsedForStatus(): void
    {
        $exception = new \Exception('exception message', 401);
        $apiProblem = new ApiProblem('500', $exception);
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('status', $payload);
        $this->assertEquals($exception->getCode(), $payload['status']);
    }

    public function testDetailStringIsUsedVerbatim(): void
    {
        $apiProblem = new ApiProblem('500', 'foo');
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('detail', $payload);
        $this->assertEquals('foo', $payload['detail']);
    }

    public function testExceptionMessageIsUsedForDetail(): void
    {
        $exception = new \Exception('exception message');
        $apiProblem = new ApiProblem('500', $exception);
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('detail', $payload);
        $this->assertEquals($exception->getMessage(), $payload['detail']);
    }

    public function testExceptionsCanTriggerInclusionOfStackTraceInDetails(): void
    {
        $exception = new \Exception('exception message');
        $apiProblem = new ApiProblem('500', $exception);
        $apiProblem->setDetailIncludesStackTrace(true);
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('trace', $payload);
        $this->assertIsArray($payload['trace']);
        $this->assertEquals($exception->getTrace(), $payload['trace']);
    }

    public function testExceptionsCanTriggerInclusionOfNestedExceptions(): void
    {
        $exceptionChild = new \Exception('child exception');
        $exceptionParent = new \Exception('parent exception', 0, $exceptionChild);

        $apiProblem = new ApiProblem('500', $exceptionParent);
        $apiProblem->setDetailIncludesStackTrace(true);
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('exception_stack', $payload);
        $this->assertIsArray($payload['exception_stack']);
        $expected = [
            [
                'code' => $exceptionChild->getCode(),
                'message' => $exceptionChild->getMessage(),
                'trace' => $exceptionChild->getTrace(),
            ],
        ];
        $this->assertEquals($expected, $payload['exception_stack']);
    }

    public function testTypeUrlIsUsedVerbatim(): void
    {
        $apiProblem = new ApiProblem('500', 'foo', 'http://status.dev:8080/details.md');
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('type', $payload);
        $this->assertEquals('http://status.dev:8080/details.md', $payload['type']);
    }

    /** @psalm-return array<string, array{0: int}> */
    public function knownStatusCodes(): array
    {
        return [
            '404' => [404],
            '409' => [409],
            '422' => [422],
            '500' => [500],
        ];
    }

    /**
     * @dataProvider knownStatusCodes
     */
    public function testKnownStatusResultsInKnownTitle(int $status): void
    {
        $apiProblem = new ApiProblem($status, 'foo');
        $r = new ReflectionObject($apiProblem);
        $p = $r->getProperty('problemStatusTitles');
        $p->setAccessible(true);
        $titles = $p->getValue($apiProblem);

        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('title', $payload);
        $this->assertEquals($titles[$status], $payload['title']);
    }

    public function testUnknownStatusResultsInUnknownTitle(): void
    {
        $apiProblem = new ApiProblem(420, 'foo');
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('title', $payload);
        $this->assertEquals('Unknown', $payload['title']);
    }

    public function testProvidedTitleIsUsedVerbatim(): void
    {
        $apiProblem = new ApiProblem('500', 'foo', 'http://status.dev:8080/details.md', 'some title');
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('title', $payload);
        $this->assertEquals('some title', $payload['title']);
    }

    public function testCanPassArbitraryDetailsToConstructor(): void
    {
        $problem = new ApiProblem(
            400,
            'Invalid input',
            'http://example.com/api/problem/400',
            'Invalid entity',
            ['foo' => 'bar']
        );
        $this->assertEquals('bar', $problem->foo);
    }

    public function testArraySerializationIncludesArbitraryDetails(): void
    {
        $problem = new ApiProblem(
            400,
            'Invalid input',
            'http://example.com/api/problem/400',
            'Invalid entity',
            ['foo' => 'bar']
        );
        $array = $problem->toArray();
        $this->assertArrayHasKey('foo', $array);
        $this->assertEquals('bar', $array['foo']);
    }

    public function testArbitraryDetailsShouldNotOverwriteRequiredFieldsInArraySerialization(): void
    {
        $problem = new ApiProblem(
            400,
            'Invalid input',
            'http://example.com/api/problem/400',
            'Invalid entity',
            ['title' => 'SHOULD NOT GET THIS']
        );
        $array = $problem->toArray();
        $this->assertArrayHasKey('title', $array);
        $this->assertEquals('Invalid entity', $array['title']);
    }

    public function testUsesTitleFromExceptionWhenProvided(): void
    {
        $exception = new Exception\DomainException('exception message', 401);
        $exception->setTitle('problem title');
        $apiProblem = new ApiProblem('401', $exception);
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('title', $payload);
        $this->assertEquals($exception->getTitle(), $payload['title']);
    }

    public function testUsesTypeFromExceptionWhenProvided(): void
    {
        $exception = new Exception\DomainException('exception message', 401);
        $exception->setType('http://example.com/api/help/401');
        $apiProblem = new ApiProblem('401', $exception);
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('type', $payload);
        $this->assertEquals($exception->getType(), $payload['type']);
    }

    public function testUsesAdditionalDetailsFromExceptionWhenProvided(): void
    {
        $exception = new Exception\DomainException('exception message', 401);
        $exception->setAdditionalDetails(['foo' => 'bar']);
        $apiProblem = new ApiProblem('401', $exception);
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('foo', $payload);
        $this->assertEquals('bar', $payload['foo']);
    }

    /** @psalm-return array<string, array{0: int}> */
    public function invalidStatusCodes(): array
    {
        return [
            '-1' => [-1],
            '0' => [0],
            '7' => [7], // reported
            '14' => [14], // observed
            '600' => [600],
        ];
    }

    /**
     * @dataProvider invalidStatusCodes
     * @group api-tools-118
     */
    public function testInvalidHttpStatusCodesAreCastTo500(int $code): void
    {
        $e = new \Exception('Testing', $code);
        $problem = new ApiProblem($code, $e);
        $this->assertEquals(500, $problem->status);
    }

    /**
     * @dataProvider statusCodes
     * @group api-tools-118
     */
    public function testMagicGetInvalidArgument(int $code): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $apiProblem = new ApiProblem($code, 'Testing', null, null, ['more' => 'testing']);

        $this->assertEquals('testing', $apiProblem->code);
    }

    /**
     * @dataProvider statusCodes
     * @group api-tools-118
     */
    public function testMagicGetNormalizedProperties(int $code): void
    {
        $apiProblem = new ApiProblem($code, 'Testing', 'test', 'title test', ['more' => 'testing']);

        $this->assertEquals('test', $apiProblem->__get('type'));
        $this->assertEquals($code, $apiProblem->__get('status'));
        $this->assertEquals('title test', $apiProblem->__get('title'));
        $this->assertEquals('Testing', $apiProblem->__get('detail'));
    }

    /**
     * @dataProvider statusCodes
     * @group api-tools-118
     */
    public function testMagicGetAdditionalDetails(int $code): void
    {
        $apiProblem = new ApiProblem($code, 'Testing', 'test', 'title test', ['MixedCase' => 'testing']);

        $this->assertEquals('testing', $apiProblem->__get('MixedCase'));
    }

    /**
     * @dataProvider statusCodes
     * @group api-tools-118
     */
    public function testMagicGetAdditionalDetailsNormalized(int $code): void
    {
        $apiProblem = new ApiProblem($code, 'Testing', 'test', 'title test', ['xxcode' => 'testing']);

        $this->assertEquals('testing', $apiProblem->__get('xxcode'));
    }
}
