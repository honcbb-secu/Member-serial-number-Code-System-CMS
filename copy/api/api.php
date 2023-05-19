<?php
require 'vendor/autoload.php'; 
use \Firebase\JWT\JWT;
use \Psr\Http\Message\ServerRequestInterface as Request;
//use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response;
use \Slim\Middleware\BodyParsingMiddleware;

$app = \Slim\Factory\AppFactory::create();
$app->addBodyParsingMiddleware();

require_once 'database.php';

header('Content-Type: application/json');
define('SECRET_KEY', 'a874a7b3c470db985410724a05c38f271dd2b16c4a61f52b3f40e8975242ca43');  // 這是jwt key

function verify_token($jwt) {
    try {
        $decodedPayload = decode_jwt($jwt);
        if ($decodedPayload === false) {
            return 'Failed to decode JWT or signature verification failed';
        }
        if (!isset($decodedPayload['exp']) || time() > $decodedPayload['exp']) {
            return 'JWT expired';
        }
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

$jwt_middleware = function (Request $request, $handler) {
    $jwt = $request->getHeaderLine('Authorization');
    if (empty($jwt)) {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => 'Lack of identity verification']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    $jwt = str_replace('Bearer ', '', $jwt);
    $token_verification = verify_token($jwt);
    if ($token_verification !== true) {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => $token_verification]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    $request = $request->withAttribute('jwt', $jwt);
    $response = $handler->handle($request);
    return $response;
};








//定義jwt (編碼) 函數
function encode_jwt($token) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    $payload = json_encode($token);
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, SECRET_KEY, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

//定義jwt (解碼) 函數
function decode_jwt($jwt) {
    list($header, $payload, $signature) = explode('.', $jwt);

    $base64UrlHeader = str_replace(['-', '_', ''], ['+', '/', '='], $header);
    $decodedHeader = json_decode(base64_decode($base64UrlHeader), true);

    $base64UrlPayload = str_replace(['-', '_', ''], ['+', '/', '='], $payload);
    $decodedPayload = json_decode(base64_decode($base64UrlPayload), true);

    $base64UrlSignature = str_replace(['-', '_', ''], ['+', '/', '='], $signature);
    $decodedSignature = base64_decode($base64UrlSignature);

    $validSignature = hash_hmac('sha256', $header . "." . $payload, SECRET_KEY, true);

    if ($decodedSignature != $validSignature) {
        return false;
    }

    return $decodedPayload;
}

//統一路由
$app->group('/api', function (\Slim\Routing\RouteCollectorProxy $group) use ($pdo, $jwt_middleware) {
//底下各路由資訊：
$group->post('/login', function (Request $request, Response $response) use ($pdo) {
    $body = $request->getParsedBody();

    $username = isset($body['username']) ? $body['username'] : null;
    $password = isset($body['password']) ? $body['password'] : null;

    if (!$username || !$password) {
        // 處理缺少 username 或 password 的情況（防止一大堆debug issue)
        $response->getBody()->write(json_encode(['error' => 'Missing username or password']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }


    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $payload = [
            'username' => $username,
            'is_admin' => $user['is_admin'],
            'iat' => time(),
            'exp' => time() + (60*60) // 1 小時到期，可自定義
        ];
        $jwt = encode_jwt($payload);
        $response->getBody()->write(json_encode(['jwt' => $jwt]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode(['error' => 'The account number or password is incorrect']));
    return $response->withStatus(401);
});


// 註冊
$group->post('/register', function (Request $request, Response $response) use ($pdo) {
    $body = $request->getParsedBody();
    $username = $body['username'];
    $password = password_hash($body['password'], PASSWORD_BCRYPT);

    // 檢查資料庫中是否已存在相同使用者
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        // 使用者已存在，返回錯誤訊息
        $response->getBody()->write(json_encode(['error' => 'User already exists']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // 插入新使用者
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $result = $stmt->execute([$username, $password]);

    if ($result) {
		$response->getBody()->write(json_encode(['success' => 'true']));
        return $response->withStatus(200);
    }

    return $response->withStatus(500);
});



//驗證序號
$group->post('/submit_code', function (Request $request, Response $response) use ($pdo) {
    $jwt = $request->getAttribute('jwt');

    if (!$jwt) {
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(['error' => 'Missing JWT']));
        return $response->withStatus(401);
    }

    $decodedPayload = decode_jwt($jwt);

    if (!$decodedPayload) {
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(['error' => 'Invalid JWT']));
        return $response->withStatus(401);
    }

    $body = $request->getParsedBody();
    $username = $body['username'];
    $code = $body['code'];

    // 驗證序號是否有效
    $stmt = $pdo->prepare("SELECT * FROM codes WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $codeData = $stmt->fetch();

    // 檢查 JWT 的 username 是否與請求的 username 相符
    if ($decodedPayload['username'] !== $username) {
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(['error' => 'Insufficient permissions']));
        return $response->withStatus(403);
    }

    // 如果序號有效且 JWT 的 username 與請求的 username 相符，則更新用戶的有效日期和狀態
    if ($codeData) {
        $stmt = $pdo->prepare("UPDATE users SET expiration_date = DATE_ADD(CURDATE(), INTERVAL ? DAY) WHERE username = ?");
        $stmt->execute([$codeData['duration'], $username]);

        $stmt = $pdo->prepare("UPDATE codes SET used_by = ?, is_active = 0, used_date = CURRENT_DATE WHERE code = ?");
        $stmt->execute([$username, $code]);

        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withStatus(200);
    }

    $response = $response->withHeader('Content-Type', 'application/json');
    $response->getBody()->write(json_encode(['error' => 'Invalid code or code already in use']));
    return $response->withStatus(400);
})->add($jwt_middleware);





// 獲取用戶(狀態,on/off)
$group->get('/user/{username}/status', function (Request $request, Response $response, $args) use ($pdo) {
    $username = $args['username'];
    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $status = $user['is_active'] ? 'active' : 'inactive';
        $response->getBody()->write(json_encode(['status' => $status]));
    } else {
        $response->getBody()->write(json_encode(['error' => 'User not found']));
        return $response->withStatus(404);
    }

    return $response->withHeader('Content-Type', 'application/json');
});

// 獲取用戶(有效日期)
$group->get('/user/{username}/expiration', function (Request $request, Response $response, $args) use ($pdo) {
    $username = $args['username'];
    $stmt = $pdo->prepare("SELECT expiration_date FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $expiration_date = $user['expiration_date'];
        $response->getBody()->write(json_encode(['expiration_date' => $expiration_date]));
    } else {
        $response->getBody()->write(json_encode(['error' => 'User not found']));
        return $response->withStatus(404);
    }

    return $response->withHeader('Content-Type', 'application/json');
});



});
$app->run();
