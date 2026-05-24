#!/usr/bin/env python3
"""Build a reproducible WordPress.org-compatible plugin zip.

Reads the version from donatotomato.php's `Version:` header. Output zip
entries use forward-slash paths (see vault note feedback_powershell_zip_backslash
for why PowerShell's Compress-Archive is unsafe).
"""
import pathlib
import re
import zipfile

ROOT = pathlib.Path(__file__).resolve().parent
FILES = [
    'donatotomato.php',
    'readme.txt',
    'uninstall.php',
    'block.json',
    'includes/class-admin.php',
    'includes/class-block.php',
    'includes/class-shortcode.php',
    'assets/css/donatotomato.css',
    'assets/js/resize.js',
    'build/index.asset.php',
    'build/index.js',
]


def read_version() -> str:
    text = (ROOT / 'donatotomato.php').read_text(encoding='utf-8')
    match = re.search(r'^\s*\*\s*Version:\s*(\S+)\s*$', text, re.MULTILINE)
    if not match:
        raise SystemExit('donatotomato.php: no Version header found')
    return match.group(1)


def main() -> None:
    version = read_version()
    out = ROOT / f'donatotomato-{version}.zip'
    if out.exists():
        out.unlink()

    missing = [f for f in FILES if not (ROOT / f).exists()]
    if missing:
        raise SystemExit('Missing files (run `npm run build` first?):\n  ' + '\n  '.join(missing))

    with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
        for f in FILES:
            z.write(ROOT / f, 'donatotomato/' + f)

    print(f'Built {out.name} ({out.stat().st_size} bytes)')


if __name__ == '__main__':
    main()
