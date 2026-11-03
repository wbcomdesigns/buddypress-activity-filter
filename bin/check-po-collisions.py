#!/usr/bin/env python3
"""Report when two different msgids in a PO file share one translation.

That collision is the fingerprint of translation contamination: msgmerge
fuzzy-matches a renamed msgid to an unrelated entry and carries the wrong
msgstr across, or a translator pastes one label over another. The result reads
as a plausible translation, passes `msgfmt -c`, keeps the msgid count correct,
and is invisible to every other check in this pipeline - so it survives until a
native speaker opens the admin screen and reports it as a bug.

That is exactly how the fr_FR plugin name ended up as the label for "Default
Activity Filters" and "Hidden Activity Types".

Severity follows what the user actually sees:

  FAIL - two or more non-fuzzy entries share a translation. WordPress serves
         these, so at least one screen is showing text that belongs to a
         different string. A real bug.

  WARN - the collision only involves fuzzy entries. WordPress skips fuzzy
         translations and falls back to English, so no user sees it. It still
         matters, because a translator reviewing the file is shown a confident
         looking wrong suggestion and may confirm it - but it does not block a
         release, and these files are deliberately synced rather than
         translated, so fuzzy carry-over is the expected state.

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
    """Map msgstr -> [(msgid, is_fuzzy), ...] for msgstrs used more than once."""
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
    warned = 0
    for path in paths:
        name = path.rsplit('/', 1)[-1]
        locale = locale_of(path)
        found = {s: ids for s, ids in collisions(path).items()
                 if (locale, s) not in ALLOWED}

        live = {s: ids for s, ids in found.items()
                if sum(1 for _, fuzzy in ids if not fuzzy) > 1}
        fuzzy_only = {s: ids for s, ids in found.items() if s not in live}

        for msgstr, ids in sorted(live.items()):
            failed = 1
            print('    FAIL %s: %d live msgids share one translation: "%s"'
                  % (name, len(ids), msgstr), file=sys.stderr)
            for msgid, fuzzy in ids:
                print('           <- "%s"%s'
                      % (msgid, '  [fuzzy]' if fuzzy else '  [LIVE]'),
                      file=sys.stderr)

        for msgstr, ids in sorted(fuzzy_only.items()):
            warned += 1
            print('    warn %s: fuzzy carry-over, falls back to English: "%s"'
                  % (name, msgstr))

        if not live:
            note = ' (%d fuzzy carry-over)' % len(fuzzy_only) if fuzzy_only else ''
            print('    ok   %s: no live msgstr shared by two msgids%s'
                  % (name, note))

    if warned and not failed:
        print('    %d fuzzy carry-over(s) noted - English at runtime, for '
              'translators to resolve' % warned)
    return failed


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print('usage: check-po-collisions.py languages/*.po', file=sys.stderr)
        sys.exit(2)
    sys.exit(main(sys.argv[1:]))
