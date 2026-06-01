# -*- coding: utf-8 -*-
"""
منسق المهام — Task Orchestrator v5.0
=====================================
يدير دورة حياة المهمة عبر 9 وكلاء، كل وكيل يمر بـ 6 مراحل إلزامية:
  1. فحص 🔎    2. اختبار أولي 🧪    3. فحص موسع 🔬
  4. تطوير ⚒️   5. اختبار نهائي ✅   6. تأكيد 🏁

التدفق الافتراضي:
  CEO → LEAD → UI/UX → [BACKEND, FRONTEND, FLUTTER] → [QA-UI, QA-API] → DOC
"""

from session_manager import SessionManager
from file_manager import parse_and_save_output
from agent_profiles import (
    AGENT_PROFILES,
    VERIFICATION_STAGES,
    list_agent_repos,
    get_stage_for_agent,
    list_stages,
)

# التدفق الافتراضي
DEFAULT_FLOW = ["ceo", "lead", "uiux", "backend", "frontend", "flutter", "qa_ui", "qa_api", "doc"]

# المهام المتوازية
PARALLEL_GROUPS = {
    "dev": ["backend", "frontend", "flutter"],
    "qa": ["qa_ui", "qa_api"],
    "design_chain": ["uiux", "frontend", "flutter"],
}


class StageTracker:
    """يتتبع مراحل وكيل واحد خلال pipeline."""

    def __init__(self, agent_id: str):
        self.agent_id = agent_id
        self.stages = list_stages()
        self.current_stage_index = 0
        self.stage_status = {}  # stage_id → "pending"|"in_progress"|"passed"|"failed"
        self.stage_outputs = {}  # stage_id → output text
        for s in self.stages:
            self.stage_status[s] = "pending"

    def current_stage(self) -> str:
        if self.current_stage_index >= len(self.stages):
            return None
        return self.stages[self.current_stage_index]

    def current_stage_info(self) -> dict:
        sid = self.current_stage()
        if not sid:
            return {}
        info = dict(VERIFICATION_STAGES.get(sid, {}))
        agent_stage = get_stage_for_agent(self.agent_id, sid)
        info.update(agent_stage)
        info["stage_id"] = sid
        return info

    def start_current_stage(self):
        sid = self.current_stage()
        if sid:
            self.stage_status[sid] = "in_progress"

    def pass_stage(self, output: str = ""):
        sid = self.current_stage()
        if sid:
            self.stage_status[sid] = "passed"
            self.stage_outputs[sid] = output
            self.current_stage_index += 1

    def fail_stage(self, reason: str):
        sid = self.current_stage()
        if sid:
            self.stage_status[sid] = "failed"
            self.stage_outputs[sid] = reason
            # لا نزيد المؤشر — يعيد المحاولة

    def is_complete(self) -> bool:
        return self.current_stage_index >= len(self.stages)

    def all_passed(self) -> bool:
        return all(v == "passed" for v in self.stage_status.values())

    def summary(self) -> dict:
        return {
            "agent": self.agent_id,
            "stage": self.current_stage(),
            "stage_index": self.current_stage_index,
            "total_stages": len(self.stages),
            "status": self.stage_status,
            "complete": self.is_complete(),
        }


