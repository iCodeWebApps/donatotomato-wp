#!/usr/bin/env python3
"""Bump the plugin version across all five canonical locations.

Usage: python bin/bump-version.py 1.2.0

Updates:
  donatotomato.php  - "Version:" header + DONATOTOMATO_VERSION define
  block.json        - "version" key
  package.json      - "version" key
  readme.txt        - "Stable tag" + prepends new "= X.Y.Z =" Changelog stub
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


def main() -> None:
    if len(sys.argv) != 2 or not SEMVER.match(sys.argv[1]):
        raise SystemExit('Usage: python bin/bump-version.py <X.Y.Z>')
    version = sys.argv[1]
    root = pathlib.Path(__file__).resolve().parent.parent
    bump_php(root / 'donatotomato.php', version)
    bump_json(root / 'block.json', version)
    bump_json(root / 'block-button.json', version)
    bump_json(root / 'package.json', version)
    bump_readme(root / 'readme.txt', version)
    print(f'Bumped to {version} in donatotomato.php, block.json, block-button.json, package.json, readme.txt')


if __name__ == '__main__':
    main()
