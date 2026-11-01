#!/usr/bin/env python3
"""Package runtime source and built assets without secrets, tests or mutable data."""
import argparse
import json
from pathlib import Path
import tarfile

ROOT = Path(__file__).resolve().parents[1]
TREES = (
    'app', 'bootstrap', 'config', 'database/migrations', 'resources/views', 'routes', 'public',
)
FILES = (
    'artisan', 'composer.json', 'composer.lock', '.env.example', '.user.ini', 'LICENSE.md',
    'LICENSE-STOREFRONT.md',
)
EMPTY_DIRS = (
    'bootstrap/cache', 'storage/app/private', 'storage/app/public',
    'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs',
)


def runtime_file(path):
    relative = path.relative_to(ROOT)
    parts = relative.parts
    if path.is_symlink() or not path.is_file():
        return False
    if any(part.startswith('.') for part in parts) and relative.name not in ('.htaccess', '.user.ini'):
        return False
    if ('bootstrap' in parts and 'cache' in parts) or relative.as_posix() in ('public/hot',):
        return False
    if relative.as_posix().startswith(('public/storage/',)):
        return False
    return relative.suffix not in ('.log', '.bak', '.tmp', '.orig', '.pyc')


def release_metadata(info):
    info.uid = info.gid = 0
    info.uname = info.gname = 'root'
    info.mode = 0o755 if info.name == 'artisan' else 0o644
    return info


def package(output):
    manifest_path = ROOT / 'public/build/manifest.json'
    if not manifest_path.is_file():
        raise SystemExit('Build assets first: npm ci && npm run build')
    manifest = json.loads(manifest_path.read_text())
    for entry in manifest.values():
        for asset in [entry['file'], *entry.get('css', []), *entry.get('assets', [])]:
            if not (ROOT / 'public/build' / asset).is_file():
                raise SystemExit(f'Missing built asset: {asset}')
    selected = set()
    for tree in TREES:
        selected.update(path for path in (ROOT / tree).rglob('*') if runtime_file(path))
    for name in FILES:
        path = ROOT / name
        if not path.is_file() or path.is_symlink():
            raise SystemExit(f'Missing release file: {name}')
        selected.add(path)
    # Never overwrite application inputs, user data or a source file with an archive.
    protected = [ROOT / tree for tree in TREES] + [ROOT / 'storage', ROOT / 'vendor', ROOT / 'node_modules', ROOT / '.git']
    if output == ROOT or output in selected or any(output.is_relative_to(path) for path in protected):
        raise SystemExit('Choose an output outside application/source/storage directories (default: releases/).')
    output.parent.mkdir(parents=True, exist_ok=True)
    with output.open('xb') as stream:
        with tarfile.open(fileobj=stream, mode='w:gz') as archive:
            for name in EMPTY_DIRS:
                info = tarfile.TarInfo(name)
                info.type = tarfile.DIRTYPE
                info.mode = 0o755
                archive.addfile(info)
            for path in sorted(selected):
                archive.add(path, arcname=path.relative_to(ROOT).as_posix(), recursive=False, filter=release_metadata)
    print(f'Created {output} ({len(selected)} runtime files; no vendor, tests, secrets or user uploads).')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, default=ROOT / 'releases/metis-production.tar.gz')
    package(parser.parse_args().output.resolve())
