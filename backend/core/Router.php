<?php
declare(strict_types=1);

/**
 * راوتر خفيف: يطابق (METHOD + PATH) مع دالة معالجة، ويدعم باراميترات مسار مثل {id}.
 */
final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: callable, middlewares: callable[]}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler, array $middlewares = []): void
    {
        $this->routes[] = compact('method', 'pattern', 'handler', 'middlewares');
    }

    public function get(string $pattern, callable $handler, array $middlewares = []): void
    {
        $this->add('GET', $pattern, $handler, $middlewares);
    }

    public function post(string $pattern, callable $handler, array $middlewares = []): void
    {
        $this->add('POST', $pattern, $handler, $middlewares);
    }

    public function put(string $pattern, callable $handler, array $middlewares = []): void
    {
        $this->add('PUT', $pattern, $handler, $middlewares);
    }

    public function delete(string $pattern, callable $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middlewares);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // إزالة مسار المجلد الأساسي إن كان الـ API داخل مجلد فرعي على الاستضافة
        $basePath = '/api';
        if (str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        $path = '/' . trim($path, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            foreach ($route['middlewares'] as $middleware) {
                $middleware($params);
            }

            call_user_func($route['handler'], $params);
            return;
        }

        Response::error('المسار غير موجود', 404);
    }

    /**
     * يحوّل نمطًا مثل /ads/{id} إلى Regex ويحاول مطابقته مع المسار الفعلي.
     * @return array<string,string>|null
     */
    private function match(string $pattern, string $path): ?array
    {
        $pattern = '/' . trim($pattern, '/');
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#u';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        return array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);
    }
}
