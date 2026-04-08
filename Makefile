ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))

downgrade: downgrade-inner
rename: rename-inner fix-cs

downgrade-inner:
	/opt/homebrew/Cellar/php@8.4/8.4.16_1.reinstall/bin/php -d memory_limit=2G vendor/bin/rector process src test -c build/downgrade-config.php

rename-inner:
	php build/rename.php

fix-cs:
	vendor/bin/phpcbf
