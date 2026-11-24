<?php

namespace Andre\Tombstone\Middleware;

use Andre\Tombstone\Tombstone;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RedirectGoneDiscussions implements MiddlewareInterface
{
    public function __construct(
        private readonly UrlGenerator $url,
        private readonly SettingsRepositoryInterface $settings
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        $match = static::matchDiscussionPath($request->getUri()->getPath());

        if ($match === null) {
            return $response;
        }

        $tombstone = Tombstone::findForDiscussion($match['id']);

        if ($tombstone === null) {
            return $response;
        }

        if (! empty($tombstone->redirect_url)) {
            return new RedirectResponse($tombstone->redirect_url, 301);
        }

        if ($match['format'] === 'api') {
            return $this->jsonGoneResponse($tombstone);
        }

        return $this->htmlGoneResponse($tombstone);
    }

    /**
     * Pure path matcher, kept framework-free so it can be unit tested in isolation.
     *
     * @return array{id: int, format: 'html'|'api'}|null
     */
    public static function matchDiscussionPath(string $path): ?array
    {
        if (preg_match('#/api/discussions/(\d+)(?:$|[/?])#', $path, $matches)) {
            return ['id' => (int) $matches[1], 'format' => 'api'];
        }

        if (preg_match('#/d/(\d+)(?:[-/]|$)#', $path, $matches)) {
            return ['id' => (int) $matches[1], 'format' => 'html'];
        }

        return null;
    }

    private function jsonGoneResponse(Tombstone $tombstone): ResponseInterface
    {
        $detail = 'This discussion has been permanently deleted.';

        if ($tombstone->deleted_at) {
            $detail .= ' Removed on '.$tombstone->deleted_at->toDateString().'.';
        }

        return new JsonResponse([
            'errors' => [
                [
                    'status' => '410',
                    'code' => 'discussion_gone',
                    'title' => 'Discussion Gone',
                    'detail' => $detail,
                ],
            ],
        ], 410);
    }

    private function htmlGoneResponse(Tombstone $tombstone): ResponseInterface
    {
        $forumTitle = $this->escape((string) $this->settings->get('forum_title', 'This forum'));
        $homeUrl = $this->escape($this->url->to('forum')->base());
        $discussionTitle = $tombstone->title ? $this->escape($tombstone->title) : null;
        $deletedDate = $tombstone->deleted_at ? $this->escape($tombstone->deleted_at->toFormattedDateString()) : null;

        $heading = $discussionTitle
            ? '&ldquo;'.$discussionTitle.'&rdquo; has been removed'
            : 'This discussion has been removed';

        $meta = $deletedDate
            ? '<p class="andre-tombstone-meta">Removed on '.$deletedDate.'.</p>'
            : '';

        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{$heading} &middot; {$forumTitle}</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:#f5f5f6;color:#1a1a1d;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;padding:24px;box-sizing:border-box}
.andre-tombstone-card{max-width:480px;text-align:center;background:#fff;border-radius:8px;padding:40px 32px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.andre-tombstone-card h1{font-size:20px;margin:0 0 12px}
.andre-tombstone-card p{color:#666;line-height:1.5;margin:0 0 8px}
.andre-tombstone-meta{font-size:13px;color:#999 !important}
.andre-tombstone-card a{display:inline-block;margin-top:20px;color:#fff;background:#4d4dc8;text-decoration:none;padding:10px 20px;border-radius:6px;font-size:14px}
.andre-tombstone-card a:hover{opacity:.9}
</style>
</head>
<body>
<div class="andre-tombstone-card">
<h1>{$heading}</h1>
<p>This content was permanently deleted by a moderator and is not coming back at this address.</p>
{$meta}
<a href="{$homeUrl}">Go to {$forumTitle}</a>
</div>
</body>
</html>
HTML;

        return new HtmlResponse($html, 410);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
