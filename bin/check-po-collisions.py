#!/usr/bin/env python3
"""Fail when two different msgids in a PO file share one translation.

That collision is the fingerprint of translation contamination: msgmerge
fuzzy-matches a renamed msgid to an unrelated entry and carries the wrong
msgstr across, or a translator pastes one label over another. The result reads
as a plausible translation, passes `msgfmt -c`, keeps the msgid count correct,
and is invisible to every other check in this pipeline - so it survives until a
native speaker opens the admin screen and reports it as a bug.

That is exactly how the fr_FR plugin name ended up as the label for "Default
Activity Filters" and "Hidden Activity Types", and how "Open settings" ended up
translated as "Save settings" in all four locales.

Fuzzy entries are checked too. They fall back to English at runtime, so they
are not user-facing yet, but a translator reviewing the file sees the wrong
suggestion and is likely to confirm it.

Genuine collisions do happen - two distinct English strings can legitimately
share one translation in another language. Add those to ALLOWED below, as
(locale, msgstr) pairs, so the check stays a hard gate rather than noise.

Usage: check-po-collisions.py languages/*.po
"""
import re
import sys

# (locale, msgstr) pairs that are legitimately shared by more than one msgid.
ALLOWED = set()

STR_RE = re.compile(r'"((?:[^"\\]|\\.)*)"')
MSGID_RE = re.compile(r'^msgid ((?:"(?:[^"\\]|\\.)*"[ \t]*\n?)+)', re.M)
MSGSTR_RE = re.compile(r'^msgstr ((?:"(?:[^"\\]|\\.)*"[ \t]*\n?)+)', re.M)


def unquote(chunk):
    return ''.join(STR_RE.findall(chunk))


def locale_of(path):
    name = path.rsplit('/', 1)[-1]
    return name.rsplit('-', 1)[-1][:-3] if '-' in name else name[:-3]


def collisions(path):
    """Map msgstr -> [msgid, ...] for every msgstr used more than once."""
    src = open(path, encoding='utf-8').read()
    seen = {}
    for block in src.split('\n\n'):
        msgid_match = MSGID_RE.search(block)
        msgstr_match = MSGSTR_RE.search(block)
        if not msgid_match or not msgstr_match:
            continue
        msgid = unquote(msgid_match.group(1))
        msgstr = unquote(msgstr_match.group(1))
        # Skip the header (empty msgid) and untranslated entries.
        if not msgid or not msgstr:
            continue
        seen.setdefault(msgstr, []).append((msgid, '#, fuzzy' in block))
    return {s: ids for s, ids in seen.items() if len(ids) > 1}


def main(paths):
    failed = 0
    for path in paths:
        locale = locale_of(path)
        found = collisions(path)
        found = {s: ids for s, ids in found.items()
                 if (locale, s) not in ALLOWED}
        if not found:
            print('    ok   %s: no msgstr shared by two msgids'
                  % path.rsplit('/', 1)[-1])
            continue
        failed = 1
        for msgstr, ids in sorted(found.items()):
            print('    FAIL %s: %d msgids share one translation: "%s"'
                  % (path.rsplit('/', 1)[-1], len(ids), msgstr),
                  file=sys.stderr)
            for msgid, fuzzy in ids:
                print('           <- "%s"%s'
                      % (msgid, '  [fuzzy]' if fuzzy else '  [LIVE]'),
                      file=sys.stderr)
    return failed


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(__doc__.strip().splitlines()[-1], file=sys.stderr)
        sys.exit(2)
    sys.exit(main(sys.argv[1:]))