class Workflow:
    """يدير دورة حياة مهمة عبر الفريق — كل وكيل عبر 6 مراحل."""

    def __init__(self, user_request: str, flow: list = None):
        self.user_request = user_request
        self.flow = flow or DEFAULT_FLOW[:]
        self.current_agent_index = 0
        self._session_mgr = SessionManager()
        self.session = self._session_mgr.create_session(
            f"task_{hash(user_request) & 0xFFFF}"
        )
        self.artifacts = []
        # Stage tracker لكل وكيل
        self.agent_trackers = {
            aid: StageTracker(aid) for aid in self.flow
        }

    def current_agent(self) -> dict:
        if self.current_agent_index >= len(self.flow):
            return None
        return AGENT_PROFILES.get(self.flow[self.current_agent_index])

    def current_agent_id(self) -> str:
        if self.current_agent_index >= len(self.flow):
            return None
        return self.flow[self.current_agent_index]

    def next_agent(self) -> dict:
        self.current_agent_index += 1
        return self.current_agent()

    def current_tracker(self) -> StageTracker:
        aid = self.current_agent_id()
        if not aid:
            return None
        return self.agent_trackers.get(aid)

    def get_stage_prompt(self, agent_id: str, context: str = "") -> str:
        """توليد prompt للمرحلة الحالية للوكيل."""
        profile = AGENT_PROFILES.get(agent_id)
        if not profile:
            return ""

        tracker = self.agent_trackers.get(agent_id)
        stage_id = tracker.current_stage()
        stage_info = tracker.current_stage_info()

        self.session["current_task"] = f"{agent_id}/{stage_id}"
        self._session_mgr.save_session(self.session)

        prompt = (
            f"# {profile.get('emoji', '')} دور: {profile['role']}\n"
            f"**{profile['description']}**\n\n"
            f"## المرحلة: {stage_info.get('icon', '')} {stage_info.get('name', stage_id)}\n"
            f"**الهدف:** {stage_info.get('objective', '')}\n\n"
        )

        if stage_info.get("conditions"):
            prompt += "**الشروط:**\n" + "\n".join(f"- {c}" for c in stage_info["conditions"]) + "\n\n"

        if stage_info.get("actions"):
            prompt += "**الإجراءات:**\n" + "\n".join(f"- {a}" for a in stage_info["actions"]) + "\n\n"

        if stage_info.get("commands"):
            prompt += "**الأوامر:**\n" + "\n".join(f"- `{c}`" for c in stage_info["commands"]) + "\n\n"

        if stage_info.get("success_criteria"):
            prompt += "**معايير النجاح:**\n" + "\n".join(f"- [ ] {c}" for c in stage_info["success_criteria"]) + "\n\n"

        prompt += profile["prompt_template"].format(task=self.user_request)

        if context:
            prompt += f"\n\n**السياق:**\n{context}"

        return prompt

    def start_agent_stage(self, agent_id: str, context: str = "") -> str:
        """بدء المرحلة الحالية للوكيل. يرجع الـ prompt."""
        tracker = self.agent_trackers.get(agent_id)
        if not tracker or tracker.is_complete():
            return ""

        tracker.start_current_stage()
        return self.get_stage_prompt(agent_id, context)

    def complete_agent_stage(self, agent_id: str, output: str):
        """إكمال المرحلة الحالية للوكيل بنجاح."""
        tracker = self.agent_trackers.get(agent_id)
        if not tracker:
            return

        stage_id = tracker.current_stage()
        tracker.pass_stage(output)

        # سجل في الجلسة
        self.session["task_outputs"][f"{agent_id}/{stage_id}"] = output
        self._session_mgr.save_session(self.session)

        # احفظ المخرجات إن كانت المرحلة النهائية (تطوير أو اختبار)
        if stage_id in ("4_development", "5_final_test"):
            saved = parse_and_save_output(output, "generated", agent_id)
            for f in saved:
                self._session_mgr.append_artifact(self.session, {
                    "agent": agent_id,
                    "stage": stage_id,
                    "file": f,
                })
                self.artifacts.append({"agent": agent_id, "stage": stage_id, "file": f})

        # إذا اكتمل الوكيل، انتقل للتالي
        if tracker.is_complete():
            self.session["current_task"] = agent_id + "_complete"
            self.session["completed_tasks"].append(agent_id)
            self._session_mgr.save_session(self.session)

    def fail_agent_stage(self, agent_id: str, reason: str):
        """فشل في المرحلة الحالية — يتطلب إعادة."""
        tracker = self.agent_trackers.get(agent_id)
        if tracker:
            tracker.fail_stage(reason)
            self._session_mgr.save_session(self.session)

    def get_context(self, agent_ids: list = None) -> str:
        """يجمع مخرجات المراحل السابقة كسياق."""
        if agent_ids is None:
            agent_ids = self.flow[:self.current_agent_index]
        parts = []
        for aid in agent_ids:
            out = self.session["task_outputs"].get(f"{aid}/5_final_test")
            if not out:
                out = self.session["task_outputs"].get(f"{aid}/4_development")
            if not out:
                # اجمع كل مخرجات الوكيل
                for k, v in self.session["task_outputs"].items():
                    if k.startswith(aid + "/"):
                        parts.append(f"=== {k} ===\n{v[:300]}")
            else:
                parts.append(f"=== {aid} ===\n{out[:500]}")
        return "\n\n".join(parts)

    def status(self) -> dict:
        current_agent = self.current_agent()
        tracker = self.current_tracker()
        stage_info = tracker.current_stage_info() if tracker else {}

        return {
            "request": self.user_request,
            "session_id": self.session["session_id"],
            "current_agent": current_agent["role"] if current_agent else "مكتمل",
            "current_stage": stage_info.get("name", "—"),
            "current_stage_icon": stage_info.get("icon", ""),
            "completed_agents": list(self.session.get("completed_tasks", [])),
            "total_agents": len(self.flow),
            "agent_progress": f"{self.current_agent_index + 1}/{len(self.flow)}" if self.current_agent() else "مكتمل",
            "artifacts": self.artifacts,
            "agent_repos": list_agent_repos(),
            "pipeline": {
                aid: {
                    "stage": t.current_stage(),
                    "index": t.current_stage_index,
                    "total": len(t.stages),
                    "complete": t.is_complete(),
                }
                for aid, t in self.agent_trackers.items()
            },
        }


def create_workflow(user_request: str, flow: list = None) -> Workflow:
    return Workflow(user_request, flow)
