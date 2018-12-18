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
	mkdir _release
	cp blog-map.php _release/ -v
	cp idiorm.php _release/ -v
	cp README.txt _release/ -v
	cp LICENSE.txt _release/ -v
	mkdir _release/src
	cp src/php _release/src/ -r -v
	cp public _release/ -r -v
	tree _release
	mv _release blog-map-plugin -v
	zip -9 -r blog-map-plugin.zip blog-map-plugin -v
	mv blog-map-plugin _release -v

