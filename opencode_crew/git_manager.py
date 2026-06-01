# -*- coding: utf-8 -*-
"""
مدير Git للوكلاء — Agent Git Manager v3.0
==========================================
كل وكيل له repo خاص به كـ submodule.
يدير: commit, push, status, log لكل repo.

الاستخدام:
  from git_manager import AgentGit
  g = AgentGit("backend")
  g.commit("إضافة API المصادقة")
  g.push()
"""

import subprocess
from pathlib import Path

from agent_profiles import get_profile, list_agent_repos

BASE_DIR = Path(__file__).resolve().parent.parent

# خريطة سريعة: agent_id → المسار المحلي
AGENT_PATHS = {aid: BASE_DIR / d for aid, _, d in list_agent_repos()}


class AgentGit:
    def __init__(self, agent_id: str, worktree_dir: str = None):
        self.agent_id = agent_id
        self.profile = get_profile(agent_id)
        if not self.profile:
            raise ValueError(f"الوكيل {agent_id} غير موجود")

        if worktree_dir:
            self.repo_path = Path(worktree_dir)
        elif agent_id in AGENT_PATHS:
            self.repo_path = AGENT_PATHS[agent_id]
        else:
            self.repo_path = BASE_DIR  # CEO

    def _run(self, *args) -> str:
        cmd = ["git"] + list(args)
        result = subprocess.run(
            cmd, cwd=self.repo_path,
            capture_output=True, text=True,
        )
        if result.returncode != 0:
            raise RuntimeError(
                f"Git error [{self.agent_id}]: {result.stderr.strip()}"
            )
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
        return self._run("log", "--oneline", f"-{n}")

    def diff(self) -> str:
        return self._run("diff")

    def has_changes(self) -> bool:
        return bool(self.status().strip())

    @staticmethod
    def status_all() -> dict:
        """حالة جميع وكلاء الـ submodule دفعة واحدة."""
        results = {}
        for aid in AGENT_PATHS:
            try:
                g = AgentGit(aid)
                s = g.status()
                results[aid] = {
                    "has_changes": bool(s.strip()),
                    "status": s or "نظيف",
                }
            except Exception as e:
                results[aid] = {"error": str(e)}
        return results

    @staticmethod
    def commit_all(message: str):
        """commit لكل وكلاء لديهم تغييرات."""
        committed = []
        for aid in AGENT_PATHS:
            try:
                g = AgentGit(aid)
                if g.has_changes():
                    g.commit(f"{message} [{aid}]")
                    committed.append(aid)
            except Exception as e:
                print(f"  ⚠ {aid}: {e}")
        return committed
