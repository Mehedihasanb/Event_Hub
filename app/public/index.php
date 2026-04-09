<?php

declare(strict_types=1);

/**
 * EventHub API — front controller (routing, CORS, JWT gates, JSON errors).
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\EventController;
use App\Database;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\BookingRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Services\JwtService;
use App\Services\PaymentService;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = Database::get();
$jwt = new JwtService();
$userRepo = new UserRepository($pdo);
$eventRepo = new EventRepository($pdo);
$bookingRepo = new BookingRepository($pdo, $eventRepo);
$paymentService = new PaymentService();

$authController = new AuthController($userRepo, $jwt);
$eventController = new EventController($eventRepo);
$bookingController = new BookingController($bookingRepo, $eventRepo, $paymentService);

/**
 * Route handler: [callable, 'public'|'jwt'|'admin'].
 *
 * @return list{callable, string}
 */
$dispatcher = simpleDispatcher(function (RouteCollector $r): void {
    $r->addRoute('POST', '/api/auth/register', [[AuthController::class, 'register'], 'public']);
    $r->addRoute('POST', '/api/auth/login', [[AuthController::class, 'login'], 'public']);
    $r->addRoute('GET', '/api/auth/me', [[AuthController::class, 'me'], 'jwt']);

    $r->addRoute('GET', '/api/events', [[EventController::class, 'index'], 'public']);
    $r->addRoute('GET', '/api/events/{id:\d+}', [[EventController::class, 'show'], 'public']);
    $r->addRoute('POST', '/api/events', [[EventController::class, 'store'], 'admin']);
    $r->addRoute('PUT', '/api/events/{id:\d+}', [[EventController::class, 'update'], 'admin']);
    $r->addRoute('DELETE', '/api/events/{id:\d+}', [[EventController::class, 'destroy'], 'admin']);

    $r->addRoute('GET', '/api/bookings', [[BookingController::class, 'index'], 'jwt']);
    $r->addRoute('GET', '/api/bookings/{id:\d+}', [[BookingController::class, 'show'], 'jwt']);
    $r->addRoute('POST', '/api/bookings', [[BookingController::class, 'store'], 'jwt']);
    $r->addRoute('PUT', '/api/bookings/{id:\d+}', [[BookingController::class, 'update'], 'admin']);
    $r->addRoute('DELETE', '/api/bookings/{id:\d+}', [[BookingController::class, 'destroy'], 'jwt']);
    $r->addRoute('POST', '/api/bookings/{id:\d+}/pay', [[BookingController::class, 'pay'], 'jwt']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = rawurldecode(strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/');

// Helpful root when someone opens http://localhost/api in the browser (no route matched otherwise).
if ($httpMethod === 'GET' && ($uri === '/api' || $uri === '/api/')) {
    Response::json(200, [
        'name' => 'EventHub API',
        'ok' => true,
        'hint' => 'There is no data at /api alone. Use a full path, for example:',
        'try' => [
            'GET /api/events' => 'List events (public)',
            'GET /api/events/1' => 'One event by id',
            'POST /api/auth/login' => 'Body: {"email":"...","password":"..."}',
        ],
    ]);
}

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

try {
    switch ($routeInfo[0]) {
        case Dispatcher::NOT_FOUND:
            Response::json(404, ['error' => 'Not found', 'path' => $uri]);

        case Dispatcher::METHOD_NOT_ALLOWED:
            Response::json(405, ['error' => 'Method not allowed']);

        case Dispatcher::FOUND:
            /** @var list{callable, string} $handlerInfo */
            $handlerInfo = $routeInfo[1];
            $vars = $routeInfo[2];

            [$callable, $authMode] = $handlerInfo;

            $user = null;
            if ($authMode === 'jwt' || $authMode === 'admin') {
                $user = $jwt->requireBearer();
            }
            if ($authMode === 'admin' && ($user === null || $user['role'] !== 'admin')) {
                Response::json(403, ['error' => 'Administrator role required']);
            }

            $request = Request::fromGlobals($vars, $user);

            if (is_array($callable) && is_string($callable[0])) {
                $class = $callable[0];
                $method = $callable[1];
                $controller = match ($class) {
                    AuthController::class => $authController,
                    EventController::class => $eventController,
                    BookingController::class => $bookingController,
                    default => throw new \RuntimeException('Unknown controller'),
                };

                $id = isset($vars['id']) ? (int) $vars['id'] : null;

                match (true) {
                    $class === EventController::class && $method === 'show' => $controller->show($request, $id),
                    $class === EventController::class && $method === 'update' => $controller->update($request, $id),
                    $class === EventController::class && $method === 'destroy' => $controller->destroy($id),
                    $class === BookingController::class && $method === 'show' => $controller->show($request, $id),
                    $class === BookingController::class && $method === 'update' => $controller->update($request, $id),
                    $class === BookingController::class && $method === 'destroy' => $controller->destroy($request, $id),
                    $class === BookingController::class && $method === 'pay' => $controller->pay($request, $id),
                    default => $controller->{$method}($request),
                };
            }

            break;

        default:
            Response::json(500, ['error' => 'Routing failure']);
    }
} catch (HttpException $e) {
    Response::json($e->getStatusCode(), ['error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log((string) $e);
    Response::json(500, ['error' => 'Internal server error']);
}
