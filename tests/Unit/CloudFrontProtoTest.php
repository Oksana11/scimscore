<?php

namespace Tests\Unit;

use App\Http\Middleware\CloudFrontProto;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class CloudFrontProtoTest extends TestCase
{
    private function run(array $headers): Request
    {
        $request = Request::create('http://origin.internal/');
        foreach ($headers as $k => $v) {
            $request->headers->set($k, $v);
        }
        (new CloudFrontProto)->handle($request, fn () => new Response('ok'));

        return $request;
    }

    public function test_copies_cloudfront_proto(): void
    {
        $this->assertSame('https', $this->run(['CloudFront-Forwarded-Proto' => 'https'])->headers->get('X-Forwarded-Proto'));
    }

    public function test_keeps_existing_header_and_ignores_garbage(): void
    {
        $this->assertSame('http', $this->run(['CloudFront-Forwarded-Proto' => 'https', 'X-Forwarded-Proto' => 'http'])->headers->get('X-Forwarded-Proto'));
        $this->assertNull($this->run(['CloudFront-Forwarded-Proto' => 'javascript'])->headers->get('X-Forwarded-Proto'));
    }
}
