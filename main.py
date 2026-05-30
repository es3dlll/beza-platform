import os
from pathlib import Path
from datetime import datetime

# ==========================================
# Configuration
# ==========================================

PROJECT_ROOT = Path(".").resolve()
OUTPUT_FILE = PROJECT_ROOT / "project_snapshot.txt"

# تجاهل هذه المجلدات
EXCLUDED_DIRS = {
    ".git",
    "vendor",
    "node_modules",
    ".idea",
    ".vscode",
    "storage",
    "bootstrap/cache",
    "__pycache__",
    ".dart_tool",
    "build",
    "dist",
    "coverage",
    ".next",
}

# تجاهل هذه الامتدادات الثنائية
BINARY_EXTENSIONS = {
    ".png",
    ".jpg",
    ".jpeg",
    ".gif",
    ".webp",
    ".svg",
    ".ico",
    ".pdf",
    ".zip",
    ".rar",
    ".7z",
    ".tar",
    ".gz",
    ".mp4",
    ".avi",
    ".mov",
    ".mp3",
    ".wav",
    ".ogg",
    ".ttf",
    ".otf",
    ".woff",
    ".woff2",
    ".exe",
    ".dll",
    ".so",
    ".bin",
    ".class",
    ".jar",
    ".sqlite",
    ".db",
    ".lock",
}

MAX_FILE_SIZE_MB = 5
MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024


# ==========================================
# Helpers
# ==========================================

def should_skip_dir(path: Path):
    parts = set(path.parts)

    for excluded in EXCLUDED_DIRS:
        excluded_parts = excluded.split("/")
        if all(part in parts for part in excluded_parts):
            return True

    return False


def is_binary_file(file_path: Path):
    return file_path.suffix.lower() in BINARY_EXTENSIONS


def build_tree(root: Path):
    lines = []

    def walk(directory: Path, prefix=""):
        try:
            items = sorted(
                [x for x in directory.iterdir() if not should_skip_dir(x)],
                key=lambda p: (not p.is_dir(), p.name.lower())
            )
        except PermissionError:
            return

        count = len(items)

        for index, item in enumerate(items):
            connector = "└── " if index == count - 1 else "├── "

            lines.append(prefix + connector + item.name)

            if item.is_dir():
                extension = "    " if index == count - 1 else "│   "
                walk(item, prefix + extension)

    lines.append(str(root.name))
    walk(root)

    return "\n".join(lines)


def collect_files(root: Path):
    files = []

    for current_root, dirs, filenames in os.walk(root):

        current_path = Path(current_root)

        dirs[:] = [
            d for d in dirs
            if not should_skip_dir(current_path / d)
        ]

        for filename in filenames:
            file_path = current_path / filename

            if is_binary_file(file_path):
                continue

            try:
                size = file_path.stat().st_size

                if size > MAX_FILE_SIZE_BYTES:
                    continue

                files.append(file_path)

            except Exception:
                pass

    return sorted(files)


# ==========================================
# Main
# ==========================================

with open(OUTPUT_FILE, "w", encoding="utf-8") as out:

    out.write("=" * 100 + "\n")
    out.write("BEZA PLATFORM PROJECT SNAPSHOT\n")
    out.write("=" * 100 + "\n")
    out.write(f"Generated: {datetime.now()}\n")
    out.write(f"Project Root: {PROJECT_ROOT}\n\n")

    # =====================================================
    # TREE
    # =====================================================

    out.write("\n")
    out.write("#" * 100 + "\n")
    out.write("PROJECT TREE\n")
    out.write("#" * 100 + "\n\n")

    out.write(build_tree(PROJECT_ROOT))
    out.write("\n\n")

    # =====================================================
    # FILE CONTENTS
    # =====================================================

    files = collect_files(PROJECT_ROOT)

    out.write("\n")
    out.write("#" * 100 + "\n")
    out.write(f"FILES ({len(files)})\n")
    out.write("#" * 100 + "\n\n")

    for file_path in files:

        try:
            relative_path = file_path.relative_to(PROJECT_ROOT)

            out.write("\n")
            out.write("=" * 100 + "\n")
            out.write(f"FILE: {relative_path}\n")
            out.write("=" * 100 + "\n\n")

            content = file_path.read_text(
                encoding="utf-8",
                errors="replace"
            )

            out.write(content)
            out.write("\n\n")

        except Exception as e:
            out.write(f"[ERROR READING FILE] {e}\n\n")

print()
print("=" * 80)
print("DONE")
print(f"OUTPUT: {OUTPUT_FILE}")
print("=" * 80)