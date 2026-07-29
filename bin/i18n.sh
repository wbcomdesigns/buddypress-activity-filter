#!/usr/bin/env bash
#
# Full i18n rebuild for BuddyPress Activity Filter.
#
# Does the whole sequence in the right order and then verifies it, so the
# translation files cannot drift from the source or from each other:
#
#   1. regenerate the POT from source
#   2. merge every locale against it (keeping fuzzy matches as translator hints)
#   3. drop obsolete entries, so strings whose code was deleted stop being
#      reported as translation bugs
#   4. compile .mo AND .l10n.php
#   5. assert every locale matches the POT, passes msgfmt -c, and has no two
#      msgids sharing one translation (see bin/check-po-collisions.py)
#
# Step 4 is the one that bites. WordPress 6.5+ loads languages/*.l10n.php in
# preference to the .mo, so rebuilding only the .mo leaves a stale .l10n.php
# that silently wins and the update appears to do nothing.
#
# Step 3 uses --no-obsolete. Do NOT reach for --no-fuzzy: that deletes the
# whole entry rather than clearing the flag, and every casual check still
# passes afterwards.
#
# Requires: wp-cli, gettext (msgmerge, msgattrib, msgfmt).
# Mirrors `grunt sync`, but runs without node/grunt installed.

set -euo pipefail

cd "$( dirname "${BASH_SOURCE[0]}" )/.."

SLUG="bp-activity-filter"
POT="languages/${SLUG}.pot"

for tool in wp msgmerge msgattrib msgfmt python3; do
	if ! command -v "$tool" >/dev/null 2>&1; then
		echo "error: '$tool' not found." >&2
		case "$tool" in
			wp)      echo "  install wp-cli: https://wp-cli.org/" >&2 ;;
			python3) echo "  macOS: brew install python" >&2 ;;
			*)       echo "  macOS: brew install gettext && brew link --force gettext" >&2 ;;
		esac
		exit 1
	fi
done

echo "==> Regenerating $POT"
wp i18n make-pot . "$POT" --slug="$SLUG" --exclude=audit,qa-reports,node_modules,bin

echo "==> Syncing locales"
for po in languages/*.po; do
	msgmerge --previous -q -o "$po.tmp" "$po" "$POT"
	msgattrib --no-obsolete -o "$po" "$po.tmp"
	rm -f "$po.tmp"
	echo "    $( basename "$po" )"
done

echo "==> Compiling .mo and .l10n.php"
rm -f languages/*.mo languages/*.l10n.php
wp i18n make-mo languages/ languages/ >/dev/null
wp i18n make-php languages/ >/dev/null

echo "==> Verifying"
expected=$( grep -c '^msgid ' "$POT" )
failed=0

for po in languages/*.po; do
	name=$( basename "$po" )
	actual=$( grep -c '^msgid ' "$po" )

	if [ "$actual" -ne "$expected" ]; then
		echo "    FAIL $name: $actual msgids, expected $expected" >&2
		failed=1
		continue
	fi

	if ! msgfmt -c -o /dev/null "$po" 2>/dev/null; then
		echo "    FAIL $name: msgfmt -c reported errors" >&2
		failed=1
		continue
	fi

	# Both compiled artifacts must exist, or the locale ships incomplete.
	base="${po%.po}"
	for artifact in "$base.mo" "$base.l10n.php"; do
		if [ ! -s "$artifact" ]; then
			echo "    FAIL $name: missing or empty $( basename "$artifact" )" >&2
			failed=1
		fi
	done

	[ "$failed" -eq 0 ] && echo "    ok   $name ($actual msgids, .mo + .l10n.php present)"
done

# A contaminated msgstr keeps the msgid count correct and passes msgfmt -c, so
# it slips past every check above. This is the one that catches it.
if ! python3 bin/check-po-collisions.py languages/*.po; then
	failed=1
fi

if [ "$failed" -ne 0 ]; then
	echo "i18n rebuild FAILED" >&2
	exit 1
fi

echo "i18n rebuild clean - $expected strings across $( ls -1 languages/*.po | wc -l | tr -d ' ' ) locales"
