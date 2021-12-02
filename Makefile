ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))

downgrade: downgrade-inner
rename: rename-inner fix-cs

downgrade-inner:
	/opt/homebrew/opt/php@8.1/bin/php -d memory_limit=2G vendor/bin/rector process src test -c build/downgrade-config.php

rename-inner:
	php build/rename.php

fix-cs:
	vendor/bin/phpcbf
