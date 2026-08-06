#!/usr/bin/env python3
"""Bump the plugin version across all six canonical locations, or check them.

Usage: python bin/bump-version.py 1.2.0         bump every location to 1.2.0
       python bin/bump-version.py --check       verify they already agree
       python bin/bump-version.py --check DIR   check some other tree, e.g. the
                                                SVN working copy before svn ci

Updates:
  donatotomato.php   - "Version:" header + DONATOTOMATO_VERSION define
  block.json         - "version" key
  block-button.json  - "version" key
  package.json       - "version" key
  readme.txt         - "Stable tag" + prepends new "= X.Y.Z =" Changelog stub

Five of those six ship inside the distributed zip. package.json does not
(build-zip.py excludes it), so a published tree legitimately has none and
--check treats it as optional-but-must-agree-when-present. package-lock.json
also carries the version and historically drifted at five tags, but it is
deliberately excluded: bumping it reindents a ~950KB file from npm's 2-space to
4-space, and it neither ships nor affects runtime.

Why --check exists: six published releases (1.3.0, 1.4.0, 1.4.1, 1.4.2, 1.4.3,
1.4.6) reached wordpress.org with these locations disagreeing, because this
script was bypassed and nothing compared them. Nothing in the pipeline could
see it: CI reads a version only to name the zip, and release.yml compares the
git tag against package.json alone. No step ever compared two locations to each
other. Run this from CI on every PR.

And run it against the SVN working copy before `svn ci`. CI only sees the git
tree, but publishing is a manual copy into SVN, and the two have diverged: the
published bytes at 1.4.1-1.4.3 were staler than their own git tags, so the
hand-copy introduced drift of its own on top of what the repo already had.
"""
import json
import pathlib
import re
import sys

SEMVER = re.compile(r'^\d+\.\d+\.\d+$')


def bump_php(path: pathlib.Path, version: str) -> None:
    text = path.read_text(encoding='utf-8')
    text, n1 = re.subn(r'(^\s*\*\s*Version:\s*).*$', rf'\g<1>{version}', text, count=1, flags=re.MULTILINE)
    text, n2 = re.subn(r"(define\(\s*'DONATOTOMATO_VERSION',\s*')[^']+(')", rf'\g<1>{version}\g<2>', text, count=1)
    if n1 != 1 or n2 != 1:
        raise SystemExit(f'donatotomato.php: expected 1 Version header + 1 DONATOTOMATO_VERSION define; got {n1} + {n2}')
    path.write_text(text, encoding='utf-8')


def bump_json(path: pathlib.Path, version: str) -> None:
    data = json.loads(path.read_text(encoding='utf-8'))
    data['version'] = version
    path.write_text(json.dumps(data, indent=4) + '\n', encoding='utf-8')


def bump_readme(path: pathlib.Path, version: str) -> None:
    text = path.read_text(encoding='utf-8')
    text, n = re.subn(r'(^Stable tag:\s*).*$', rf'\g<1>{version}', text, count=1, flags=re.MULTILINE)
    if n != 1:
        raise SystemExit(f'readme.txt: expected 1 Stable tag line; got {n}')
    # Only the changelog stub is conditional. This used to return early when the
    # heading already existed, which discarded the Stable tag substitution above
    # along with it -- so writing release notes before bumping (an ordinary order
    # of work) left readme.txt untouched while the script reported success, and
    # --check then failed telling you to run the command that had just no-opped.
    if not re.search(rf'^= {re.escape(version)} =\s*$', text, re.MULTILINE):
        stub = f'= {version} =\n* TODO: describe changes\n\n'
        text, n = re.subn(r'(== Changelog ==\n\n)', rf'\g<1>{stub}', text, count=1)
        if n != 1:
            raise SystemExit('readme.txt: could not find "== Changelog ==" followed by a blank line to insert the stub')
    path.write_text(text, encoding='utf-8')


def _read_text(path: pathlib.Path):
    """Text if the file exists, else None. Absent is reported, never raised."""
    return path.read_text(encoding='utf-8') if path.exists() else None


