import zipfile, pathlib

root = pathlib.Path(__file__).parent
files = [
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

out = root / 'donatotomato-1.0.2.zip'
if out.exists():
    out.unlink()

with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
    for f in files:
        src = root / f
        if not src.exists():
            print(f'MISSING: {f}')
            continue
        arc = 'donatotomato/' + f
        z.write(src, arc)

print(f'Built {out} ({out.stat().st_size} bytes)')
with zipfile.ZipFile(out) as z:
    for n in z.namelist():
        print(' ', n)
