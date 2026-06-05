<?php
// test_redis.php
echo "Testing Redis connection...\n";

$redis = new Redis();
try {
    // Пробуем разные таймауты и параметры
    $redis->connect(redis, 6379, 2.5);
    // или
    // $redis->connect('localhost', 6379, 2.5);
    // или если в Docker сети
    // $redis->connect('redis', 6379, 2.5);

    echo "Connected successfully!\n";

    // Тестовые операции
    $redis->set('php_test', 'Hello from PHP!');
    $value = $redis->get('php_test');
    echo "PHP test value: " . $value . "\n";

    // Получаем информацию
    $info = $redis->info();
    echo "Redis version: " . $info['redis_version'] . "\n";

    // Все ключи
    $keys = $redis->keys('*');
    echo "All keys: " . implode(', ', $keys) . "\n";

} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";

    // Дополнительная диагностика
    echo "Trying to check socket...\n";
    if (fsockopen('127.0.0.1', 6379, $errno, $errstr, 2)) {
        echo "Port 6379 is open but PHP can't connect\n";
    } else {
        echo "Port 6379 is closed: $errstr ($errno)\n";
    }
}
?>
