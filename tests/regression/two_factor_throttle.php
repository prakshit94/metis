<?php
// No database, credentials, email delivery, or live rate-limit state is used.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['cache.default' => 'array', 'session.driver' => 'array', 'app.debug' => false]);
$limiter = app(Illuminate\Cache\RateLimiter::class);
(new ReflectionProperty($limiter, 'cache'))->setValue($limiter, app('cache')->store('array'));
function otpCheck(bool $ok, string $label): void {
    if (!$ok) throw new RuntimeException($label);
    echo 'PASS: '.$label.PHP_EOL;
}
$request = Illuminate\Http\Request::create('/login/two-factor', 'POST', [], [], [], ['REMOTE_ADDR' => '192.0.2.30', 'HTTP_ACCEPT' => 'text/html']);
$request->setUserResolver(fn () => null);
$request->setRouteResolver(fn () => new Illuminate\Routing\Route('POST', 'login/two-factor', fn () => null));
$session = new Illuminate\Session\Store('otp-throttle-test', new Illuminate\Session\ArraySessionHandler(120));
$session->start();
$request->setLaravelSession($session);
$app->instance('session.store', $session);
$app->instance('request', $request);
$middleware = new Illuminate\Routing\Middleware\ThrottleRequests($limiter);
$next = fn () => response('ok');
for ($i = 0; $i < 10; $i++) $middleware->handle($request, $next, 10, 1, 'otp-web-email:');
try {
    $middleware->handle($request, $next, 10, 1, 'otp-web-email:');
    throw new RuntimeException('resend limit did not block');
} catch (Illuminate\Http\Exceptions\ThrottleRequestsException $error) {
    otpCheck((int)$error->getHeaders()['Retry-After'] > 0, 'resend retains its 10 per-minute limit and retry header');
}
otpCheck($middleware->handle($request, $next, 20, 1, 'otp-web-verify:')->getStatusCode() === 200, 'resend requests do not consume verification route budget');
$service = app(App\Services\TwoFactorService::class);
$throttle = new ReflectionMethod($service, 'throttle');
for ($i = 0; $i < 5; $i++) $throttle->invoke($service, 999999);
try {
    $throttle->invoke($service, 999999);
    throw new RuntimeException('account limit did not block');
} catch (Symfony\Component\HttpKernel\Exception\HttpException $error) {
    otpCheck($error->getStatusCode() === 429 && (int)$error->getHeaders()['Retry-After'] > 0, 'account OTP budget remains five attempts with an accurate retry header');
    $response = app(Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request, $error);
    otpCheck($response->getStatusCode() === 302 && str_ends_with($response->headers->get('Location'), '/login/two-factor'), 'browser lockout returns to the OTP form');
    otpCheck(str_contains($session->get('error'), 'Wait ') && !$session->has('_old_input.code'), 'browser explains retry without flashing an OTP');
    $apiRequest = Illuminate\Http\Request::create('/api/auth/two-factor/challenge', 'POST', [], [], [], ['HTTP_ACCEPT'=>'application/json']);
    $response = app(Illuminate\Contracts\Debug\ExceptionHandler::class)->render($apiRequest, $error);
    otpCheck($response->getStatusCode() === 429 && $response->headers->has('Retry-After'), 'mobile lockout retains HTTP 429 and retry header');
}
foreach (['web', 'api'] as $channel) {
    $routes = file_get_contents(base_path('routes/'.$channel.'.php'));
    foreach (['enroll', 'email', 'verify'] as $action) {
        otpCheck(str_contains($routes, 'otp-'.($channel === 'web' ? 'web' : 'mobile').'-'.$action.':'), $channel.' '.$action.' route uses an isolated limiter');
    }
}
