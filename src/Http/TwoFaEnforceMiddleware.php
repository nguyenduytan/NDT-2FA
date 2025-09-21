<?php
declare(strict_types=1);
namespace ndtan\TwoFA\Http;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

/**
 * Example PSR-15 middleware that enforces a '2fa_verified' request attribute.
 * Adapt this to your framework / session handling as needed.
 */
final class TwoFaEnforceMiddleware implements MiddlewareInterface
{
    public function __construct(private string $attribute = '2fa_verified', private int $status = 403) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isVerified = (bool)($request->getAttribute($this->attribute, false));
        if ($isVerified) {
            return $handler->handle($request);
        }
        $resp = new Response($this->status);
        return $resp->withHeader('Content-Type', 'application/json')
                    ->withBody(Utils::streamFor(json_encode(['error'=>'2fa_required'])));
    }
}
