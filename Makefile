ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))

# Downgrading needs PHP 8.4+, e.g. `make downgrade PHP=/opt/homebrew/Cellar/php@8.4/8.4.23/bin/php`
PHP ?= php

downgrade: downgrade-inner
rename: rename-inner fix-cs

downgrade-inner:
	$(PHP) -d memory_limit=2G tools/vendor/bin/rector process src test -c build/downgrade-config.php

rename-inner:
	$(PHP) build/rename.php

fix-cs:
	vendor/bin/phpcbf
