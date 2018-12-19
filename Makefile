deps:
	composer install
	mv vendor/j4mie/idiorm/idiorm.php idiorm.php
	rm -rvf vendor
release:
	rimraf _release
	yarn clean
	yarn copy-assets
	yarn build
	NODE_ENV=production yarn build
	mkdir -p _release/blog-map/src
	cp -v blog-map.php _release/blog-map/
	cp -v idiorm.php _release/blog-map/
	cp -v README.txt _release/blog-map/
	cp -v LICENSE.txt _release/blog-map/
	cp -rv src/php _release/blog-map/src/
	cp -rv public _release/blog-map/
	zip -9rv _release/blog-map.zip _release/blog-map
	tree _release

