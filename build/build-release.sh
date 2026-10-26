#!/usr/bin/env bash
# Turns the X.Y.x source tree into the release/X.Y.x tree: PHPStan\BetterReflection namespace, code downgraded to PHP 7.4.
# Needs root and tools/ dependencies installed and PHP 8.4+.
# With --commit, each step becomes its own commit, as on the release branch.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

commit=false
if [ "${1:-}" = "--commit" ]; then
	commit=true
fi

step() {
	if $commit; then
		git commit -q -am "$1"
	fi
}

sed -i -E 's#"Roave(..BetterReflection..": "(\.\./)?src")#"PHPStan\1#' composer.json tools/composer.json
step "Change autoload namespace"

make downgrade
step "[GENERATED] Downgraded"

make rename-inner
step "[GENERATED] Renamed"
