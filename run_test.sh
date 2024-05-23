#!/bin/bash

# ./vendor/phpunit/phpunit/phpunit --filter tests/Feature/AppointmentTest
# ./vendor/phpunit/phpunit/phpunit --filter tests/Feature/AppointmentControllerTest

php artisan test --filter AppointmentControllerTest
# php artisan test --group=group1
# php artisan test --filter AppointmentControllerTest::test_can_get_all_appointments_for_patient
# php artisan test --filter test_can_login
# php artisan test
# php artisan serve --host=192.168.100.5 --port=8000

# php artisan migrate:refresh --seed
