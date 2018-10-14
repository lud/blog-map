deps:
	composer install
	mv vendor/j4mie/idiorm/idiorm.php idiorm.php
	rm -rvf vendor
