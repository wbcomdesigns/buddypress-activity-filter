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
#   4. compile .mo AND .l10n.php, with fuzzy entries excluded
#   5. assert every locale matches the POT, passes msgfmt -c, and that no two
#      msgids share one translation - in the .po and again in the compiled
#      artifact (see bin/check-po-collisions.py)
#
# These files are deliberately SYNCED, not translated. Wording belongs to
# translators on translate.wordpress.org; this script's job is to keep the
# structure honest so the plugin stays translation-ready.
#
# Two things bite in step 4:
#
# 1. WordPress 6.5+ loads languages/*.l10n.php in preference to the .mo, so
#    rebuilding only the .mo leaves a stale .l10n.php that silently wins and
#    the update appears to do nothing.
#
# 2. `wp i18n make-mo` and `make-php` include fuzzy entries, where GNU msgfmt
#    drops them. Fuzzy is a machine guess, so shipping it puts wrong text on
#    screen: fr_FR served the plugin name as two tab headings, and all four
#    locales served "Open settings" as "Save settings". Step 4 therefore
#    compiles from a fuzzy-stripped copy.
#
# Step 3 uses --no-obsolete on the .po. Never point --no-fuzzy at a .po: it
# deletes whole entries rather than clearing the flag and every casual check
# still passes. It is used in step 4 only, against a throwaway copy.
#
# Requires: wp-cli, gettext (msgmerge, msgattrib, msgfmt), python3, php.
# Mirrors `grunt sync`, but runs without node/grunt installed.

set -euo pipefail

cd "$( dirname "${BASH_SOURCE[0]}" )/.."

SLUG="bp-activity-filter"
POT="languages/${SLUG}.pot"

for tool in wp msgmerge msgattrib msgfmt python3 php; do
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

# Compile from fuzzy-stripped copies, never from the .po itself.
#
# `wp i18n make-mo` and `make-php` do NOT skip fuzzy entries - unlike GNU
# msgfmt, which drops them per gettext convention. Compiling fr_FR directly
# gave a 47-entry .mo where GNU msgfmt gave 27, and the extra 20 were fuzzy
# carry-over: "Open settings" shipped as "Save settings", and two labels
# shipped as the plugin name. Fuzzy means "a machine guessed this", so it must
# reach translators but never users.
#
# --no-fuzzy is used ONLY on the throwaway copy. It deletes whole entries
# rather than clearing the flag, which is exactly right for a compile input and
# exactly wrong for the .po - never point it at languages/*.po.
echo "==> Compiling .mo and .l10n.php (fuzzy excluded)"
rm -f languages/*.mo languages/*.l10n.php
STAGE=$( mktemp -d )
trap 'rm -rf "$STAGE"' EXIT

for po in languages/*.po; do
	msgattrib --no-fuzzy --no-obsolete -o "$STAGE/$( basename "$po" )" "$po"
done

# Destination omitted on purpose - make-mo rejects an explicit destination when
# the source is a directory, and writes alongside the source instead.
wp i18n make-mo "$STAGE/" >/dev/null
wp i18n make-php "$STAGE/" >/dev/null
mv "$STAGE"/*.mo "$STAGE"/*.l10n.php languages/

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

# Then the same question asked of the artifact WordPress actually loads. Same
# script grunt verify-i18n calls, so the two entry points cannot drift.
if ! php bin/check-l10n-collisions.php languages/*.l10n.php; then
	failed=1
fi

if [ "$failed" -ne 0 ]; then
	echo "i18n rebuild FAILED" >&2
	exit 1
fi

echo "i18n rebuild clean - $expected strings across $( ls -1 languages/*.po | wc -l | tr -d ' ' ) locales"
