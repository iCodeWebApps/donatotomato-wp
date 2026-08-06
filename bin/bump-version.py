#!/usr/bin/env python3
"""Bump the plugin version across all six canonical locations, or check them.

Usage: python bin/bump-version.py 1.2.0     bump every location to 1.2.0
       python bin/bump-version.py --check   verify they already agree

Updates:
  donatotomato.php   - "Version:" header + DONATOTOMATO_VERSION define
  block.json         - "version" key
  block-button.json  - "version" key
  package.json       - "version" key
  readme.txt         - "Stable tag" + prepends new "= X.Y.Z =" Changelog stub

Five of those six ship inside the distributed zip; package.json does not, but a
disagreement there still means the bump was done by hand and something else was
probably missed too, so --check treats all six as one set.

Why --check exists: six published releases (1.3.0, 1.4.0, 1.4.1, 1.4.2, 1.4.3,
1.4.6) reached wordpress.org with these locations disagreeing, because this
script was bypassed and nothing compared them. CI reads a version only to name
the zip, and release.yml compares just the git tag against package.json, so
neither can see drift among the other four. Run this from CI on every PR.
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
    if re.search(rf'^= {re.escape(version)} =\s*$', text, re.MULTILINE):
        return
    stub = f'= {version} =\n* TODO: describe changes\n\n'
    text = re.sub(r'(== Changelog ==\n\n)', rf'\g<1>{stub}', text, count=1)
    path.write_text(text, encoding='utf-8')


def read_versions(root: pathlib.Path) -> dict:
    """Every canonical version value, keyed by where it lives."""
    php = (root / 'donatotomato.php').read_text(encoding='utf-8')
    header = re.search(r'^\s*\*\s*Version:\s*(\S+)\s*$', php, re.MULTILINE)
    define = re.search(r"define\(\s*'DONATOTOMATO_VERSION',\s*'([^']+)'", php)
    readme = re.search(
        r'^Stable tag:\s*(\S+)\s*$',
        (root / 'readme.txt').read_text(encoding='utf-8'),
        re.MULTILINE,
    )
    found = {
        'donatotomato.php Version:': header.group(1) if header else None,
        'donatotomato.php DONATOTOMATO_VERSION': define.group(1) if define else None,
        'readme.txt Stable tag:': readme.group(1) if readme else None,
    }
    for name in ('block.json', 'block-button.json', 'package.json'):
        path = root / name
        # Absent is reported, never raised: block-button.json did not exist
        # before 1.2.0, so a traceback here would make the checker useless for
        # inspecting an older tree and would obscure a genuinely deleted file.
        if not path.exists():
            found[name] = None
            continue
        found[name] = json.loads(path.read_text(encoding='utf-8')).get('version')
    return found


def check(root: pathlib.Path) -> None:
    found = read_versions(root)
    width = max(len(k) for k in found)
    for location, value in found.items():
        print(f'  {location.ljust(width)}  {value if value is not None else "NOT FOUND"}')
    missing = [k for k, v in found.items() if v is None]
    if missing:
        raise SystemExit('\nNo version found in: ' + ', '.join(missing))
    distinct = set(found.values())
    if len(distinct) != 1:
        raise SystemExit(
            f'\nVersion drift: {len(distinct)} different values across {len(found)} locations '
            f'({", ".join(sorted(distinct))}).\n'
            'Run: python bin/bump-version.py <X.Y.Z>'
        )
    print(f'\nAll {len(found)} locations agree: {distinct.pop()}')


def main() -> None:
    root = pathlib.Path(__file__).resolve().parent.parent
    if len(sys.argv) == 2 and sys.argv[1] == '--check':
        check(root)
        return
    if len(sys.argv) != 2 or not SEMVER.match(sys.argv[1]):
        raise SystemExit('Usage: python bin/bump-version.py <X.Y.Z> | --check')
    version = sys.argv[1]
    bump_php(root / 'donatotomato.php', version)
    bump_json(root / 'block.json', version)
    bump_json(root / 'block-button.json', version)
    bump_json(root / 'package.json', version)
    bump_readme(root / 'readme.txt', version)
    print(f'Bumped to {version} in donatotomato.php, block.json, block-button.json, package.json, readme.txt')


if __name__ == '__main__':
    main()
