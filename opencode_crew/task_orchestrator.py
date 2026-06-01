# -*- coding: utf-8 -*-
"""
منسق المهام — Task Orchestrator v3.0
=====================================
يدير تدفق العمل عبر 7 وكلاء في نظام عقد مترابطة.

التدفق الافتراضي:
  CEO → LEAD → [BACKEND, FRONTEND, FLUTTER, UI/UX] بالتوازي → DOC

التبعيات:
  - UI/UX → Frontend, Flutter (التصميم قبل البرمجة)
  - Lead → Backend, Frontend, Flutter, UI/UX (توجيه تقني)
  - Doc → ALL (توثيق بعد الإنجاز)
"""

from session_manager import SessionManager
from file_manager import parse_and_save_output
from agent_profiles import AGENT_PROFILES, list_agent_repos

# التدفق الافتراضي — CEO يقرر الترتيب حسب المهمة
DEFAULT_FLOW = ["ceo", "lead", "uiux", "backend", "frontend", "flutter", "doc"]

# المهام المتوازية (يمكن تشغيلها معاً)
PARALLEL_GROUPS = {
    "dev": ["backend", "frontend", "flutter"],
    "design_chain": ["uiux", "frontend", "flutter"],
}


class Workflow:
    """يدير دورة حياة مهمة عبر الفريق المترابط."""

    def __init__(self, user_request: str, flow: list = None):
        self.user_request = user_request
        self.flow = flow or DEFAULT_FLOW[:]
        self.current_index = 0
        self._session_mgr = SessionManager()
        self.session = self._session_mgr.create_session(
            f"task_{hash(user_request) & 0xFFFF}"
        )
        self.artifacts = []

    def current_agent(self) -> dict:
        if self.current_index >= len(self.flow):
            return None
        return AGENT_PROFILES.get(self.flow[self.current_index])

    def next_agent(self) -> dict:
        self.current_index += 1
        return self.current_agent()

    def start_task(self, agent_id: str, context: str = "") -> str:
        profile = AGENT_PROFILES.get(agent_id)
        if not profile:
            return ""
        self.session["current_task"] = agent_id
        self._session_mgr.save_session(self.session)

        prompt = profile["prompt_template"].format(task=self.user_request)
        if context:
            prompt += f"\n\n{context}"
        return (
            f"# {profile.get('emoji', '')} دور: {profile['role']}\n"
            f"**{profile['description']}**\n\n"
            f"{prompt}"
        )

    def complete_task(self, agent_id: str, output: str):
        self._session_mgr.update_task_output(self.session, agent_id, output)
        saved = parse_and_save_output(output, "generated", agent_id)
        for f in saved:
            self._session_mgr.append_artifact(self.session, {
                "agent": agent_id,
                "file": f,
            })
        if agent_id not in [a["agent"] for a in self.artifacts]:
            self.artifacts.extend([{"agent": agent_id, "file": f} for f in saved])

    def get_context(self, agent_ids: list = None) -> str:
        """يجمع مخرجات وكلاء سابقين كسياق."""
        if agent_ids is None:
            agent_ids = self.flow[:self.current_index]
        parts = []
        for aid in agent_ids:
            out = self.session["task_outputs"].get(aid)
            if out:
                parts.append(f"=== {aid} ===\n{out[:500]}")
        return "\n\n".join(parts)

    def status(self) -> dict:
        return {
            "request": self.user_request,
            "session_id": self.session["session_id"],
            "current_agent": (
                self.current_agent()["role"]
                if self.current_agent()
                else "مكتمل"
            ),
            "completed": list(self.session["task_outputs"].keys()),
            "total_agents": len(self.flow),
            "progress": f"{self.current_index}/{len(self.flow)}",
            "artifacts": self.artifacts,
            "agent_repos": list_agent_repos(),
        }


def create_workflow(user_request: str, flow: list = None) -> Workflow:
    return Workflow(user_request, flow)
