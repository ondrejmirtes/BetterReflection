ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))

downgrade: downgrade-inner
rename: rename-inner fix-cs

generate:
	@git diff-files --quiet || (echo "ERROR: contains dirty changes - exiting." && exit 1)
	make downgrade
	git add .
	git commit -m "[GENERATED] Downgraded"
	make rename-inner
	git add .
	git commit -m "[GENERATED] Renamed"

drop-generated:
	@git diff-files --quiet || (echo "ERROR: contains dirty changes - exiting." && exit 1)
	@git log -1 --oneline|grep "GENERATED" || (echo "ERROR: Latest commit seems not to be generated." && exit 1)
	git reset HEAD~2
	git restore .
	git clean -fd

downgrade-inner:
	/opt/homebrew/opt/php@8.1/bin/php -d memory_limit=2G vendor/bin/rector process src test -c build/downgrade-config.php --clear-cache

rename-inner:
	php build/rename.php

fix-cs:
	vendor/bin/phpcbf
