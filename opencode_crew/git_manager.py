# -*- coding: utf-8 -*-
"""
مدير Git للوكلاء — Agent Git Manager
======================================
يدير عمليات git لكل وكيل: commit, push, pull.

كل وكيل له repo خاص به ضمن agents/ كـ submodule.

الاستخدام:
  from git_manager import AgentGit
  g = AgentGit("backend")
  g.commit("إضافة نظام مصادقة JWT")
  g.push()
"""

import os
import subprocess
from pathlib import Path

from agent_profiles import get_profile

BASE_DIR = Path(__file__).resolve().parent.parent


class AgentGit:
    def __init__(self, agent_id: str, worktree_dir: str = None):
        self.profile = get_profile(agent_id)
        if not self.profile:
            raise ValueError(f"الوكيل {agent_id} غير موجود")
        if worktree_dir:
            self.repo_path = Path(worktree_dir)
        else:
            agent_dir = self.profile.get("agent_dir")
            if agent_dir:
                self.repo_path = BASE_DIR / agent_dir
            else:
                self.repo_path = BASE_DIR  # CEO يعمل في الرئيسي

    def _run(self, *args) -> str:
        cmd = ["git"] + list(args)
        result = subprocess.run(
            cmd,
            cwd=self.repo_path,
            capture_output=True,
            text=True,
        )
        if result.returncode != 0:
            raise RuntimeError(f"Git error: {result.stderr}")
        return result.stdout.strip()

    def status(self) -> str:
        return self._run("status", "--short")

    def add(self, *paths: str):
        if paths:
            self._run("add", *paths)
        else:
            self._run("add", "-A")

    def commit(self, message: str):
        self.add()
        self._run("commit", "-m", message)

    def push(self, remote: str = "origin", branch: str = "main"):
        self._run("push", remote, branch)

    def pull(self, remote: str = "origin", branch: str = "main"):
        self._run("pull", remote, branch)

    def log(self, n: int = 5) -> str:
        return self._run("log", f"--oneline", f"-{n}")

    def diff(self) -> str:
        return self._run("diff")

    def has_changes(self) -> bool:
        return bool(self.status().strip())
