<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>


## Холдирование слотов

Тестовое задание.

Удаленный github репозиторий.
* https://github.com/gromr007/holds/

Ссылка на документацию
* https://holds.pet-project.online/api/documentation

Ссылка на Видео презентацию по этапам разработки
* https://drive.google.com/drive/folders/1swWN_egfOjUb6Abe_e-lfR1hvwoLjkRx?usp=sharing

//==========================
## Задача:

Реализуйте минимальный API бронирования слотов с горячим кешем и защитой от оверсела.
Вы проектируете сервис, который управляет бронью слотов (складские окна, доставка или время приема клиентов).
Каждый слот имеет ограниченную вместимость, пользователи могут создавать временные холды, а затем подтверждать их.
Нужно обеспечить корректную работу под нагрузкой: горячий кеш, транзакции, защита от оверсела.

### Функциональные требования:

#### Получение доступных слотов
    Метод:
    GET /slots/availability
    Пример ответа:
    [
    { "slot_id": 1, "capacity": 10, "remaining": 6 },
    { "slot_id": 2, "capacity": 5, "remaining": 0 }
    ]
    Кешировать результат на 5–15 секунд, предусмотреть защиту от cache stampede.
    После подтверждения или отмены данных инвалидировать кеш.

#### Создание холда
    Метод:
    POST /slots/{id}/hold
    Заголовок:
    Idempotency-Key: <UUID>
    Создает запись в таблице holds со статусом held.
    Проверяет доступность мест и возвращает 409 Conflict, если capacity исчерпан.
    Повторный запрос с тем же ключом возвращает прежний результат (идемпотентность).
    Холды живут 5 минут (фоновую очистку можно не реализовывать).

#### Подтверждение холда
    Метод:
    POST /holds/{id}/confirm
    Переводит холд в состояние confirmed.
    Атомарно уменьшает remaining в слоте на 1 с защитой от оверсела.
    При отсутствии мест возвращает 409 Conflict.
    После успешного подтверждения инвалидирует кеш доступности.

#### Отмена холда
    Метод:
    DELETE /holds/{id}
    Меняет состояние холда на cancelled.
    Возвращает слот в доступ, обновляя остаток.
    Инвалидирует кеш доступных слотов.

#### Ожидаемые результаты:
    Изучите требования и спланируйте архитектуру сервиса.
    Реализуйте API с учетом кеша, транзакций и идемпотентности.
    Код на Laravel 12 (PHP 8.2+) и MySQL 8+.
    Маршруты определены в routes/api.php.
    Контроллеры: AvailabilityController, HoldController.
    Сервисный слой: SlotService (транзакции, кеш, идемпотентность).
    Минимальные миграции для таблиц слотов и холдов.
    README с инструкциями запуска (php artisan migrate, php artisan serve) и примерами curl-запросов (создание холда, повтор с тем же ключом, подтверждение, отмена, конфликт при оверселе).

## Проверка

    docker exec vds_php84 bash -c "cd /var/www/holds; php artisan migrate:fresh --seed"
    docker exec vds_php84 bash -c "cd /var/www/holds; php artisan optimize:clear"
    docker exec vds_php84 bash -c "cd /var/www/holds; php artisan route:list"
    
### Запуск тестов
    docker exec vds_php84 bash -c "cd /var/www/holds; php artisan test --env=testing"



