# -*- coding: utf-8 -*-
"""
منسق المهام — Task Orchestrator
================================
يدير تدفق العمل عندما يعطي المستخدم أمراً. يحدد:
  1. أي وكيل يعمل الآن (CEO ثم Lead ثم باقي الفريق)
  2. حالة كل مهمة
  3. المخرجات المنتظرة

يستخدم session_manager لتسجيل كل خطوة و file_manager لحفظ المخرجات.

الاستخدام (داخلي — أنا OpenCode أستخدمه):
  from task_orchestrator import Workflow
  wf = Workflow("إنشاء نظام تسجيل دخول")
  wf.start(profile="ceo", context="..." )
  # ... أنفذ المهمة باستخدام أدواتي ...
  wf.complete(output="...")
  wf.next_agent()
"""

from session_manager import SessionManager
from file_manager import parse_and_save_output
from agent_profiles import AGENT_PROFILES

DEFAULT_FLOW = ["ceo", "lead", "backend", "frontend", "flutter", "doc"]


class Workflow:
    """يدير دورة حياة مهمة واحدة عبر الوكلاء بالترتيب."""

    def __init__(self, user_request: str, flow: list = None):
        self.user_request = user_request
        self.flow = flow or DEFAULT_FLOW[:]
        self.current_index = 0
        self.session_mgr = SessionManager()
        self.session = self.session_mgr.create_session(f"task_{hash(user_request) & 0xFFFF}")
        self.outputs = {}
        self.artifacts = []

    def current_agent(self) -> dict:
        if self.current_index >= len(self.flow):
            return None
        aid = self.flow[self.current_index]
        return AGENT_PROFILES.get(aid)

    def next_agent(self) -> dict:
        self.current_index += 1
        return self.current_agent()

    def start_task(self, agent_id: str, context: str = "") -> str:
        profile = AGENT_PROFILES.get(agent_id)
        if not profile:
            return ""
        self.session["current_task"] = agent_id
        self.session_mgr.save_session(self.session)
        prompt = profile["prompt_template"].format(task=self.user_request)
        if context:
            prompt += f"\n\n{context}"
        return f"# دور: {profile['role']}\n\n{prompt}"

    def complete_task(self, agent_id: str, output: str):
        self.session_mgr.update_task_output(self.session, agent_id, output)
        saved = parse_and_save_output(output, "generated", agent_id)
        for f in saved:
            self.session_mgr.append_artifact(self.session, {
                "agent": agent_id,
                "file": f,
            })
        self.artifacts.extend([{"agent": agent_id, "file": f} for f in saved])

    def status(self) -> dict:
        return {
            "request": self.user_request,
            "session_id": self.session["session_id"],
            "current_agent": self.current_agent()["role"] if self.current_agent() else None,
            "completed": list(self.session["task_outputs"].keys()),
            "total_agents": len(self.flow),
            "progress": f"{self.current_index}/{len(self.flow)}",
            "artifacts": self.artifacts,
        }


def create_workflow(user_request: str, flow: list = None) -> Workflow:
    """ينشئ دورة عمل جديدة من طلب المستخدم."""
    return Workflow(user_request, flow)
