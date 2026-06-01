# -*- coding: utf-8 -*-
import os
import re
from typing import List, Tuple


def parse_code_blocks(text: str) -> List[Tuple[str, str, str]]:
    pattern = r"```(\w+)?\s*\n?(.*?)```"
    matches = re.findall(pattern, text, re.DOTALL)
    result = []
    for lang, code in matches:
        lang = lang.strip() if lang else "txt"
        code = code.strip()
        if code:
            result.append((lang, code, extract_filename(code, lang)))
    return result


def extract_filename(code: str, lang: str) -> str:
    ext_map = {
        "python": "py",
        "javascript": "js",
        "typescript": "ts",
        "jsx": "jsx",
        "tsx": "tsx",
        "html": "html",
        "css": "css",
        "json": "json",
        "yaml": "yaml",
        "yml": "yaml",
        "markdown": "md",
        "bash": "sh",
        "shell": "sh",
        "dockerfile": "Dockerfile",
        "sql": "sql",
        "dart": "dart",
        "java": "java",
        "go": "go",
        "rust": "rs",
    }
    ext = ext_map.get(lang, lang)
    first_line = code.split("\n")[0].strip()
    name_match = re.match(r"^[#/]+\s*(\S+\.\w+)", first_line)
    if name_match:
        return name_match.group(1)
    return f"output_{lang}.{ext}"


def save_artifact(filepath: str, content: str, base_dir: str = "generated") -> str:
    full_path = os.path.join(base_dir, filepath)
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    with open(full_path, "w", encoding="utf-8") as f:
        f.write(content)
    return full_path


def parse_and_save_output(text: str, base_dir: str = "generated", task_name: str = "unknown") -> List[str]:
    blocks = parse_code_blocks(text)
    saved = []
    for lang, code, fname in blocks:
        rel_path = os.path.join(task_name, fname)
        full_path = save_artifact(rel_path, code, base_dir)
        saved.append(full_path)
    if not saved:
        fallback = os.path.join(task_name, "output.md")
        full_path = save_artifact(fallback, text, base_dir)
        saved.append(full_path)
    return saved