# The five that ship inside the distribution zip. All must be present and agree
# in any tree being checked, whether that is the git repo or an SVN working copy.
SHIPPED = (
    'donatotomato.php Version:',
    'donatotomato.php DONATOTOMATO_VERSION',
    'readme.txt Stable tag:',
    'block.json',
    'block-button.json',
)
# package.json is canonical in the repo but excluded from the zip by
# build-zip.py, so a published tree legitimately has none. It is checked when
# present because a disagreement there means the bump was done by hand.
# package-lock.json is deliberately NOT checked: it carries the version too and
# historically drifted at five tags, but bumping it reindents a ~950KB file from
# npm's 2-space to 4-space, and it neither ships nor affects runtime.
OPTIONAL = ('package.json',)


def read_versions(root: pathlib.Path) -> dict:
    """Every canonical version value, keyed by where it lives. None when absent."""
    php = _read_text(root / 'donatotomato.php')
    readme = _read_text(root / 'readme.txt')
    header = re.search(r'^\s*\*\s*Version:\s*(\S+)\s*$', php, re.MULTILINE) if php else None
    define = re.search(r"define\(\s*'DONATOTOMATO_VERSION',\s*'([^']+)'", php) if php else None
    stable = re.search(r'^Stable tag:\s*(\S+)\s*$', readme, re.MULTILINE) if readme else None
    found = {
        'donatotomato.php Version:': header.group(1) if header else None,
        'donatotomato.php DONATOTOMATO_VERSION': define.group(1) if define else None,
        'readme.txt Stable tag:': stable.group(1) if stable else None,
    }
    for name in ('block.json', 'block-button.json', *OPTIONAL):
        text = _read_text(root / name)
        found[name] = json.loads(text).get('version') if text else None
    return found


def check(root: pathlib.Path) -> None:
    print(f'Checking {root}')
    found = read_versions(root)
    width = max(len(k) for k in found)
    for location, value in found.items():
        note = '' if value else ('  (not in this tree)' if location in OPTIONAL else '')
        print(f'  {location.ljust(width)}  {value if value is not None else "NOT FOUND"}{note}')

    missing = [k for k in SHIPPED if found.get(k) is None]
    if missing:
        raise SystemExit('\nNo version found in: ' + ', '.join(missing))

    present = {k: v for k, v in found.items() if v is not None}
    # Equality alone would pass on a typo propagated everywhere, e.g. 1.4.1O
    # with a letter O, which would then reach the zip name and the ?ver= buster.
    invalid = sorted({v for v in present.values() if not SEMVER.match(v)})
    if invalid:
        raise SystemExit(f'\nNot a valid X.Y.Z version: {", ".join(invalid)}')

    distinct = set(present.values())
    if len(distinct) != 1:
        raise SystemExit(
            f'\nVersion drift: {len(distinct)} different values across {len(present)} locations '
            f'({", ".join(sorted(distinct))}).\n'
            'Run: python bin/bump-version.py <X.Y.Z>'
        )
    print(f'\nAll {len(present)} locations agree: {distinct.pop()}')


def main() -> None:
    root = pathlib.Path(__file__).resolve().parent.parent
    if len(sys.argv) in (2, 3) and sys.argv[1] == '--check':
        # An explicit path lets the release flow check the SVN working copy
        # right before `svn ci`. CI only ever sees the git tree, but publishing
        # is a manual copy into SVN, and history shows the two diverge: the
        # published bytes at 1.4.1-1.4.3 were staler than the git tags.
        check(pathlib.Path(sys.argv[2]).resolve() if len(sys.argv) == 3 else root)
        return
    if len(sys.argv) != 2 or not SEMVER.match(sys.argv[1]):
        raise SystemExit('Usage: python bin/bump-version.py <X.Y.Z> | --check [dir]')
    version = sys.argv[1]
    bump_php(root / 'donatotomato.php', version)
    bump_json(root / 'block.json', version)
    bump_json(root / 'block-button.json', version)
    bump_json(root / 'package.json', version)
    bump_readme(root / 'readme.txt', version)
    print(f'Bumped to {version} in donatotomato.php, block.json, block-button.json, package.json, readme.txt')


if __name__ == '__main__':
    main()
